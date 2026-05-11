<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantUser extends Pivot
{
    protected $table = 'tenant_user';

    public $incrementing = true;

    protected $casts = [
        'role' => Role::class,
        'channel_account_scope' => 'array',
    ];
}
