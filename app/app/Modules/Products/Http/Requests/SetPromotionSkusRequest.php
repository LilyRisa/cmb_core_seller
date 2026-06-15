<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetPromotionSkusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'skus' => ['present', 'array'],
            'skus.*.channel_listing_id' => ['nullable', 'integer'],
            'skus.*.external_product_id' => ['nullable', 'string'],
            'skus.*.external_sku_id' => ['nullable', 'string'],
            'skus.*.seller_sku' => ['nullable', 'string'],
            'skus.*.base_price' => ['required', 'integer', 'min:0'],
            'skus.*.discount_value' => ['required', 'integer', 'min:0'],
        ];
    }
}
