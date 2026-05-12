<?php

namespace CMBcoreSeller\Modules\Inventory\Console;

use CMBcoreSeller\Modules\Inventory\Services\OrderInventoryService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;

/**
 * Re-resolve `order_items.sku_id` (via SKU mappings / auto-match) and re-apply
 * stock effects for orders still flagged "SKU chưa ghép" — run after fetching
 * listings / creating mappings so older orders pick up the new links. Idempotent.
 * See SPEC 0003 §3-4.
 */
class ResyncOrderSkus extends Command
{
    protected $signature = 'inventory:resync-order-skus {--channel-account= : Limit to one channel account} {--chunk=200}';

    protected $description = 'Re-resolve SKU mapping & stock effects for orders flagged "SKU chưa ghép".';

    public function handle(OrderInventoryService $inventory): int
    {
        $query = Order::withoutGlobalScope(TenantScope::class)
            ->where('has_issue', true)->where('issue_reason', 'SKU chưa ghép')->orderBy('id');
        if ($cid = $this->option('channel-account')) {
            $query->where('channel_account_id', (int) $cid);
        }
        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No orders flagged "SKU chưa ghép".');

            return self::SUCCESS;
        }

        $this->info("Re-resolving SKUs for {$total} order(s)…");
        $bar = $this->output->createProgressBar($total);
        $query->chunkById((int) $this->option('chunk'), function ($orders) use ($inventory, $bar) {
            foreach ($orders as $order) {
                $inventory->apply($order);
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine(2);
        $stillUnmapped = Order::withoutGlobalScope(TenantScope::class)->where('has_issue', true)->where('issue_reason', 'SKU chưa ghép')->count();
        $this->info('Done. Còn lại '.$stillUnmapped.' đơn chưa ghép được SKU.');

        return self::SUCCESS;
    }
}
