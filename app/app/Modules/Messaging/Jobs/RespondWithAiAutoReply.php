<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AiAutoModeResolver;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Debounce AI auto-reply (SPEC-0024 §4.6): mỗi inbound hẹn 1 job trễ
 * (`messaging.ai.auto_reply_debounce_seconds`). Khi chạy CHỈ trả lời nếu tin
 * trigger vẫn là tin INBOUND mới nhất của hội thoại (latest-wins) và chưa có ai
 * trả lời sau nó.
 *
 * ⇒ Gộp các tin khách gửi liên tiếp (3 text rời, hoặc Facebook tách text + ảnh
 * thành 2 event) thành MỘT lượt trả lời, tập trung vào lượt mới nhất. Các job của
 * tin cũ tự bỏ qua (đã có tin mới hơn). Tránh AI gửi 2 tin lặp.
 *
 * Queue `messaging-ai` (cùng supervisor với AiAutoModeOnInbound). Best-effort:
 * lỗi nuốt (không làm hỏng luồng nhận tin).
 */
class RespondWithAiAutoReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $conversationId, public int $triggerMessageId)
    {
        $this->onQueue('messaging-ai');
    }

    public function handle(AiSuggestionService $suggestions, AiAutoModeResolver $aiMode): void
    {
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->find($this->conversationId);
        if (! $conv || $conv->status === Conversation::STATUS_SPAM || $conv->blocked_at !== null) {
            return;
        }

        // Re-check lúc GỬI (sau debounce): "tắt là tắt" — đóng khe đua bật→tắt trong lúc chờ,
        // và chặn mọi đường dispatch không qua listener. Node ai_reply trong flow KHÔNG đi qua đây.
        if (! $aiMode->enabledFor($conv)) {
            return;
        }

        // Latest-wins: chỉ tin INBOUND mới nhất mới được trả lời ⇒ gộp burst, các job cũ bỏ qua.
        $latestInboundId = (int) Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_INBOUND)
            ->max('id');
        if ($latestInboundId !== $this->triggerMessageId) {
            return;
        }

        // Đã có outbound (rule/NV/AI) trả lời SAU tin này ⇒ thôi (tránh chồng tin).
        $repliedAfter = Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_OUTBOUND)
            ->where('id', '>', $this->triggerMessageId)
            ->exists();
        if ($repliedAfter) {
            return;
        }

        try {
            $suggestions->autoRespondToLatestTurn($conv);
        } catch (\Throwable $e) {
            // Auto-mode best-effort — log, không ném (không làm hỏng luồng nhận tin).
            Log::warning('messaging.auto_mode.failed', [
                'conversation_id' => $conv->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
