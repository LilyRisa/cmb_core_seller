<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeoExclusionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'payload' => ['present', 'array'],
            'payload.*.key' => ['required', 'string'],
            'payload.*.name' => ['required', 'string'],
            'payload.*.type' => ['required', 'string', 'in:country,region,city'],
        ];
    }
}
