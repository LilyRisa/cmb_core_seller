<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Một lần đẩy tồn lên sàn (lịch sử). `status` = ok|failed; `error` có khi failed.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $channel_listing_id
 * @property int|null $channel_account_id
 * @property string|null $seller_sku
 * @property string|null $external_sku_id
 * @property int $desired_qty
 * @property string $status
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property-read ChannelAccount|null $channelAccount
 * @property-read ChannelListing|null $channelListing
 */
class StockPushLog extends Model
{
    use BelongsToTenant;

    public const STATUS_OK = 'ok';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['desired_qty' => 'integer'];
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    public function channelListing(): BelongsTo
    {
        return $this->belongsTo(ChannelListing::class);
    }
}
