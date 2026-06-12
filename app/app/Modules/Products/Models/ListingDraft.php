<?php

namespace CMBcoreSeller\Modules\Products\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A draft product to be published (or already live) on a marketplace channel.
 *
 * Distinct from {@see ChannelListing} which is the inventory-stock-sync entity
 * for marketplace SKUs that already exist. ListingDraft drives the create/update
 * product publishing flow. See SPEC (marketplace-product-publishing).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $product_id
 * @property int $channel_account_id
 * @property string $provider
 * @property string|null $external_item_id
 * @property string|null $category_id
 * @property string|null $brand_id
 * @property array|null $attributes
 * @property array|null $media_refs
 * @property array|null $logistics
 * @property string $status
 * @property array|null $validation_errors
 * @property string|null $raw_qc_status
 * @property array|null $last_error
 * @property Carbon|null $pushed_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, ListingDraftSku> $skus
 * @property-read Product|null $product
 */
class ListingDraft extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_READY = 'ready';

    public const STATUS_PUSHING = 'pushing';

    /** Đã đẩy lên sàn, đang chờ sàn xét duyệt (QC). Cập nhật qua webhook/poll product_update. */
    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_LIVE = 'live';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'media_refs' => 'array',
            'logistics' => 'array',
            'validation_errors' => 'array',
            'last_error' => 'array',
            'pushed_at' => 'datetime',
        ];
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ListingDraftSku::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
