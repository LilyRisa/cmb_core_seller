<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Đếm hạn mức theo (tenant, metric, period). V1 chỉ 1 metric `channel_accounts`.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $metric
 * @property string $period
 * @property int $value
 */
class UsageCounter extends Model
{
    use BelongsToTenant;

    public const METRIC_CHANNEL_ACCOUNTS = 'channel_accounts';

    public const PERIOD_CURRENT = 'current';

    protected $fillable = ['tenant_id', 'metric', 'period', 'value', 'last_updated_at'];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'last_updated_at' => 'datetime',
        ];
    }
}
