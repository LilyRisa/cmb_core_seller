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
     * @return array<string,mixed>
     */
    public static function toBody(ListingDraftDTO $d, string $saveMode = 'LISTING', ?string $videoId = null): array
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
            'product_attributes' => $d->attributes['product_attributes'] ?? [],
            'skus' => array_map(fn ($s) => array_merge([
                'seller_sku' => $s['seller_sku'],
                // sales_attributes: khóa SỐ ⇒ thuộc tính dựng sẵn (gửi `id` kiểu Int64);
                // khóa CHỮ (vd "Phân loại") là thuộc tính tùy biến ⇒ gửi `name` (KHÔNG gửi
                // `id`, sàn tự sinh id). Gửi `id`=chuỗi-chữ sẽ lỗi "must be convertible to Int64".
                // Giới hạn chính chủ: name ≤20, value_name ≤50 ký tự.
                'sales_attributes' => array_map(
                    fn ($k, $v) => (ctype_digit((string) $k)
                        ? ['id' => (string) $k]
                        : ['name' => mb_substr((string) $k, 0, 20)])
                        + ['value_name' => mb_substr((string) $v, 0, 50)],
                    array_keys($s['sale_props'] ?? []),
                    array_values($s['sale_props'] ?? [])
                ),
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
            ],
                // Mã định danh (GTIN/EAN/UPC) — BẮT BUỘC ở nhiều ngành; chỉ gửi khi master
                // SKU có mã. {code, type} theo schema chính chủ Create Product 202309.
                ! empty($s['gtin'])
                    ? ['identifier_code' => ['code' => (string) $s['gtin'], 'type' => (string) ($s['gtin_type'] ?? 'GTIN')]]
                    : []
            ), $d->skus),
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
}
