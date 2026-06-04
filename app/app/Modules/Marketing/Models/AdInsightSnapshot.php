<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Latest insight metrics per (entity, window, date range). Upserted in place by
 * SyncAdInsights. `is_finalizing` = within FB's 28-day re-attribution window.
 *
 * @property int $tenant_id
 * @property int $ad_account_id
 * @property ?int $ad_entity_id
 * @property string $level
 * @property string $external_id
 * @property string $window
 * @property bool $is_finalizing
 */
class AdInsightSnapshot extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'ad_account_id', 'ad_entity_id', 'level', 'external_id',
        'date_start', 'date_stop', 'window', 'is_finalizing',
        'spend', 'impressions', 'clicks', 'reach', 'ctr', 'cpc', 'cpm', 'frequency', 'purchase_roas',
        'metrics', 'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            // Kept as Y-m-d strings (no datetime cast) so the upsert natural key in
            // SyncAdInsights matches storage exactly (avoids '… 00:00:00' mismatch).
            'is_finalizing' => 'boolean',
            'spend' => 'integer',
            'impressions' => 'integer',
            'clicks' => 'integer',
            'reach' => 'integer',
            'ctr' => 'float',
            'cpc' => 'integer',
            'cpm' => 'integer',
            'frequency' => 'float',
            'purchase_roas' => 'float',
            'metrics' => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
