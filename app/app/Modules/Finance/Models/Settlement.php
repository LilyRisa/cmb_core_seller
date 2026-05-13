<?php

namespace CMBcoreSeller\Modules\Finance\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Đối soát/Statement của 1 gian hàng (Phase 6.2 — SPEC 0016).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_account_id
 * @property string|null $external_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property string $currency
 * @property int $total_payout
 * @property int $total_revenue
 * @property int $total_fee
 * @property int $total_shipping_fee
 * @property string $status
 * @property array|null $raw
 * @property Carbon|null $fetched_at
 * @property Carbon|null $reconciled_at
 * @property Carbon|null $paid_at
 * @property-read ChannelAccount|null $channelAccount
 * @property-read Collection<int, SettlementLine> $lines
 */
class Settlement extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RECONCILED = 'reconciled';

    public const STATUS_ERROR = 'error';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_RECONCILED, self::STATUS_ERROR];

    protected $fillable = [
        'tenant_id', 'channel_account_id', 'external_id', 'period_start', 'period_end',
        'currency', 'total_payout', 'total_revenue', 'total_fee', 'total_shipping_fee',
        'status', 'raw', 'fetched_at', 'reconciled_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime', 'period_end' => 'datetime',
            'fetched_at' => 'datetime', 'reconciled_at' => 'datetime', 'paid_at' => 'datetime',
            'total_payout' => 'integer', 'total_revenue' => 'integer', 'total_fee' => 'integer', 'total_shipping_fee' => 'integer',
            'raw' => 'array',
        ];
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SettlementLine::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Chờ đối chiếu',
            self::STATUS_RECONCILED => 'Đã đối chiếu',
            self::STATUS_ERROR => 'Lỗi',
            default => (string) $this->status,
        };
    }
}
