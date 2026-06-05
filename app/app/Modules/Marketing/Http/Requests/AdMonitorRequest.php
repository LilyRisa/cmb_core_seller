<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdMonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'target_level' => ['required', Rule::in(['campaign', 'adset'])],
            'target_external_id' => ['required', 'string', 'max:64'],
            'enabled' => ['nullable', 'boolean'],
            'increase_enabled' => ['nullable', 'boolean'],
            'increase_below' => ['nullable', 'integer', 'min:1'],
            'increase_step_pct' => ['nullable', 'integer', 'min:1', 'max:500'],
            'max_daily_budget' => ['nullable', 'integer', 'min:1000'],
            'pause_enabled' => ['nullable', 'boolean'],
            'pause_above' => ['nullable', 'integer', 'min:1'],
            'min_results' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
