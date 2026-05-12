<?php

namespace CMBcoreSeller\Modules\Orders\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An order from any source. Money fields are bigint VND đồng. `status` is the
 * canonical code; `raw_status` is what the channel reported.
 * See docs/03-domain/order-status-state-machine.md.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $source
 * @property int|null $channel_account_id
 * @property int|null $customer_id
 * @property string|null $external_order_id
 * @property string|null $order_number
 * @property StandardOrderStatus $status
 * @property string|null $raw_status
 * @property string|null $payment_status
 * @property string|null $buyer_name
 * @property string|null $buyer_phone
 * @property array|null $shipping_address
 * @property string $currency
 * @property int $item_total
 * @property int $shipping_fee
 * @property int $platform_discount
 * @property int $seller_discount
 * @property int $tax
 * @property int $cod_amount
 * @property int $grand_total
 * @property bool $is_cod
 * @property string|null $fulfillment_type
 * @property Carbon|null $placed_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $shipped_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancel_reason
 * @property string|null $note
 * @property array|null $tags
 * @property bool $has_issue
 * @property string|null $issue_reason
 * @property array|null $packages
 * @property array|null $raw_payload
 * @property Carbon|null $source_updated_at
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $items_count
 */
class Order extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'source', 'channel_account_id', 'customer_id', 'external_order_id', 'order_number',
        'status', 'raw_status', 'payment_status', 'buyer_name', 'buyer_phone', 'shipping_address',
        'currency', 'item_total', 'shipping_fee', 'platform_discount', 'seller_discount', 'tax',
        'cod_amount', 'grand_total', 'is_cod', 'fulfillment_type',
        'placed_at', 'paid_at', 'shipped_at', 'delivered_at', 'completed_at', 'cancelled_at', 'cancel_reason',
        'note', 'tags', 'has_issue', 'issue_reason', 'packages', 'raw_payload', 'source_updated_at', 'last_synced_at',
    ];

    protected $hidden = ['buyer_phone', 'raw_payload'];

    protected function casts(): array
    {
        return [
            'status' => StandardOrderStatus::class,
            'customer_id' => 'integer',
            'buyer_phone' => 'encrypted',
            'shipping_address' => 'array',
            'is_cod' => 'boolean',
            'has_issue' => 'boolean',
            'tags' => 'array',
            'packages' => 'array',
            'raw_payload' => 'array',
            'item_total' => 'integer', 'shipping_fee' => 'integer', 'platform_discount' => 'integer',
            'seller_discount' => 'integer', 'tax' => 'integer', 'cod_amount' => 'integer', 'grand_total' => 'integer',
            'placed_at' => 'datetime', 'paid_at' => 'datetime', 'shipped_at' => 'datetime',
            'delivered_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
            'source_updated_at' => 'datetime', 'last_synced_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('changed_at');
    }

    public function channelAccount(): BelongsTo
    {
        // Soft cross-module reference (read-only relationship for eager loading).
        return $this->belongsTo(ChannelAccount::class);
    }

    // --- Query scopes used by GET /api/v1/orders -----------------------------

    public function scopeStatusIn(Builder $q, array $statuses): Builder
    {
        return $q->whereIn('status', $statuses);
    }

    public function scopeSearch(Builder $q, string $term): Builder
    {
        $term = trim($term);

        return $q->where(function (Builder $q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
                ->orWhere('external_order_id', 'like', "%{$term}%")
                ->orWhere('buyer_name', 'like', "%{$term}%");
            // buyer_phone is encrypted (not searchable in SQL) — phone search is a later concern (search engine).
        });
    }

    public function maskedBuyerPhone(): ?string
    {
        $p = $this->buyer_phone;
        if (! $p) {
            return null;
        }
        $len = strlen($p);

        return $len <= 4 ? str_repeat('*', $len) : substr($p, 0, 3).str_repeat('*', max(0, $len - 5)).substr($p, -2);
    }
}
