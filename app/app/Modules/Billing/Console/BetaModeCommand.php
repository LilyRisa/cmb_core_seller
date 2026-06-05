<?php

namespace CMBcoreSeller\Modules\Billing\Console;

use CMBcoreSeller\Modules\Billing\Database\Seeders\TestUnlimitedPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionExpiryService;
use Illuminate\Console\Command;

/**
 * Bật/tắt chế độ beta — gói TEST không giới hạn cho tenant mới (SPEC 0032).
 *
 * Công tắc beta = cờ `is_active` của gói `test_unlimited`:
 *   php artisan billing:beta on   → seed/active gói test ⇒ tenant mới dùng `test_unlimited` (vô thời hạn, full + AI).
 *   php artisan billing:beta off  → hủy kích hoạt gói test ⇒ hạ mọi shop đang dùng gói test về trial.
 */
class BetaModeCommand extends Command
{
    protected $signature = 'billing:beta {state : on | off}';

    protected $description = 'Bật/tắt gói TEST beta. Tắt sẽ hạ các shop đang dùng gói test về trial.';

    public function handle(SubscriptionExpiryService $expiry): int
    {
        $state = strtolower((string) $this->argument('state'));
        if (! in_array($state, ['on', 'off'], true)) {
            $this->error('state phải là "on" hoặc "off".');

            return self::FAILURE;
        }

        if ($state === 'on') {
            $this->callSilent('db:seed', ['--class' => TestUnlimitedPlanSeeder::class, '--force' => true]);
            $this->info('Beta BẬT — tenant mới sẽ dùng gói TEST không giới hạn.');

            return self::SUCCESS;
        }

        // off: hủy kích hoạt gói test rồi hạ các shop đang dùng về trial.
        Plan::query()->where('code', 'test_unlimited')->update(['is_active' => false]);
        $result = $expiry->run(); // step 0 hạ test_unlimited → trial
        $this->info("Beta TẮT. Đã hạ {$result['expired']} subscription về trial.");

        return self::SUCCESS;
    }
}
