<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\OrderItemDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;

/**
 * Translates TikTok Shop Partner API (v202309) JSON into the standard DTOs.
 * The ONLY place TikTok field names appear. Defensive about missing fields
 * (the API evolves; DTOs carry `raw` for anything not modelled). Money is
 * parsed to bigint VND đồng — TikTok returns money as strings; VND has no
 * subunits. See docs/04-channels/README.md §2, docs/04-channels/tiktok-shop.md.
 */
final class TikTokMappers
{
    /** @param array<string,mixed> $data token data from /api/v2/token/get|refresh */
    public static function token(array $data): TokenDTO
    {
        return new TokenDTO(
            accessToken: (string) ($data['access_token'] ?? ''),
            refreshToken: ($rt = $data['refresh_token'] ?? null) !== null && $rt !== '' ? (string) $rt : null,
            // *_expire_in here is a Unix timestamp (the expiry instant), not seconds-until.
            expiresAt: self::tsOrNull($data['access_token_expire_in'] ?? null),
            refreshExpiresAt: self::tsOrNull($data['refresh_token_expire_in'] ?? null),
            // TikTok returns granted_scopes as a list (e.g. ["seller.shop","seller.order"]); the
            // standard DTO's scope is a ?string — join it. (Some responses send a comma-string.)
            scope: self::scopeString($data['granted_scopes'] ?? $data['scope'] ?? null),
            raw: $data,
        );
    }

    private static function tsOrNull(mixed $value): ?CarbonImmutable
    {
        return $value !== null && $value !== '' && (int) $value > 0
            ? CarbonImmutable::createFromTimestamp((int) $value)
            : null;
    }

    /** @return non-empty-string|null */
    private static function scopeString(mixed $scopes): ?string
    {
        if (is_array($scopes)) {
            $joined = implode(',', array_filter(array_map(
                fn ($s) => is_array($s) ? (string) ($s['scope'] ?? $s['name'] ?? '') : (string) $s,
                $scopes,
            ), fn ($s) => $s !== ''));

            return $joined !== '' ? $joined : null;
        }

        return $scopes !== null && $scopes !== '' ? (string) $scopes : null;
    }

    /** @param array<string,mixed> $shop one element of /authorization/202309/shops -> data.shops[] */
    public static function shopInfo(array $shop): ShopInfoDTO
    {
        return new ShopInfoDTO(
            externalShopId: (string) ($shop['id'] ?? ''),
            name: (string) ($shop['name'] ?? ''),
            region: (string) ($shop['region'] ?? 'VN'),
            sellerType: $shop['seller_type'] ?? null,
            raw: $shop,
        );
    }

    /**
     * Flatten one TikTok product (which holds nested `skus`) into one
     * {@see ChannelListingDTO} per SKU.
     *
     * @param  array<string,mixed>  $product  one element of products[] from /product/202309/products/search
     * @return list<ChannelListingDTO>
     */
    public static function listings(array $product): array
    {
        $productId = (string) ($product['id'] ?? '');
        $title = isset($product['title']) ? (string) $product['title'] : null;
        $status = strtoupper((string) ($product['status'] ?? ''));
        $isActive = ! in_array($status, ['DEACTIVATED', 'DELETED', 'SUSPENDED'], true);
        $image = null;
        foreach ((array) ($product['main_images'] ?? $product['images'] ?? []) as $img) {
            $urls = (array) ($img['thumb_urls'] ?? $img['urls'] ?? []);
            if ($urls !== []) {
                $image = (string) reset($urls);
                break;
            }
        }

        $out = [];
        foreach ((array) ($product['skus'] ?? []) as $sku) {
            $skuId = (string) ($sku['id'] ?? '');
            if ($skuId === '') {
                continue;
            }
            $price = $sku['price'] ?? [];
            $currency = (string) ($price['currency'] ?? 'VND');
            $priceVal = $price['sale_price'] ?? $price['tax_exclusive_price'] ?? $price['original_price'] ?? null;
            $stock = null;
            foreach ((array) ($sku['inventory'] ?? []) as $inv) {
                $stock = (int) ($stock ?? 0) + (int) ($inv['quantity'] ?? 0);
            }
            $variation = collect((array) ($sku['sales_attributes'] ?? []))
                ->map(fn ($a) => trim((string) ($a['name'] ?? '')).': '.trim((string) ($a['value_name'] ?? $a['value'] ?? '')))
                ->filter(fn ($s) => $s !== ': ' && trim($s) !== ':')->implode(', ') ?: null;

            $out[] = new ChannelListingDTO(
                externalSkuId: $skuId,
                externalProductId: $productId ?: null,
                sellerSku: isset($sku['seller_sku']) ? (string) $sku['seller_sku'] : null,
                title: $title,
                variation: $variation,
                price: $priceVal !== null ? self::money($priceVal) : null,
                channelStock: $stock,
                currency: $currency ?: 'VND',
                image: $image,
                isActive: $isActive,
                raw: ['product' => array_diff_key($product, ['skus' => true]), 'sku' => $sku],
            );
        }

        return $out;
    }

    /** @param array<string,mixed> $o one element of orders[] from /order/202309/orders or /orders/search */
    public static function order(array $o): OrderDTO
    {
        $payment = (array) data_get($o, 'payment', []);
        $currency = strtoupper((string) data_get($o, 'currency', $payment['currency'] ?? 'VND'));
        $m = fn (string $key) => self::money($payment[$key] ?? null);

        $itemTotal = $m('sub_total');
        $shippingFee = $m('shipping_fee');
        $sellerDiscount = $m('seller_discount') + $m('shipping_fee_seller_discount');
        $platformDiscount = $m('platform_discount') + $m('shipping_fee_platform_discount');
        $tax = $m('tax');
        $grandTotal = $m('total_amount');
        if ($grandTotal === 0) {
            $grandTotal = max(0, $itemTotal + $shippingFee + $tax - $sellerDiscount - $platformDiscount);
        }

        $paymentMethod = (string) data_get($o, 'payment_method_name', '');
        $isCod = (bool) data_get($o, 'is_cod', false) || str_contains(strtolower($paymentMethod), 'cod');

        $ts = fn (string $key) => ($v = data_get($o, $key)) ? CarbonImmutable::createFromTimestamp((int) $v) : null;

        $rawStatus = (string) data_get($o, 'status', '');
        $items = self::lineItems((array) data_get($o, 'line_items', []));
        $packages = array_values(array_map(
            fn ($p) => [
                'externalPackageId' => (string) data_get($p, 'id', ''),
                'trackingNo' => data_get($p, 'tracking_number') ?: data_get($o, 'tracking_number'),
                'carrier' => data_get($o, 'shipping_provider'),
                'status' => data_get($p, 'status'),
            ],
            (array) data_get($o, 'packages', []),
        ));

        return new OrderDTO(
            externalOrderId: (string) data_get($o, 'id', ''),
            source: 'tiktok',
            rawStatus: $rawStatus,
            sourceUpdatedAt: $ts('update_time') ?? CarbonImmutable::now(),
            orderNumber: (string) data_get($o, 'id', ''),  // TikTok exposes the same id as the order number
            paymentStatus: $ts('paid_time') ? 'paid' : ($rawStatus === 'UNPAID' ? 'unpaid' : null),
            placedAt: $ts('create_time'),
            paidAt: $ts('paid_time'),
            shippedAt: $ts('collection_time') ?? $ts('rts_time'),
            deliveredAt: $ts('delivery_time'),
            completedAt: $rawStatus === 'COMPLETED' ? ($ts('update_time')) : null,
            cancelledAt: $ts('cancel_time'),
            cancelReason: data_get($o, 'cancel_reason'),
            buyer: array_filter([
                'name' => data_get($o, 'recipient_address.name') ?: trim((string) data_get($o, 'recipient_address.first_name', '').' '.(string) data_get($o, 'recipient_address.last_name', '')),
                'email' => data_get($o, 'buyer_email'),
            ], fn ($v) => $v !== null && $v !== ''),
            shippingAddress: self::address((array) data_get($o, 'recipient_address', []), (string) data_get($o, 'buyer_message', '')),
            currency: $currency ?: 'VND',
            itemTotal: $itemTotal,
            shippingFee: $shippingFee,
            platformDiscount: $platformDiscount,
            sellerDiscount: $sellerDiscount,
            tax: $tax,
            codAmount: $isCod ? $grandTotal : 0,
            grandTotal: $grandTotal,
            isCod: $isCod,
            fulfillmentType: data_get($o, 'fulfillment_type'),
            items: $items,
            packages: $packages,
            raw: $o,
        );
    }

    /**
     * TikTok line_items are one row per unit; group by SKU to get quantity.
     *
     * @param  list<array<string,mixed>>  $rawItems
     * @return list<OrderItemDTO>
     */
    private static function lineItems(array $rawItems): array
    {
        /** @var array<string, array{rows: list<array<string,mixed>>}> $groups */
        $groups = [];
        foreach ($rawItems as $li) {
            $key = (string) (data_get($li, 'sku_id') ?: data_get($li, 'product_id') ?: data_get($li, 'seller_sku') ?: data_get($li, 'id') ?: spl_object_hash((object) $li));
            $groups[$key]['rows'][] = $li;
        }

        $out = [];
        foreach ($groups as $key => $g) {
            $first = $g['rows'][0];
            $qty = 0;
            $discount = 0;
            foreach ($g['rows'] as $r) {
                $qty += (int) (data_get($r, 'quantity') ?: 1);
                $discount += self::money(data_get($r, 'seller_discount')) + self::money(data_get($r, 'platform_discount'));
            }
            $unitPrice = self::money(data_get($first, 'sale_price') ?: data_get($first, 'original_price'));
            $out[] = new OrderItemDTO(
                externalItemId: $key,
                externalProductId: data_get($first, 'product_id'),
                externalSkuId: data_get($first, 'sku_id'),
                sellerSku: data_get($first, 'seller_sku'),
                name: (string) (data_get($first, 'product_name') ?: data_get($first, 'sku_name') ?: 'Sản phẩm'),
                variation: data_get($first, 'sku_name'),
                quantity: max(1, $qty),
                unitPrice: $unitPrice,
                discount: $discount,
                image: data_get($first, 'sku_image') ?: data_get($first, 'product_image'),
                raw: ['line_items' => $g['rows']],
            );
        }

        return $out;
    }

    /**
     * @param  array<string,mixed>  $a  recipient_address object
     * @return array<string,string>
     */
    private static function address(array $a, string $note = ''): array
    {
        $byLevel = [];
        foreach ((array) data_get($a, 'district_info', []) as $d) {
            $byLevel[strtoupper((string) data_get($d, 'address_level', ''))] = (string) data_get($d, 'address_name', '');
        }
        $line1 = (string) (data_get($a, 'address_detail') ?: data_get($a, 'address_line1') ?: data_get($a, 'full_address', ''));

        return array_filter([
            'fullName' => (string) (data_get($a, 'name') ?: trim((string) data_get($a, 'first_name', '').' '.(string) data_get($a, 'last_name', ''))),
            'phone' => (string) data_get($a, 'phone_number', ''),
            'line1' => $line1,
            'ward' => $byLevel['L3'] ?? null,
            'district' => $byLevel['L2'] ?? null,
            'province' => $byLevel['L1'] ?? ($byLevel['L2'] ?? null),
            'country' => (string) (data_get($a, 'region_code') ?: 'VN'),
            'zip' => data_get($a, 'postal_code'),
            'note' => $note ?: null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Build a {@see SettlementDTO} from a TikTok statement row + its transactions.
     *
     * SDK ref: `sdk_tiktok_seller/api/financeV202309Api.ts` (`statements`, `statement_transactions`).
     * Statement fields: `id, statement_time, settlement_amount, payment_status, currency`.
     * Transaction line fields (đối chiếu sandbox): `order_id, type, sub_type, amount` (string), `time/create_time`.
     *
     * @param  array<string,mixed>  $st
     * @param  list<array<string,mixed>>  $transactions  raw từ `statement_transactions`
     */
    public static function settlement(array $st, array $transactions = []): \CMBcoreSeller\Integrations\Channels\DTO\SettlementDTO
    {
        $startTs = (int) (data_get($st, 'statement_start_time') ?? data_get($st, 'period_start') ?? data_get($st, 'statement_time') ?? 0);
        $endTs = (int) (data_get($st, 'statement_end_time') ?? data_get($st, 'period_end') ?? data_get($st, 'statement_time') ?? 0);
        $periodStart = $startTs > 0 ? CarbonImmutable::createFromTimestamp($startTs) : CarbonImmutable::now();
        $periodEnd = $endTs > 0 ? CarbonImmutable::createFromTimestamp($endTs) : $periodStart;
        $paidAt = self::tsOrNull(data_get($st, 'paid_time') ?? data_get($st, 'settlement_time'));

        // map transactions → SettlementLineDTO
        $lines = [];
        $totalRev = 0;
        $totalFee = 0;
        $totalShip = 0;
        foreach ($transactions as $tx) {
            $rawType = strtolower((string) (data_get($tx, 'sub_type') ?? data_get($tx, 'type') ?? 'other'));
            $feeType = self::mapTikTokFeeType($rawType);
            $amount = self::money(data_get($tx, 'amount'));
            // Convention: phía seller — phí "âm", doanh thu "dương". TikTok có thể trả sẵn dấu — giữ nguyên.
            $occurred = self::tsOrNull(data_get($tx, 'time') ?? data_get($tx, 'create_time') ?? data_get($tx, 'transaction_time'));
            $lines[] = new \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO(
                feeType: $feeType,
                amount: $amount,
                externalOrderId: ($oid = data_get($tx, 'order_id')) !== null ? (string) $oid : null,
                externalLineId: ($txid = data_get($tx, 'id') ?? data_get($tx, 'transaction_id')) !== null ? (string) $txid : null,
                occurredAt: $occurred,
                description: data_get($tx, 'description') ? (string) data_get($tx, 'description') : null,
                raw: $tx,
            );
            match ($feeType) {
                \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_REVENUE => $totalRev += $amount,
                \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_SHIPPING_FEE,
                \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_SHIPPING_SUBSIDY => $totalShip += $amount,
                \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_COMMISSION,
                \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_PAYMENT_FEE,
                \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_VOUCHER_SELLER => $totalFee += $amount,
                default => null,
            };
        }

        return new \CMBcoreSeller\Integrations\Channels\DTO\SettlementDTO(
            externalId: ($id = data_get($st, 'id') ?? data_get($st, 'statement_id')) !== null ? (string) $id : null,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            totalPayout: self::money(data_get($st, 'settlement_amount') ?? data_get($st, 'net_sales_amount') ?? data_get($st, 'payout_amount')),
            totalRevenue: $totalRev,
            totalFee: $totalFee,
            totalShippingFee: $totalShip,
            currency: (string) (data_get($st, 'currency') ?: 'VND'),
            lines: $lines,
            paidAt: $paidAt,
            raw: $st,
        );
    }

    /** Quy đổi sub_type/type của TikTok về `SettlementLineDTO::TYPES`. Mapping mở rộng được khi gặp sample sandbox. */
    private static function mapTikTokFeeType(string $rawType): string
    {
        $t = str_replace([' ', '-'], '_', strtolower($rawType));

        return match (true) {
            str_contains($t, 'commission') => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_COMMISSION,
            str_contains($t, 'transaction_fee') || str_contains($t, 'payment_fee') => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_PAYMENT_FEE,
            str_contains($t, 'ship') && str_contains($t, 'subsidy') => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_SHIPPING_SUBSIDY,
            str_contains($t, 'ship') => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_SHIPPING_FEE,
            str_contains($t, 'voucher') && str_contains($t, 'seller') => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_VOUCHER_SELLER,
            str_contains($t, 'voucher') || str_contains($t, 'platform_subsidy') => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_VOUCHER_PLATFORM,
            str_contains($t, 'refund') || str_contains($t, 'return') => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_REFUND,
            str_contains($t, 'adjust') => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_ADJUSTMENT,
            $t === 'order' || str_contains($t, 'sale') || str_contains($t, 'sku_amount') || str_contains($t, 'order_amount') => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_REVENUE,
            default => \CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO::TYPE_OTHER,
        };
    }

    /** TikTok money strings -> integer VND đồng. Defensive against currency symbols / thousands separators. */
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
