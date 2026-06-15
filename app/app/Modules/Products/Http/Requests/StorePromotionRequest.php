<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }
}
