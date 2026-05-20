<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\SettlementDTO;
use CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO;
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
            $modelId = (string) ($it['model_id'] ?? '0');
            $itemId = (string) ($it['item_id'] ?? '');
            $items[] = new \CMBcoreSeller\Integrations\Channels\DTO\OrderItemDTO(
                externalItemId: $itemId.'-'.$modelId,
                externalProductId: $itemId !== '' ? $itemId : null,
                externalSkuId: ($modelId !== '' && $modelId !== '0') ? $modelId : ($itemId !== '' ? $itemId : null),
                sellerSku: ($it['model_sku'] ?? '') !== '' ? (string) $it['model_sku'] : (($it['item_sku'] ?? '') !== '' ? (string) $it['item_sku'] : null),
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
            // Địa chỉ VN: province-level lấy `city` (fallback `state`/region). ⚠ field chính xác phải verify trên sandbox Shopee.
            shippingAddress: [
                'fullName' => (string) ($addr['name'] ?? ''), 'phone' => (string) ($addr['phone'] ?? ''),
                'line1' => (string) ($addr['full_address'] ?? ''), 'ward' => (string) ($addr['town'] ?? ''),
                'district' => (string) ($addr['district'] ?? ''), 'province' => (string) ($addr['city'] ?? ($addr['state'] ?? '')),
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

    /**
     * @param  array<string,mixed>  $itemBase  one get_item_base_info item
     * @param  array<string,mixed>  $modelRes  get_model_list `response`
     * @return list<\CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO>
     */
    public static function listings(array $itemBase, array $modelRes): array
    {
        $itemId = (string) ($itemBase['item_id'] ?? '');
        $title = (string) ($itemBase['item_name'] ?? '');
        $image = $itemBase['image']['image_url_list'][0] ?? null;
        $active = (string) ($itemBase['item_status'] ?? 'NORMAL') === 'NORMAL';
        $models = (array) ($modelRes['model'] ?? []);
        $out = [];
        if ($models === []) {
            $out[] = new \CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO(
                externalSkuId: $itemId, externalProductId: $itemId,
                sellerSku: ($itemBase['item_sku'] ?? '') !== '' ? (string) $itemBase['item_sku'] : null,
                title: $title, variation: null,
                price: (int) round((float) ($itemBase['price_info'][0]['current_price'] ?? 0)),
                channelStock: null, image: $image, isActive: $active, raw: $itemBase,
            );

            return $out;
        }
        foreach ($models as $m) {
            $modelId = (string) ($m['model_id'] ?? '0');
            $out[] = new \CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO(
                externalSkuId: ($modelId !== '' && $modelId !== '0') ? $modelId : $itemId,
                externalProductId: $itemId,
                sellerSku: ($m['model_sku'] ?? '') !== '' ? (string) $m['model_sku'] : (($itemBase['item_sku'] ?? '') !== '' ? (string) $itemBase['item_sku'] : null),
                title: $title,
                variation: ($m['model_name'] ?? '') !== '' ? (string) $m['model_name'] : null,
                price: (int) round((float) ($m['price_info'][0]['current_price'] ?? 0)),
                channelStock: isset($m['stock_info_v2']['summary_info']['total_available_stock']) ? (int) $m['stock_info_v2']['summary_info']['total_available_stock'] : null,
                image: $image, isActive: $active, raw: $m,
            );
        }

        return $out;
    }

    /**
     * @param  list<array<string,mixed>>  $escrows  list of get_escrow_detail `response`
     */
    public static function settlement(array $escrows, CarbonImmutable $from, CarbonImmutable $to): SettlementDTO
    {
        $lines = [];
        $payout = 0;
        $revenue = 0;
        $fee = 0;
        $ship = 0;
        foreach ($escrows as $e) {
            $sn = (string) ($e['order_sn'] ?? '');
            $inc = (array) ($e['order_income'] ?? []);
            $payout += (int) round((float) ($inc['escrow_amount'] ?? 0));
            $add = function (string $type, int $amount, ?string $sn) use (&$lines, &$revenue, &$fee, &$ship) {
                if ($amount === 0) {
                    return;
                }
                $lines[] = new SettlementLineDTO(feeType: $type, amount: $amount, externalOrderId: $sn);
                if ($type === SettlementLineDTO::TYPE_REVENUE) {
                    $revenue += $amount;
                } elseif ($type === SettlementLineDTO::TYPE_SHIPPING_FEE || $type === SettlementLineDTO::TYPE_SHIPPING_SUBSIDY) {
                    $ship += $amount;
                } elseif ($type === SettlementLineDTO::TYPE_VOUCHER_SELLER || $type === SettlementLineDTO::TYPE_VOUCHER_PLATFORM) {
                    // vouchers tracked as lines only — not part of platform-fee total (SettlementDTO.totalFee = commission+payment+...)
                } else {
                    $fee += $amount;
                }
            };
            $add(SettlementLineDTO::TYPE_REVENUE, (int) round((float) ($inc['buyer_total_amount'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_COMMISSION, -(int) round((float) ($inc['commission_fee'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_PAYMENT_FEE, -(int) round((float) ($inc['seller_transaction_fee'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_OTHER, -(int) round((float) ($inc['service_fee'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_SHIPPING_FEE, -(int) round((float) ($inc['actual_shipping_fee'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_SHIPPING_SUBSIDY, (int) round((float) ($inc['shopee_shipping_rebate'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_VOUCHER_SELLER, -(int) round((float) ($inc['voucher_from_seller'] ?? 0)), $sn);
            $add(SettlementLineDTO::TYPE_VOUCHER_PLATFORM, (int) round((float) ($inc['voucher_from_shopee'] ?? 0)), $sn);
        }

        return new SettlementDTO(
            externalId: null, periodStart: $from, periodEnd: $to,
            totalPayout: $payout, totalRevenue: $revenue, totalFee: $fee, totalShippingFee: $ship,
            lines: $lines, raw: ['escrows' => $escrows],
        );
    }
}
