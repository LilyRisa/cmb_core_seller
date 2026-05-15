<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Creates / edits / cancels manually-entered orders. A manual order is just an
 * order with `source = 'manual'`, so the same pipeline (inventory effects,
 * customer linking — both via the OrderUpserted event) applies. Stock validity
 * of `sku_id`s is checked by the Inventory module when it handles the event;
 * here we only do shape validation. See SPEC 0003 §6, docs/03-domain/manual-orders-and-finance.md §1.
 */
class ManualOrderService
{
    private const PRE_SHIPMENT_CHOICES = [StandardOrderStatus::Pending, StandardOrderStatus::Processing];

    /** @param array<string,mixed> $data */
    public function create(int $tenantId, ?int $userId, array $data): Order
    {
        $items = $this->normalizeItems($data['items'] ?? []);
        $status = $this->chosenStatus($data['status'] ?? null);
        $buyer = (array) ($data['buyer'] ?? []);
        $recipient = (array) ($data['recipient'] ?? []);
        // SPEC 0021 (UI taodon.png): miễn phí giao hàng, giảm giá đơn, tiền chuyển khoản, phụ thu.
        $freeShipping = (bool) ($data['free_shipping'] ?? false);
        $shippingFee = $freeShipping ? 0 : max(0, (int) ($data['shipping_fee'] ?? 0));
        $tax = max(0, (int) ($data['tax'] ?? 0));
        $orderDiscount = max(0, (int) ($data['order_discount'] ?? 0));     // "Giảm giá đơn hàng"
        $prepaidAmount = max(0, (int) ($data['prepaid_amount'] ?? 0));     // Đã trả trước (CK)
        $surcharge = max(0, (int) ($data['surcharge'] ?? 0));              // Phụ thu
        $isCod = (bool) ($data['is_cod'] ?? false);
        $itemTotal = array_sum(array_map(fn ($i) => $i['unit_price'] * $i['quantity'] - $i['discount'], $items));
        $sellerDiscount = array_sum(array_map(fn ($i) => $i['discount'], $items)) + $orderDiscount;
        // grand_total = (item_total + ship + tax + surcharge) − order_discount.
        $grandTotal = max(0, $itemTotal + $shippingFee + $tax + $surcharge - $orderDiscount);
        // B1 fix (Sprint 1 P0): COD amount = số tiền cần thu hộ qua ĐVVC = grand_total − prepaid (clamp ≥ 0).
        // FE chỉ truyền `cod_amount` khi muốn override (hiếm). Trước đây BE trừ prepaid LẦN NỮA sau khi FE
        // đã trừ ⇒ COD ghi vào DB thiếu = (FE_needCollect − prepaid). Giờ BE TỰ tính chuẩn từ raw inputs.
        $codAmount = $isCod ? max(0, ((int) ($data['cod_amount'] ?? ($grandTotal - $prepaidAmount)))) : 0;
        // B5 fix (Sprint 1 P0): chỉ ghi 'paid' khi prepaid đủ phủ toàn bộ grand_total. Nếu trả 1 phần ⇒ 'partial'
        // (thêm vào enum/notes, tạm dùng 'unpaid' với grand_total > prepaid > 0; chuẩn hoá khi có enum).
        $paymentStatus = match (true) {
            $isCod => 'cod',
            $prepaidAmount >= $grandTotal && $grandTotal > 0 => 'paid',
            $prepaidAmount > 0 => 'partial',
            default => 'unpaid',
        };
        $now = now();

        $order = DB::transaction(function () use ($tenantId, $userId, $items, $status, $buyer, $recipient, $shippingFee, $tax, $isCod, $itemTotal, $sellerDiscount, $grandTotal, $codAmount, $prepaidAmount, $surcharge, $freeShipping, $paymentStatus, $now, $data) {
            $order = Order::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $tenantId,
                'source' => 'manual',
                'channel_account_id' => null,
                'external_order_id' => null,
                'order_number' => $this->generateOrderNumber($tenantId),
                'status' => $status,
                'raw_status' => $status->value,
                'payment_status' => $paymentStatus,
                'buyer_name' => $buyer['name'] ?? null,
                'buyer_phone' => $buyer['phone'] ?? null,
                // shipping_address ưu tiên `recipient` (FE mới); fallback `buyer` (legacy / shape cũ).
                'shipping_address' => $this->buildShippingAddress($buyer, $recipient),
                'currency' => 'VND',
                'item_total' => $itemTotal,
                'shipping_fee' => $shippingFee,
                'platform_discount' => 0,
                'seller_discount' => $sellerDiscount,
                'tax' => $tax,
                'cod_amount' => $codAmount,
                'prepaid_amount' => $prepaidAmount,
                'surcharge' => $surcharge,
                'grand_total' => $grandTotal,
                'is_cod' => $isCod,
                'fulfillment_type' => 'manual',
                'placed_at' => $now,
                'paid_at' => $prepaidAmount > 0 ? $now : null,
                'note' => $data['note'] ?? null,
                'tags' => array_values(array_filter((array) ($data['tags'] ?? []))),
                'has_issue' => false,
                'packages' => [],
                'meta' => $this->normalizeMeta((array) ($data['meta'] ?? []), $freeShipping, $userId, (string) ($data['sub_source'] ?? '')),
                'source_updated_at' => $now,
            ]);

            foreach ($items as $i => $item) {
                OrderItem::withoutGlobalScope(TenantScope::class)->create([
                    'tenant_id' => $tenantId,
                    'order_id' => $order->getKey(),
                    'external_item_id' => 'M'.($i + 1),
                    'sku_id' => $item['sku_id'],
                    'seller_sku' => $item['sku_code'] ?? null,
                    'name' => $item['name'],
                    'variation' => $item['variation'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'],
                    'subtotal' => $item['unit_price'] * $item['quantity'] - $item['discount'],
                    'image' => $item['image'] ?? null,
                ]);
            }

            OrderStatusHistory::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $tenantId, 'order_id' => $order->getKey(), 'from_status' => null,
                'to_status' => $status->value, 'raw_status' => $status->value, 'source' => OrderStatusHistory::SOURCE_USER,
                'changed_at' => $now, 'payload' => ['created_by' => $userId], 'created_at' => $now,
            ]);

            return $order;
        });

        OrderUpserted::dispatch($order, true);

        return $order;
    }

    /** Cancel a manual order that hasn't shipped yet → releases stock via the event. */
    public function cancel(Order $order, ?int $userId, ?string $reason = null): Order
    {
        $this->assertManualEditable($order);
        $from = $order->status;
        if ($from === StandardOrderStatus::Cancelled) {
            return $order;
        }
        $now = now();
        $order->forceFill(['status' => StandardOrderStatus::Cancelled, 'raw_status' => 'cancelled', 'cancel_reason' => $reason, 'cancelled_at' => $now, 'source_updated_at' => $now])->save();
        OrderStatusHistory::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $order->tenant_id, 'order_id' => $order->getKey(), 'from_status' => $from->value, 'to_status' => 'cancelled',
            'raw_status' => 'cancelled', 'source' => OrderStatusHistory::SOURCE_USER, 'changed_at' => $now, 'payload' => ['cancelled_by' => $userId, 'reason' => $reason], 'created_at' => $now,
        ]);
        OrderUpserted::dispatch($order, false);

        return $order;
    }

    /** Edit buyer / shipping fee / cod / note / tags of a manual order that hasn't shipped. (Editing line items: Phase 5.) */
    public function update(Order $order, array $data): Order
    {
        $this->assertManualEditable($order);
        $buyer = (array) ($data['buyer'] ?? []);
        $fill = [];
        if (array_key_exists('shipping_fee', $data)) {
            $fill['shipping_fee'] = (int) $data['shipping_fee'];
        }
        if (array_key_exists('tax', $data)) {
            $fill['tax'] = (int) $data['tax'];
        }
        if (array_key_exists('is_cod', $data)) {
            $fill['is_cod'] = (bool) $data['is_cod'];
        }
        if (array_key_exists('cod_amount', $data)) {
            $fill['cod_amount'] = (int) $data['cod_amount'];
        }
        if (array_key_exists('note', $data)) {
            $fill['note'] = $data['note'] ?: null;
        }
        if (array_key_exists('tags', $data)) {
            $fill['tags'] = array_values(array_filter((array) $data['tags']));
        }
        if ($buyer !== []) {
            $addr = $order->shipping_address ?? [];
            foreach (['name' => 'name', 'phone' => 'phone', 'address' => 'address', 'ward' => 'ward', 'district' => 'district', 'province' => 'province'] as $src => $dst) {
                if (array_key_exists($src, $buyer)) {
                    $addr[$dst] = $buyer[$src];
                }
            }
            $fill['shipping_address'] = array_filter($addr, fn ($v) => $v !== null && $v !== '');
            if (array_key_exists('name', $buyer)) {
                $fill['buyer_name'] = $buyer['name'] ?: null;
            }
            if (array_key_exists('phone', $buyer)) {
                $fill['buyer_phone'] = $buyer['phone'] ?: null;
            }
        }
        if (isset($fill['shipping_fee']) || isset($fill['tax'])) {
            $fill['grand_total'] = (int) $order->item_total + ($fill['shipping_fee'] ?? $order->shipping_fee) + ($fill['tax'] ?? $order->tax);
        }
        $fill['source_updated_at'] = now();
        $order->forceFill($fill)->save();
        OrderUpserted::dispatch($order, false);

        return $order;
    }

    // --- helpers --------------------------------------------------------------

    private function assertManualEditable(Order $order): void
    {
        if ($order->source !== 'manual') {
            throw ValidationException::withMessages(['order' => 'Chỉ sửa được đơn thủ công.']);
        }
        $status = $order->status;
        if (! $status->isPreShipment() && $status !== StandardOrderStatus::Cancelled) {
            throw ValidationException::withMessages(['order' => 'Không sửa/huỷ được đơn đã bàn giao vận chuyển.']);
        }
    }

    private function chosenStatus(?string $value): StandardOrderStatus
    {
        $s = $value ? StandardOrderStatus::tryFrom($value) : null;

        return ($s !== null && in_array($s, self::PRE_SHIPMENT_CHOICES, true)) ? $s : StandardOrderStatus::Processing;
    }

    /**
     * Build `shipping_address` array — ưu tiên trường `recipient` từ FE mới (UI taodon.png), fallback
     * shape cũ ở `buyer`. `district_id`/`ward_code` cần cho GHN — cast int khi có. SPEC 0021.
     *
     * @param  array<string,mixed>  $buyer
     * @param  array<string,mixed>  $recipient
     * @return array<string,mixed>
     */
    private function buildShippingAddress(array $buyer, array $recipient): array
    {
        $src = $recipient !== [] ? $recipient : $buyer;
        $province = $src['province'] ?? $src['province_name'] ?? $src['city'] ?? null;
        $district = $src['district'] ?? $src['district_name'] ?? null;
        $ward = $src['ward'] ?? $src['ward_name'] ?? null;
        $out = [
            'name' => $src['name'] ?? $buyer['name'] ?? null,
            'phone' => $src['phone'] ?? $buyer['phone'] ?? null,
            'address' => $src['address'] ?? null,
            'ward' => $ward,
            'ward_code' => isset($src['ward_code']) ? (string) $src['ward_code'] : null,
            'district' => $district,
            'district_id' => isset($src['district_id']) ? (int) $src['district_id'] : null,
            'province' => $province,
            'province_id' => isset($src['province_id']) ? (int) $src['province_id'] : null,
            'expected_at' => $src['expected_at'] ?? null,
        ];

        return array_filter($out, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Normalize `meta` để lưu vào `orders.meta`. Whitelist field cho phép — tránh user nhồi data
     * tuỳ tiện vào JSON. SPEC 0021 (UI taodon.png).
     *
     * @param  array<string,mixed>  $raw
     * @return array<string,mixed>
     */
    private function normalizeMeta(array $raw, bool $freeShipping, ?int $createdBy, string $subSource): array
    {
        $out = array_filter([
            'sub_source' => $subSource !== '' ? $subSource : ($raw['sub_source'] ?? null),
            'assignee_user_id' => isset($raw['assignee_user_id']) ? (int) $raw['assignee_user_id'] : ($createdBy ?: null),
            'care_user_id' => isset($raw['care_user_id']) ? (int) $raw['care_user_id'] : null,
            'marketer_user_id' => isset($raw['marketer_user_id']) ? (int) $raw['marketer_user_id'] : null,
            'expected_delivery_date' => isset($raw['expected_delivery_date']) && $raw['expected_delivery_date'] !== '' ? (string) $raw['expected_delivery_date'] : null,
            'gender' => isset($raw['gender']) && in_array($raw['gender'], ['male', 'female', 'other'], true) ? $raw['gender'] : null,
            'dob' => isset($raw['dob']) && $raw['dob'] !== '' ? (string) $raw['dob'] : null,
            'email' => isset($raw['email']) && $raw['email'] !== '' ? (string) $raw['email'] : null,
            'print_note' => isset($raw['print_note']) && $raw['print_note'] !== '' ? (string) $raw['print_note'] : null,
            'free_shipping' => $freeShipping ? true : null,
            'collect_fee_on_return_only' => ! empty($raw['collect_fee_on_return_only']) ? true : null,
            // B2 fix (Sprint 1 P0): hint ĐVVC user đã chọn ở form tạo đơn — KHÔNG tạo shipment ngay (đợi
            // user click "Chuẩn bị hàng" để qua CarrierAccountPicker xác nhận). Picker đọc key này để
            // pre-select. Không gắn FK ⇒ nếu account bị xoá sau đó, picker tự fallback default.
            'preferred_carrier_account_id' => isset($raw['preferred_carrier_account_id']) ? (int) $raw['preferred_carrier_account_id'] : null,
        ], fn ($v) => $v !== null && $v !== '');

        return $out;
    }

    /**
     * Each row is either a master SKU line (`sku_id` set — name / sku_code / image filled from the SKU
     * when omitted, so the FE only needs to send sku_id + qty + price) or an ad-hoc "quick product" line
     * (no `sku_id`; `name` is required, plus optional `image`/`unit_price`/`quantity`). Ad-hoc lines stay
     * unlinked to inventory — see OrderInventoryService and docs/03-domain/manual-orders-and-finance.md §1.
     *
     * @return list<array{sku_id:?int,name:string,variation:?string,quantity:int,unit_price:int,discount:int,sku_code:?string,image:?string}>
     */
    private function normalizeItems(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            throw ValidationException::withMessages(['items' => 'Đơn phải có ít nhất một dòng hàng.']);
        }
        $rows = array_values(array_filter($raw, 'is_array'));
        foreach ($rows as $row) {
            if (empty($row['sku_id']) && trim((string) ($row['name'] ?? '')) === '') {
                throw ValidationException::withMessages(['items' => 'Mỗi dòng hàng phải chọn một SKU hoặc nhập tên sản phẩm.']);
            }
        }
        // Fill name / sku_code / image from the SKU record for lines that reference one.
        $skuIds = array_values(array_filter(array_map(fn ($r) => (int) ($r['sku_id'] ?? 0), $rows)));
        $skus = $skuIds === [] ? collect() : Sku::query()->whereIn('id', $skuIds)->get()->keyBy('id');

        $out = [];
        foreach ($rows as $row) {
            $skuId = (int) ($row['sku_id'] ?? 0) ?: null;
            $sku = $skuId !== null ? $skus->get($skuId) : null;
            $name = trim((string) ($row['name'] ?? ''));
            $image = trim((string) ($row['image'] ?? ''));
            $qty = (int) ($row['quantity'] ?? 1);
            $out[] = [
                'sku_id' => $skuId,
                'name' => $name !== '' ? $name : ((string) ($sku->name ?? '') ?: 'Hàng'),
                'variation' => isset($row['variation']) ? (string) $row['variation'] : null,
                'quantity' => max(1, $qty),
                'unit_price' => max(0, (int) ($row['unit_price'] ?? 0)),
                'discount' => max(0, (int) ($row['discount'] ?? 0)),
                'sku_code' => isset($row['sku_code']) ? (string) $row['sku_code'] : ($sku->sku_code ?? null),
                'image' => $image !== '' ? $image : ($sku?->image_url ?: null),
            ];
        }
        if ($out === []) {
            throw ValidationException::withMessages(['items' => 'Đơn phải có ít nhất một dòng hàng.']);
        }

        return $out;
    }

    private function generateOrderNumber(int $tenantId): string
    {
        for ($i = 0; $i < 6; $i++) {
            $candidate = 'M'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            if (! Order::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'M'.now()->format('ymdHis').'-'.Str::upper(Str::random(4));
    }
}
