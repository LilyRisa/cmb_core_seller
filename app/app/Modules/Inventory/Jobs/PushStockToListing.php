<?php

namespace CMBcoreSeller\Modules\Inventory\Jobs;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Events\StockPushed;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Push the desired stock of one listing to its marketplace via the connector's
 * updateStock(). On success records `channel_stock`/`last_pushed_at`/`sync_status=ok`;
 * on final failure marks `sync_status=error` keeping the desired value for a retry.
 * queue: inventory-push (throttled per provider+shop inside the client). See SPEC 0003 §4.
 */
class PushStockToListing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public int $channelListingId, public int $desired)
    {
        $this->onQueue('inventory-push');
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(ChannelRegistry $registry): void
    {
        /** @var ChannelListing|null $listing */
        $listing = ChannelListing::withoutGlobalScope(TenantScope::class)->find($this->channelListingId);
        if (! $listing || $listing->is_stock_locked) {
            return;
        }
        /** @var ChannelAccount|null $account */
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($listing->channel_account_id);
        if (! $account || ! $account->isActive() || ! $registry->has($account->provider)) {
            return;
        }
        $connector = $registry->for($account->provider);
        if (! $connector->supports('listings.updateStock')) {
            $listing->forceFill(['sync_status' => ChannelListing::SYNC_ERROR, 'sync_error' => 'connector không hỗ trợ updateStock'])->save();

            return;
        }

        try {
            $connector->updateStock($account->authContext(), (string) $listing->external_sku_id, $this->desired, [
                'external_product_id' => $listing->external_product_id,
                'warehouse_id' => $listing->meta['warehouse_id'] ?? null,
            ]);
        } catch (UnsupportedOperation $e) {
            // Permanent (e.g. listing missing its product id, or connector can't push): mark & stop.
            $listing->forceFill(['sync_status' => ChannelListing::SYNC_ERROR, 'sync_error' => $e->getMessage()])->save();
            StockPushed::dispatch($listing, $this->desired, false);

            return;
        } catch (Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $listing->forceFill(['sync_status' => ChannelListing::SYNC_ERROR, 'sync_error' => $e->getMessage()])->save();
                StockPushed::dispatch($listing, $this->desired, false);
            }
            throw $e;
        }

        $listing->forceFill([
            'channel_stock' => $this->desired, 'last_pushed_at' => now(),
            'sync_status' => ChannelListing::SYNC_OK, 'sync_error' => null,
        ])->save();
        StockPushed::dispatch($listing, $this->desired, true);
    }
}
