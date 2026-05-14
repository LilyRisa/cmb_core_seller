<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Thông tin xuất hoá đơn của tenant. 1-1 với tenant.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string|null $company_name
 * @property string|null $tax_code
 * @property string|null $billing_address
 * @property string|null $contact_email
 * @property string|null $contact_phone
 */
class BillingProfile extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'company_name', 'tax_code', 'billing_address', 'contact_email', 'contact_phone'];
}
