<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Đăng ký gói của tenant — state machine: trialing → active → past_due → expired (+ cancelled).
 * SPEC 0018 §4.5.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $plan_id
 * @property string $status
 * @property string $billing_cycle
 * @property Carbon|null $trial_ends_at
 * @property Carbon $current_period_start
 * @property Carbon $current_period_end
 * @property Carbon|null $cancel_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $over_quota_warned_at  SPEC 0020 — mốc set khi phát hiện vượt hạn mức.
 * @property array|null $meta
 * @property-read Plan|null $plan
 */
class Subscription extends Model
{
    use BelongsToTenant;

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    /** Các trạng thái coi như "đang dùng" — chiếm slot duy nhất per tenant. */
    public const ALIVE_STATUSES = [self::STATUS_TRIALING, self::STATUS_ACTIVE, self::STATUS_PAST_DUE];

    public const CYCLE_MONTHLY = 'monthly';

    public const CYCLE_YEARLY = 'yearly';

    public const CYCLE_TRIAL = 'trial';

    protected $fillable = [
        'tenant_id', 'plan_id', 'status', 'billing_cycle',
        'trial_ends_at', 'current_period_start', 'current_period_end',
        'cancel_at', 'cancelled_at', 'ended_at', 'over_quota_warned_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'ended_at' => 'datetime',
            'over_quota_warned_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isAlive(): bool
    {
        return in_array($this->status, self::ALIVE_STATUSES, true);
    }

    /** Subscription "dùng được" để gating: trialing | active | past_due (trong grace period). */
    public function isUsable(): bool
    {
        return $this->isAlive();
    }
}
