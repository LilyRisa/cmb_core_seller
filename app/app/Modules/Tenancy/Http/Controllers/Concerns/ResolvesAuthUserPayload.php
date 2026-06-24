<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers\Concerns;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\TenantRole;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\Tenancy\Services\UserPreferenceService;
use Illuminate\Database\Eloquent\Model;

/**
 * Hồ sơ user chuẩn trả cho client auth (SPA cookie lẫn mobile bearer token).
 * Dùng chung giữa AuthController (SPA) và TokenAuthController (mobile) — 1 nguồn
 * duy nhất cho shape `data.user`.
 *
 * SPEC 0031 — mỗi tenant trả thêm `code` (mã shop), `role_id`/`role_name` (custom
 * role) và `permissions[]`. Giữ `role` (string preset key) để FE cũ không gãy.
 */
trait ResolvesAuthUserPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function userPayload(User $user): array
    {
        $user->load('tenants');

        // Resolve membership roles in one cross-tenant query (no tenant scope).
        $roleIds = $user->tenants
            ->map(fn (Model $t) => ($p = $t->getRelation('pivot')) instanceof Model ? $p->getAttribute('role_id') : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $roles = TenantRole::withoutGlobalScope(TenantScope::class)->whereIn('id', $roleIds)->get()->keyBy('id');

        $tenants = $user->tenants->map(function (Model $tenant) use ($roles): array {
            $pivot = $tenant->getRelation('pivot');
            $rawRole = $pivot instanceof Model ? $pivot->getAttribute('role') : null;
            $roleId = $pivot instanceof Model ? $pivot->getAttribute('role_id') : null;
            /** @var TenantRole|null $role */
            $role = $roleId !== null ? $roles->get($roleId) : null;

            // Fallback to the legacy enum when a membership has no mapped role_id yet.
            $enum = $role === null && is_string($rawRole) ? Role::tryFrom($rawRole) : null;

            return [
                'id' => $tenant->getKey(),
                'name' => $tenant->getAttribute('name'),
                'slug' => $tenant->getAttribute('slug'),
                'code' => $tenant->getAttribute('code'),
                // Legacy preset key kept for backward-compat; prefer role_id/role_name.
                'role' => $rawRole,
                'role_id' => $role?->getKey(),
                'role_name' => $role !== null ? $role->name : $enum?->label(),
                'permissions' => $role !== null ? $this->rolePermissions($role) : $this->legacyPermissions($enum),
            ];
        })->all();

        /** @var UserPreferenceService $prefsSvc */
        $prefsSvc = app(UserPreferenceService::class);
        $preferences = UserPreferenceService::shape($prefsSvc->all((int) $user->getKey()));

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->getAttribute('username'),
            'email_verified_at' => optional($user->email_verified_at)->toIso8601String(), // SPEC 0022 — FE hiện banner nếu null.
            'tenants' => $tenants,
            'preferences' => $preferences,
        ];
    }

    /**
     * Ability strings a role grants. Owner ⇒ `['*']` (mobile hiểu là toàn quyền).
     *
     * @return list<string>
     */
    private function rolePermissions(TenantRole $role): array
    {
        if ($role->is_owner) {
            return ['*'];
        }

        $perms = array_values(array_filter((array) $role->permissions, 'is_string'));

        return in_array('*', $perms, true) ? ['*'] : $perms;
    }

    /**
     * Legacy permission resolution from the Role enum (memberships without a role_id).
     *
     * @return list<string>
     */
    private function legacyPermissions(?Role $role): array
    {
        if ($role === null) {
            return [];
        }

        $perms = $role->permissions();
        if (in_array('*', $perms, true)) {
            return ['*'];
        }

        return array_values(array_filter($perms, static fn (string $p): bool => ! str_starts_with($p, '!')));
    }
}
