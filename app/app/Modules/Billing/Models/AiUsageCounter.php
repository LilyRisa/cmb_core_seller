<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Bộ đếm lượt gọi AI theo (tenant, user, tháng, tính năng). user_id=0 ⇒ hệ thống/auto.
 *
 * @property int $tenant_id
 * @property int $user_id
 * @property int $period_ym
 * @property string $feature
 * @property int $count
 */
class AiUsageCounter extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'period_ym', 'feature', 'count'];

    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'period_ym' => 'integer',
        'count' => 'integer',
    ];
}
