<?php

namespace CMBcoreSeller\Modules\Procurement\Http\Resources;

use CMBcoreSeller\Modules\Procurement\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PurchaseOrder */
class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier ? ['id' => $this->supplier->id, 'code' => $this->supplier->code, 'name' => $this->supplier->name] : null),
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? ['id' => $this->warehouse->id, 'name' => $this->warehouse->name] : null),
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'expected_at' => $this->expected_at?->format('Y-m-d'),
            'note' => $this->note,
            'total_qty' => (int) $this->total_qty,
            'total_cost' => (int) $this->total_cost,
            'currency' => 'VND',
            'received_qty' => $this->whenLoaded('items', fn () => (int) $this->items->sum('qty_received')),
            'progress_percent' => $this->whenLoaded('items', function () {
                $ordered = (int) $this->items->sum('qty_ordered');
                $received = (int) $this->items->sum('qty_received');

                return $ordered > 0 ? (int) round($received * 100 / $ordered) : 0;
            }),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($it) => [
                'id' => $it->id, 'sku_id' => $it->sku_id, 'qty_ordered' => (int) $it->qty_ordered,
                'qty_received' => (int) $it->qty_received, 'qty_remaining' => max(0, (int) $it->qty_ordered - (int) $it->qty_received),
                'unit_cost' => (int) $it->unit_cost, 'subtotal' => (int) ($it->unit_cost * $it->qty_ordered),
                'note' => $it->note,
                'sku' => $it->relationLoaded('sku') && $it->sku ? ['id' => $it->sku->id, 'sku_code' => $it->sku->sku_code, 'name' => $it->sku->name, 'image_url' => $it->sku->image_url] : null,
            ])->values()->all()),
            'goods_receipts' => $this->whenLoaded('goodsReceipts', fn () => $this->goodsReceipts->map(fn ($gr) => [
                'id' => $gr->id, 'code' => $gr->code, 'status' => $gr->status,
                'total_cost' => (int) $gr->total_cost, 'confirmed_at' => $gr->confirmed_at?->toIso8601String(),
                'created_at' => $gr->created_at?->toIso8601String(),
            ])->values()->all()),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            PurchaseOrder::STATUS_DRAFT => 'Nháp',
            PurchaseOrder::STATUS_CONFIRMED => 'Đã chốt',
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED => 'Nhận một phần',
            PurchaseOrder::STATUS_RECEIVED => 'Đã nhận đủ',
            PurchaseOrder::STATUS_CANCELLED => 'Đã huỷ',
            default => (string) $this->status,
        };
    }
}
