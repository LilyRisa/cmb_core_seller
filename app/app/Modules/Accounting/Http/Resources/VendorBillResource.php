<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\VendorBill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VendorBill */
class VendorBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'supplier_id' => $this->supplier_id,
            'purchase_order_id' => $this->purchase_order_id,
            'goods_receipt_id' => $this->goods_receipt_id,
            'bill_no' => $this->bill_no,
            'bill_date' => $this->bill_date?->toIso8601String(),
            'due_date' => $this->due_date?->toIso8601String(),
            'subtotal' => (int) $this->subtotal,
            'tax' => (int) $this->tax,
            'total' => (int) $this->total,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                VendorBill::STATUS_DRAFT => 'Nháp',
                VendorBill::STATUS_RECORDED => 'Đã ghi sổ',
                VendorBill::STATUS_PAID => 'Đã trả',
                VendorBill::STATUS_VOID => 'Đã huỷ',
                default => $this->status,
            },
            'memo' => $this->memo,
            'journal_entry_id' => $this->journal_entry_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'recorded_at' => $this->recorded_at?->toIso8601String(),
        ];
    }
}
