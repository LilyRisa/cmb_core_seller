<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Sao kê. Phase 7.4 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $cash_account_id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property string $imported_from
 * @property int $lines_count
 * @property int $total_in
 * @property int $total_out
 * @property string $status
 * @property int|null $created_by
 */
class BankStatement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'cash_account_id', 'period_start', 'period_end',
        'imported_from', 'lines_count', 'total_in', 'total_out', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'lines_count' => 'integer',
            'total_in' => 'integer',
            'total_out' => 'integer',
            'created_by' => 'integer',
            'cash_account_id' => 'integer',
        ];
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class)->orderBy('txn_date');
    }
}
