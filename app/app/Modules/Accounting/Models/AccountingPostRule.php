<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Mapping rule cho auto-post (tenant chỉnh được). Phase 7.1 — SPEC 0019.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $event_key
 * @property string $debit_account_code
 * @property string $credit_account_code
 * @property bool $is_enabled
 * @property string|null $notes
 * @property int|null $updated_by
 */
class AccountingPostRule extends Model
{
    use BelongsToTenant;

    protected $table = 'accounting_post_rules';

    protected $fillable = [
        'tenant_id', 'event_key',
        'debit_account_code', 'credit_account_code',
        'is_enabled', 'notes', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'updated_by' => 'integer',
        ];
    }
}
