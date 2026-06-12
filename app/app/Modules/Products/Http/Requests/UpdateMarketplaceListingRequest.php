<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an edit to an existing marketplace product (title / description /
 * images / per-SKU price). Stock is intentionally not accepted here.
 */
class UpdateMarketplaceListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('products.manage');
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:300'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'images' => ['sometimes', 'array', 'max:9'],
            'images.*' => ['string', 'url', 'max:2048'],
            'prices' => ['sometimes', 'array', 'max:100'],
            'prices.*.external_sku_id' => ['required_with:prices', 'string', 'max:64'],
            'prices.*.price' => ['required_with:prices', 'integer', 'min:0'],
        ];
    }
}
