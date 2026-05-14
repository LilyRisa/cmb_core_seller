<?php

namespace CMBcoreSeller\Modules\Billing\Console;

use CMBcoreSeller\Modules\Billing\Services\UsageService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Console\Command;

/**
 * `php artisan billing:recompute-usage` — chạy hằng giờ. SPEC 0018 §6.5.
 *
 * Lưới an toàn: re-count `channel_accounts` cho mọi tenant và lưu vào `usage_counters`.
 * Phòng listener `BumpChannelAccountCounter` miss event (xảy ra ở PR2).
 */
class RecomputeUsageCommand extends Command
{
    protected $signature = 'billing:recompute-usage';

    protected $description = 'Recompute usage_counters cho mọi tenant (Billing — SPEC 0018).';

    public function handle(UsageService $usage): int
    {
        $count = 0;
        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use ($usage, &$count) {
            $usage->refresh((int) $tenant->getKey(), 'channel_accounts');
            $count++;
        });
        $this->info("Recomputed usage for {$count} tenants.");

        return self::SUCCESS;
    }
}
