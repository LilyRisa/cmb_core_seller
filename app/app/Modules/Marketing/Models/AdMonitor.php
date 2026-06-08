<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Auto-rule for one campaign/adset: raise budget when cost-per-result is cheap,
 * pause when it's too expensive. Evaluated in the background every 30'.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $ad_account_id
 * @property string $target_level
 * @property string $target_external_id
 * @property bool $enabled
 * @property bool $increase_enabled
 * @property ?int $increase_below
 * @property int $increase_step_pct
 * @property ?int $max_daily_budget
 * @property bool $pause_enabled
 * @property ?int $pause_above
 * @property int $min_results
 * @property ?Carbon $last_evaluated_at
 * @property ?string $last_action
 * @property ?Carbon $last_action_at
 * @property ?int $created_by
 */
class AdMonitor extends Model
{
    use BelongsToTenant;

    public const LEVEL_CAMPAIGN = 'campaign';

    public const LEVEL_ADSET = 'adset';

    /** SPEC 0036 — ngưỡng "sắp đạt mức cần tắt": chi phí/kết quả ≥ 80% pause_above ⇒ cảnh báo. */
    public const APPROACHING_RATIO = 0.8;

    protected $fillable = [
        'tenant_id', 'ad_account_id', 'target_level', 'target_external_id', 'enabled',
        'increase_enabled', 'increase_below', 'increase_step_pct', 'max_daily_budget',
        'pause_enabled', 'pause_above', 'min_results', 'last_evaluated_at', 'last_action', 'last_action_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'increase_enabled' => 'boolean',
            'pause_enabled' => 'boolean',
            'increase_below' => 'integer',
            'increase_step_pct' => 'integer',
            'max_daily_budget' => 'integer',
            'pause_above' => 'integer',
            'min_results' => 'integer',
            'last_evaluated_at' => 'datetime',
            'last_action_at' => 'datetime',
        ];
    }
}
