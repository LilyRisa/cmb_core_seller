<?php

namespace CMBcoreSeller\Modules\Billing\Console;

use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\OverQuotaCheckService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * SPEC 0020 — chạy mỗi giờ. Iterate mọi alive subscription, đồng bộ
 * `over_quota_warned_at` theo state hiện tại (idempotent).
 *
 * Để rẻ: load `plan` qua eager, không phát event, không gửi mail v1.
 */
class CheckOverQuotaCommand extends Command
{
    protected $signature = 'subscriptions:check-over-quota';

    protected $description = 'Recompute over-quota state cho mọi tenant + set/clear timer 2-day grace.';

    public function handle(OverQuotaCheckService $check): int
    {
        $count = 0;
        $set = 0;
        $cleared = 0;

        Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->whereIn('status', Subscription::ALIVE_STATUSES)
            ->with('plan')
            ->chunkById(200, function ($subs) use ($check, &$count, &$set, &$cleared) {
                foreach ($subs as $sub) {
                    $hadTimer = $sub->over_quota_warned_at !== null;
                    $check->apply($sub);
                    $nowHasTimer = $sub->over_quota_warned_at !== null;
                    if (! $hadTimer && $nowHasTimer) {
                        $set++;
                    }
                    if ($hadTimer && ! $nowHasTimer) {
                        $cleared++;
                    }
                    $count++;
                }
            });

        $this->info("Checked {$count} alive subscriptions; set {$set} new warnings, cleared {$cleared}.");

        return self::SUCCESS;
    }
}
