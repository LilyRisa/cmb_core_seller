<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $tenant_id
 * @property int $user_id
 * @property string|null $role legacy preset key, kept for display/compat
 * @property int|null $role_id FK → roles (new source of truth, SPEC 0031)
 * @property-read User|null $user
 * @property-read Tenant|null $tenant
 * @property-read TenantRole|null $tenantRole
 */
class TenantUser extends Pivot
{
    protected $table = 'tenant_user';

    public $incrementing = true;

    protected $casts = [
        'role_id' => 'integer',
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

    /** The custom role granting this member's permissions. */
    public function tenantRole(): BelongsTo
    {
        return $this->belongsTo(TenantRole::class, 'role_id');
    }
}
