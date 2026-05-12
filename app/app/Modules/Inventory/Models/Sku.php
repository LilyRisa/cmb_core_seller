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
 * @property string|null $spu_code
 * @property string|null $category
 * @property string $sku_code
 * @property string|null $barcode
 * @property array|null $gtins
 * @property string $name
 * @property string $base_unit
 * @property int $cost_price
 * @property int|null $ref_sale_price
 * @property Carbon|null $sale_start_date
 * @property string|null $note
 * @property int|null $weight_grams
 * @property string|null $length_cm
 * @property string|null $width_cm
 * @property string|null $height_cm
 * @property string|null $image_url
 * @property string|null $image_path
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

    protected $fillable = [
        'tenant_id', 'product_id', 'spu_code', 'category', 'sku_code', 'barcode', 'gtins', 'name', 'base_unit',
        'cost_price', 'ref_sale_price', 'sale_start_date', 'note', 'weight_grams', 'length_cm', 'width_cm', 'height_cm',
        'image_url', 'image_path', 'attributes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'integer', 'ref_sale_price' => 'integer', 'weight_grams' => 'integer',
            'gtins' => 'array', 'attributes' => 'array', 'is_active' => 'boolean', 'sale_start_date' => 'date',
        ];
    }

    /**
     * Reference gross profit per unit = reference sale price − reference cost.
     * Null when no reference sale price is set. Profit reporting (Phase 6) uses
     * the warehouse-level cost when known and falls back to this. See SPEC 0005 §6.
     */
    public function refProfitPerUnit(): ?int
    {
        return $this->ref_sale_price === null ? null : $this->ref_sale_price - $this->cost_price;
    }

    /** Reference margin % = profit ÷ sale price × 100. Null when sale price is 0/null. */
    public function refMarginPercent(): ?float
    {
        if (! $this->ref_sale_price) {
            return null;
        }

        return round(($this->ref_sale_price - $this->cost_price) / $this->ref_sale_price * 100, 1);
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
