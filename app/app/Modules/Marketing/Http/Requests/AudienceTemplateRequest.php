<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AudienceTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $itemRules = [
            'array',
            // each item is a detailed-targeting option {id, name, type}
        ];

        return [
            'name' => ['required', 'string', 'max:120'],
            'payload' => ['present', 'array'],
            'payload.include' => $itemRules,
            'payload.narrow' => $itemRules,
            'payload.exclude' => $itemRules,
            'payload.include.*.id' => ['required', 'string'],
            'payload.include.*.name' => ['required', 'string'],
            'payload.include.*.type' => ['required', 'string', 'max:64'],
            'payload.narrow.*.id' => ['required', 'string'],
            'payload.narrow.*.name' => ['required', 'string'],
            'payload.narrow.*.type' => ['required', 'string', 'max:64'],
            'payload.exclude.*.id' => ['required', 'string'],
            'payload.exclude.*.name' => ['required', 'string'],
            'payload.exclude.*.type' => ['required', 'string', 'max:64'],
        ];
    }
}
