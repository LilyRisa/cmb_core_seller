<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Tính lại last_message_at + last_message_preview (+ last_inbound_at/last_outbound_at)
 * cho hội thoại CÓ SẴN từ tin nhắn thực tế.
 *
 * Sửa dữ liệu bị "clobber" bởi backfill cũ: trước đây mỗi tin ingest đều ghi đè
 * last_message_at/preview vô điều kiện; backfill trả tin newest→oldest nên tin CŨ
 * NHẤT (ingest cuối) ghi đè ⇒ inbox sai thứ tự + preview hiện tin đầu thay vì tin cuối.
 * Logic ingest đã được vá; lệnh này dọn dữ liệu lịch sử. Idempotent — chạy lại an toàn.
 */
class RecomputeConversationPreviews extends Command
{
    protected $signature = 'messaging:recompute-previews {--tenant= : Giới hạn 1 tenant id} {--chunk=200}';

    protected $description = 'Tính lại last_message_at/preview của hội thoại từ tin nhắn thực tế (sửa dữ liệu clobber).';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $tenant = $this->option('tenant');

        $q = Conversation::withoutGlobalScope(TenantScope::class);
        if ($tenant !== null && $tenant !== '') {
            $q->where('tenant_id', (int) $tenant);
        }

        $updated = 0;
        $q->orderBy('id')->chunkById($chunk, function ($convs) use (&$updated) {
            foreach ($convs as $conv) {
                $latest = $this->latest($conv->id);
                if ($latest === null) {
                    continue;
                }

                $lastInbound = $this->latest($conv->id, Message::DIRECTION_INBOUND);
                $lastOutbound = $this->latest($conv->id, Message::DIRECTION_OUTBOUND);

                $preview = $latest->body !== null
                    ? Str::limit(preg_replace('/\s+/', ' ', $latest->body), 197)
                    : '['.$latest->kind.']';

                $conv->forceFill([
                    'last_message_at' => $latest->sent_at ?? $latest->created_at,
                    'last_message_preview' => $preview,
                    'last_inbound_at' => $lastInbound ? ($lastInbound->sent_at ?? $lastInbound->created_at) : $conv->last_inbound_at,
                    'last_outbound_at' => $lastOutbound ? ($lastOutbound->sent_at ?? $lastOutbound->created_at) : $conv->last_outbound_at,
                ])->saveQuietly();
                $updated++;
            }
        });

        $this->info("recompute-previews: updated {$updated} conversations.");

        return self::SUCCESS;
    }

    /** Tin mới nhất theo (sent_at|created_at) của conversation, lọc direction nếu truyền. */
    private function latest(int $conversationId, ?string $direction = null): ?Message
    {
        return Message::withoutGlobalScope(TenantScope::class)
            ->where('conversation_id', $conversationId)
            ->when($direction !== null, fn ($qq) => $qq->where('direction', $direction))
            ->orderByRaw('COALESCE(sent_at, created_at) DESC')
            ->orderByDesc('id')
            ->first();
    }
}
