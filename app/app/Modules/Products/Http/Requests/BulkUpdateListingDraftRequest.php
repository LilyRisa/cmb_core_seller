<?php

namespace CMBcoreSeller\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates bulk-editing nhiều listing draft cùng lúc (SPEC 2026-07-15). Mỗi
 * item lặp lại đúng field của {@see UpdateListingDraftRequest} + khóa 'id'.
 */
class BulkUpdateListingDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer'],
            'items.*.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.description' => ['sometimes', 'nullable', 'string'],
            'items.*.video_url' => ['sometimes', 'nullable', 'string'],
            'items.*.category_id' => ['sometimes', 'nullable', 'string'],
            'items.*.brand_id' => ['sometimes', 'nullable', 'string'],
            'items.*.attributes' => ['sometimes', 'array'],
            'items.*.media_refs' => ['sometimes', 'array'],
            'items.*.logistics' => ['sometimes', 'array'],
            'items.*.skus' => ['sometimes', 'array'],
        ];
    }
}
