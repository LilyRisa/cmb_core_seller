<?php

namespace CMBcoreSeller\Modules\Channels\Jobs;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Inventory\Services\SkuMappingService;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pull a shop's listings (one row per product variant/SKU) into `channel_listings`,
 * then auto-match unmapped ones on `seller_sku == sku_code`. Idempotent (upsert by
 * `(channel_account_id, external_sku_id)`); unique per shop. queue: listings.
 * Triggered by `POST /channel-accounts/{id}/resync-listings`, `/channel-listings/sync`,
 * and daily by the scheduler. See SPEC 0003 §3-4, docs/03-domain/inventory-and-sku-mapping.md §2.
 */
class FetchChannelListings implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueForSeconds = 1800;

    public function __construct(public int $channelAccountId)
    {
        $this->onQueue('listings');
    }

    public function uniqueId(): string
    {
        return "fetch-listings:{$this->channelAccountId}";
    }

    public function uniqueFor(): int
    {
        return $this->uniqueForSeconds;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ChannelRegistry $registry, SkuMappingService $mappings, TokenRefresher $tokens): void
    {
        /** @var ChannelAccount|null $account */
        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($this->channelAccountId);
        if (! $account || ! $account->isActive() || ! $registry->has($account->provider)) {
            return;
        }
        $connector = $registry->for($account->provider);
        if (! $connector->supports('listings.fetch')) {
            return;
        }
        $tenantId = (int) $account->tenant_id;
        $accountId = (int) $account->getKey();

        $cursor = null;
        $upserted = 0;
        $now = now();
        for ($page = 0; $page < 200; $page++) {
            try {
                $pageResult = $connector->fetchListings($account->authContext(), ['cursor' => $cursor, 'pageSize' => 50]);
            } catch (Throwable $e) {
                $authErr = method_exists($e, 'isAuthError') ? $e->isAuthError() : str_contains(strtolower($e->getMessage()), 'access_token');
                if ($authErr && $tokens->refresh($account)) {
                    $account = $account->fresh();
                    $page--;

                    continue;
                }
                throw $e;
            }

            foreach ($pageResult->items as $dto) {
                /** @var ChannelListingDTO $dto */
                ChannelListing::withoutGlobalScope(TenantScope::class)->updateOrCreate(
                    ['channel_account_id' => $accountId, 'external_sku_id' => $dto->externalSkuId],
                    [
                        'tenant_id' => $tenantId,
                        'external_product_id' => $dto->externalProductId,
                        'seller_sku' => $dto->sellerSku,
                        'title' => $dto->title,
                        'variation' => $dto->variation,
                        'price' => $dto->price,
                        'channel_stock' => $dto->channelStock,
                        'currency' => $dto->currency ?: 'VND',
                        'image' => $dto->image,
                        'is_active' => $dto->isActive,
                        'last_fetched_at' => $now,
                        'meta' => $dto->raw ?: null,
                        // sync_status / is_stock_locked are managed by the push pipeline / user — left as-is.
                    ],
                );
                $upserted++;
            }

            if (! $pageResult->hasMore || ! $pageResult->nextCursor) {
                break;
            }
            $cursor = $pageResult->nextCursor;
        }

        // Link new listings whose seller_sku matches an existing master SKU.
        $matched = $mappings->autoMatchUnmapped($tenantId);

        Log::info('listings.fetched', ['channel_account_id' => $accountId, 'upserted' => $upserted, 'auto_matched' => $matched]);
    }
}
