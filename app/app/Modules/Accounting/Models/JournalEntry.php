<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bút toán nhật ký (bất biến). Phase 7.1 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property Carbon $posted_at
 * @property int $period_id
 * @property string|null $narration
 * @property string $source_module
 * @property string $source_type
 * @property int|null $source_id
 * @property string $idempotency_key
 * @property bool $is_adjustment
 * @property int|null $is_reversal_of_id
 * @property int|null $adjusted_period_id
 * @property int $total_debit
 * @property int $total_credit
 * @property string $currency
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read FiscalPeriod $period
 * @property-read Collection<int, JournalLine> $lines
 * @property-read JournalEntry|null $reversalOf
 */
class JournalEntry extends Model
{
    use BelongsToTenant;

    /** Không cập nhật updated_at — entry bất biến. */
    public const UPDATED_AT = null;

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_OPENING = 'opening';

    protected $fillable = [
        'tenant_id', 'code', 'posted_at', 'period_id', 'narration',
        'source_module', 'source_type', 'source_id', 'idempotency_key',
        'is_adjustment', 'is_reversal_of_id', 'adjusted_period_id',
        'total_debit', 'total_credit', 'currency', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'created_at' => 'datetime',
            'is_adjustment' => 'boolean',
            'total_debit' => 'integer',
            'total_credit' => 'integer',
            'period_id' => 'integer',
            'is_reversal_of_id' => 'integer',
            'adjusted_period_id' => 'integer',
            'source_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'entry_id')->orderBy('line_no');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'is_reversal_of_id');
    }
}
