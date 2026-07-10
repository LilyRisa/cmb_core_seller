<?php

namespace CMBcoreSeller\Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProTrialRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('billing.manage');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'terms_accepted' => ['required', 'accepted'],
            'terms_version' => ['required', 'string', 'max:32'],
        ];
    }
}
