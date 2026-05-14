<?php

namespace CMBcoreSeller\Modules\Billing\Http\Resources;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plan
 */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'price_monthly' => (int) $this->price_monthly,
            'price_yearly' => (int) $this->price_yearly,
            'currency' => $this->currency,
            'trial_days' => (int) $this->trial_days,
            'limits' => $this->limits ?? [],
            'features' => $this->features ?? [],
        ];
    }
}
