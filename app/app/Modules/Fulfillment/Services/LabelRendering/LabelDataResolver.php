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
            cod: (int) ($order->cod_amount ?: ($order->is_cod ? $order->grand_total : 0)),
            weight_g: $shipment?->weight_grams ? (int) $shipment->weight_grams : null,
            total_qty: (int) $order->items->sum('quantity'),
            print_note: (string) (data_get($order->meta, 'print_note') ?: ''),
            created_at_fmt: $createdAt,
            items: $items,
        );
    }
}
