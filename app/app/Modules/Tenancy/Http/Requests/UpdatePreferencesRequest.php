<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'ui_shell' => ['sometimes', 'in:v1,v2'],
            'ui_active_tab' => ['sometimes', 'nullable', 'string', 'max:64'],
            'ui_open_tabs' => ['sometimes', 'array', 'max:30'],
            'ui_open_tabs.*.appKey' => ['required', 'string', 'max:64'],
            'ui_open_tabs.*.path' => ['required', 'string', 'max:255'],
        ];
    }
}
