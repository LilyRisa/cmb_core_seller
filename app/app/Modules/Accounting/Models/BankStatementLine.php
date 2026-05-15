<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Dòng sao kê (giao dịch). Phase 7.4 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $bank_statement_id
 * @property Carbon $txn_date
 * @property int $amount
 * @property string|null $counter_party
 * @property string|null $memo
 * @property string|null $external_ref
 * @property string $status
 * @property string|null $matched_ref_type
 * @property int|null $matched_ref_id
 * @property int|null $matched_journal_entry_id
 * @property Carbon|null $matched_at
 * @property int|null $matched_by
 */
class BankStatementLine extends Model
{
    use BelongsToTenant;

    public const STATUS_UNMATCHED = 'unmatched';
    public const STATUS_MATCHED = 'matched';
    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'tenant_id', 'bank_statement_id', 'txn_date', 'amount',
        'counter_party', 'memo', 'external_ref', 'status',
        'matched_ref_type', 'matched_ref_id', 'matched_journal_entry_id',
        'matched_at', 'matched_by',
    ];

    protected function casts(): array
    {
        return [
            'txn_date' => 'datetime',
            'matched_at' => 'datetime',
            'amount' => 'integer',
            'matched_ref_id' => 'integer',
            'matched_journal_entry_id' => 'integer',
            'matched_by' => 'integer',
        ];
    }
}
