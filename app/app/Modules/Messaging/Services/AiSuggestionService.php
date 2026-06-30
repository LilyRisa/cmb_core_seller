<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerProfileContract;
use CMBcoreSeller\Modules\Messaging\Exceptions\AiSuggestionException;
use CMBcoreSeller\Modules\Messaging\Jobs\RespondWithAiAutoReply;
use CMBcoreSeller\Modules\Messaging\Models\AiAssistantRun;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Models\MessageDraft;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Orders\Contracts\OrderLookupContract;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Contracts\VisualItemSearch;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemCandidate;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualLookupOptions;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;

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
        private IntentClassifier $intentClassifier,
        private OutboundMessageService $outbound,
        private AiCreditMeter $credits,
        private OrderLookupContract $orderLookup,
        private MediaStorage $media,
        private VisualItemSearch $visualSearch,
    ) {}

    /**
     * Gate hạn mức AI (SPEC 0032): còn lượt mới cho gọi provider. Hết / gói không có AI
     * ⇒ ném {@see AiSuggestionException::limitReached} (manual → 402; auto-mode → caller nuốt).
     * Đếm thực tế dùng {@see AiCreditMeter::record} SAU mỗi response provider thành công.
     */
    private function assertHasCredit(int $tenantId): void
    {
        if (! $this->credits->canUse($tenantId, 1)) {
            $s = $this->credits->summary($tenantId);
            throw AiSuggestionException::limitReached((int) $s['period_used'], (int) $s['monthly_allowance']);
        }
    }

    /**
     * Auto-mode (S7): AI tự trả lời KHÔNG cần NV duyệt — nhưng qua guardrail
     * intent. Intent nhạy cảm (complaint/refund/urgent/legal_threat/abuse) ⇒
     * KHÔNG gửi, đánh `requires_human` để NV vào. SPEC §4.6.
     *
     * Vẫn đi qua đúng `OutboundMessageService` + ghi `ai_assistant_runs` (mode=auto)
     * — cùng pipeline với NV gửi tay (audit + window guard ở job).
     *
     * @return array{action:string, intent:string, message?:Message}
     */
    /**
     * Chỉ dẫn luôn đính vào system prompt: gộp burst + tập trung tin mới nhất.
     * Khách hay gửi nhiều tin liên tiếp (3 text rời / text + ảnh) ⇒ AI trả lời 1 lần.
     */
    private const REPLY_FOCUS_DIRECTIVE = 'Khách có thể gửi NHIỀU tin liên tiếp (text rời nhau hoặc kèm ảnh) — '
        .'hãy coi đó là MỘT yêu cầu và trả lời DUY NHẤT 1 lần, tự nhiên, KHÔNG lặp lại. '
        .'Tập trung vào Ý ĐỊNH MỚI NHẤT của khách; các tin trước chỉ là ngữ cảnh.';

    /**
     * Auto-mode cho LƯỢT mới nhất của khách (gộp các tin inbound chưa được trả lời).
     * Dùng bởi {@see RespondWithAiAutoReply} sau debounce.
     *
     * @return array{action:string, intent:string, message?:Message}
     */
    public function autoRespondToLatestTurn(Conversation $conv): array
    {
        return $this->autoRespond($conv, $this->currentTurnText($conv));
    }

    public function autoRespond(Conversation $conv, string $inboundText): array
    {
        $draft = $this->draftAutoReply($conv, $inboundText);
        if ($draft['action'] === 'escalated') {
            return ['action' => 'escalated', 'intent' => $draft['intent']];
        }

        $message = $this->outbound->queueText($conv, [
            'body' => (string) $draft['body'],
            'sent_by_user_id' => null,
            'sent_by_ai' => true,
            'ai_run_id' => $draft['run_id'] ?? null,
        ]);

        return ['action' => 'sent', 'intent' => $draft['intent'], 'message' => $message];
    }

    /**
     * Sinh nội dung auto-reply qua guardrail intent — DÙNG CHUNG cho auto-mode DM
     * ({@see autoRespond}) lẫn auto-reply comment (AutoReplyEngine action `ai_reply`).
     *
     * Escalate (intent nhạy cảm) ⇒ `action='escalated'` + đánh `requires_human`,
     * KHÔNG sinh body. Lỗi cứng (hết hạn mức / provider không khả dụng / sinh lỗi)
     * ⇒ ném {@see AiSuggestionException} (caller tự xử lý: DM ném tiếp, comment skip).
     *
     * @return array{action:'escalated'|'generated', intent:string, body?:string, run_id?:int}
     */
    public function draftAutoReply(Conversation $conv, string $inboundText): array
    {
        $tenantId = (int) $conv->tenant_id;
        $providerCode = $this->resolveProviderCode($tenantId);
        $this->assertHasCredit($tenantId);

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
        $kb = $this->retriever->retrieve($tenantId, $inboundText, channelAccountId: (int) $conv->channel_account_id, provider: (string) $conv->provider);
        $provider = AiProvider::query()->find($providerCode);
        $extra = $this->withVisualContext($this->baseSystemExtra(), $conv, $tenantId, $providerCode, $provider?->default_model);
        $ctx = new AiContext(tenantId: $tenantId, providerCode: $providerCode, model: $provider?->default_model, systemPromptExtra: $extra, meta: ['mode' => 'auto']);

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

        // 1 response provider thành công = 1 lượt AI (classify đã đếm riêng trong IntentClassifier).
        $this->credits->record($tenantId, 1);

        return ['action' => 'generated', 'intent' => $intent->intent, 'body' => $body, 'run_id' => (int) $run->id];
    }

    public function suggest(Conversation $conv, ?int $userId = null): MessageDraft
    {
        $tenantId = (int) $conv->tenant_id;

        $providerCode = $this->resolveProviderCode($tenantId);
        $this->assertHasCredit($tenantId);

        try {
            $connector = $this->registry->for($providerCode);
        } catch (ProviderNotConfigured) {
            throw AiSuggestionException::providerNotAvailable();
        }

        [$snapshot, $mapping, $redactedCount] = $this->buildSnapshot($conv, $tenantId);
        $kb = $this->retriever->retrieve($tenantId, $this->lastInboundBody($conv) ?? '', channelAccountId: (int) $conv->channel_account_id, provider: (string) $conv->provider);

        $provider = AiProvider::query()->find($providerCode);
        $ctx = new AiContext(
            tenantId: $tenantId,
            providerCode: $providerCode,
            model: $provider?->default_model,
            systemPromptExtra: $this->withVisualContext($this->baseSystemExtra(), $conv, $tenantId, $providerCode, $provider?->default_model),
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

        // 1 response provider thành công = 1 lượt AI (SPEC 0032).
        $this->credits->record($tenantId, 1);

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

    /**
     * Prompt chung do super-admin cấu hình (system_setting), ghép vào system prompt
     * khi sinh reply. Rỗng ⇒ null (connector bỏ qua). KHÔNG dùng cho classify intent.
     */
    private function globalSystemPrompt(): ?string
    {
        $prompt = trim((string) system_setting('messaging.ai.system_prompt', ''));

        return $prompt !== '' ? $prompt : null;
    }

    /**
     * (Visual search 2026-06-16) Ghép khối "sản phẩm nhận diện từ ảnh" vào systemPromptExtra.
     * TÁCH BIỆT: tắt feature / không có ảnh / lỗi ⇒ trả nguyên `$base` (luồng tối ưu bất biến).
     */
    private function withVisualContext(?string $base, Conversation $conv, int $tenantId, string $providerCode, ?string $model): ?string
    {
        $visual = $this->visualTrainingContext($conv, $tenantId, $providerCode, $model);
        if ($visual === null) {
            return $base;
        }

        return trim(($base !== null ? $base."\n\n" : '').$visual);
    }

    private function visualTrainingContext(Conversation $conv, int $tenantId, string $providerCode, ?string $model): ?string
    {
        // KHÔNG gate feature riêng — đây là 1 phần của AI tự động trả lời (đã qua gate
        // messaging_ai ở route suggest / AiAutoModeOnInbound). Không ảnh/không khớp/lỗi ⇒ null.
        try {
            $input = $this->latestInboundImage($conv);
            if ($input === null) {
                return null;
            }

            $opts = new VisualLookupOptions(
                channelAccountId: (int) $conv->channel_account_id,
                rerank: (bool) config('visual_search.rerank.enabled', true),
                providerCode: $providerCode,
                aiContext: new AiContext(tenantId: $tenantId, providerCode: $providerCode, model: $model, meta: ['mode' => 'visual_rerank']),
            );

            $result = $this->visualSearch->lookup($tenantId, $input, $opts);

            if ($result->status === VisualMatchResult::STATUS_MATCHED && $result->item !== null) {
                return $this->renderMatchedItem($result->item);
            }
            if ($result->status === VisualMatchResult::STATUS_AMBIGUOUS && $result->candidates !== []) {
                $names = implode(', ', array_map(fn (VisualItemCandidate $c) => $c->name, $result->candidates));

                return "Khách vừa gửi ảnh. Hệ thống nhận diện CÓ THỂ là một trong các sản phẩm: {$names}. "
                    .'Hãy HỎI LẠI khách để xác nhận đúng sản phẩm trước khi tư vấn (đừng tự ý chọn).';
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function renderMatchedItem(VisualItemCandidate $item): string
    {
        $lines = ["Sản phẩm khách đang hỏi (nhận diện từ ảnh khách gửi): {$item->name}."];
        if ($item->description !== null && $item->description !== '') {
            $lines[] = 'Mô tả: '.$item->description;
        }
        $attrs = [];
        foreach ($item->attributes as $k => $v) {
            if (is_scalar($v)) {
                $attrs[] = "{$k}: {$v}";
            }
        }
        if ($attrs !== []) {
            $lines[] = 'Đặc điểm: '.implode('; ', $attrs);
        }
        $lines[] = 'Hãy dùng ĐÚNG sản phẩm này để tư vấn (không nhầm sang sản phẩm khác).';

        return implode("\n", $lines);
    }

    /** Ảnh inbound (downloaded) gần nhất của hội thoại → VisualImageInput; không có ⇒ null. */
    private function latestInboundImage(Conversation $conv): ?VisualImageInput
    {
        $msgIds = Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_INBOUND)
            ->orderByDesc('created_at')
            ->limit(3)
            ->pluck('id');
        if ($msgIds->isEmpty()) {
            return null;
        }

        $att = MessageAttachment::withoutGlobalScope(TenantScope::class)
            ->whereIn('message_id', $msgIds->all())
            ->where('kind', MessageAttachment::KIND_IMAGE)
            ->where('status', MessageAttachment::STATUS_DOWNLOADED)
            ->orderByDesc('id')
            ->first();
        if ($att === null || ! $att->storage_path) {
            return null;
        }

        try {
            $bytes = (string) $this->media->disk()->get($att->storage_path);
        } catch (\Throwable) {
            return null;
        }
        if ($bytes === '') {
            return null;
        }

        return VisualImageInput::fromBinary($bytes, (string) ($att->mime ?: 'image/jpeg'));
    }

    /**
     * Text của 1 tin để đưa vào ngữ cảnh AI. Ngoài `body`, GỘP thêm text của NÚT BẤM
     * Facebook (template/quick-reply ⇒ meta.buttons; khách bấm nút ⇒ meta.postback_title)
     * — nếu không AI sẽ "mù" với các tin chỉ có nút (body rỗng). Rỗng hẳn ⇒ null.
     */
    public function snapshotMessageText(Message $m): ?string
    {
        $parts = [];
        $body = trim((string) $m->body);
        if ($body !== '') {
            $parts[] = $body;
        }

        $meta = (array) ($m->meta ?? []);

        // Khách BẤM NÚT (postback): tiêu đề nút = điều khách chọn.
        $postbackTitle = trim((string) ($meta['postback_title'] ?? ''));
        if ($postbackTitle !== '' && ! in_array($postbackTitle, $parts, true)) {
            $parts[] = $postbackTitle;
        }

        // Tin CÓ NÚT (template/quick-reply): đưa tiêu đề các nút vào ngữ cảnh.
        $titles = [];
        foreach ((array) ($meta['buttons'] ?? []) as $b) {
            $title = is_array($b) ? trim((string) ($b['title'] ?? '')) : '';
            if ($title !== '') {
                $titles[] = $title;
            }
        }
        if ($titles !== []) {
            $parts[] = '[Nút: '.implode(', ', $titles).']';
        }

        return $parts === [] ? null : implode("\n", $parts);
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
            $body = $this->snapshotMessageText($m);
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
            $entry = [
                'direction' => $m->direction,
                'kind' => $m->kind,
                'body' => $body,
                'sent_at' => $m->created_at?->toIso8601String(),
            ];
            // Vision: đính link ảnh khách gửi (chỉ tin INBOUND) để AI phân tích.
            if ($m->direction === Message::DIRECTION_INBOUND) {
                $imageUrls = $this->imageUrlsFor($m);
                if ($imageUrls !== []) {
                    $entry['image_urls'] = $imageUrls;
                }
            }
            $recent[] = $entry;
        }

        // Ngữ cảnh khách + đơn lấy TRỰC TIẾP từ liên kết hội thoại (customer_id / order_id đã gắn)
        // — KHÔNG tra số điện thoại. order_id đã gắn ⇒ resolve customer của đơn để lấy toàn bộ đơn của khách.
        $linkedOrderId = $conv->order_id ? (int) $conv->order_id : null;
        $linkedOrder = $linkedOrderId !== null ? $this->orderLookup->find($tenantId, $linkedOrderId) : null;
        $customerId = $conv->customer_id ? (int) $conv->customer_id : ($linkedOrder?->customerId);

        $customerProfile = null;
        if ($customerId !== null) {
            $p = $this->customers->findById($tenantId, $customerId);
            $customerProfile = $p ? ['name' => $p->name, 'reputation' => $p->reputationLabel] : null;
        }

        $orderContext = null;
        if ($customerId !== null) {
            $orders = $this->orderLookup->recentByCustomer($tenantId, $customerId, 5);
            if ($orders !== []) {
                $orderContext = ['orders' => array_map(fn ($o) => $o->toArray(), $orders)];
            }
        }
        // Đơn thủ công gắn hội thoại nhưng chưa gắn customer ⇒ vẫn đưa đúng đơn đã gắn.
        if ($orderContext === null && $linkedOrder !== null) {
            $orderContext = ['orders' => [$linkedOrder->toArray()]];
        }

        $snapshot = new ConversationSnapshot(
            conversationId: (int) $conv->id,
            provider: (string) $conv->provider,
            buyerName: $conv->buyer_name,
            recentMessages: $recent,
            customerProfile: $customerProfile,
            orderContext: $orderContext,
        );

        return [$snapshot, $mapping, $redactedCount];
    }

    /**
     * Link (hoặc data-URI) ảnh đã relay của 1 tin, để gửi cho AI vision.
     * Ưu tiên signed URL; `ai.vision.inline_base64`=true ⇒ nhúng base64 (dev/local).
     *
     * @return list<string>
     */
    private function imageUrlsFor(Message $m): array
    {
        if (! (bool) config('ai.vision.enabled', true) || (int) $m->attachments_count < 1) {
            return [];
        }
        $inline = (bool) config('ai.vision.inline_base64', false);
        $max = max(1, (int) config('ai.vision.max_images_per_message', 3));
        $maxKb = (int) config('ai.vision.inline_max_kb', 4096);

        $atts = MessageAttachment::withoutGlobalScope(TenantScope::class)
            ->where('message_id', $m->id)
            ->where('kind', MessageAttachment::KIND_IMAGE)
            ->where('status', MessageAttachment::STATUS_DOWNLOADED)
            ->limit($max)
            ->get();

        $urls = [];
        foreach ($atts as $att) {
            $u = $inline ? $this->media->imageDataUrl($att, $maxKb) : $this->media->temporaryUrl($att);
            if (is_string($u) && $u !== '') {
                $urls[] = $u;
            }
        }

        return $urls;
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
     * Text của LƯỢT khách hiện tại (các tin inbound chưa được trả lời = sau outbound gần
     * nhất), gộp lại để classify intent + truy hồi KB. Tập trung lượt mới nhất; ảnh được
     * xử lý riêng qua snapshot/visual context. Rỗng (vd chỉ có ảnh) ⇒ ''.
     */
    private function currentTurnText(Conversation $conv): string
    {
        $lastOutboundId = (int) Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_OUTBOUND)
            ->max('id');

        $messages = Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_INBOUND)
            ->when($lastOutboundId > 0, fn ($q) => $q->where('id', '>', $lastOutboundId))
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->reverse();

        $parts = [];
        foreach ($messages as $m) {
            $t = $this->snapshotMessageText($m);
            if ($t !== null && $t !== '') {
                $parts[] = $t;
            }
        }

        return implode("\n", $parts);
    }

    /** Chỉ dẫn cơ sở (focus lượt mới nhất) + prompt chung super-admin ⇒ systemPromptExtra. */
    private function baseSystemExtra(): string
    {
        $global = $this->globalSystemPrompt();

        return $global !== null && $global !== ''
            ? self::REPLY_FOCUS_DIRECTIVE."\n\n".$global
            : self::REPLY_FOCUS_DIRECTIVE;
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
