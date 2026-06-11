<?php

namespace CMBcoreSeller\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates creation of a marketplace listing draft from a master product.
 */
class StoreListingDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'channel_account_id' => ['required', 'integer'],
            'provider' => ['required', 'string', 'in:lazada,tiktok,shopee'],
        ];
    }
}
