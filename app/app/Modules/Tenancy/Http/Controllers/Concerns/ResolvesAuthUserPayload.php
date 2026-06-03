<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers\Concerns;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use Illuminate\Database\Eloquent\Model;

/**
 * Hồ sơ user chuẩn trả cho client auth (SPA cookie lẫn mobile bearer token).
 * Dùng chung giữa AuthController (SPA) và TokenAuthController (mobile) — 1 nguồn
 * duy nhất cho shape `data.user`.
 *
 * Truy cập cột/pivot qua getAttribute/getRelation (trả mixed) thay vì property
 * động ($t->name, $t->pivot) để phpstan level 5 sạch — không cần baseline.
 */
trait ResolvesAuthUserPayload
{
    /**
     * @return array<string, mixed>
     */
    protected function userPayload(User $user): array
    {
        $user->load('tenants');

        $tenants = $user->tenants->map(function (Model $tenant): array {
            $pivot = $tenant->getRelation('pivot');
            $rawRole = $pivot instanceof Model ? $pivot->getAttribute('role') : null;

            $role = $rawRole instanceof Role
                ? $rawRole
                : (is_string($rawRole) ? Role::tryFrom($rawRole) : null);

            return [
                'id' => $tenant->getKey(),
                'name' => $tenant->getAttribute('name'),
                'slug' => $tenant->getAttribute('slug'),
                // Spec 2026-05-17 — super-admin đã tách bảng `admin_users`; user thường không bao giờ là super-admin.
                'role' => $role instanceof Role ? $role->value : $rawRole,
                // SPEC 0029 (mobile) — ability strings của role trong tenant này; mobile dùng
                // để ẩn/hiện chức năng. Lấy từ Role::permissions() (1 nguồn — không hardcode).
                'permissions' => $role instanceof Role ? $this->resolveTenantPermissions($role) : [],
            ];
        })->all();

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => optional($user->email_verified_at)->toIso8601String(), // SPEC 0022 — FE hiện banner nếu null.
            'tenants' => $tenants,
        ];
    }

    /**
     * Ability strings cho 1 role, suy từ Role::permissions() (single source of truth).
     *
     * - Owner/Admin (chứa '*') ⇒ trả `['*']` (mobile hiểu là toàn quyền).
     * - Role hạt mịn ⇒ trả danh sách quyền tường minh, BỎ chuỗi phủ định ('!...')
     *   vì mobile chỉ cần các quyền được CẤP để bật/tắt UI.
     *
     * @return list<string>
     */
    private function resolveTenantPermissions(Role $role): array
    {
        $perms = $role->permissions();

        if (in_array('*', $perms, true)) {
            return ['*'];
        }

        return array_values(array_filter(
            $perms,
            static fn (string $p): bool => ! str_starts_with($p, '!'),
        ));
    }
}
