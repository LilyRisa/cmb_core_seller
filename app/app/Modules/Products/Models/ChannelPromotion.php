<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Models;

use Carbon\CarbonInterface;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Chiến dịch giảm giá nhiều SKU cho 1 gian hàng. Shopee/TikTok có `external_promotion_id`;
 * Lazada null (rải SalePrice theo SKU). `source` = app|sync.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_account_id
 * @property string $provider
 * @property string|null $external_promotion_id
 * @property string $title
 * @property string $discount_type
 * @property CarbonInterface|null $starts_at
 * @property CarbonInterface|null $ends_at
 * @property string $status
 * @property string $source
 * @property array|null $last_error
 * @property CarbonInterface|null $pushed_at
 * @property CarbonInterface|null $synced_at
 * @property int|null $created_by
 */
class ChannelPromotion extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUSHING = 'pushing';

    public const STATUS_LIVE = 'live';

    public const STATUS_ENDED = 'ended';

    public const STATUS_FAILED = 'failed';

    public const DISCOUNT_PERCENT = 'percent';

    public const DISCOUNT_FIXED = 'fixed';

    protected $fillable = [
        'tenant_id', 'channel_account_id', 'provider', 'external_promotion_id', 'title',
        'discount_type', 'starts_at', 'ends_at', 'status', 'source', 'last_error',
        'pushed_at', 'synced_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'pushed_at' => 'datetime',
            'synced_at' => 'datetime',
            'last_error' => 'array',
        ];
    }

    /** @return HasMany<ChannelPromotionSku, $this> */
    public function skus(): HasMany
    {
        return $this->hasMany(ChannelPromotionSku::class, 'promotion_id');
    }

    /** Trạng thái "đang chiếm SKU" (khoá SKU khỏi chương trình khác): live/pushing/draft chưa kết thúc. */
    public function occupiesSkus(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PUSHING, self::STATUS_LIVE], true);
    }
}
