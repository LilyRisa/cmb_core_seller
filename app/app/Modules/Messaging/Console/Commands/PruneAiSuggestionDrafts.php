<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Modules\Messaging\Models\MessageDraft;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Dọn AI suggestion drafts (SPEC-0024 §6.4):
 *   - pending mà quá `expires_at` (mặc định 1h) ⇒ đánh `expired`.
 *   - bản ghi expired/rejected cũ hơn 24h ⇒ xoá hẳn (gọn DB; audit đã có ở ai_assistant_runs).
 *
 * Chạy hằng ngày (routes/console.php).
 */
class PruneAiSuggestionDrafts extends Command
{
    protected $signature = 'messaging:prune-drafts';

    protected $description = 'Expire AI drafts quá hạn + xoá draft cũ.';

    public function handle(): int
    {
        $expired = MessageDraft::withoutGlobalScope(TenantScope::class)
            ->where('status', MessageDraft::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->update(['status' => MessageDraft::STATUS_EXPIRED]);

        $deleted = MessageDraft::withoutGlobalScope(TenantScope::class)
            ->whereIn('status', [MessageDraft::STATUS_EXPIRED, MessageDraft::STATUS_REJECTED])
            ->where('updated_at', '<', now()->subDay())
            ->delete();

        $this->info("prune-drafts: expired {$expired}, deleted {$deleted}.");

        return self::SUCCESS;
    }
}
