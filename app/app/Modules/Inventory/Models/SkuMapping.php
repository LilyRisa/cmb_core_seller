<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Links a channel_listing to a master SKU × quantity. `type=single` (one row) or
 * `type=bundle` (combo — many rows for the same listing). See SPEC 0003 §5.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_listing_id
 * @property int $sku_id
 * @property int $quantity
 * @property string $type
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Sku|null $sku
 * @property-read ChannelListing|null $channelListing
 */
class SkuMapping extends Model
{
    use BelongsToTenant;

    public const TYPE_SINGLE = 'single';

    public const TYPE_BUNDLE = 'bundle';

    protected $fillable = ['tenant_id', 'channel_listing_id', 'sku_id', 'quantity', 'type', 'created_by'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function channelListing(): BelongsTo
    {
        return $this->belongsTo(ChannelListing::class);
    }
}
