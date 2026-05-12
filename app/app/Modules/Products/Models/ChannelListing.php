<?php

namespace CMBcoreSeller\Modules\Products\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A product/variant as it appears on a marketplace shop. `channel_stock` mirrors
 * what the marketplace currently shows; stock is pushed from the linked master
 * SKU(s) via sku_mappings. See SPEC 0003 §5.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_account_id
 * @property string|null $external_product_id
 * @property string $external_sku_id
 * @property string|null $seller_sku
 * @property string|null $title
 * @property string|null $variation
 * @property int|null $price
 * @property int|null $channel_stock
 * @property string $currency
 * @property string|null $image
 * @property bool $is_active
 * @property bool $is_stock_locked
 * @property string $sync_status
 * @property string|null $sync_error
 * @property Carbon|null $last_pushed_at
 * @property Carbon|null $last_fetched_at
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ChannelAccount|null $channelAccount
 * @property-read Collection<int, SkuMapping> $mappings
 */
class ChannelListing extends Model
{
    use BelongsToTenant;

    public const SYNC_OK = 'ok';

    public const SYNC_ERROR = 'error';

    public const SYNC_PENDING = 'pending';

    protected $fillable = [
        'tenant_id', 'channel_account_id', 'external_product_id', 'external_sku_id', 'seller_sku',
        'title', 'variation', 'price', 'channel_stock', 'currency', 'image', 'is_active', 'is_stock_locked',
        'sync_status', 'sync_error', 'last_pushed_at', 'last_fetched_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'channel_stock' => 'integer',
            'is_active' => 'boolean',
            'is_stock_locked' => 'boolean',
            'last_pushed_at' => 'datetime',
            'last_fetched_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(SkuMapping::class);
    }

    public function scopeUnmapped(Builder $q): Builder
    {
        return $q->whereNotExists(fn ($sub) => $sub->selectRaw('1')->from('sku_mappings')->whereColumn('sku_mappings.channel_listing_id', 'channel_listings.id'));
    }
}
