<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * A tenant-scoped role: a named set of permission strings. The built-in `owner`
 * role (is_owner) bypasses every check; everything else grants exactly the
 * permissions in its list. See SPEC 0031.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property list<string> $permissions
 * @property bool $is_owner
 * @property bool $is_system
 */
class TenantRole extends Model
{
    use BelongsToTenant;

    protected $table = 'roles';

    protected $fillable = ['tenant_id', 'name', 'permissions', 'is_owner', 'is_system'];

    protected $casts = [
        'permissions' => 'array',
        'is_owner' => 'boolean',
        'is_system' => 'boolean',
    ];

    /** Does this role grant the given ability? Owner role grants everything. */
    public function grants(string $ability): bool
    {
        if ($this->is_owner) {
            return true;
        }

        $perms = (array) $this->permissions;

        return in_array('*', $perms, true) || in_array($ability, $perms, true);
    }
}
