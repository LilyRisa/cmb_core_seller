<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cash/Bank account. Phase 7.4 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property string $kind
 * @property string|null $bank_name
 * @property string|null $account_no
 * @property string|null $account_holder
 * @property string $currency
 * @property int $gl_account_id
 * @property bool $is_active
 * @property string|null $description
 */
class CashAccount extends Model
{
    use BelongsToTenant;

    public const KIND_CASH = 'cash';
    public const KIND_BANK = 'bank';
    public const KIND_EWALLET = 'ewallet';
    public const KIND_COD_INTRANSIT = 'cod_intransit';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'kind', 'bank_name', 'account_no',
        'account_holder', 'currency', 'gl_account_id', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'gl_account_id' => 'integer',
        ];
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'gl_account_id');
    }
}
