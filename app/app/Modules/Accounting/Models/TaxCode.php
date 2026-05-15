<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Bảng mã VAT. Phase 7.5 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property int $rate_bps
 * @property string $kind
 * @property string $gl_account_code
 * @property bool $is_active
 */
class TaxCode extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'code', 'name', 'rate_bps', 'kind', 'gl_account_code', 'is_active'];

    protected function casts(): array
    {
        return [
            'rate_bps' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function rate(): float
    {
        return $this->rate_bps / 100.0; // percent
    }
}
