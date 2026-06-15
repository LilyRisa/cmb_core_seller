<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'discount_type' => ['sometimes', 'in:percent,fixed'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
        ];
    }
}
