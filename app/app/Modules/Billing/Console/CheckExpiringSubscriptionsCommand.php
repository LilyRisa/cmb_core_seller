<?php

namespace CMBcoreSeller\Modules\Billing\Console;

use CMBcoreSeller\Modules\Billing\Services\SubscriptionExpiryService;
use Illuminate\Console\Command;

/**
 * `php artisan subscriptions:check-expiring` — chạy hằng ngày. SPEC 0018 §3.4.
 *
 * Áp state machine: trial→expired, active→past_due, past_due quá 7d→expired+fallback,
 * cancelled→expired+fallback. Idempotent.
 */
class CheckExpiringSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:check-expiring';

    protected $description = 'Áp luật hết hạn / grace / fallback trial cho subscriptions (Billing — SPEC 0018).';

    public function handle(SubscriptionExpiryService $service): int
    {
        $stats = $service->run();
        $this->info(sprintf(
            'past_due=%d, expired=%d, fallback_trial_created=%d',
            $stats['past_due'], $stats['expired'], $stats['fallback_created']
        ));

        return self::SUCCESS;
    }
}
