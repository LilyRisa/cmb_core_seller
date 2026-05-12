<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Master SKU — the single source of truth for stock (ADR-0008). `sku_code` is
 * unique per tenant. See SPEC 0003 §5.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $product_id
 * @property string $sku_code
 * @property string|null $barcode
 * @property string $name
 * @property int $cost_price
 * @property array|null $attributes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, InventoryLevel> $levels
 * @property-read Collection<int, SkuMapping> $mappings
 */
class Sku extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['tenant_id', 'product_id', 'sku_code', 'barcode', 'name', 'cost_price', 'attributes', 'is_active'];

    protected function casts(): array
    {
        return ['cost_price' => 'integer', 'attributes' => 'array', 'is_active' => 'boolean'];
    }

    public function levels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(SkuMapping::class);
    }

    public function scopeSearch(Builder $q, string $term): Builder
    {
        $term = trim($term);

        return $q->where(fn (Builder $q) => $q->where('sku_code', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%")->orWhere('barcode', 'like', "%{$term}%"));
    }

    /** Sum of `available` across the tenant's warehouses. */
    public function availableTotal(): int
    {
        return (int) $this->levels()->sum('available_cached');
    }

    public function onHandTotal(): int
    {
        return (int) $this->levels()->sum('on_hand');
    }

    public function reservedTotal(): int
    {
        return (int) $this->levels()->sum('reserved');
    }
}
