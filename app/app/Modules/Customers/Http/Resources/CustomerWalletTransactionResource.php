<?php

namespace CMBcoreSeller\Modules\Customers\Http\Resources;

use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CustomerWalletTransaction */
class CustomerWalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'type' => $this->type,
            'amount' => (int) $this->amount,
            'balance_after' => (int) $this->balance_after,
            'payment_method' => $this->payment_method,
            'invoice_ref' => $this->invoice_ref,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
