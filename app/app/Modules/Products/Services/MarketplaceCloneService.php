<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Services;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDetailDTO;
use CMBcoreSeller\Integrations\Channels\PublisherRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Products\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Sao chép một sản phẩm "đã có trên sàn" (ChannelListing) sang nhiều gian hàng khác.
 *
 * Lấy nội dung đầy đủ từ sàn nguồn ({@see ProductPublishingConnector::getListingDetail()}),
 * gắn về một master {@see Product}, rồi tạo nháp đăng sàn cho từng shop đích:
 *  - Cùng nền tảng: copy cả ngành hàng/thương hiệu/thuộc tính ⇒ revalidate ⇒ READY (đẩy được luôn).
 *  - Khác nền tảng: chỉ giữ mô tả/ảnh/SKU ⇒ DRAFT (cần soạn ngành hàng/thuộc tính trước khi đẩy).
 */
final class MarketplaceCloneService
{
    public function __construct(
        private PublisherRegistry $publishers,
        private ListingDraftService $drafts,
    ) {}

    /**
     * @param  int[]  $targetShopIds
     * @return array<int,array{id:int,provider:string,status:string}>
     */
    public function cloneToShops(int $channelListingId, array $targetShopIds): array
    {
        $listing = ChannelListing::with('mappings.sku')->findOrFail($channelListingId);
        $source = ChannelAccount::findOrFail($listing->channel_account_id);
        $productId = (string) ($listing->external_product_id ?? '');
        abort_if($productId === '', 422, 'Sản phẩm này chưa có mã trên sàn để sao chép.');
        abort_unless($this->publishers->has($source->provider), 422, 'Sàn nguồn chưa hỗ trợ sao chép.');

        $detail = $this->publishers->for($source->provider)->getListingDetail($source->authContext(), $productId);

        return DB::transaction(function () use ($listing, $source, $detail, $targetShopIds) {
            $product = $this->resolveOrCreateProduct($listing, $detail);

            $out = [];
            foreach (array_unique(array_map('intval', $targetShopIds)) as $shopId) {
                $target = ChannelAccount::query()->active()->find($shopId);
                if (! $target || (int) $target->getKey() === (int) $source->getKey()) {
                    continue;
                }

                $draft = $this->drafts->createDraft((int) $product->getKey(), (int) $target->getKey(), $target->provider);

                $sameProvider = $target->provider === $source->provider;
                $payload = [
                    'description' => $detail->description,
                    'media_refs' => $detail->images,
                    'skus' => array_map(fn ($s) => ['seller_sku' => $s['seller_sku'], 'price' => $s['price']], $detail->skus),
                ];
                if ($sameProvider) {
                    // Đủ dữ liệu để đẩy luôn: tái dùng ngành hàng/thương hiệu/thuộc tính của sàn nguồn.
                    $payload['category_id'] = $detail->categoryId;
                    $payload['brand_id'] = $detail->brandId;
                    $payload['attributes'] = $detail->attributes;
                }

                $updated = $this->drafts->update((int) $draft->getKey(), $payload);
                $out[] = ['id' => (int) $updated->getKey(), 'provider' => $target->provider, 'status' => $updated->status];
            }

            return $out;
        });
    }

    /**
     * Sao chép NHIỀU sản phẩm cùng lúc (SPEC 2026-07-14) — mỗi `channel_listing_id` đại diện 1 sản phẩm,
     * xử lý ĐỘC LẬP: lỗi 1 sản phẩm (token hết hạn, sản phẩm bị gỡ...) không chặn các sản phẩm còn lại.
     *
     * @param  int[]  $channelListingIds
     * @param  int[]  $targetShopIds
     * @return list<array{channel_listing_id:int, ok:bool, results?:array, error?:string}>
     */
    public function bulkCloneToShops(array $channelListingIds, array $targetShopIds): array
    {
        $out = [];
        foreach (array_unique(array_map('intval', $channelListingIds)) as $id) {
            try {
                $out[] = ['channel_listing_id' => $id, 'ok' => true, 'results' => $this->cloneToShops($id, $targetShopIds)];
            } catch (\Throwable $e) {
                $out[] = ['channel_listing_id' => $id, 'ok' => false, 'error' => $e->getMessage()];
            }
        }

        return $out;
    }

    /** Master product cho listing: theo SKU mapping nếu có, ngược lại tạo mới từ detail. */
    private function resolveOrCreateProduct(ChannelListing $listing, ListingDetailDTO $detail): Product
    {
        $mappedProductId = $listing->mappings
            ->map(fn ($m) => $m->sku?->product_id)
            ->filter()
            ->first();

        if ($mappedProductId && ($p = Product::find($mappedProductId))) {
            return $p;
        }

        $product = Product::query()->create([
            'name' => $detail->title !== '' ? $detail->title : 'Sản phẩm sao chép',
            'image' => $detail->images[0] ?? null,
            'meta' => ['image_links' => $detail->images, 'source' => 'clone'],
        ]);

        foreach (array_values($detail->skus) as $i => $s) {
            $product->skus()->create([
                'tenant_id' => $product->tenant_id,
                'sku_code' => 'CL'.$product->getKey().'-'.($i + 1),
                'name' => (string) $product->name,
                'base_unit' => 'cái',
                'cost_price' => 0,
                'cost_method' => Sku::COST_AVERAGE,
                'ref_sale_price' => (int) $s['price'],
                'attributes' => array_filter(['source_sku' => $s['seller_sku'] ?: null]),
                'is_active' => true,
            ]);
        }

        return $product;
    }
}
