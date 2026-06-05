<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One thing an auto-monitor did (pause / raise budget). History, reviewable + deletable.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $ad_account_id
 * @property ?int $ad_monitor_id
 * @property string $target_level
 * @property string $target_external_id
 * @property ?string $target_name
 * @property string $type
 * @property ?int $cpr
 * @property ?int $spend
 * @property ?int $results
 * @property ?int $from_budget
 * @property ?int $to_budget
 * @property ?Carbon $created_at
 */
class AdMonitorAction extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'ad_account_id', 'ad_monitor_id', 'target_level', 'target_external_id', 'target_name',
        'type', 'cpr', 'spend', 'results', 'from_budget', 'to_budget',
    ];

    protected function casts(): array
    {
        return [
            'cpr' => 'integer', 'spend' => 'integer', 'results' => 'integer',
            'from_budget' => 'integer', 'to_budget' => 'integer',
        ];
    }
}
