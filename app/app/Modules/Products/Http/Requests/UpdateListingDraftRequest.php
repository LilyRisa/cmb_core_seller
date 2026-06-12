<?php

namespace CMBcoreSeller\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates editing of a listing draft. All fields are optional — drafts are
 * work-in-progress and completeness is enforced by the provider validator on
 * revalidation, not here.
 */
class UpdateListingDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'nullable', 'string'],
            'category_id' => ['sometimes', 'nullable', 'string'],
            'brand_id' => ['sometimes', 'nullable', 'string'],
            'attributes' => ['sometimes', 'array'],
            'media_refs' => ['sometimes', 'array'],
            'logistics' => ['sometimes', 'array'],
            'skus' => ['sometimes', 'array'],
        ];
    }
}
