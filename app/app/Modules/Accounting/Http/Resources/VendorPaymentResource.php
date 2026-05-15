<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VendorPayment */
class VendorPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'supplier_id' => $this->supplier_id,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'amount' => (int) $this->amount,
            'payment_method' => $this->payment_method,
            'applied_bills' => $this->applied_bills,
            'memo' => $this->memo,
            'journal_entry_id' => $this->journal_entry_id,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                VendorPayment::STATUS_DRAFT => 'Nháp',
                VendorPayment::STATUS_CONFIRMED => 'Đã chi',
                VendorPayment::STATUS_CANCELLED => 'Đã huỷ',
                default => $this->status,
            },
            'created_at' => $this->created_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
        ];
    }
}
