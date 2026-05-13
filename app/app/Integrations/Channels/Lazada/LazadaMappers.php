<?php

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\OrderItemDTO;
use CMBcoreSeller\Integrations\Channels\DTO\SettlementDTO;
use CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;

/**
 * Translates Lazada Open Platform JSON into the standard DTOs. The ONLY place
 * Lazada field names appear. Defensive about missing fields (DTOs carry `raw`).
 * Money is parsed to bigint VND đồng (Lazada returns money as strings/floats with
 * 2 decimals; VND has no subunits). Times are like "2026-05-17 10:00:00 +0700".
 * See docs/04-channels/lazada.md, docs/04-channels/README.md §2.
 */
final class LazadaMappers
{
    /** @param array<string,mixed> $data token data from /auth/token/create|refresh */
    public static function token(array $data): TokenDTO
    {
        return new TokenDTO(
            accessToken: (string) ($data['access_token'] ?? ''),
            refreshToken: ($rt = $data['refresh_token'] ?? null) !== null && $rt !== '' ? (string) $rt : null,
            expiresAt: self::expiresInToInstant($data['expires_in'] ?? null),
            refreshExpiresAt: self::expiresInToInstant($data['refresh_expires_in'] ?? null),
            scope: self::scopeString($data),
            raw: $data,
        );
    }

    private static function expiresInToInstant(mixed $secondsFromNow): ?CarbonImmutable
    {
        return $secondsFromNow !== null && $secondsFromNow !== '' && (int) $secondsFromNow > 0
            ? CarbonImmutable::now()->addSeconds((int) $secondsFromNow)
            : null;
    }

    /** @param array<string,mixed> $data */
    private static function scopeString(array $data): ?string
    {
        // Lazada returns country + a per-country user info block; surface the country/account as "scope"-ish context.
        $parts = array_filter([$data['country'] ?? null, $data['account'] ?? null], fn ($v) => $v !== null && $v !== '');

        return $parts !== [] ? implode(',', array_map('strval', $parts)) : null;
    }

    /**
     * Build a {@see ShopInfoDTO} from Lazada's `/seller/get` payload + the raw token response.
     *
     * `/seller/get` (open.lazada.com docs — `path=/seller/get`) returns
     * `{ name, name_company, seller_id (int64), short_code, logo_url, email, cb, location }`.
     * The token response from `/auth/token/create|refresh` carries cross-country identity in either
     * `country_user_info` (legacy field) **or** `country_user_info_list` (current field) — both are
     * accepted here. Each entry is `{ country, user_id, seller_id, short_code }`.
     *
     * Selection rules (in order):
     * 1. `external_shop_id` = `seller_id` (numeric, từ /seller/get → token fallback). Webhook push của
     *    Lazada gửi `data.seller_id` — phải khớp 1-1 để không bị "shop_not_found".
     * 2. Nếu cả 2 nguồn đều không có `seller_id` (rất hiếm) → dùng `short_code` để đỡ chặn flow connect,
     *    nhưng webhook sẽ không khớp (đã có warning trong log API).
     *
     * @param  array<string,mixed>  $sellerData  envelope `data` của /seller/get
     * @param  array<string,mixed>  $tokenRaw  payload thô của /auth/token/create|refresh
     */
    public static function shopInfo(array $sellerData, array $tokenRaw = []): ShopInfoDTO
    {
        $userInfo = [];
        $userInfoList = (array) ($tokenRaw['country_user_info_list'] ?? $tokenRaw['country_user_info'] ?? []);
        foreach ($userInfoList as $u) {
            $userInfo = (array) $u;
            break;   // chỉ lấy shop đầu tiên; cross-border seller chọn shop khác = một channel_account khác
        }
        $shopId = (string) ($sellerData['seller_id'] ?? $userInfo['seller_id'] ?? $sellerData['short_code'] ?? $userInfo['short_code'] ?? '');
        $name = (string) ($sellerData['name'] ?? $sellerData['name_company'] ?? $sellerData['seller_name'] ?? $sellerData['company'] ?? $tokenRaw['account'] ?? 'Lazada shop');
        // `region` = mã quốc gia ISO 2 ký tự. Lazada `/seller/get` field `location` là **tên thành phố/khu
        // vực FREE-FORM** (vd "Hà Nội (Mới)") — KHÔNG phải country code. Phải ưu tiên `country` từ token
        // (`"vn"` / `"sg"` / ...) hoặc `country_user_info[].country`. Bug cũ dùng `location` ⇒ tràn cột
        // `shop_region varchar(8)` với data tiếng Việt (SQLSTATE 22001).
        $rawRegion = strtolower(trim((string) ($tokenRaw['country'] ?? $userInfo['country'] ?? 'vn')));
        // `cb` = cross-border seller (Lazada gắn cho tài khoản xuyên biên giới) — vẫn để CB để không nhầm.
        $region = match ($rawRegion) {
            'vn', 'vietnam', 'vie' => 'VN',
            'sg', 'singapore' => 'SG',
            'my', 'malaysia' => 'MY',
            'th', 'thailand' => 'TH',
            'ph', 'philippines' => 'PH',
            'id', 'indonesia' => 'ID',
            'cb' => 'CB',
            default => strtoupper(mb_substr($rawRegion, 0, 4)) ?: 'VN',   // cắt 4 ký tự cho an toàn
        };

        return new ShopInfoDTO(
            externalShopId: $shopId,
            name: $name,
            region: $region ?: 'VN',
            sellerType: $sellerData['seller_type'] ?? null,
            raw: ['seller' => $sellerData, 'token_country_user_info' => $userInfo],
        );
    }

    /**
     * @param  array<string,mixed>  $order  one element of /orders/get -> data.orders[]  (or /order/get -> data)
     * @param  list<array<string,mixed>>  $items  /order/items/get -> data  (or /orders/items/get -> data[].order_items[])
     */
    public static function order(array $order, array $items = []): OrderDTO
    {
        $statuses = array_values(array_map('strval', (array) ($order['statuses'] ?? [])));
        if ($statuses === [] && $items !== []) {
            $statuses = array_values(array_filter(array_map(fn ($i) => (string) ($i['status'] ?? ''), $items), fn ($s) => $s !== ''));
        }
        $rawStatus = LazadaStatusMap::collapse($statuses);

        $shippingFee = self::money($order['shipping_fee'] ?? null);
        $shipSellerDiscount = self::money($order['shipping_fee_discount_seller'] ?? null);
        $shipPlatformDiscount = self::money($order['shipping_fee_discount_platform'] ?? null);
        $sellerVoucher = self::money($order['voucher_seller'] ?? null);
        $platformVoucher = self::money($order['voucher_platform'] ?? null);
        $totalVoucher = self::money($order['voucher'] ?? null);
        // split the total voucher when the breakdown isn't given
        if ($sellerVoucher === 0 && $platformVoucher === 0 && $totalVoucher > 0) {
            $sellerVoucher = $totalVoucher;
        }
        $grandTotal = self::money($order['price'] ?? null);
        $itemTotal = max(0, $grandTotal - $shippingFee + $sellerVoucher + $platformVoucher);
        if ($items !== []) {
            $sum = 0;
            foreach ($items as $i) {
                $sum += self::money($i['item_price'] ?? $i['paid_price'] ?? null);
            }
            if ($sum > 0) {
                $itemTotal = $sum;
            }
        }

        $payMethod = (string) ($order['payment_method'] ?? '');
        $isCod = str_contains(strtolower($payMethod), 'cod') || str_contains(strtolower($payMethod), 'cash on delivery');

        $ship = (array) ($order['address_shipping'] ?? []);
        $buyerName = trim((string) ($order['customer_first_name'] ?? '').' '.(string) ($order['customer_last_name'] ?? ''))
            ?: trim((string) ($ship['first_name'] ?? '').' '.(string) ($ship['last_name'] ?? ''));

        $dt = fn ($v) => self::time($v);
        $lineItems = self::lineItems($items);

        // shipped/delivered/cancelled timestamps: Lazada doesn't put these at order level; derive from items if present.
        $shippedAt = null;
        $deliveredAt = null;
        foreach ($items as $i) {
            $st = strtolower((string) ($i['status'] ?? ''));
            if (in_array($st, ['shipped', 'delivered'], true) && ! $shippedAt) {
                $shippedAt = $dt($i['updated_at'] ?? null);
            }
            if ($st === 'delivered' && ! $deliveredAt) {
                $deliveredAt = $dt($i['updated_at'] ?? null);
            }
        }

        $packages = [];
        $seenPkg = [];
        foreach ($items as $i) {
            $pid = (string) ($i['package_id'] ?? '');
            $key = $pid !== '' ? $pid : ((string) ($i['tracking_code'] ?? ''));
            if ($key === '' || isset($seenPkg[$key])) {
                continue;
            }
            $seenPkg[$key] = true;
            $packages[] = [
                'externalPackageId' => $pid !== '' ? $pid : null,
                'trackingNo' => self::str($i['tracking_code'] ?? null),
                'carrier' => self::str($i['shipment_provider'] ?? null),
                'status' => self::str($i['status'] ?? null),
            ];
        }

        return new OrderDTO(
            externalOrderId: (string) ($order['order_id'] ?? ''),
            source: 'lazada',
            rawStatus: $rawStatus,
            sourceUpdatedAt: $dt($order['updated_at'] ?? null) ?? CarbonImmutable::now(),
            orderNumber: isset($order['order_number']) ? (string) $order['order_number'] : ((string) ($order['order_id'] ?? '')),
            paymentStatus: $rawStatus === 'unpaid' ? 'unpaid' : ($isCod ? 'cod' : 'paid'),
            placedAt: $dt($order['created_at'] ?? null),
            paidAt: null,
            shippedAt: $shippedAt,
            deliveredAt: $deliveredAt,
            completedAt: $rawStatus === 'delivered' ? $deliveredAt : null,
            cancelledAt: $rawStatus === 'canceled' ? ($dt($order['updated_at'] ?? null)) : null,
            cancelReason: self::str($order['cancel_reason'] ?? null),
            buyer: $buyerName !== '' ? ['name' => $buyerName] : [],
            shippingAddress: self::address($ship, (string) ($order['buyer_note'] ?? $order['remarks'] ?? '')),
            currency: 'VND',
            itemTotal: $itemTotal,
            shippingFee: $shippingFee,
            platformDiscount: $platformVoucher + $shipPlatformDiscount,
            sellerDiscount: $sellerVoucher + $shipSellerDiscount,
            tax: 0,
            codAmount: $isCod ? $grandTotal : 0,
            grandTotal: $grandTotal,
            isCod: $isCod,
            fulfillmentType: ($fbl = ($items[0]['shipping_type'] ?? null)) ? (string) $fbl : null,
            items: $lineItems,
            packages: $packages,
            raw: ['order' => $order] + ($items !== [] ? ['items' => $items] : []),
        );
    }

    /**
     * Lazada order items are one row per unit; group by SKU to get quantity.
     *
     * @param  list<array<string,mixed>>  $rawItems
     * @return list<OrderItemDTO>
     */
    private static function lineItems(array $rawItems): array
    {
        /** @var array<string, list<array<string,mixed>>> $groups */
        $groups = [];
        foreach ($rawItems as $li) {
            $key = (string) ($li['sku'] ?? $li['shop_sku'] ?? $li['order_item_id'] ?? spl_object_hash((object) $li));
            $groups[$key][] = $li;
        }
        $out = [];
        foreach ($groups as $key => $rows) {
            $first = $rows[0];
            $qty = count($rows);
            $discount = 0;
            foreach ($rows as $r) {
                $discount += self::money($r['voucher_amount'] ?? null);
            }
            $unitPrice = self::money($first['item_price'] ?? $first['paid_price'] ?? null);
            $out[] = new OrderItemDTO(
                externalItemId: (string) ($first['order_item_id'] ?? $key),
                externalProductId: isset($first['shop_sku']) ? (string) $first['shop_sku'] : null,   // Lazada has no separate product id at item level; shop_sku is the closest
                externalSkuId: isset($first['sku']) ? (string) $first['sku'] : null,
                sellerSku: isset($first['sku']) ? (string) $first['sku'] : null,
                name: (string) ($first['name'] ?? 'Sản phẩm'),
                variation: ($v = $first['variation'] ?? null) !== null && $v !== '' ? (string) $v : null,
                quantity: max(1, $qty),
                unitPrice: $unitPrice,
                discount: $discount,
                image: ($img = $first['product_main_image'] ?? null) !== null && $img !== '' ? (string) $img : null,
                raw: ['items' => $rows],
            );
        }

        return $out;
    }

    /**
     * @param  array<string,mixed>  $product  one element of /products/get -> data.products[]
     * @return list<ChannelListingDTO>
     */
    public static function listings(array $product): array
    {
        $itemId = (string) ($product['item_id'] ?? '');
        $attrs = (array) ($product['attributes'] ?? []);
        $title = isset($attrs['name']) ? (string) $attrs['name'] : null;
        $out = [];
        foreach ((array) ($product['skus'] ?? []) as $sku) {
            $skuId = (string) ($sku['ShopSku'] ?? $sku['SkuId'] ?? $sku['shop_sku'] ?? $sku['sku_id'] ?? '');
            if ($skuId === '') {
                continue;
            }
            $status = strtolower((string) ($sku['Status'] ?? $sku['status'] ?? 'active'));
            $isActive = ! in_array($status, ['inactive', 'deleted', 'suspended', 'rejected'], true);
            $price = $sku['special_price'] ?? $sku['price'] ?? null;
            $image = null;
            foreach ((array) ($sku['Images'] ?? $sku['images'] ?? []) as $img) {
                $u = is_array($img) ? ($img['url'] ?? null) : $img;
                if (is_string($u) && $u !== '') {
                    $image = $u;
                    break;
                }
            }
            // variation from any "saleProp"-ish keys
            $varParts = [];
            foreach (['color_family', 'size', 'variation'] as $k) {
                if (! empty($sku[$k]) && is_scalar($sku[$k])) {
                    $varParts[] = (string) $sku[$k];
                }
            }
            $out[] = new ChannelListingDTO(
                externalSkuId: $skuId,
                externalProductId: $itemId ?: null,
                sellerSku: isset($sku['SellerSku']) ? (string) $sku['SellerSku'] : (isset($sku['seller_sku']) ? (string) $sku['seller_sku'] : null),
                title: $title,
                variation: $varParts !== [] ? implode(', ', $varParts) : null,
                price: $price !== null ? self::money($price) : null,
                channelStock: isset($sku['quantity']) ? (int) $sku['quantity'] : (isset($sku['Available']) ? (int) $sku['Available'] : null),
                currency: 'VND',
                image: $image,
                isActive: $isActive,
                raw: ['product' => array_diff_key($product, ['skus' => true]), 'sku' => $sku],
            );
        }

        return $out;
    }

    /**
     * @param  array<string,mixed>  $a  address_shipping object
     * @return array<string,string>
     */
    private static function address(array $a, string $note = ''): array
    {
        return array_filter([
            'fullName' => trim((string) ($a['first_name'] ?? '').' '.(string) ($a['last_name'] ?? '')) ?: null,
            'phone' => (string) ($a['phone'] ?? $a['phone2'] ?? ''),
            'line1' => trim((string) ($a['address1'] ?? '').' '.(string) ($a['address2'] ?? '')) ?: ((string) ($a['address1'] ?? '')),
            'ward' => ($w = $a['address3'] ?? $a['address5'] ?? null) ? (string) $w : null,
            'district' => ($d = $a['address4'] ?? null) ? (string) $d : null,
            'province' => ($c = $a['city'] ?? null) ? (string) $c : null,
            'country' => (string) ($a['country'] ?? 'Vietnam'),
            'zip' => ($z = $a['post_code'] ?? null) ? (string) $z : null,
            'note' => $note !== '' ? $note : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** Trimmed string, or null when empty/missing. */
    private static function str(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    public static function time(mixed $v): ?CarbonImmutable
    {
        if ($v === null || $v === '') {
            return null;
        }
        try {
            return CarbonImmutable::parse((string) $v);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Gom toàn bộ rows từ `/finance/transaction/details/get` trong khoảng `[from, to]` thành **một** SettlementDTO.
     * Lazada row sample: `{ transaction_type, fee_name, amount, lazada_id, seller_sku, order_no, transaction_date,
     * statement, paid_status, currency, ... }`. Mapping `transaction_type|fee_name → SettlementLineDTO::TYPES`.
     *
     * @param  list<array<string,mixed>>  $rows
     */
    public static function settlement(array $rows, CarbonImmutable $from, CarbonImmutable $to): SettlementDTO
    {
        $lines = [];
        $totalRev = 0;
        $totalFee = 0;
        $totalShip = 0;
        $payout = 0;
        $currency = 'VND';
        $statementId = null;
        foreach ($rows as $r) {
            $type = strtolower((string) (data_get($r, 'transaction_type') ?? data_get($r, 'fee_name') ?? 'other'));
            $feeType = self::mapLazadaFeeType($type);
            $amount = self::money(data_get($r, 'amount'));
            $occurred = self::time(data_get($r, 'transaction_date') ?? data_get($r, 'payment_time'));
            $orderId = data_get($r, 'order_no') ?? data_get($r, 'order_id');
            $currency = (string) (data_get($r, 'currency') ?: $currency);
            $statementId = $statementId ?: ((string) (data_get($r, 'statement') ?? '') ?: null);
            $lines[] = new SettlementLineDTO(
                feeType: $feeType,
                amount: $amount,
                externalOrderId: $orderId ? (string) $orderId : null,
                externalLineId: ($txid = data_get($r, 'lazada_id') ?? data_get($r, 'id')) !== null ? (string) $txid : null,
                occurredAt: $occurred,
                description: data_get($r, 'fee_name') ? (string) data_get($r, 'fee_name') : null,
                raw: $r,
            );
            $payout += $amount;
            match ($feeType) {
                SettlementLineDTO::TYPE_REVENUE => $totalRev += $amount,
                SettlementLineDTO::TYPE_SHIPPING_FEE,
                SettlementLineDTO::TYPE_SHIPPING_SUBSIDY => $totalShip += $amount,
                SettlementLineDTO::TYPE_COMMISSION,
                SettlementLineDTO::TYPE_PAYMENT_FEE,
                SettlementLineDTO::TYPE_VOUCHER_SELLER => $totalFee += $amount,
                default => null,
            };
        }

        return new SettlementDTO(
            externalId: $statementId,
            periodStart: $from, periodEnd: $to,
            totalPayout: $payout, totalRevenue: $totalRev,
            totalFee: $totalFee, totalShippingFee: $totalShip,
            currency: $currency,
            lines: $lines,
            paidAt: null,
            raw: ['rows_count' => count($rows)],
        );
    }

    /** Quy đổi Lazada transaction_type/fee_name về `SettlementLineDTO::TYPES`. */
    private static function mapLazadaFeeType(string $rawType): string
    {
        $t = str_replace([' ', '-'], '_', strtolower($rawType));

        return match (true) {
            str_contains($t, 'commission') => SettlementLineDTO::TYPE_COMMISSION,
            str_contains($t, 'payment') && str_contains($t, 'fee') => SettlementLineDTO::TYPE_PAYMENT_FEE,
            str_contains($t, 'shipping') && (str_contains($t, 'sponsor') || str_contains($t, 'subsidy')) => SettlementLineDTO::TYPE_SHIPPING_SUBSIDY,
            str_contains($t, 'shipping') || str_contains($t, 'delivery_fee') => SettlementLineDTO::TYPE_SHIPPING_FEE,
            str_contains($t, 'voucher') && str_contains($t, 'seller') => SettlementLineDTO::TYPE_VOUCHER_SELLER,
            str_contains($t, 'voucher') || str_contains($t, 'sponsor') => SettlementLineDTO::TYPE_VOUCHER_PLATFORM,
            str_contains($t, 'refund') || str_contains($t, 'return') => SettlementLineDTO::TYPE_REFUND,
            str_contains($t, 'adjust') => SettlementLineDTO::TYPE_ADJUSTMENT,
            str_contains($t, 'item_price') || str_contains($t, 'order') || str_contains($t, 'sale') || str_contains($t, 'price') => SettlementLineDTO::TYPE_REVENUE,
            default => SettlementLineDTO::TYPE_OTHER,
        };
    }

    /** Lazada money (string/float "200000.00") -> integer VND đồng. */
    public static function money(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_int($value)) {
            return $value;
        }
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value) ?? '0';
        if ($clean === '' || $clean === '-' || $clean === '.') {
            return 0;
        }

        return (int) round((float) $clean);
    }
}
