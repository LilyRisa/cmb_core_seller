<?php

namespace CMBcoreSeller\Modules\Billing\Http\Resources;

use CMBcoreSeller\Modules\Billing\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subscription
 */
class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $plan = $this->whenLoaded('plan', fn () => new PlanResource($this->plan));

        // `days_left` = số ngày còn lại trong kỳ (làm tròn xuống, không âm).
        $now = now();
        $end = $this->current_period_end;
        $daysLeft = ($end !== null && $end->isFuture()) ? (int) $now->diffInDays($end, false) : 0;
        $daysLeft = max(0, $daysLeft);

        return [
            'id' => $this->id,
            'plan' => $plan,
            'plan_code' => $this->plan?->code,
            'status' => $this->status,
            'billing_cycle' => $this->billing_cycle,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'current_period_start' => $this->current_period_start?->toIso8601String(),
            'current_period_end' => $this->current_period_end?->toIso8601String(),
            'cancel_at' => $this->cancel_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'days_left' => $daysLeft,
            'is_trialing' => $this->status === Subscription::STATUS_TRIALING,
            'is_past_due' => $this->status === Subscription::STATUS_PAST_DUE,
        ];
    }
}
