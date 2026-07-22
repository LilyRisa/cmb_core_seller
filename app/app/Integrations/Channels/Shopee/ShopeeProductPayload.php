<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;

/**
 * Builds Shopee Open API v2 request bodies for product publishing.
 *
 * add_item   — the initial create-item call (single-SKU: price+stock on root; multi-SKU: no price/stock).
 * tierVariation — the init_tier_variation call that follows add_item for multi-SKU items.
 *
 * Note: tier_variation is NOT part of add_item per Shopee API v2 spec.
 */
final class ShopeeProductPayload
{
    /**
     * Build the body for the add_item API call.
     *
     * @param  string|null  $videoUploadId  media_space video id (đã transcode xong), nếu có.
     * @return array<string,mixed>
     */
    public static function addItem(ListingDraftDTO $d, ?string $videoUploadId = null): array
    {
        $first = $d->skus[0];

        $body = [
            'category_id' => (int) $d->categoryId,
            'item_name' => $d->title,
            'description' => $d->description,
            // Tình trạng hàng (NEW/USED) — Shopee yêu cầu ở phần lớn ngành; mặc định NEW.
            'condition' => strtoupper((string) ($d->attributes['condition'] ?? 'NEW')),
            'image' => ['image_id_list' => array_map(fn ($m) => $m->ref, $d->media)],
            'logistic_info' => array_map(
                fn ($c) => array_filter([
                    // add_item dùng trường `logistic_id` (KHÔNG phải logistics_channel_id —
                    // đó là tên trường ở get_channel_list). Đã đối chiếu guide 211 + SDK shopeego.
                    // ÉP KIỂU int: FE lưu id kênh dạng string (Checkbox.Group), Shopee Go backend
                    // yêu cầu uint64 — gửi JSON string sẽ bị từ chối "cannot unmarshal string
                    // into Go struct field LogisticInfoSet.logistic_info.logistic_id".
                    'logistic_id' => isset($c['logistics_channel_id']) || isset($c['logistic_id'])
                        ? (int) ($c['logistics_channel_id'] ?? $c['logistic_id'])
                        : null,
                    'enabled' => (bool) $c['enabled'],
                    // is_free=true ⇒ người bán chịu phí ship. Gửi tường minh (doc guide 211 §6).
                    'is_free' => (bool) ($c['is_free'] ?? false),
                    'size_id' => $c['size_id'] ?? null,
                    'shipping_fee' => $c['shipping_fee'] ?? null,
                ], fn ($v) => $v !== null),
                $d->logistics['channels'],
            ),
            'weight' => $d->logistics['weight'] ?? null,
        ];

        if ($d->brandId !== null) {
            $body['brand'] = ['brand_id' => (int) $d->brandId];
        }

        if (! empty($d->attributes['attribute_list'])) {
            $body['attribute_list'] = $d->attributes['attribute_list'];
        }

        if (isset($d->logistics['dimension'])) {
            $body['dimension'] = $d->logistics['dimension'];
        }

        // Hàng đặt trước (pre-order): chỉ gửi khi người bán bật — giữ nguyên payload
        // hàng có sẵn. days_to_ship = số ngày chuẩn bị hàng (Shopee VN: 7–30).
        $preOrder = $d->logistics['pre_order'] ?? null;
        if (is_array($preOrder) && ! empty($preOrder['is_pre_order'])) {
            $body['pre_order'] = [
                'is_pre_order' => true,
                'days_to_ship' => (int) ($preOrder['days_to_ship'] ?? 0),
            ];
        }

        if ($videoUploadId !== null && $videoUploadId !== '') {
            $body['video_upload_id'] = [$videoUploadId];
        }

        if (count($d->skus) === 1) {
            $body['original_price'] = $first['price'];
            // Mô hình tồn kho hiện hành của add_item là `seller_stock: [{stock}]`
            // (đối chiếu trang tham chiếu chính chủ v2.product.add_item — `normal_stock`
            // đã bị loại). location_id để trống ⇒ kho mặc định của shop.
            $body['seller_stock'] = [['stock' => (int) $first['stock']]];
            if (! empty($first['seller_sku'])) {
                $body['item_sku'] = $first['seller_sku'];
            }
        }

        return array_filter($body, fn ($v) => $v !== null);
    }

    /**
     * Build the body for the init_tier_variation API call (multi-SKU items).
     *
     * @return array<string,mixed>
     */
    public static function tierVariation(int $itemId, ListingDraftDTO $d): array
    {
        [$tiers, $models] = self::buildTiers($d->skus);

        return ['item_id' => $itemId, 'tier_variation' => $tiers, 'model' => $models];
    }

    /**
     * Derive tier_variation + model arrays from the SKU list.
     *
     * @param  array<int,array<string,mixed>>  $skus
     * @return array{0:array<int,mixed>,1:array<int,mixed>}
     */
    private static function buildTiers(array $skus): array
    {
        /** @var string[] $tierNames */
        $tierNames = array_keys($skus[0]['sale_props'] ?? []); // max 2

        /** @var array<string,string[]> $options */
        $options = []; // tierName => ordered unique values

        foreach ($skus as $s) {
            foreach ($tierNames as $t) {
                $val = (string) ($s['sale_props'][$t] ?? '');
                if (! in_array($val, $options[$t] ?? [], true)) {
                    $options[$t][] = $val;
                }
            }
        }

        // Ảnh biến thể: Shopee CHỈ cho gắn ảnh ở tier ĐẦU TIÊN, và nếu gắn thì MỌI option của
        // tier đó phải có ảnh (thiếu 1 ⇒ bỏ hết, tránh lỗi). image_id do upload_image trả về.
        // Tham chiếu: Shopee Open Platform — Creating Product (option_list[].image.image_id).
        $firstTier = $tierNames[0] ?? null;
        $optionImage = []; // value (tier đầu) => image_id
        if ($firstTier !== null) {
            foreach ($skus as $s) {
                $val = (string) ($s['sale_props'][$firstTier] ?? '');
                $img = trim((string) ($s['image'] ?? ''));
                if ($val !== '' && $img !== '' && ! isset($optionImage[$val])) {
                    $optionImage[$val] = $img;
                }
            }
        }
        $allFirstTierHaveImage = $firstTier !== null
            && ($options[$firstTier] ?? []) !== []
            && count(array_filter($options[$firstTier], fn ($v) => isset($optionImage[$v]))) === count($options[$firstTier]);

        $tiers = [];
        foreach ($tierNames as $ti => $t) {
            $optionList = [];
            foreach ($options[$t] as $v) {
                $opt = ['option' => $v];
                if ($ti === 0 && $allFirstTierHaveImage) {
                    $opt['image'] = ['image_id' => $optionImage[$v]];
                }
                $optionList[] = $opt;
            }
            $tiers[] = ['name' => $t, 'option_list' => $optionList];
        }

        $models = [];
        foreach ($skus as $s) {
            $tierIndex = [];
            foreach ($tierNames as $t) {
                $pos = array_search((string) $s['sale_props'][$t], $options[$t], true);
                $tierIndex[] = $pos !== false ? (int) $pos : 0;
            }
            $models[] = [
                'tier_index' => $tierIndex,
                'original_price' => $s['price'],
                // init_tier_variation đặt tồn kho qua `seller_stock` (chính chủ),
                // không còn `normal_stock`.
                'seller_stock' => [['stock' => (int) $s['stock']]],
                'model_sku' => $s['seller_sku'] ?? '',
            ];
        }

        return [$tiers, $models];
    }
}
