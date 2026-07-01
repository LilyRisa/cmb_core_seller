<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Events\OrderUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
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

    public function __construct(
        private readonly CustomerWallet $wallet,
    ) {}

    /** @param array<string,mixed> $data */
    public function create(int $tenantId, ?int $userId, array $data): Order
    {
        $items = $this->normalizeItems($data['items'] ?? []);
        $warehouseId = $this->resolveWarehouseId($tenantId, $data['warehouse_id'] ?? null);
        $status = $this->chosenStatus($data['status'] ?? null);
        $buyer = (array) ($data['buyer'] ?? []);
        $recipient = (array) ($data['recipient'] ?? []);
        // SPEC 0021 — print_note: nếu user không nhập, fallback `tenant.settings.print.default_note`.
        // Có thể giữ template biến `{{order_number}}`, `{{customer_name}}`, `{{cod_amount}}` để FE chèn động sau.
        $meta = (array) ($data['meta'] ?? []);
        if (empty($meta['print_note'])) {
            $tenant = Tenant::query()->find($tenantId);
            $default = (string) (data_get($tenant?->settings, 'print.default_note') ?: '');
            if ($default !== '') {
                $meta['print_note'] = $default;
            }
        }
        $data['meta'] = $meta;
        // SPEC 0021 (UI taodon.png): miễn phí giao hàng, giảm giá đơn, tiền chuyển khoản, phụ thu.
        $freeShipping = (bool) ($data['free_shipping'] ?? false);
        $shippingFee = $freeShipping ? 0 : max(0, (int) ($data['shipping_fee'] ?? 0));
        $tax = max(0, (int) ($data['tax'] ?? 0));
        $orderDiscount = max(0, (int) ($data['order_discount'] ?? 0));     // "Giảm giá đơn hàng"
        $prepaidAmount = max(0, (int) ($data['prepaid_amount'] ?? 0));     // Đã trả trước (CK)
        $surcharge = max(0, (int) ($data['surcharge'] ?? 0));              // Phụ thu
        $itemTotal = array_sum(array_map(fn ($i) => $i['unit_price'] * $i['quantity'] - $i['discount'], $items));
        $sellerDiscount = array_sum(array_map(fn ($i) => $i['discount'], $items)) + $orderDiscount;
        // grand_total = (item_total + ship + tax + surcharge) − order_discount.
        $grandTotal = max(0, $itemTotal + $shippingFee + $tax + $surcharge - $orderDiscount);
        // Quy tắc COD (2026-06-03): COD = số tiền CÒN THIẾU cuối cùng = grand_total − prepaid (clamp ≥ 0).
        // `is_cod` được TỰ SUY RA (còn thiếu > 0) — KHÔNG phụ thuộc checkbox FE. Trước đây checkbox `is_cod`
        // default off ⇒ đơn lưu cod_amount=0 ⇒ đẩy GHN COD 0đ dù khách phải trả khi nhận. Nếu trả vượt
        // (prepaid > grand_total) ⇒ còn thiếu âm ⇒ COD kẹp về 0 (đơn coi như đã thanh toán).
        $needCollect = $grandTotal - $prepaidAmount;
        $codAmount = max(0, $needCollect);
        $isCod = $codAmount > 0;
        // 'paid' khi prepaid phủ toàn bộ; 'partial' khi trả 1 phần; 'cod' khi còn thu hộ & chưa trả trước.
        $paymentStatus = match (true) {
            $prepaidAmount >= $grandTotal && $grandTotal > 0 => 'paid',
            $prepaidAmount > 0 => 'partial',
            $isCod => 'cod',
            default => 'unpaid',
        };
        $now = now();

        $order = DB::transaction(function () use ($tenantId, $userId, $items, $status, $buyer, $recipient, $shippingFee, $tax, $isCod, $itemTotal, $sellerDiscount, $grandTotal, $codAmount, $prepaidAmount, $surcharge, $freeShipping, $paymentStatus, $now, $data, $warehouseId) {
            $order = Order::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'source' => 'manual',
                'channel_account_id' => null,
                'external_order_id' => null,
                'order_number' => $this->generateOrderNumber($tenantId),
                // R3 (Sprint 4) — denormalize `orders.carrier='manual'` ngay từ lúc tạo. Trước đây null đến
                // tận khi chuẩn bị hàng ⇒ chip "Vận chuyển" trên trang Đơn hàng bỏ sót đơn manual chưa prepare.
                'carrier' => 'manual',
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
                'meta' => $this->normalizeMeta((array) $data['meta'], $freeShipping, $userId, (string) ($data['sub_source'] ?? '')),
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

            // Trừ ví trả trước (nếu đơn dùng ví). wallet_amount ⊆ prepaid_amount ⊆ số dư ví. Trong cùng
            // transaction ⇒ thiếu số dư thì rollback cả đơn. SPEC 2026-06-26.
            $walletAmount = max(0, (int) ($data['wallet_amount'] ?? 0));
            if ($walletAmount > 0) {
                $customerId = (int) ($data['customer_id'] ?? 0);
                if ($customerId <= 0) {
                    throw ValidationException::withMessages(['wallet_amount' => 'Thiếu khách hàng để trừ ví trả trước.']);
                }
                if ($walletAmount > $prepaidAmount) {
                    throw ValidationException::withMessages(['wallet_amount' => 'Số tiền trừ ví vượt số đã trả trước của đơn.']);
                }
                try {
                    $this->wallet->deductForOrder($tenantId, $customerId, (int) $order->getKey(), $walletAmount, $userId);
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages(['wallet_amount' => $e->getMessage()]);
                }
            }

            return $order;
        });

        OrderUpserted::dispatch($order, true);

        return $order;
    }

    /** Cancel a manual order that hasn't shipped yet → releases stock via the event. */
    public function cancel(Order $order, ?int $userId, ?string $reason = null): Order
    {
        $this->assertManualEditable($order);
        $this->assertCancellable($order);
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

    /**
     * Edit buyer / address / items / payment / meta of a manual order.
     *
     * SPEC 2026-05-17 — Đơn manual có thể sửa MỌI LÚC (kể cả sau khi đã đẩy ĐVVC). Nếu shipment đã
     * tạo trên hệ ĐVVC thì FE đã cảnh báo "thay đổi chỉ áp dụng local, không can thiệp vận đơn đã đẩy".
     *
     * Payload — toàn bộ field giống `create()`:
     *   - items[]      : nếu có ⇒ thay thế hoàn toàn order_items (delete + insert), rebalance inventory qua OrderUpserted
     *   - buyer{}      : name/phone
     *   - recipient{}  : full address (name/phone/address + province/district/ward + codes)
     *   - sub_source, free_shipping, shipping_fee, order_discount, prepaid_amount, surcharge, tax
     *   - is_cod, cod_amount (auto-derive nếu thiếu)
     *   - note, tags, meta{}
     */
    public function update(Order $order, array $data): Order
    {
        $this->assertManualEditable($order);

        $now = now();
        $tenantId = (int) $order->tenant_id;
        $fill = [];

        // ---- Items (replace toàn bộ nếu được cung cấp) ----
        $newItems = null;
        if (array_key_exists('items', $data)) {
            $newItems = $this->normalizeItems((array) $data['items']);
        }

        // ---- Buyer + recipient (shipping_address) ----
        $buyer = (array) ($data['buyer'] ?? []);
        $recipient = (array) ($data['recipient'] ?? []);
        if ($buyer !== [] || $recipient !== []) {
            // Khi `recipient` có ⇒ rebuild toàn bộ shipping_address bằng helper (giống create).
            // Khi chỉ có `buyer` ⇒ merge từng field vào address cũ để giữ data đã chọn trước đó.
            if ($recipient !== []) {
                $fill['shipping_address'] = $this->buildShippingAddress($buyer, $recipient);
            } else {
                $addr = (array) ($order->shipping_address ?? []);
                foreach (['name' => 'name', 'phone' => 'phone', 'address' => 'address'] as $src => $dst) {
                    if (array_key_exists($src, $buyer)) {
                        $addr[$dst] = $buyer[$src];
                    }
                }
                $fill['shipping_address'] = array_filter($addr, fn ($v) => $v !== null && $v !== '');
            }
            if (array_key_exists('name', $buyer)) {
                $fill['buyer_name'] = $buyer['name'] ?: null;
            }
            if (array_key_exists('phone', $buyer)) {
                $fill['buyer_phone'] = $buyer['phone'] ?: null;
            }
        }

        // ---- Payment fields (giống create) ----
        $freeShipping = array_key_exists('free_shipping', $data) ? (bool) $data['free_shipping'] : null;
        $shippingFeeIn = array_key_exists('shipping_fee', $data) ? max(0, (int) $data['shipping_fee']) : null;
        $tax = array_key_exists('tax', $data) ? max(0, (int) $data['tax']) : null;
        $orderDiscount = array_key_exists('order_discount', $data) ? max(0, (int) $data['order_discount']) : null;
        $prepaidAmount = array_key_exists('prepaid_amount', $data) ? max(0, (int) $data['prepaid_amount']) : null;
        $surcharge = array_key_exists('surcharge', $data) ? max(0, (int) $data['surcharge']) : null;
        $isCodIn = array_key_exists('is_cod', $data) ? (bool) $data['is_cod'] : null;
        $codAmountIn = array_key_exists('cod_amount', $data) ? max(0, (int) $data['cod_amount']) : null;

        // Tính lại totals — dùng giá trị NEW nếu có, fallback giá trị cũ trong DB.
        $itemTotal = $newItems !== null
            ? array_sum(array_map(fn ($i) => $i['unit_price'] * $i['quantity'] - $i['discount'], $newItems))
            : (int) $order->item_total;
        $sellerDiscount = $newItems !== null
            ? array_sum(array_map(fn ($i) => $i['discount'], $newItems)) + ($orderDiscount ?? 0)
            : (int) $order->seller_discount;
        $shippingFeeEff = ($freeShipping === true) ? 0 : ($shippingFeeIn ?? (int) $order->shipping_fee);
        $taxEff = $tax ?? (int) $order->tax;
        $orderDiscountEff = $orderDiscount ?? 0;
        $surchargeEff = $surcharge ?? (int) ($order->surcharge ?? 0);
        $prepaidEff = $prepaidAmount ?? (int) ($order->prepaid_amount ?? 0);

        // Recompute chỉ khi có ít nhất 1 field tiền OR items đổi.
        $shouldRecompute = $newItems !== null
            || $freeShipping !== null || $shippingFeeIn !== null || $tax !== null || $orderDiscount !== null
            || $prepaidAmount !== null || $surcharge !== null || $isCodIn !== null || $codAmountIn !== null;
        if ($shouldRecompute) {
            $grandTotal = max(0, $itemTotal + $shippingFeeEff + $taxEff + $surchargeEff - $orderDiscountEff);
            // Quy tắc COD (2026-06-03): COD = tiền CÒN THIẾU = grand_total − prepaid (clamp ≥ 0); is_cod tự suy ra.
            $needCollect = $grandTotal - $prepaidEff;
            $codAmount = max(0, $needCollect);
            $isCodEff = $codAmount > 0;
            $paymentStatus = match (true) {
                $prepaidEff >= $grandTotal && $grandTotal > 0 => 'paid',
                $prepaidEff > 0 => 'partial',
                $isCodEff => 'cod',
                default => 'unpaid',
            };
            $fill['item_total'] = $itemTotal;
            $fill['seller_discount'] = $sellerDiscount;
            $fill['shipping_fee'] = $shippingFeeEff;
            $fill['tax'] = $taxEff;
            $fill['surcharge'] = $surchargeEff;
            $fill['prepaid_amount'] = $prepaidEff;
            $fill['grand_total'] = $grandTotal;
            $fill['is_cod'] = $isCodEff;
            $fill['cod_amount'] = $codAmount;
            $fill['payment_status'] = $paymentStatus;
            $fill['paid_at'] = $prepaidEff > 0 ? ($order->paid_at ?? $now) : null;
        }

        // ---- Note / tags / meta ----
        if (array_key_exists('note', $data)) {
            $fill['note'] = $data['note'] ?: null;
        }
        if (array_key_exists('tags', $data)) {
            $fill['tags'] = array_values(array_filter((array) $data['tags']));
        }
        if (array_key_exists('meta', $data) || array_key_exists('sub_source', $data) || $freeShipping !== null) {
            $existingMeta = (array) ($order->meta ?? []);
            $newMetaRaw = (array) ($data['meta'] ?? []);
            // Trộn meta cũ + meta mới — meta mới ghi đè key trùng.
            $merged = array_merge($existingMeta, $newMetaRaw);
            $subSource = (string) ($data['sub_source'] ?? $existingMeta['sub_source'] ?? '');
            $fill['meta'] = $this->normalizeMeta($merged, $freeShipping ?? (bool) ($existingMeta['free_shipping'] ?? false), null, $subSource);
        }

        $fill['source_updated_at'] = $now;

        DB::transaction(function () use ($order, $tenantId, $fill, $newItems) {
            $order->forceFill($fill)->save();

            // Replace order_items khi có items[] trong payload — delete + insert ⇒ OrderUpserted handler
            // sẽ tự rebalance inventory (release old, reserve new) qua diff item_total/sku_id.
            if ($newItems !== null) {
                OrderItem::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenantId)->where('order_id', $order->getKey())->delete();
                foreach ($newItems as $i => $item) {
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
            }
        });

        OrderUpserted::dispatch($order->refresh(), false);

        return $order;
    }

    // --- helpers --------------------------------------------------------------

    private function assertManualEditable(Order $order): void
    {
        if ($order->source !== 'manual') {
            throw ValidationException::withMessages(['order' => 'Chỉ sửa được đơn thủ công.']);
        }
        // SPEC 2026-05-17 — gỡ block "không sửa được đơn đã bàn giao". User được sửa mọi lúc;
        // FE hiển thị Alert cảnh báo khi shipment đã đẩy ĐVVC. Chỉ chặn khi đơn đã giao xong /
        // bị hoàn hoàn toàn (terminal trừ Cancelled) — lúc đó sửa cũng vô nghĩa với kế toán.
        $status = $order->status;
        if ($status->isTerminal() && $status !== StandardOrderStatus::Cancelled) {
            throw ValidationException::withMessages(['order' => 'Đơn đã ở trạng thái kết thúc (đã giao / đã hoàn) — không thể sửa.']);
        }
    }

    /**
     * Chặn `cancel()` khi shipment đã đẩy ĐVVC — user phải huỷ vận đơn trên hệ ĐVVC trước,
     * rồi mới huỷ đơn ở hệ thống (tránh kho ĐVVC vẫn ship trong khi đơn đã huỷ).
     */
    private function assertCancellable(Order $order): void
    {
        $shipped = $order->shipments()
            ->where('carrier', '!=', 'manual')
            ->where('carrier', '!=', '')
            ->whereNotIn('status', ['pending', 'cancelled'])
            ->exists();
        if ($shipped) {
            throw ValidationException::withMessages(['order' => 'Đơn đã đẩy lên ĐVVC — hãy huỷ vận đơn trên ĐVVC trước rồi mới huỷ đơn.']);
        }
    }

    private function chosenStatus(?string $value): StandardOrderStatus
    {
        $s = $value ? StandardOrderStatus::tryFrom($value) : null;

        // Mặc định đơn tạo thủ công = "Chờ xử lý" (pending). Chỉ chuyển "Đang xử lý" (processing) sau khi
        // "Chuẩn bị hàng" đẩy vận đơn lên ĐVVC thành công — xem ShipmentService (Pending → Processing).
        return ($s !== null && in_array($s, self::PRE_SHIPMENT_CHOICES, true)) ? $s : StandardOrderStatus::Pending;
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
        // SPEC 0021 — `address_format` ('new' | 'old') ghi vào shipping_address để biết dữ liệu nguồn từ
        // hệ nào. ĐVVC sau này resolve qua GhnAddressResolver dựa trên name + format. Giữ legacy `*_id`
        // (int) khi user nhập trực tiếp mã GHN; song song với `*_code` (string) từ admin_* DB.
        $out = [
            'name' => $src['name'] ?? $buyer['name'] ?? null,
            'phone' => $src['phone'] ?? $buyer['phone'] ?? null,
            'address' => $src['address'] ?? null,
            'address_format' => $src['address_format'] ?? null,
            'ward' => $ward,
            'ward_code' => isset($src['ward_code']) ? (string) $src['ward_code'] : null,
            'district' => $district,
            'district_code' => isset($src['district_code']) ? (string) $src['district_code'] : null,
            'district_id' => isset($src['district_id']) ? (int) $src['district_id'] : null,
            'province' => $province,
            'province_code' => isset($src['province_code']) ? (string) $src['province_code'] : null,
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
            // U8 (Sprint 2) — tách file đính kèm khỏi `note` để in phiếu không lộ URL. Mỗi item shape
            // `{name, url}`. Max 20 file để tránh JSON phình.
            'attachments' => isset($raw['attachments']) && is_array($raw['attachments'])
                ? array_slice(array_values(array_filter(array_map(function ($a) {
                    if (! is_array($a)) {
                        return null;
                    }
                    $url = trim((string) ($a['url'] ?? ''));
                    $name = trim((string) ($a['name'] ?? ''));

                    return $url !== '' ? ['url' => $url, 'name' => $name !== '' ? $name : 'file'] : null;
                }, $raw['attachments']))), 0, 20)
                : null,
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

    private function resolveWarehouseId(int $tenantId, mixed $requested): int
    {
        if ($requested === null || $requested === '') {
            return Warehouse::defaultFor($tenantId)->id;
        }
        $exists = Warehouse::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('id', (int) $requested)->exists();
        if (! $exists) {
            throw ValidationException::withMessages(['warehouse_id' => 'Kho gửi không thuộc shop hiện tại.']);
        }

        return (int) $requested;
    }
}
