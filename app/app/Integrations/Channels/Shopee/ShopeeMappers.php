<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;

/** Shopee v2 JSON -> standard DTOs. The ONLY place Shopee field names live (besides StatusMap/Verifier). */
final class ShopeeMappers
{
    /** @param array<string,mixed> $res token/get|refresh response @param string $shopId from context */
    public static function token(array $res, string $shopId): TokenDTO
    {
        $expireIn = (int) ($res['expire_in'] ?? 14400);
        $raw = $res;
        $raw['shop_id'] = $res['shop_id'] ?? $shopId;

        return new TokenDTO(
            accessToken: (string) ($res['access_token'] ?? ''),
            refreshToken: ($res['refresh_token'] ?? null) ? (string) $res['refresh_token'] : null,
            expiresAt: CarbonImmutable::now()->addSeconds($expireIn),
            refreshExpiresAt: CarbonImmutable::now()->addDays(30), // Shopee refresh ~30d
            scope: null,
            raw: $raw,
        );
    }

    /** @param array<string,mixed> $res get_shop_info `response` */
    public static function shopInfo(array $res, string $shopId): ShopInfoDTO
    {
        return new ShopInfoDTO(
            externalShopId: $shopId,
            name: (string) ($res['shop_name'] ?? ('Shopee '.$shopId)),
            region: (string) ($res['region'] ?? 'VN'),
            sellerType: isset($res['shop_type']) ? (string) $res['shop_type'] : null,
            raw: $res,
        );
    }

    /** @param array<string,mixed> $d a single get_order_detail order row */
    public static function order(array $d): \CMBcoreSeller\Integrations\Channels\DTO\OrderDTO
    {
        $addr = (array) ($d['recipient_address'] ?? []);
        $items = [];
        foreach ((array) ($d['item_list'] ?? []) as $it) {
            $items[] = new \CMBcoreSeller\Integrations\Channels\DTO\OrderItemDTO(
                externalItemId: (string) ($it['item_id'] ?? '').'-'.(string) ($it['model_id'] ?? '0'),
                externalProductId: isset($it['item_id']) ? (string) $it['item_id'] : null,
                externalSkuId: ($it['model_sku'] ?? '') !== '' ? (string) $it['model_sku'] : (string) ($it['model_id'] ?? ''),
                sellerSku: ($it['item_sku'] ?? '') !== '' ? (string) $it['item_sku'] : (($it['model_sku'] ?? '') !== '' ? (string) $it['model_sku'] : null),
                name: (string) ($it['item_name'] ?? ''),
                variation: ($it['model_name'] ?? '') !== '' ? (string) $it['model_name'] : null,
                quantity: (int) ($it['model_quantity_purchased'] ?? 1),
                unitPrice: (int) round((float) ($it['model_discounted_price'] ?? 0)),
                discount: 0,
                image: $it['image_info']['image_url'] ?? null,
                raw: (array) $it,
            );
        }
        $packages = [];
        foreach ((array) ($d['package_list'] ?? []) as $p) {
            $packages[] = [
                'externalPackageId' => isset($p['package_number']) ? (string) $p['package_number'] : null,
                'trackingNo' => isset($p['tracking_number']) ? (string) $p['tracking_number'] : null,
                'carrier' => isset($p['shipping_carrier']) ? (string) $p['shipping_carrier'] : null,
                'status' => isset($p['logistics_status']) ? (string) $p['logistics_status'] : null,
            ];
        }
        $isCod = (bool) ($d['cod'] ?? false);
        $grand = (int) round((float) ($d['total_amount'] ?? 0));

        return new \CMBcoreSeller\Integrations\Channels\DTO\OrderDTO(
            externalOrderId: (string) ($d['order_sn'] ?? ''),
            source: 'shopee',
            rawStatus: (string) ($d['order_status'] ?? ''),
            sourceUpdatedAt: CarbonImmutable::createFromTimestamp((int) ($d['update_time'] ?? time())),
            orderNumber: (string) ($d['order_sn'] ?? ''),
            placedAt: isset($d['create_time']) ? CarbonImmutable::createFromTimestamp((int) $d['create_time']) : null,
            paidAt: isset($d['pay_time']) ? CarbonImmutable::createFromTimestamp((int) $d['pay_time']) : null,
            buyer: ['name' => (string) ($addr['name'] ?? ($d['buyer_username'] ?? '')), 'phone' => (string) ($addr['phone'] ?? '')],
            shippingAddress: [
                'fullName' => (string) ($addr['name'] ?? ''), 'phone' => (string) ($addr['phone'] ?? ''),
                'line1' => (string) ($addr['full_address'] ?? ''), 'ward' => (string) ($addr['town'] ?? ''),
                'district' => (string) ($addr['district'] ?? ''), 'province' => (string) ($addr['state'] ?? ($addr['city'] ?? '')),
                'country' => 'VN', 'zip' => (string) ($addr['zipcode'] ?? ''),
            ],
            shippingFee: (int) round((float) ($d['actual_shipping_fee'] ?? $d['estimated_shipping_fee'] ?? 0)),
            codAmount: $isCod ? $grand : 0,
            grandTotal: $grand,
            isCod: $isCod,
            items: $items,
            packages: $packages,
            raw: $d,
        );
    }
}
