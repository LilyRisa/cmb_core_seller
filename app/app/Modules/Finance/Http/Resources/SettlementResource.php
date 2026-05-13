<?php

namespace CMBcoreSeller\Modules\Finance\Http\Resources;

use CMBcoreSeller\Modules\Finance\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Settlement */
class SettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_account_id' => $this->channel_account_id,
            'channel_account' => $this->whenLoaded('channelAccount', fn () => $this->channelAccount ? [
                'id' => $this->channelAccount->id, 'name' => $this->channelAccount->effectiveName(), 'provider' => $this->channelAccount->provider,
            ] : null),
            'external_id' => $this->external_id,
            'period_start' => $this->period_start?->toIso8601String(),
            'period_end' => $this->period_end?->toIso8601String(),
            'currency' => $this->currency,
            'total_payout' => (int) $this->total_payout,
            'total_revenue' => (int) $this->total_revenue,
            'total_fee' => (int) $this->total_fee,
            'total_shipping_fee' => (int) $this->total_shipping_fee,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'fetched_at' => $this->fetched_at?->toIso8601String(),
            'reconciled_at' => $this->reconciled_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'lines_count' => $this->whenLoaded('lines', fn () => $this->lines->count()),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($l) => [
                'id' => $l->id, 'order_id' => $l->order_id, 'external_order_id' => $l->external_order_id, 'external_line_id' => $l->external_line_id,
                'fee_type' => $l->fee_type, 'amount' => (int) $l->amount,
                'occurred_at' => $l->occurred_at?->toIso8601String(),
                'description' => $l->description,
                'order' => $l->relationLoaded('order') && $l->order ? ['id' => $l->order->id, 'order_number' => $l->order->order_number, 'external_order_id' => $l->order->external_order_id] : null,
            ])->values()->all()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
