<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerProfileContract;
use CMBcoreSeller\Modules\Messaging\Exceptions\AiSuggestionException;
use CMBcoreSeller\Modules\Messaging\Models\AiAssistantRun;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageDraft;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Orchestrate AI suggestion (suggest-mode mặc định — SPEC-0024 §4.6).
 *
 * Pipeline:
 *   1. Resolve provider code tenant chọn (fallback: provider active đầu tiên).
 *   2. Check hạn mức `messaging_ai_replies_monthly` (đếm `ai_assistant_runs`
 *      success trong tháng) — tự enforce ở đây vì `EnforcePlanLimit` chỉ làm
 *      active-count, không làm time-window. -1 = không giới hạn.
 *   3. Build ConversationSnapshot — body QUA `PiiRedactor` (KHÔNG gửi PII thô
 *      ra LLM ngoài, §8.4). Giữ mapping để restore placeholder trong draft.
 *   4. Retrieve KB chunks (RAG keyword fallback).
 *   5. `connector->generateReply()` — ghi `ai_assistant_runs` (cost/token audit).
 *      Lỗi/UnsupportedOperation ⇒ ghi run error + ném `AiSuggestionException`.
 *   6. Tạo `MessageDraft` pending (NV duyệt rồi mới gửi — KHÔNG auto-send).
 *
 * Guardrail auto-mode (intent classify + escalate) là S7 — service này CHỈ suggest.
 */
class AiSuggestionService
{
    public function __construct(
        private AiAssistantRegistry $registry,
        private PiiRedactor $redactor,
        private KnowledgeRetriever $retriever,
        private CustomerProfileContract $customers,
        private SubscriptionService $subscriptions,
        private IntentClassifier $intentClassifier,
        private OutboundMessageService $outbound,
    ) {}

    /**
     * Auto-mode (S7): AI tự trả lời KHÔNG cần NV duyệt — nhưng qua guardrail
     * intent. Intent nhạy cảm (complaint/refund/urgent/legal_threat/abuse) ⇒
     * KHÔNG gửi, đánh `requires_human` để NV vào. SPEC §4.6.
     *
     * Vẫn đi qua đúng `OutboundMessageService` + ghi `ai_assistant_runs` (mode=auto)
     * — cùng pipeline với NV gửi tay (audit + window guard ở job).
     *
     * @return array{action:string, intent:string, message?:\CMBcoreSeller\Modules\Messaging\Models\Message}
     */
    public function autoRespond(Conversation $conv, string $inboundText): array
    {
        $tenantId = (int) $conv->tenant_id;
        $providerCode = $this->resolveProviderCode($tenantId);
        $this->assertWithinMonthlyLimit($tenantId);

        // Guardrail: phân loại intent trước khi cho AI tự gửi.
        $intent = $this->intentClassifier->classify($tenantId, $providerCode, $inboundText);
        if ($this->intentClassifier->shouldEscalate($intent)) {
            $conv->forceFill([
                'meta' => array_merge((array) $conv->meta, ['requires_human' => true, 'last_intent' => $intent->intent]),
            ])->save();

            return ['action' => 'escalated', 'intent' => $intent->intent];
        }

        try {
            $connector = $this->registry->for($providerCode);
        } catch (ProviderNotConfigured) {
            throw AiSuggestionException::providerNotAvailable();
        }

        [$snapshot, $mapping, $redactedCount] = $this->buildSnapshot($conv, $tenantId);
        $kb = $this->retriever->retrieve($tenantId, $inboundText);
        $provider = AiProvider::query()->find($providerCode);
        $ctx = new AiContext(tenantId: $tenantId, providerCode: $providerCode, model: $provider?->default_model, meta: ['mode' => 'auto']);

        $startedAt = microtime(true);
        try {
            $reply = $connector->generateReply($ctx, $snapshot, $kb);
        } catch (\Throwable $e) {
            $this->recordRun($tenantId, $conv, $providerCode, $provider?->default_model, AiAssistantRun::STATUS_ERROR, [
                'mode' => AiAssistantRun::MODE_AUTO,
                'error' => substr($e->getMessage(), 0, 250),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'meta' => ['redacted_count' => $redactedCount],
            ]);
            throw AiSuggestionException::generationFailed($e->getMessage());
        }

        $body = $mapping === [] ? $reply->body : strtr($reply->body, $mapping);

        $run = $this->recordRun($tenantId, $conv, $providerCode, $provider?->default_model, AiAssistantRun::STATUS_SUCCESS, [
            'mode' => AiAssistantRun::MODE_AUTO,
            'prompt_tokens' => $reply->promptTokens,
            'completion_tokens' => $reply->completionTokens,
            'cost_micro_vnd' => $reply->costMicroVnd,
            'duration_ms' => $reply->durationMs,
            'meta' => ['redacted_count' => $redactedCount, 'intent' => $intent->intent, 'kb_chunks' => count($kb->chunks)],
        ]);

        $message = $this->outbound->queueText($conv, [
            'body' => $body,
            'sent_by_user_id' => null,
            'sent_by_ai' => true,
            'ai_run_id' => $run->id,
        ]);

        return ['action' => 'sent', 'intent' => $intent->intent, 'message' => $message];
    }

    public function suggest(Conversation $conv, ?int $userId = null): MessageDraft
    {
        $tenantId = (int) $conv->tenant_id;

        $providerCode = $this->resolveProviderCode($tenantId);
        $this->assertWithinMonthlyLimit($tenantId);

        try {
            $connector = $this->registry->for($providerCode);
        } catch (ProviderNotConfigured) {
            throw AiSuggestionException::providerNotAvailable();
        }

        [$snapshot, $mapping, $redactedCount] = $this->buildSnapshot($conv, $tenantId);
        $kb = $this->retriever->retrieve($tenantId, $this->lastInboundBody($conv) ?? '');

        $provider = AiProvider::query()->find($providerCode);
        $ctx = new AiContext(
            tenantId: $tenantId,
            providerCode: $providerCode,
            model: $provider?->default_model,
        );

        $startedAt = microtime(true);
        try {
            $reply = $connector->generateReply($ctx, $snapshot, $kb);
        } catch (\Throwable $e) {
            $this->recordRun($tenantId, $conv, $providerCode, $provider?->default_model, AiAssistantRun::STATUS_ERROR, [
                'created_by' => $userId,
                'error' => substr($e->getMessage(), 0, 250),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'meta' => ['redacted_count' => $redactedCount],
            ]);
            throw AiSuggestionException::generationFailed($e->getMessage());
        }

        // Restore PII placeholder trong draft → NV thấy giá trị thật.
        $draftText = $mapping === [] ? $reply->body : strtr($reply->body, $mapping);

        $run = $this->recordRun($tenantId, $conv, $providerCode, $provider?->default_model, AiAssistantRun::STATUS_SUCCESS, [
            'created_by' => $userId,
            'prompt_tokens' => $reply->promptTokens,
            'completion_tokens' => $reply->completionTokens,
            'cost_micro_vnd' => $reply->costMicroVnd,
            'duration_ms' => $reply->durationMs,
            'meta' => ['redacted_count' => $redactedCount, 'kb_chunks' => count($kb->chunks)],
        ]);

        return MessageDraft::create([
            'tenant_id' => $tenantId,
            'conversation_id' => $conv->id,
            'ai_run_id' => $run->id,
            'draft_text' => $draftText,
            'suggested_attachments' => [],
            'status' => MessageDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);
    }

    private function resolveProviderCode(int $tenantId): string
    {
        $active = $this->registry->activeProviders();

        $chosen = MessagingSetting::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->value('ai_provider_code');

        if ($chosen && in_array($chosen, $active, true)) {
            return $chosen;
        }
        if ($active !== []) {
            return $active[0];
        }

        throw AiSuggestionException::providerNotAvailable();
    }

    private function assertWithinMonthlyLimit(int $tenantId): void
    {
        $sub = $this->subscriptions->currentFor($tenantId) ?? $this->subscriptions->ensureTrialFallback($tenantId);
        $limit = (int) ($sub?->plan?->limits['messaging_ai_replies_monthly'] ?? 0);

        if ($limit < 0) {
            return; // không giới hạn
        }

        $used = AiAssistantRun::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('mode', [AiAssistantRun::MODE_SUGGEST, AiAssistantRun::MODE_AUTO])
            ->where('status', AiAssistantRun::STATUS_SUCCESS)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        if ($used >= $limit) {
            throw AiSuggestionException::limitReached($used, $limit);
        }
    }

    /**
     * @return array{0:ConversationSnapshot,1:array<string,string>,2:int}
     */
    private function buildSnapshot(Conversation $conv, int $tenantId): array
    {
        $messages = Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->reverse()
            ->values();

        $recent = [];
        $mapping = [];
        $redactedCount = 0;

        foreach ($messages as $m) {
            $body = $m->body;
            if ($body !== null && $body !== '') {
                $r = $this->redactor->redact($body);
                $body = $r->redacted;
                foreach ($r->mapping as $placeholder => $original) {
                    if (! isset($mapping[$placeholder])) {
                        $mapping[$placeholder] = $original;
                        $redactedCount++;
                    }
                }
            }
            $recent[] = [
                'direction' => $m->direction,
                'kind' => $m->kind,
                'body' => $body,
                'sent_at' => $m->created_at?->toIso8601String(),
            ];
        }

        $customerProfile = null;
        if ($conv->customer_id) {
            $p = $this->customers->findById($tenantId, (int) $conv->customer_id);
            $customerProfile = $p ? ['name' => $p->name, 'reputation' => $p->reputationLabel] : null;
        }

        $snapshot = new ConversationSnapshot(
            conversationId: (int) $conv->id,
            provider: (string) $conv->provider,
            buyerName: $conv->buyer_name,
            recentMessages: $recent,
            customerProfile: $customerProfile,
        );

        return [$snapshot, $mapping, $redactedCount];
    }

    private function lastInboundBody(Conversation $conv): ?string
    {
        return Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_INBOUND)
            ->orderByDesc('created_at')
            ->value('body');
    }

    /**
     * @param  array<string,mixed>  $attrs
     */
    private function recordRun(int $tenantId, Conversation $conv, string $providerCode, ?string $model, string $status, array $attrs): AiAssistantRun
    {
        return AiAssistantRun::create(array_merge([
            'tenant_id' => $tenantId,
            'conversation_id' => $conv->id,
            'provider_code' => $providerCode,
            'model' => $model,
            'mode' => AiAssistantRun::MODE_SUGGEST,
            'status' => $status,
        ], $attrs));
    }
}
