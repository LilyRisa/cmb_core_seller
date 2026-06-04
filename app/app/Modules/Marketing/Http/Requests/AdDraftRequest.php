<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates ad-draft create/update. Drafts are work-in-progress so fields are
 * lenient (most nullable); strict completeness is checked at publish (Plan 4).
 */
class AdDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'ad_account_id' => [$creating ? 'required' : 'prohibited', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:32'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
