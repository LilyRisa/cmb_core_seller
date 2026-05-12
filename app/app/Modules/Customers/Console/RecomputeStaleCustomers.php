<?php

namespace CMBcoreSeller\Modules\Customers\Console;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Services\CustomerLinkingService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Eventual-consistency safety net: recompute stats/reputation for customers whose
 * orders changed recently, in case the OrderUpserted listener failed. Runs hourly
 * (see routes/console.php). See SPEC 0002 §6.3.
 */
class RecomputeStaleCustomers extends Command
{
    protected $signature = 'customers:recompute-stale {--hours=2 : Recompute customers with orders updated within this window}';

    protected $description = 'Recompute lifetime stats & reputation for customers with recently-changed orders.';

    public function handle(CustomerLinkingService $linking): int
    {
        $since = now()->subHours(max(1, (int) $this->option('hours')));

        $customerIds = Order::withoutGlobalScope(TenantScope::class)
            ->whereNotNull('customer_id')
            ->where('updated_at', '>=', $since)
            ->distinct()->pluck('customer_id');

        if ($customerIds->isEmpty()) {
            $this->info('No customers to recompute.');

            return self::SUCCESS;
        }

        $n = 0;
        Customer::withoutGlobalScope(TenantScope::class)->whereIn('id', $customerIds)->chunkById(200, function ($customers) use ($linking, &$n) {
            foreach ($customers as $customer) {
                $linking->recompute($customer);
                $n++;
            }
        });

        $this->info("Recomputed {$n} customer(s).");

        return self::SUCCESS;
    }
}
