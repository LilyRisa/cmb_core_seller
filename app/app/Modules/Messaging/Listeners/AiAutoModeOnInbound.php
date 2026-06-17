<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Jobs\RespondWithAiAutoReply;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Messaging\Services\AutoReplyEngine;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowMatcher;
use CMBcoreSeller\Modules\Messaging\Support\MessagingChannelGroup;
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
        private FlowMatcher $flowMatcher,
        private AutoReplyEngine $autoReply,
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

        $setting = MessagingSetting::withoutGlobalScope(TenantScope::class)->find($conv->tenant_id);
        if (! $setting || ! $setting->ai_enabled || ! $this->autoModeFor($conv, $setting)) {
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
     * Công tắc "AI tự trả lời" — SPEC 0035: theo TỪNG PAGE (messaging_account_meta.ai_auto_mode).
     * Page chưa có meta ⇒ fallback cờ nhóm-tenant (giai đoạn chuyển tiếp, ADR-0022).
     */
    private function autoModeFor(Conversation $conv, MessagingSetting $setting): bool
    {
        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($conv->channel_account_id);
        if ($meta !== null) {
            return (bool) $meta->ai_auto_mode;
        }

        return MessagingChannelGroup::isFacebook($conv->provider)
            ? (bool) $setting->auto_mode_facebook
            : (bool) $setting->auto_mode_marketplace;
    }

    /**
     * Có handler Tầng 1 KHỚP tin này không (⇒ AI nhường). Tầng 1 = flow đang chạy
     * (run active/waiting) + flow/rule `first_message`/`keyword` khớp.
     */
    private function higherPriorityClaims(Conversation $conv, string $body): bool
    {
        $activeRun = FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $conv->tenant_id)
            ->where('conversation_id', $conv->id)
            ->whereIn('status', [FlowRun::STATUS_ACTIVE, FlowRun::STATUS_WAITING])
            ->exists();
        if ($activeRun) {
            return true;
        }

        $flowMatch = $this->flowMatcher->matching($conv, [
            AutomationFlow::TRIGGER_INBOX_FIRST_MESSAGE,
            AutomationFlow::TRIGGER_INBOX_KEYWORD,
        ], $body)->isNotEmpty();
        if ($flowMatch) {
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
