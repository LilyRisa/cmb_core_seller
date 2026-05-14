<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Gói thuê bao — KHÔNG tenant-scoped (catalog chung). SPEC 0018.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property int $price_monthly
 * @property int $price_yearly
 * @property string $currency
 * @property int $trial_days
 * @property array $limits
 * @property array $features
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Plan extends Model
{
    public const CODE_TRIAL = 'trial';

    public const CODE_STARTER = 'starter';

    public const CODE_PRO = 'pro';

    public const CODE_BUSINESS = 'business';

    public const CODES = [self::CODE_TRIAL, self::CODE_STARTER, self::CODE_PRO, self::CODE_BUSINESS];

    protected $fillable = [
        'code', 'name', 'description', 'is_active', 'sort_order',
        'price_monthly', 'price_yearly', 'currency', 'trial_days',
        'limits', 'features',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'trial_days' => 'integer',
            'limits' => 'array',
            'features' => 'array',
        ];
    }

    /** Hạn mức số gian hàng. -1 = không giới hạn. */
    public function maxChannelAccounts(): int
    {
        return (int) ($this->limits['max_channel_accounts'] ?? 0);
    }

    /** Plan có bật feature `$key` (mặc định false nếu thiếu key). */
    public function hasFeature(string $key): bool
    {
        return (bool) ($this->features[$key] ?? false);
    }
}
