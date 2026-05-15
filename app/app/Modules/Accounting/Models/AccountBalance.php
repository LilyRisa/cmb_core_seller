<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Số dư tài khoản (theo period × dimensions). Materialized aggregate — rebuild idempotent.
 * Phase 7.1 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $account_id
 * @property int $period_id
 * @property string|null $party_type
 * @property int|null $party_id
 * @property int|null $dim_warehouse_id
 * @property int|null $dim_shop_id
 * @property int $opening
 * @property int $debit
 * @property int $credit
 * @property int $closing
 * @property Carbon|null $recomputed_at
 */
class AccountBalance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'account_id', 'period_id',
        'party_type', 'party_id', 'dim_warehouse_id', 'dim_shop_id',
        'opening', 'debit', 'credit', 'closing', 'recomputed_at',
    ];

    protected function casts(): array
    {
        return [
            'opening' => 'integer',
            'debit' => 'integer',
            'credit' => 'integer',
            'closing' => 'integer',
            'account_id' => 'integer',
            'period_id' => 'integer',
            'party_id' => 'integer',
            'dim_warehouse_id' => 'integer',
            'dim_shop_id' => 'integer',
            'recomputed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'account_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'period_id');
    }
}
