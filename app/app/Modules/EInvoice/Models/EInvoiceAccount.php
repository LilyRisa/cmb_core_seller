<?php

namespace CMBcoreSeller\Modules\EInvoice\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Credentials của một tenant cho một nhà cung cấp HĐĐT (MISA...). `credentials`
 * mã hóa at-rest. Mirror CarrierAccount. SPEC 0041.
 */
class EInvoiceAccount extends Model
{
    use BelongsToTenant;

    protected $table = 'einvoice_accounts';

    protected $fillable = [
        'tenant_id', 'provider', 'name', 'credentials', 'is_invoice_with_code',
        'default_mode', 'templates', 'seller_info', 'auto_issue', 'meta', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_invoice_with_code' => 'boolean',
            'templates' => 'array',
            'seller_info' => 'array',
            'auto_issue' => 'array',
            'meta' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** Shape truyền vào EInvoiceConnector (tham số `$account`). */
    public function toConnectorArray(): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'credentials' => $this->credentials ?? [],
            'meta' => $this->meta ?? [],
        ];
    }
}
