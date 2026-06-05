<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A saved snapshot of one report run (level + date range + filters + rows).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $ad_account_id
 * @property ?int $created_by
 * @property string $name
 * @property string $level
 * @property Carbon $since
 * @property Carbon $until
 * @property ?string $currency
 * @property array<string,mixed> $filters
 * @property array<int,array<string,mixed>> $snapshot
 * @property ?Carbon $created_at
 */
class SavedReport extends Model
{
    use BelongsToTenant;

    protected $table = 'marketing_saved_reports';

    protected $fillable = ['tenant_id', 'ad_account_id', 'created_by', 'name', 'level', 'since', 'until', 'currency', 'filters', 'snapshot'];

    protected function casts(): array
    {
        return ['since' => 'date', 'until' => 'date', 'filters' => 'array', 'snapshot' => 'array'];
    }
}
