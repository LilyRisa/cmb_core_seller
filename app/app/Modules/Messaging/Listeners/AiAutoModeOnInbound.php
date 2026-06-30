<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Jobs\RespondWithAiAutoReply;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AiAutoModeResolver;
use CMBcoreSeller\Modules\Messaging\Services\AutoReplyEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowPrecedence;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Auto-mode (S7): khi bật, AI tự trả lời inbound qua guardrail intent.
 *
 * Điều kiện (ADR-0022): công tắc "tự gửi tất cả" theo NHÓM KÊNH của conversation
 * (`auto_mode_facebook` cho Facebook, `auto_mode_marketplace` cho sàn) + `ai_enabled`
 * + gói có feature `messaging_ai` (Business). Bỏ qua spam / không phải inbound.
 *
 * AI là Tầng 2 (phương án cuối): NHƯỜNG Tầng 1 — nếu hội thoại đang giữa flow
 * (run active/waiting), hoặc có flow/rule `first_message`/`keyword` KHỚP tin này
 * thì KHÔNG dùng AI. Kiểm tra "có khớp" (tất định), không phải "đã trả lời" (race
 * giữa các queue song song).
 *
 * ShouldQueue (queue messaging-ai): gọi LLM tốn thời gian — KHÔNG chặn webhook
 * ingest. Lỗi auto-mode nuốt (best-effort) — không làm hỏng luồng nhận tin.
 */
class AiAutoModeOnInbound implements ShouldQueue
{
    public string $queue = 'messaging-ai';

    public function __construct(
        private SubscriptionService $subscriptions,
        private FlowPrecedence $flowPrecedence,
        private AutoReplyEngine $autoReply,
        private AiAutoModeResolver $aiMode,
    ) {}

    public function handle(MessageReceived $event): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($event->conversationId);
        if (! $conv || $conv->status === Conversation::STATUS_SPAM || $conv->blocked_at !== null) {
            return;
        }

        $message = Message::withoutGlobalScope(TenantScope::class)->find($event->messageId);
        if (! $message || ! $message->isInbound()) {
            return;
        }

        if (! $this->aiMode->enabledFor($conv)) {
            return;
        }

        if (! $this->hasAiFeature((int) $conv->tenant_id)) {
            return;
        }

        // Nhường Tầng 1: tin đầu / từ khoá / flow đang chạy ⇒ không dùng AI (ADR-0022).
        if ($this->higherPriorityClaims($conv, (string) $message->body)) {
            return;
        }

        // Debounce: hẹn job trễ; chỉ tin INBOUND mới nhất mới thực sự trả lời (latest-wins)
        // ⇒ gộp burst (3 text rời / text+ảnh tách event) thành 1 reply. SPEC-0024 §4.6.
        $delay = (int) config('messaging.ai.auto_reply_debounce_seconds', 4);
        RespondWithAiAutoReply::dispatch((int) $conv->id, (int) $message->id)
            ->delay($delay > 0 ? now()->addSeconds($delay) : null);
    }

    /**
     * Có handler Tầng 1 KHỚP tin này không (⇒ AI nhường). Tầng 1 = flow chiếm hội thoại
     * (run active/waiting hoặc flow inbox first_message/keyword/ANY khớp — qua {@see FlowPrecedence})
     * + rule `first_message`/`keyword` khớp. Bao gồm `inbox_any` ⇒ catch-all áp mọi trang
     * khiến AI tự tắt (quyết định 2.1 "flow ưu tiên khi khớp").
     */
    private function higherPriorityClaims(Conversation $conv, string $body): bool
    {
        if ($this->flowPrecedence->claims($conv, $body)) {
            return true;
        }

        $ctx = ['inbound_body' => $body];

        return $this->autoReply->matches($conv, AutoReplyRule::TRIGGER_FIRST_MESSAGE, $ctx)
            || $this->autoReply->matches($conv, AutoReplyRule::TRIGGER_KEYWORD, $ctx);
    }

    private function hasAiFeature(int $tenantId): bool
    {
        $sub = $this->subscriptions->currentFor($tenantId) ?? $this->subscriptions->ensureTrialFallback($tenantId);

        return (bool) $sub?->plan?->hasFeature('messaging_ai');
    }
}
