<?php

namespace CMBcoreSeller\Modules\Billing\Http\Resources;

use CMBcoreSeller\Modules\Billing\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'subscription_id' => $this->subscription_id,
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'subtotal' => (int) $this->subtotal,
            'tax' => (int) $this->tax,
            'total' => (int) $this->total,
            'currency' => $this->currency,
            'due_at' => $this->due_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'voided_at' => $this->voided_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($l) => [
                'id' => $l->id,
                'kind' => $l->kind,
                'description' => $l->description,
                'quantity' => (int) $l->quantity,
                'unit_price' => (int) $l->unit_price,
                'amount' => (int) $l->amount,
            ])->values()),
        ];
    }
}
