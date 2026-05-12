<?php

namespace CMBcoreSeller\Modules\Products\Models;

use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A tenant's base product. SKUs (master SKUs, owned by the Inventory module)
 * reference it by `product_id`. See SPEC 0003 §5.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $image
 * @property string|null $brand
 * @property string|null $category
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Sku> $skus
 */
class Product extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'image', 'brand', 'category', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    /** Master SKUs of this product (owned by the Inventory module — read-only relation). */
    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class);
    }
}
