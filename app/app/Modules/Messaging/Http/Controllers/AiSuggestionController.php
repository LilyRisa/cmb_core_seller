<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\Exceptions\OutboundWindowClosed;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Messaging\Exceptions\AiSuggestionException;
use CMBcoreSeller\Modules\Messaging\Http\Resources\MessageResource;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessageDraft;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Messaging\Services\OutboundMessageService;
use CMBcoreSeller\Modules\Messaging\Services\OutboundWindowGuard;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * AI suggestion: sinh draft (NV duyệt) → accept (gửi như tin thường) / reject.
 * SPEC-0024 §6.1.
 *
 * `generate` chạy đồng bộ (≤30s) để trả draft ngay cho FE. Route gắn
 * `plan.feature:messaging_ai` (chỉ Business) + permission `messaging.reply`.
 *
 * Guardrail quan trọng (§4.6): AI KHÔNG bao giờ tự gửi — accept đi qua đúng
 * `OutboundMessageService` + window guard như NV gửi tay (audit + window enforce).
 */
class AiSuggestionController extends Controller
{
    public function __construct(
        private AiSuggestionService $suggestions,
        private OutboundMessageService $outbound,
        private MessagingRegistry $registry,
        private OutboundWindowGuard $guard,
    ) {}

    public function generate(int $conversationId, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $conv = Conversation::query()->findOrFail($conversationId);

        // SPEC 0032 — trừ 1 lượt AI (ném 402 nếu gói không có AI / hết lượt).
        $tenantId = app(CurrentTenant::class)->id();
        if ($tenantId !== null) {
            app(AiCreditMeter::class)->consume($tenantId, 1);
        }

        try {
            $draft = $this->suggestions->suggest($conv, $request->user()->id);
        } catch (AiSuggestionException $e) {
            return response()->json([
                'error' => array_filter([
                    'code' => $e->errorCode,
                    'message' => $e->getMessage(),
                    'details' => $e->details ?: null,
                ]),
            ], $e->httpStatus);
        }

        return response()->json([
            'data' => [
                'draft_id' => $draft->id,
                'draft_text' => $draft->draft_text,
                'suggested_attachments' => $draft->suggested_attachments ?? [],
            ],
        ]);
    }

    public function accept(int $conversationId, int $draftId, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],   // cho phép NV sửa draft trước khi gửi
            'message_tag' => ['nullable', 'string', 'max:64'],
        ]);

        $conv = Conversation::query()->findOrFail($conversationId);
        $draft = MessageDraft::query()
            ->where('conversation_id', $conv->id)
            ->where('id', $draftId)
            ->where('status', MessageDraft::STATUS_PENDING)
            ->firstOrFail();

        $body = $data['body'] ?? $draft->draft_text;

        if (! $this->registry->has($conv->provider)) {
            return response()->json(['error' => ['code' => 'UNKNOWN_MESSAGING_PROVIDER', 'message' => "Provider [{$conv->provider}] không khả dụng."]], 422);
        }
        $connector = $this->registry->for($conv->provider);

        try {
            $this->guard->assertCanSend($connector, $conv, ['message_tag' => $data['message_tag'] ?? null]);
        } catch (OutboundWindowClosed $e) {
            return response()->json(['error' => ['code' => 'OUTBOUND_WINDOW_CLOSED', 'message' => $e->getMessage()]], 422);
        }

        $message = $this->outbound->queueText($conv, [
            'body' => $body,
            'sent_by_user_id' => $request->user()->id,
            'sent_by_ai' => true,
            'message_tag' => $data['message_tag'] ?? null,
            'ai_run_id' => $draft->ai_run_id,
        ]);

        $draft->update([
            'status' => MessageDraft::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'accepted_by' => $request->user()->id,
            'accepted_message_id' => $message->id,
        ]);

        return response()->json([
            'data' => (new MessageResource($message))->toArray($request),
        ], 202);
    }

    public function reject(int $conversationId, int $draftId): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $draft = MessageDraft::query()
            ->where('conversation_id', $conversationId)
            ->where('id', $draftId)
            ->where('status', MessageDraft::STATUS_PENDING)
            ->firstOrFail();

        $draft->update(['status' => MessageDraft::STATUS_REJECTED]);

        return response()->json(['data' => ['ok' => true]]);
    }
}
