<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\OrderItemDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ReturnDTO;
use CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;

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
            $originalVal = $price['original_price'] ?? $priceVal;
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
                originalPrice: $originalVal !== null ? self::money($originalVal) : null,
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
        // `shipping_fee_platform_discount` là tiền sàn trợ giá phí ship cho KHÁCH (đã phản ánh ở
        // `shippingFee` — phí ship cuối cùng thu được), KHÔNG phải tiền hoàn về cho shop. Gộp vào
        // `platformDiscount` sẽ bị `OrderProfitService` cộng khống vào doanh thu ⇒ lãi ảo (bug 2026-07-16:
        // đơn 202.500đ báo lãi 212.650đ — cao hơn cả giá bán).
        $platformDiscount = $m('platform_discount');
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
     * Đối chiếu tài liệu chính thức + SDK (`tailieuapi_itiktok_shopee_lazada/tiktok/finance/`,
     * `sdk_tiktok_seller/model/finance/`). Hỗ trợ CẢ HAI schema statement_transactions:
     *  - **202309** (phẳng): mỗi transaction là 1 đơn với các trường `*_amount` riêng
     *    (`revenue_amount, fee_amount, shipping_fee_amount, adjustment_amount, settlement_amount`).
     *  - **202501** (breakdown): top-level transaction có `revenue_amount, fee_tax_amount,
     *    shipping_cost_amount, adjustment_amount, settlement_amount, type, order_id/adjustment_id`.
     * Mỗi transaction được tách thành NHIỀU {@see SettlementLineDTO} theo từng cấu phần (doanh thu /
     * phí&thuế / ship / điều chỉnh) để `SettlementService::aggregateFeesForOrders` cộng đúng nhóm.
     *
     * Statement fields (202309 list): `id, statement_time, settlement_amount, currency,
     * revenue_amount, fee_amount, shipping_cost_amount, adjustment_amount, payment_status, payment_id, payment_time`.
     *
     * @param  array<string,mixed>  $st
     * @param  list<array<string,mixed>>  $transactions  raw từ `statement_transactions`
     */
    public static function settlement(array $st, array $transactions = []): \CMBcoreSeller\Integrations\Channels\DTO\SettlementDTO
    {
        // Statement 202309 chỉ có `statement_time` (mốc đơn) — đại diện 1 ngày. 202501 by-statement có `create_time`.
        $startTs = (int) (data_get($st, 'statement_start_time') ?? data_get($st, 'period_start') ?? data_get($st, 'create_time') ?? data_get($st, 'statement_time') ?? 0);
        $endTs = (int) (data_get($st, 'statement_end_time') ?? data_get($st, 'period_end') ?? data_get($st, 'create_time') ?? data_get($st, 'statement_time') ?? 0);
        $periodStart = $startTs > 0 ? CarbonImmutable::createFromTimestamp($startTs) : CarbonImmutable::now();
        $periodEnd = $endTs > 0 ? CarbonImmutable::createFromTimestamp($endTs) : $periodStart;
        $paidAt = self::tsOrNull(data_get($st, 'paid_time') ?? data_get($st, 'payment_time') ?? data_get($st, 'settlement_time'));

        // Tách mỗi transaction → các dòng cấu phần, đồng thời cộng tổng theo nhóm.
        $lines = [];
        $totalRev = 0;
        $totalFee = 0;
        $totalShip = 0;
        foreach ($transactions as $tx) {
            foreach (self::settlementLines((array) $tx) as $line) {
                $lines[] = $line;
                match ($line->feeType) {
                    SettlementLineDTO::TYPE_REVENUE => $totalRev += $line->amount,
                    SettlementLineDTO::TYPE_SHIPPING_FEE,
                    SettlementLineDTO::TYPE_SHIPPING_SUBSIDY => $totalShip += $line->amount,
                    SettlementLineDTO::TYPE_COMMISSION,
                    SettlementLineDTO::TYPE_PAYMENT_FEE,
                    SettlementLineDTO::TYPE_VOUCHER_SELLER => $totalFee += $line->amount,
                    default => null,
                };
            }
        }

        return new \CMBcoreSeller\Integrations\Channels\DTO\SettlementDTO(
            externalId: ($id = data_get($st, 'id') ?? data_get($st, 'statement_id')) !== null ? (string) $id : null,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            totalPayout: self::money(data_get($st, 'settlement_amount') ?? data_get($st, 'total_settlement_amount') ?? data_get($st, 'payable_amount') ?? data_get($st, 'net_sales_amount') ?? data_get($st, 'payout_amount')),
            totalRevenue: $totalRev,
            totalFee: $totalFee,
            totalShippingFee: $totalShip,
            currency: (string) (data_get($st, 'currency') ?: 'VND'),
            lines: $lines,
            paidAt: $paidAt,
            raw: $st,
        );
    }

    /**
     * Tách 1 transaction (202309 phẳng / 202501 breakdown) → nhiều {@see SettlementLineDTO} theo cấu phần.
     * Dấu giữ nguyên theo TikTok (phí thường âm, doanh thu dương). Chỉ tạo dòng cho cấu phần khác 0.
     *
     * @param  array<string,mixed>  $tx
     * @return list<SettlementLineDTO>
     */
    private static function settlementLines(array $tx): array
    {
        $type = strtoupper((string) (data_get($tx, 'type') ?? data_get($tx, 'sub_type') ?? 'ORDER'));
        $orderId = ($o = data_get($tx, 'order_id') ?? data_get($tx, 'adjustment_order_id') ?? data_get($tx, 'associated_order_id')) !== null && (string) $o !== '' ? (string) $o : null;
        $txId = ($t = data_get($tx, 'id') ?? data_get($tx, 'transaction_id') ?? data_get($tx, 'adjustment_id') ?? data_get($tx, 'reserve_id')) !== null && (string) $t !== '' ? (string) $t : null;
        $occurred = self::tsOrNull(data_get($tx, 'order_create_time') ?? data_get($tx, 'statement_time') ?? data_get($tx, 'time') ?? data_get($tx, 'create_time') ?? data_get($tx, 'transaction_time'));
        $fallbackDesc = data_get($tx, 'description') ? (string) data_get($tx, 'description') : null;

        $mk = fn (string $feeType, mixed $amountRaw, ?string $label): SettlementLineDTO => new SettlementLineDTO(
            feeType: $feeType,
            amount: self::money($amountRaw),
            externalOrderId: $orderId,
            externalLineId: $txId,
            occurredAt: $occurred,
            description: $label ?? $fallbackDesc,
            raw: $tx,
        );

        // Điều chỉnh (không phải ORDER/RESERVE): 1 dòng theo loại (xem Phụ lục B của tài liệu).
        if ($type !== 'ORDER' && $type !== 'RESERVE') {
            $amt = data_get($tx, 'adjustment_amount') ?? data_get($tx, 'settlement_amount') ?? data_get($tx, 'amount');

            return self::money($amt) !== 0 ? [$mk(self::mapTikTokFeeType($type), $amt, 'adjustment:'.$type)] : [];
        }

        // Reserve (202501): giữ/release tiền — không phải phí, để TYPE_OTHER (gộp "fees" khi tổng hợp).
        if ($type === 'RESERVE') {
            $amt = data_get($tx, 'reserve_amount') ?? data_get($tx, 'amount');

            return self::money($amt) !== 0 ? [$mk(SettlementLineDTO::TYPE_OTHER, $amt, 'reserve:'.(string) (data_get($tx, 'reserve_status') ?? ''))] : [];
        }

        // Đơn (ORDER): tách doanh thu / phí&thuế / ship / điều chỉnh từ trường top-level (cả 202309 & 202501).
        $revenue = data_get($tx, 'revenue_amount');
        $fee = data_get($tx, 'fee_tax_amount') ?? data_get($tx, 'fee_and_tax_amount') ?? data_get($tx, 'fee_amount');
        $shipping = data_get($tx, 'shipping_cost_amount') ?? data_get($tx, 'shipping_fee_amount');
        $adjust = data_get($tx, 'adjustment_amount');
        $settlement = data_get($tx, 'settlement_amount');

        $out = [];
        if (self::money($revenue) !== 0) {
            $out[] = $mk(SettlementLineDTO::TYPE_REVENUE, $revenue, 'revenue');
        }
        if (self::money($fee) !== 0) {
            $out[] = $mk(SettlementLineDTO::TYPE_COMMISSION, $fee, 'fee_tax');
        }
        if (self::money($shipping) !== 0) {
            $out[] = $mk(SettlementLineDTO::TYPE_SHIPPING_FEE, $shipping, 'shipping');
        }
        if (self::money($adjust) !== 0) {
            $out[] = $mk(SettlementLineDTO::TYPE_ADJUSTMENT, $adjust, 'adjustment');
        }
        // Không tách được cấu phần nào nhưng có settlement_amount → 1 dòng tổng (fallback an toàn).
        if ($out === [] && self::money($settlement) !== 0) {
            $out[] = $mk(SettlementLineDTO::TYPE_OTHER, $settlement, 'settlement');
        }

        return $out;
    }

    /** Quy đổi sub_type/type của TikTok về `SettlementLineDTO::TYPES`. Mapping mở rộng được khi gặp sample sandbox. */
    private static function mapTikTokFeeType(string $rawType): string
    {
        $t = str_replace([' ', '-'], '_', strtolower($rawType));

        return match (true) {
            str_contains($t, 'commission') => SettlementLineDTO::TYPE_COMMISSION,
            str_contains($t, 'transaction_fee') || str_contains($t, 'payment_fee') => SettlementLineDTO::TYPE_PAYMENT_FEE,
            str_contains($t, 'ship') && str_contains($t, 'subsidy') => SettlementLineDTO::TYPE_SHIPPING_SUBSIDY,
            str_contains($t, 'ship') => SettlementLineDTO::TYPE_SHIPPING_FEE,
            str_contains($t, 'voucher') && str_contains($t, 'seller') => SettlementLineDTO::TYPE_VOUCHER_SELLER,
            str_contains($t, 'voucher') || str_contains($t, 'platform_subsidy') => SettlementLineDTO::TYPE_VOUCHER_PLATFORM,
            str_contains($t, 'refund') || str_contains($t, 'return') => SettlementLineDTO::TYPE_REFUND,
            str_contains($t, 'adjust') => SettlementLineDTO::TYPE_ADJUSTMENT,
            $t === 'order' || str_contains($t, 'sale') || str_contains($t, 'sku_amount') || str_contains($t, 'order_amount') => SettlementLineDTO::TYPE_REVENUE,
            default => SettlementLineDTO::TYPE_OTHER,
        };
    }

    /**
     * Map one TikTok after-sales record (return_orders[] hoặc cancellations[]) → {@see ReturnDTO}.
     *
     * @param  array<string,mixed>  $r
     * @param  string  $kind  ReturnDTO::KIND_RETURN | KIND_CANCEL
     */
    public static function returnRecord(array $r, string $kind): ReturnDTO
    {
        $isCancel = $kind === ReturnDTO::KIND_CANCEL;
        $idKey = $isCancel ? 'cancel_id' : 'return_id';
        $statusKey = $isCancel ? 'cancel_status' : 'return_status';
        $reasonKey = $isCancel ? 'cancel_reason_text' : 'return_reason_text';
        $reasonAlt = $isCancel ? 'cancel_reason' : 'return_reason';
        $itemsKey = $isCancel ? 'cancel_line_items' : 'return_line_items';

        $rawStatus = (string) data_get($r, $statusKey, '');
        $refundTotal = data_get($r, 'refund_amount.refund_total') ?? data_get($r, 'refund_amount');
        $ts = fn (string $k) => ($v = data_get($r, $k)) ? CarbonImmutable::createFromTimestamp((int) $v) : null;

        // return_type RETURN_AND_REFUND ⇒ return; REFUND ⇒ refund. Cancel luôn kind=cancel.
        $resolvedKind = $isCancel ? ReturnDTO::KIND_CANCEL
            : (strtoupper((string) data_get($r, 'return_type', '')) === 'REFUND' ? ReturnDTO::KIND_REFUND : ReturnDTO::KIND_RETURN);

        return new ReturnDTO(
            externalReturnId: (string) data_get($r, $idKey, ''),
            source: 'tiktok',
            kind: $resolvedKind,
            status: self::afterSalesStatus($rawStatus),
            rawStatus: $rawStatus,
            externalOrderId: ($o = data_get($r, 'order_id')) !== null ? (string) $o : null,
            reason: data_get($r, $reasonKey) ?: (data_get($r, $reasonAlt) ? (string) data_get($r, $reasonAlt) : null),
            refundAmount: self::money($refundTotal),
            currency: (string) (data_get($r, 'refund_amount.currency') ?: 'VND'),
            items: self::returnLineItems((array) data_get($r, $itemsKey, [])),
            requestedAt: $ts('create_time'),
            decidedAt: null,
            sourceUpdatedAt: $ts('update_time') ?? CarbonImmutable::now(),
            raw: $r,
        );
    }

    /**
     * `decision` BẮT BUỘC cho `POST /return_refund/{ver}/returns/{id}/{approve|reject}`
     * (TikTok 202309 — xem sdk_tiktok_seller .../V202309/ApproveReturnRequestBody &
     * RejectReturnRequestBody). Giá trị tuỳ `return_type` + `return_status` hiện tại:
     *  - `REFUND` (hoàn tiền, không trả hàng)  → APPROVE_REFUND / REJECT_REFUND
     *  - `REPLACEMENT` (đổi hàng)              → APPROVE_REPLACEMENT / REJECT_REPLACEMENT
     *  - `RETURN_AND_REFUND` (trả + hoàn):
     *      • khách CHƯA gửi hàng               → APPROVE_RETURN / REJECT_RETURN
     *      • khách ĐÃ gửi (`BUYER_SHIPPED_ITEM`) → APPROVE_RECEIVED_PACKAGE / REJECT_RECEIVED_PACKAGE
     *
     * Cancellation KHÔNG có field này (RejectCancellationRequestBody chỉ có comment/images/reject_reason).
     *
     * @param  string  $op  'approve' | 'reject'
     */
    public static function returnDecision(string $op, ?string $returnType, ?string $returnStatus): string
    {
        $approve = strtolower($op) !== 'reject';
        $type = strtoupper(trim((string) $returnType));
        $status = strtoupper(trim((string) $returnStatus));

        if ($type === 'REFUND') {
            return $approve ? 'APPROVE_REFUND' : 'REJECT_REFUND';
        }
        if ($type === 'REPLACEMENT') {
            return $approve ? 'APPROVE_REPLACEMENT' : 'REJECT_REPLACEMENT';
        }

        // RETURN_AND_REFUND (mặc định). Sau khi khách đã gửi hàng, thao tác là trên "gói đã nhận".
        if ($status === 'BUYER_SHIPPED_ITEM') {
            return $approve ? 'APPROVE_RECEIVED_PACKAGE' : 'REJECT_RECEIVED_PACKAGE';
        }

        return $approve ? 'APPROVE_RETURN' : 'REJECT_RETURN';
    }

    /** raw return/cancel status (TikTok) → canonical {@see AfterSalesStatus}. Config map + fallback chuỗi. */
    public static function afterSalesStatus(string $raw): AfterSalesStatus
    {
        $key = strtoupper(trim($raw));
        $map = (array) config('integrations.tiktok.return_status_map', []);
        if (isset($map[$key])) {
            return AfterSalesStatus::tryFrom((string) $map[$key]) ?? AfterSalesStatus::Requested;
        }

        return match (true) {
            str_contains($key, 'REJECT') => AfterSalesStatus::Rejected,
            str_contains($key, 'SUCCESS') || str_contains($key, 'COMPLETE') || str_contains($key, 'REFUNDED') => AfterSalesStatus::Completed,
            str_contains($key, 'CANCEL') => AfterSalesStatus::Cancelled,
            str_contains($key, 'SHIP') || str_contains($key, 'RETURNING') || str_contains($key, 'RECEIV') => AfterSalesStatus::Processing,
            str_contains($key, 'AGREE') || str_contains($key, 'APPROV') => AfterSalesStatus::Approved,
            default => AfterSalesStatus::Requested,
        };
    }

    /**
     * @param  list<mixed>  $rows
     * @return list<array<string,mixed>>
     */
    private static function returnLineItems(array $rows): array
    {
        $out = [];
        foreach ($rows as $li) {
            if (! is_array($li)) {
                continue;
            }
            $row = array_filter([
                'sku_id' => data_get($li, 'sku_id'),
                'seller_sku' => data_get($li, 'seller_sku'),
                'name' => data_get($li, 'product_name') ?: data_get($li, 'sku_name'),
                'quantity' => (int) (data_get($li, 'quantity') ?: 1),
            ], fn ($v) => $v !== null && $v !== '');
            if ($row !== []) {
                $out[] = $row;
            }
        }

        return $out;
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
