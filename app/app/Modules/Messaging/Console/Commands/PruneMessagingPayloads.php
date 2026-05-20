<?php

namespace CMBcoreSeller\Modules\Messaging\Console\Commands;

use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Xoá `messages.raw_payload` cũ hơn N ngày (mặc định 30) — giảm lưu PII thô
 * (08-security-and-privacy §6b). Chạy hằng ngày 03:00 (routes/console.php).
 *
 * Chỉ null hoá raw_payload, GIỮ message (body/metadata vẫn cần cho inbox).
 */
class PruneMessagingPayloads extends Command
{
    protected $signature = 'messaging:prune-payloads {--days=30}';

    protected $description = 'Null hoá messages.raw_payload cũ hơn N ngày (PII hygiene).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $affected = Message::withoutGlobalScope(TenantScope::class)
            ->whereNotNull('raw_payload')
            ->where('created_at', '<', $cutoff)
            ->update(['raw_payload' => null]);

        $this->info("prune-payloads: cleared raw_payload on {$affected} messages (> {$days}d).");

        return self::SUCCESS;
    }
}
