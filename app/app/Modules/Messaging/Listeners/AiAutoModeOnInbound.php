<?php

namespace CMBcoreSeller\Modules\Messaging\Listeners;

use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Auto-mode (S7): khi bật, AI tự trả lời inbound qua guardrail intent.
 *
 * Điều kiện: `messaging_settings.auto_mode=true` + `ai_enabled=true` + gói có
 * feature `messaging_ai` (Business). Bỏ qua spam / không phải inbound.
 *
 * ShouldQueue (queue messaging-ai): gọi LLM tốn thời gian — KHÔNG chặn webhook
 * ingest. Lỗi auto-mode nuốt (best-effort) — không làm hỏng luồng nhận tin.
 */
class AiAutoModeOnInbound implements ShouldQueue
{
    public string $queue = 'messaging-ai';

    public function __construct(
        private AiSuggestionService $suggestions,
        private SubscriptionService $subscriptions,
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
        if (! $setting || ! $setting->auto_mode || ! $setting->ai_enabled) {
            return;
        }

        if (! $this->hasAiFeature((int) $conv->tenant_id)) {
            return;
        }

        try {
            $this->suggestions->autoRespond($conv, (string) $message->body);
        } catch (\Throwable $e) {
            // Auto-mode best-effort — escalate ngầm cho NV bằng cách log; không ném.
            Log::warning('messaging.auto_mode.failed', [
                'conversation_id' => $conv->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function hasAiFeature(int $tenantId): bool
    {
        $sub = $this->subscriptions->currentFor($tenantId) ?? $this->subscriptions->ensureTrialFallback($tenantId);

        return (bool) $sub?->plan?->hasFeature('messaging_ai');
    }
}
