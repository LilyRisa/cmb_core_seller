<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Automation/write ownership for one Facebook ad account (provider +
 * external_account_id) that may be connected by several tenants. NOT tenant-scoped
 * — it coordinates ACROSS tenants, so no BelongsToTenant.
 *
 * @property int $id
 * @property string $provider
 * @property string $external_account_id
 * @property int $owner_ad_account_id
 * @property int $owner_tenant_id
 */
class AdAccountAutomation extends Model
{
    protected $table = 'ad_account_automation';

    protected $fillable = ['provider', 'external_account_id', 'owner_ad_account_id', 'owner_tenant_id'];
}
