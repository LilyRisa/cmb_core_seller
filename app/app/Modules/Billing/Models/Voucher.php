<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Voucher catalog — KHÔNG tenant-scoped (admin tạo). SPEC 0023.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $kind percent|fixed|free_days|plan_upgrade
 * @property int $value % cho percent, VND cho fixed, days cho free_days, plan_id cho plan_upgrade
 * @property array|null $valid_plans
 * @property array|null $valid_tenant_ids null/[] = mọi tenant; nếu có ⇒ chỉ tenant trong danh sách redeem/áp được
 * @property int $max_redemptions -1 = unlimited
 * @property int $redemption_count
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property int|null $created_by_user_id
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Voucher extends Model
{
    public const KIND_PERCENT = 'percent';

    public const KIND_FIXED = 'fixed';

    public const KIND_FREE_DAYS = 'free_days';

    public const KIND_PLAN_UPGRADE = 'plan_upgrade';

    /** SPEC 0032 — tặng số lượt gọi AI (value = số lượt). User tự nhập mã để nhận. */
    public const KIND_AI_CREDITS = 'ai_credits';

    public const KINDS = [self::KIND_PERCENT, self::KIND_FIXED, self::KIND_FREE_DAYS, self::KIND_PLAN_UPGRADE, self::KIND_AI_CREDITS];

    protected $fillable = [
        'code', 'name', 'description', 'kind', 'value',
        'valid_plans', 'valid_tenant_ids', 'max_redemptions', 'redemption_count',
        'starts_at', 'expires_at', 'is_active', 'created_by_user_id', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'valid_plans' => 'array',
            'valid_tenant_ids' => 'array',
            'max_redemptions' => 'integer',
            'redemption_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    public function isRedeemableAtCheckout(): bool
    {
        return in_array($this->kind, [self::KIND_PERCENT, self::KIND_FIXED], true);
    }

    public function isExhausted(): bool
    {
        return $this->max_redemptions >= 0 && $this->redemption_count >= $this->max_redemptions;
    }

    public function isInWindow(?Carbon $at = null): bool
    {
        $at ??= now();
        if ($this->starts_at !== null && $at->lt($this->starts_at)) {
            return false;
        }
        if ($this->expires_at !== null && $at->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    /** Voucher chỉ áp với plan trong `valid_plans`. Null/empty ⇒ mọi plan. */
    public function isValidForPlan(string $planCode): bool
    {
        $plans = $this->valid_plans;
        if (! is_array($plans) || $plans === []) {
            return true;
        }

        return in_array($planCode, $plans, true);
    }

    /** Voucher chỉ áp với tenant trong `valid_tenant_ids`. Null/empty ⇒ mọi tenant. */
    public function isValidForTenant(int $tenantId): bool
    {
        $ids = $this->valid_tenant_ids;
        if (! is_array($ids) || $ids === []) {
            return true;
        }

        return in_array($tenantId, $ids, true);
    }
}
