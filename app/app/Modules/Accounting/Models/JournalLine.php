<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Dòng bút toán (bất biến). Phase 7.1 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $entry_id
 * @property Carbon $posted_at
 * @property int $account_id
 * @property string $account_code
 * @property int $dr_amount
 * @property int $cr_amount
 * @property string|null $party_type
 * @property int|null $party_id
 * @property int|null $dim_warehouse_id
 * @property int|null $dim_shop_id
 * @property int|null $dim_sku_id
 * @property int|null $dim_order_id
 * @property string|null $dim_tax_code
 * @property string|null $memo
 * @property int $line_no
 * @property-read ChartAccount $account
 * @property-read JournalEntry $entry
 */
class JournalLine extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    public const PARTY_CUSTOMER = 'customer';
    public const PARTY_SUPPLIER = 'supplier';
    public const PARTY_STAFF = 'staff';
    public const PARTY_CHANNEL = 'channel';

    protected $fillable = [
        'tenant_id', 'entry_id', 'posted_at',
        'account_id', 'account_code', 'dr_amount', 'cr_amount',
        'party_type', 'party_id',
        'dim_warehouse_id', 'dim_shop_id', 'dim_sku_id', 'dim_order_id', 'dim_tax_code',
        'memo', 'line_no',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'dr_amount' => 'integer',
            'cr_amount' => 'integer',
            'account_id' => 'integer',
            'party_id' => 'integer',
            'dim_warehouse_id' => 'integer',
            'dim_shop_id' => 'integer',
            'dim_sku_id' => 'integer',
            'dim_order_id' => 'integer',
            'line_no' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'account_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'entry_id');
    }
}
