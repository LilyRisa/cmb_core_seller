<?php

namespace CMBcoreSeller\Modules\Customers\Console;

use CMBcoreSeller\Modules\Customers\Services\CustomerLinkingService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * One-shot: build the customer registry from existing orders (run once after the
 * Customers feature deploys). Idempotent — re-running it is safe. See SPEC 0002 §6.3.
 */
class BackfillCustomers extends Command
{
    protected $signature = 'customers:backfill {--chunk=200 : Orders per batch}';

    protected $description = 'Match existing orders to customers (one-shot backfill).';

    public function handle(CustomerLinkingService $linking): int
    {
        $query = Order::withoutGlobalScope(TenantScope::class)->whereNull('customer_id')->orderBy('id');
        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No unlinked orders — nothing to do.');

            return self::SUCCESS;
        }

        $this->info("Backfilling customers from {$total} unlinked order(s)…");
        $bar = $this->output->createProgressBar($total);
        $linked = 0;

        $query->chunkById((int) $this->option('chunk'), function ($orders) use ($linking, $bar, &$linked) {
            foreach ($orders as $order) {
                if ($linking->linkOrder($order) !== null) {
                    $linked++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Linked {$linked} order(s); ".($total - $linked).' had a masked/missing phone.');

        return self::SUCCESS;
    }
}
