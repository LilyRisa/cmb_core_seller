<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Services;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ListingEditDTO;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;

/**
 * Reads and edits an existing marketplace product (the "Sản phẩm đã có trên sàn"
 * screen). Resolves the provider's {@see ProductPublishingConnector}
 * via {@see PublisherRegistry} and pushes title / description / images / per-SKU price
 * back to the marketplace.
 *
 * Stock is deliberately out of scope — it is pushed from the linked master SKU(s).
 */
final class MarketplaceListingEditService
{
    public function __construct(private PublisherRegistry $publishers) {}

    /**
     * Fetch the full editable content of the product a ChannelListing belongs to.
     *
     * @return array{external_product_id:string,title:string,description:string,images:string[],skus:array<int,array{external_sku_id:string,seller_sku:string,price:int}>}
     */
    public function detail(ChannelListing $listing): array
    {
        [$auth, $productId] = $this->resolve($listing);
        $detail = $this->publishers->for($auth->provider)->getListingDetail($auth, $productId);

        return [
            'external_product_id' => $detail->externalProductId,
            'title' => $detail->title,
            'description' => $detail->description,
            'images' => $detail->images,
            'skus' => $detail->skus,
        ];
    }

    /**
     * Push edits to the marketplace, then mirror the changed fields onto the local
     * ChannelListing rows of that product.
     *
     * @param  array{title?:?string,description?:?string,images?:?array<int,string>,prices?:array<int,array{external_sku_id:string,price:int}>}  $data
     */
    public function update(ChannelListing $listing, array $data): array
    {
        [$auth, $productId] = $this->resolve($listing);

        $edit = new ListingEditDTO(
            title: array_key_exists('title', $data) ? $data['title'] : null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            images: array_key_exists('images', $data) ? $data['images'] : null,
            prices: $data['prices'] ?? null,
        );

        $this->publishers->for($auth->provider)->updateListing($auth, $productId, $edit);

        $this->mirrorLocally($listing, $productId, $edit);

        return $this->detail($listing->fresh() ?? $listing);
    }

    /** @return array{0:AuthContext,1:string} */
    private function resolve(ChannelListing $listing): array
    {
        $productId = (string) ($listing->external_product_id ?? '');
        abort_if($productId === '', 422, 'Sản phẩm này chưa có mã sản phẩm trên sàn để chỉnh sửa.');

        $account = ChannelAccount::findOrFail($listing->channel_account_id);
        abort_unless($this->publishers->has($account->provider), 422, 'Sàn này chưa hỗ trợ chỉnh sửa sản phẩm.');

        return [$account->authContext(), $productId];
    }

    /** Optimistically reflect the pushed edit on the tenant's local ChannelListing rows. */
    private function mirrorLocally(ChannelListing $listing, string $productId, ListingEditDTO $edit): void
    {
        $rows = ChannelListing::query()
            ->where('channel_account_id', $listing->channel_account_id)
            ->where('external_product_id', $productId)
            ->get();

        $priceBySku = [];
        foreach ($edit->prices ?? [] as $p) {
            $priceBySku[(string) $p['external_sku_id']] = (int) $p['price'];
        }
        $firstImage = $edit->images[0] ?? null;

        foreach ($rows as $row) {
            $patch = ['sync_status' => ChannelListing::SYNC_OK, 'last_pushed_at' => now()];
            if ($edit->title !== null) {
                $patch['title'] = $edit->title;
            }
            if ($firstImage !== null) {
                $patch['image'] = $firstImage;
            }
            if (array_key_exists($row->external_sku_id, $priceBySku)) {
                $patch['price'] = $priceBySku[$row->external_sku_id];
            }
            $row->forceFill($patch)->save();
        }
    }
}
