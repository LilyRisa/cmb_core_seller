<?php

namespace CMBcoreSeller\Modules\Products\Models;

use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One SKU/variant within a {@see ListingDraft}.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $listing_draft_id
 * @property int|null $master_variant_id
 * @property string $seller_sku
 * @property array|null $sale_props
 * @property int $price
 * @property int $stock
 * @property float|null $package_weight
 * @property array|null $package_dims
 * @property string|null $external_sku_id
 * @property string|null $image_ref
 * @property-read Sku|null $masterSku
 */
class ListingDraftSku extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sale_props' => 'array',
            'package_dims' => 'array',
            'price' => 'integer',
            'stock' => 'integer',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ListingDraft::class, 'listing_draft_id');
    }

    /** Master SKU đã liên kết thủ công (để đồng bộ tồn kho sau khi đẩy). */
    public function masterSku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'master_variant_id');
    }
}
