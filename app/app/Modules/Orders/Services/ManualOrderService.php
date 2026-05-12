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
        $shippingFee = (int) ($data['shipping_fee'] ?? 0);
        $tax = (int) ($data['tax'] ?? 0);
        $isCod = (bool) ($data['is_cod'] ?? false);
        $itemTotal = array_sum(array_map(fn ($i) => $i['unit_price'] * $i['quantity'] - $i['discount'], $items));
        $grandTotal = $itemTotal + $shippingFee + $tax;
        $codAmount = $isCod ? (int) ($data['cod_amount'] ?? $grandTotal) : 0;
        $now = now();

        $order = DB::transaction(function () use ($tenantId, $userId, $items, $status, $buyer, $shippingFee, $tax, $isCod, $itemTotal, $grandTotal, $codAmount, $now, $data) {
            $order = Order::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $tenantId,
                'source' => 'manual',
                'channel_account_id' => null,
                'external_order_id' => null,
                'order_number' => $this->generateOrderNumber($tenantId),
                'status' => $status,
                'raw_status' => $status->value,
                'payment_status' => $isCod ? 'cod' : 'unpaid',
                'buyer_name' => $buyer['name'] ?? null,
                'buyer_phone' => $buyer['phone'] ?? null,
                'shipping_address' => array_filter([
                    'name' => $buyer['name'] ?? null,
                    'phone' => $buyer['phone'] ?? null,
                    'address' => $buyer['address'] ?? null,
                    'ward' => $buyer['ward'] ?? null,
                    'district' => $buyer['district'] ?? null,
                    'province' => $buyer['province'] ?? ($buyer['city'] ?? null),
                ], fn ($v) => $v !== null && $v !== ''),
                'currency' => 'VND',
                'item_total' => $itemTotal,
                'shipping_fee' => $shippingFee,
                'platform_discount' => 0,
                'seller_discount' => array_sum(array_map(fn ($i) => $i['discount'], $items)),
                'tax' => $tax,
                'cod_amount' => $codAmount,
                'grand_total' => $grandTotal,
                'is_cod' => $isCod,
                'fulfillment_type' => 'manual',
                'placed_at' => $now,
                'note' => $data['note'] ?? null,
                'tags' => array_values(array_filter((array) ($data['tags'] ?? []))),
                'has_issue' => false,
                'packages' => [],
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
