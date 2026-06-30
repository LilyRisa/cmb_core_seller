<?php

namespace CMBcoreSeller\Modules\EInvoice\Http\Resources;

use CMBcoreSeller\Modules\EInvoice\Models\EInvoiceAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EInvoiceAccount — KHÔNG BAO GIỜ lộ credentials thô. */
class EInvoiceAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'name' => $this->name,
            'is_invoice_with_code' => $this->is_invoice_with_code,
            'default_mode' => $this->default_mode,
            'templates' => $this->templates ?? [],
            'seller_info' => $this->seller_info ?? [],
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'meta' => $this->meta ?? [],
            'credential_keys' => array_keys((array) ($this->credentials ?? [])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
