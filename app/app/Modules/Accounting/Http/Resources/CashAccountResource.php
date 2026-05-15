<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\CashAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CashAccount */
class CashAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'kind' => $this->kind,
            'bank_name' => $this->bank_name,
            'account_no' => $this->account_no,
            'account_holder' => $this->account_holder,
            'currency' => $this->currency,
            'gl_account_id' => $this->gl_account_id,
            'gl_account_code' => $this->whenLoaded('glAccount', fn () => $this->glAccount?->code),
            'is_active' => (bool) $this->is_active,
            'description' => $this->description,
            'balance' => (int) ($this->resource->balance_attr ?? 0),
        ];
    }
}
