<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\CustomerReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CustomerReceipt */
class CustomerReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'customer_id' => $this->customer_id,
            'received_at' => $this->received_at?->toIso8601String(),
            'amount' => (int) $this->amount,
            'payment_method' => $this->payment_method,
            'cash_account_id' => $this->cash_account_id,
            'applied_orders' => $this->applied_orders,
            'memo' => $this->memo,
            'journal_entry_id' => $this->journal_entry_id,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                CustomerReceipt::STATUS_DRAFT => 'Nháp',
                CustomerReceipt::STATUS_CONFIRMED => 'Đã xác nhận',
                CustomerReceipt::STATUS_CANCELLED => 'Đã huỷ',
                default => $this->status,
            },
            'created_at' => $this->created_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'confirmed_by' => $this->confirmed_by,
        ];
    }
}
