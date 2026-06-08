<?php

namespace CMBcoreSeller\Modules\Orders\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An after-sales record (cancel / return / refund) from any channel. Separate from the order's
 * StandardOrderStatus — `status` here is the canonical {@see AfterSalesStatus}. Money is bigint VND đồng.
 * See SPEC 0025.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $channel_account_id
 * @property int|null $order_id
 * @property string $source
 * @property string $external_return_id
 * @property string|null $external_order_id
 * @property string $kind
 * @property AfterSalesStatus $status
 * @property string|null $raw_status
 * @property string|null $reason
 * @property int $refund_amount
 * @property string $currency
 * @property array|null $items
 * @property Carbon|null $requested_at
 * @property Carbon|null $decided_at
 * @property Carbon|null $source_updated_at
 * @property array|null $raw
 * @property-read Order|null $order
 */
class OrderReturn extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'order_returns';

    protected $fillable = [
        'tenant_id', 'channel_account_id', 'order_id', 'source', 'external_return_id', 'external_order_id',
        'kind', 'status', 'raw_status', 'reason', 'refund_amount', 'currency', 'items',
        'requested_at', 'decided_at', 'source_updated_at', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'status' => AfterSalesStatus::class,
            'refund_amount' => 'integer',
            'items' => 'array',
            'raw' => 'array',
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
            'source_updated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    /** @param  Builder<self>  $q */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', [AfterSalesStatus::Requested->value, AfterSalesStatus::Approved->value, AfterSalesStatus::Processing->value]);
    }
}
