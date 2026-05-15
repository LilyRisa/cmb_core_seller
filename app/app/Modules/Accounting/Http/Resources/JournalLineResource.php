<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Resources;

use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JournalLine */
class JournalLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'line_no' => (int) $this->line_no,
            'account_id' => (int) $this->account_id,
            'account_code' => $this->account_code,
            'account_name' => $this->whenLoaded('account', fn () => $this->account?->name),
            'dr_amount' => (int) $this->dr_amount,
            'cr_amount' => (int) $this->cr_amount,
            'party_type' => $this->party_type,
            'party_id' => $this->party_id,
            'dim_warehouse_id' => $this->dim_warehouse_id,
            'dim_shop_id' => $this->dim_shop_id,
            'dim_sku_id' => $this->dim_sku_id,
            'dim_order_id' => $this->dim_order_id,
            'dim_tax_code' => $this->dim_tax_code,
            'memo' => $this->memo,
        ];
    }
}
