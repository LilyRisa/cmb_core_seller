<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;

/**
 * Builds the JSON request body for `POST /product/202309/products` (TikTok Shop Partner API).
 *
 * Key rules enforced here (VN shop):
 * - `category_version` MUST be `'v2'`
 * - `price.amount` MUST be a string
 * - `main_images` is an array of `{uri}` objects
 * - Each SKU carries `sales_attributes`, `price.{amount,currency}`, and `inventory[{warehouse_id,quantity}]`
 * - `package_dimensions` is optional for VN — omitted when not provided
 * - `brand_id` is omitted entirely when `$d->brandId` is null
 */
final class TikTokProductPayload
{
    /**
     * @param  array<int,array<string,mixed>>  $productAttributes  Thuộc tính ngành hàng ĐÃ DỰNG
     *                                                             sẵn theo schema TikTok `[{id, values:[{id|name}]}]`. Connector dựng từ map phẳng của nháp
     *                                                             (cần biết input_type qua Get Attributes) rồi truyền vào — KHÔNG suy diễn ở đây vì payload
     *                                                             builder thuần, không gọi API. Xem {@see TikTokPublisher::buildProductAttributes()}.
     * @return array<string,mixed>
     */
    public static function toBody(ListingDraftDTO $d, string $saveMode = 'LISTING', ?string $videoId = null, array $productAttributes = []): array
    {
        $body = [
            'title' => $d->title,
            'description' => $d->description,
            'category_id' => $d->categoryId,
            'category_version' => 'v2',
            'save_mode' => $saveMode,
            'main_images' => array_map(fn ($m) => ['uri' => $m->ref], $d->media),
            'package_weight' => [
                'value' => (string) ($d->logistics['package_weight'] ?? ''),
                'unit' => $d->logistics['weight_unit'] ?? 'KILOGRAM',
            ],
            'product_attributes' => $productAttributes,
            'skus' => array_map(fn ($s) => self::sku($s), $d->skus),
        ];

        if ($d->brandId !== null) {
            $body['brand_id'] = $d->brandId;
        }

        if ($videoId !== null && $videoId !== '') {
            $body['video'] = ['id' => $videoId];
        }

        // Chống tạo trùng khi retry (TikTok dedupe theo idempotency_key) — bảo toàn
        // bất biến "mọi job sync idempotent".
        if ($d->idempotencyKey !== null && $d->idempotencyKey !== '') {
            $body['idempotency_key'] = $d->idempotencyKey;
        }

        return $body;
    }

    /**
     * Một phần tử `skus[]`. Sản phẩm KHÔNG biến thể (1 SKU) ⇒ `sale_props` rỗng ⇒
     * `sales_attributes: []` (chính chủ chấp nhận). Ảnh biến thể gắn vào `sku_img.uri`
     * của thuộc tính phân loại ĐẦU TIÊN (uri do API upload ảnh trả về) — không có thuộc
     * tính phân loại thì bỏ qua (ảnh chính main_images đã đại diện).
     *
     * @param  array<string,mixed>  $s
     * @return array<string,mixed>
     */
    private static function sku(array $s): array
    {
        // sales_attributes: khóa SỐ ⇒ thuộc tính dựng sẵn (gửi `id` kiểu Int64); khóa CHỮ
        // (vd "Phân loại") là thuộc tính tùy biến ⇒ gửi `name` (KHÔNG gửi `id`, sàn tự sinh).
        // Gửi `id`=chuỗi-chữ sẽ lỗi "must be convertible to Int64". Giới hạn: name ≤20, value_name ≤50.
        $salesAttributes = array_map(
            fn ($k, $v) => (ctype_digit((string) $k)
                ? ['id' => (string) $k]
                : ['name' => mb_substr((string) $k, 0, 20)])
                + ['value_name' => mb_substr((string) $v, 0, 50)],
            array_keys($s['sale_props'] ?? []),
            array_values($s['sale_props'] ?? [])
        );

        // Ảnh biến thể: chính chủ gắn `sku_img` vào MỘT thuộc tính phân loại của SKU
        // (skus[].sales_attributes[].sku_img.uri). Gắn vào cái đầu tiên.
        $image = trim((string) ($s['image'] ?? ''));
        if ($image !== '' && $salesAttributes !== []) {
            $salesAttributes[0]['sku_img'] = ['uri' => $image];
        }

        $sku = [
            'seller_sku' => $s['seller_sku'],
            'sales_attributes' => $salesAttributes,
            'price' => [
                'amount' => (string) $s['price'],
                'currency' => $s['currency'] ?? 'VND',
            ],
            'inventory' => [
                [
                    'warehouse_id' => $s['warehouse_id'],
                    'quantity' => (int) $s['stock'],
                ],
            ],
        ];

        // Mã định danh (GTIN/EAN/UPC) — BẮT BUỘC ở nhiều ngành; chỉ gửi khi master SKU có mã.
        if (! empty($s['gtin'])) {
            $sku['identifier_code'] = ['code' => (string) $s['gtin'], 'type' => (string) ($s['gtin_type'] ?? 'GTIN')];
        }

        return $sku;
    }
}
