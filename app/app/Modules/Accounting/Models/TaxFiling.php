<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tờ khai VAT (01/GTGT-YYYY-MM). Phase 7.5 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property int $period_id
 * @property string $tax_kind
 * @property string $status
 * @property array|null $lines
 * @property int $total_output_vat
 * @property int $total_input_vat
 * @property int $net_payable
 * @property Carbon|null $submitted_at
 * @property int|null $submitted_by
 */
class TaxFiling extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'code', 'period_id', 'tax_kind', 'status',
        'lines', 'total_output_vat', 'total_input_vat', 'net_payable',
        'submitted_at', 'submitted_by',
    ];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
            'total_output_vat' => 'integer',
            'total_input_vat' => 'integer',
            'net_payable' => 'integer',
            'submitted_at' => 'datetime',
            'period_id' => 'integer',
            'submitted_by' => 'integer',
        ];
    }
}
