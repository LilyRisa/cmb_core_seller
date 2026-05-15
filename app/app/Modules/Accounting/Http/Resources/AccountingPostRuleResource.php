<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\AccountingPostRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AccountingPostRule */
class AccountingPostRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'event_key' => $this->event_key,
            'debit_account_code' => $this->debit_account_code,
            'credit_account_code' => $this->credit_account_code,
            'is_enabled' => (bool) $this->is_enabled,
            'notes' => $this->notes,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
