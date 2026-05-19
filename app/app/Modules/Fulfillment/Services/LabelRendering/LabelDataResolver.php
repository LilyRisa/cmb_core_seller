<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Models\Order;

class LabelDataResolver
{
    public function resolve(Order $order): DataContext
    {
        $tenantId = (int) $order->tenant_id;

        if (! $order->relationLoaded('items')) {
            $order->load(['items' => fn ($q) => $q->withoutGlobalScopes()->select(['order_id', 'name', 'seller_sku', 'sku_id', 'variation', 'quantity'])]);
        }
        $shipment = Shipment::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('order_id', $order->getKey())
            ->orderByDesc('id')
            ->first(['id', 'carrier', 'tracking_no', 'weight_grams', 'status']);
        $warehouse = $order->warehouse_id
            ? Warehouse::query()->withoutGlobalScopes()->find($order->warehouse_id)
            : Warehouse::defaultFor($tenantId);

        $addr = (array) ($order->shipping_address ?? []);
        $recDetail = trim((string) ($addr['line1'] ?? $addr['address'] ?? ''));
        $recAdmin = trim(implode(', ', array_filter([$addr['ward'] ?? null, $addr['district'] ?? null, $addr['province'] ?? null])));
        $recFull = trim($recDetail.($recAdmin ? ', '.$recAdmin : ''));

        $whAddr = (array) ($warehouse?->address ?? []);
        $senderPhone = (string) ($whAddr['phone'] ?? '');
        $senderName = (string) ($whAddr['contact'] ?? $warehouse?->name ?? '');
        $senderAddr = trim(implode(', ', array_filter([
            $whAddr['line1'] ?? null, $whAddr['ward'] ?? null,
            $whAddr['district'] ?? null, $whAddr['province'] ?? null,
        ])));

        $items = $order->items->map(fn ($it) => [
            'name' => trim((string) $it->name.($it->variation ? ' — '.$it->variation : '')),
            'sku' => $it->seller_sku ?: null,
            'qty' => (int) $it->quantity,
        ])->all();

        $createdAt = $order->created_at?->format('d/m/Y H:i') ?: '';

        return new DataContext(
            order_number: (string) ($order->order_number ?? $order->external_order_id ?? ('#'.$order->getKey())),
            tracking_no: $shipment?->tracking_no,
            carrier: $shipment?->carrier,
            sender_name: $senderName,
            sender_phone: $senderPhone,
            sender_address: $senderAddr,
            recipient_name: (string) ($addr['fullName'] ?? $addr['name'] ?? $order->buyer_name ?? ''),
            recipient_phone: (string) ($addr['phone'] ?? ''),
            recipient_address: $recFull,
            recipient_address_detail: $recDetail,
            recipient_address_admin: $recAdmin,
            cod: $this->resolveCod($order),
            weight_g: $shipment?->weight_grams ? (int) $shipment->weight_grams : null,
            total_qty: (int) $order->items->sum('quantity'),
            print_note: (string) (data_get($order->meta, 'print_note') ?: ''),
            created_at_fmt: $createdAt,
            items: $items,
        );
    }

    /**
     * COD displayed on the delivery slip must reflect WHAT THE COURIER COLLECTS:
     *   1. If `cod_amount` is set (and non-zero) it is the canonical value — ManualOrderService and
     *      the channel upsert pipeline both pre-compute and persist it. Use as-is.
     *   2. Else, if the order is flagged COD (`is_cod` OR `payment_status='cod'` — channel orders
     *      sometimes set only one of the two) derive it the same way ManualOrderService does:
     *      `grand_total - prepaid_amount` clamped at 0. Previously this branch used `grand_total`
     *      alone, ignoring prepaid → over-collected on the slip vs. what the courier system has.
     *   3. Else, 0 (non-COD).
     *
     * Edge case caught here: legacy/migrated orders where `cod_amount=0` but `is_cod=true`. Without
     * this fallback, DataField rendered "—" for them — the user's reported "COD không hoạt động".
     */
    private function resolveCod(Order $order): int
    {
        $persisted = (int) ($order->cod_amount ?? 0);
        if ($persisted > 0) {
            return $persisted;
        }
        $wantsCod = (bool) $order->is_cod || (string) ($order->payment_status ?? '') === 'cod';
        if (! $wantsCod) {
            return 0;
        }

        return max(0, (int) $order->grand_total - (int) ($order->prepaid_amount ?? 0));
    }
}
