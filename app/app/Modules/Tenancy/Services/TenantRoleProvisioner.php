<?php

namespace CMBcoreSeller\Modules\Tenancy\Services;

use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantRole;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\Tenancy\Support\RolePresets;

/**
 * Seeds the owner role + editable presets into a freshly created tenant (SPEC 0031).
 * Runs outside the tenant scope (the tenant may not be the "current" one yet).
 */
class TenantRoleProvisioner
{
    /**
     * @return array<string, TenantRole> keyed by legacy preset key (Role enum value)
     */
    public function seedDefaults(Tenant $tenant): array
    {
        $map = [];

        foreach (RolePresets::defaults() as $preset) {
            $role = TenantRole::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                ['tenant_id' => $tenant->getKey(), 'name' => $preset['name']],
                [
                    'permissions' => $preset['permissions'],
                    'is_owner' => $preset['is_owner'],
                    'is_system' => true,
                ],
            );
            $map[$preset['key']] = $role;
        }

        return $map;
    }

    public function ownerRole(Tenant $tenant): TenantRole
    {
        return TenantRole::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->where('is_owner', true)
            ->firstOrFail();
    }
}
