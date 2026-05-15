<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $tenant_id
 * @property int $user_id
 * @property Role $role
 * @property-read User|null $user
 * @property-read Tenant|null $tenant
 */
class TenantUser extends Pivot
{
    protected $table = 'tenant_user';

    public $incrementing = true;

    protected $casts = [
        'role' => Role::class,
        'channel_account_scope' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
