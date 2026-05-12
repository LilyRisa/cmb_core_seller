<?php

namespace CMBcoreSeller\Modules\Inventory\Jobs;

use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Recompute the channel stock to show for every listing linked to this SKU and,
 * where it differs, enqueue a per-listing push. Debounced/coalesced via
 * ShouldBeUnique + a short dispatch delay so a burst of orders doesn't spam the
 * marketplace API. queue: inventory-push. See SPEC 0003 §3-4, docs/03-domain/inventory-and-sku-mapping.md §4.
 */
class PushStockForSku implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueForSeconds = 30;

    public function __construct(public int $tenantId, public int $skuId)
    {
        $this->onQueue('inventory-push');
    }

    public function uniqueId(): string
    {
        return "push-stock:{$this->tenantId}:{$this->skuId}";
    }

    public function uniqueFor(): int
    {
        return $this->uniqueForSeconds;
    }

    public function handle(InventoryLedgerService $ledger): void
    {
        $listingIds = SkuMapping::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenantId)->where('sku_id', $this->skuId)->pluck('channel_listing_id')->unique();

        foreach ($listingIds as $listingId) {
            /** @var ChannelListing|null $listing */
            $listing = ChannelListing::withoutGlobalScope(TenantScope::class)->find($listingId);
            if (! $listing || $listing->is_stock_locked || ! $listing->is_active) {
                continue;
            }

            // Desired = min over the listing's components of floor(availableTotal(sku_i) / quantity_i).
            $desired = null;
            foreach (SkuMapping::withoutGlobalScope(TenantScope::class)->where('channel_listing_id', $listing->getKey())->get() as $m) {
                $avail = $ledger->availableTotalForSku($this->tenantId, (int) $m->sku_id);
                $perComponent = intdiv($avail, max(1, (int) $m->quantity));
                $desired = $desired === null ? $perComponent : min($desired, $perComponent);
            }
            $desired ??= 0;

            if ($desired !== $listing->channel_stock) {
                PushStockToListing::dispatch((int) $listing->getKey(), $desired);
            }
        }
    }
}
