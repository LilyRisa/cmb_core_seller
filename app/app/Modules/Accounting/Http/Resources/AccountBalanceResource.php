<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\AccountBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AccountBalance */
class AccountBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'account_id' => (int) $this->account_id,
            'account_code' => $this->whenLoaded('account', fn () => $this->account?->code),
            'account_name' => $this->whenLoaded('account', fn () => $this->account?->name),
            'period_id' => (int) $this->period_id,
            'period_code' => $this->whenLoaded('period', fn () => $this->period?->code),
            'opening' => (int) $this->opening,
            'debit' => (int) $this->debit,
            'credit' => (int) $this->credit,
            'closing' => (int) $this->closing,
            'recomputed_at' => $this->recomputed_at?->toIso8601String(),
        ];
    }
}
