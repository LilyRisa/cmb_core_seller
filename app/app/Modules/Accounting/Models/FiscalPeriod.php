<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Kỳ kế toán (`open|closed|locked`). Phase 7.1 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code      // 'YYYY-MM' | 'YYYY-Qn' | 'YYYY'
 * @property string $kind      // month|quarter|year
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $status    // open|closed|locked
 * @property Carbon|null $closed_at
 * @property int|null $closed_by
 * @property string|null $close_note
 */
class FiscalPeriod extends Model
{
    use BelongsToTenant;

    public const KIND_MONTH = 'month';
    public const KIND_QUARTER = 'quarter';
    public const KIND_YEAR = 'year';

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_LOCKED = 'locked';

    public const STATUSES = [self::STATUS_OPEN, self::STATUS_CLOSED, self::STATUS_LOCKED];

    protected $fillable = [
        'tenant_id', 'code', 'kind', 'start_date', 'end_date',
        'status', 'closed_at', 'closed_by', 'close_note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
            'closed_by' => 'integer',
        ];
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }
}
