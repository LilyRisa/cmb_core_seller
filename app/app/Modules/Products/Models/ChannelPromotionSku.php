<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một SKU trong chiến dịch giảm giá. Giá = integer VND.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $promotion_id
 * @property int|null $channel_listing_id
 * @property string|null $external_product_id
 * @property string|null $external_sku_id
 * @property string|null $seller_sku
 * @property int $base_price
 * @property string $discount_type
 * @property int $discount_value
 * @property int $sale_price
 * @property string $push_status
 * @property string|null $error
 */
class ChannelPromotionSku extends Model
{
    use BelongsToTenant;

    public const PUSH_PENDING = 'pending';

    public const PUSH_OK = 'ok';

    public const PUSH_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'promotion_id', 'channel_listing_id', 'external_product_id', 'external_sku_id',
        'seller_sku', 'base_price', 'discount_type', 'discount_value', 'sale_price', 'push_status', 'error',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'discount_value' => 'integer',
            'sale_price' => 'integer',
        ];
    }

    /** @return BelongsTo<ChannelPromotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(ChannelPromotion::class, 'promotion_id');
    }

    /** @return BelongsTo<ChannelListing, $this> */
    public function channelListing(): BelongsTo
    {
        return $this->belongsTo(ChannelListing::class, 'channel_listing_id');
    }
}
