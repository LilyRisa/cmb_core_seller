<?php

namespace CMBcoreSeller\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates copying a listing draft to another connected shop.
 */
class CloneListingDraftRequest extends FormRequest
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
        ];
    }
}
