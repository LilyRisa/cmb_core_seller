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
            $role = $pivot instanceof Model ? $pivot->getAttribute('role') : null;

            return [
                'id' => $tenant->getKey(),
                'name' => $tenant->getAttribute('name'),
                'slug' => $tenant->getAttribute('slug'),
                // Spec 2026-05-17 — super-admin đã tách bảng `admin_users`; user thường không bao giờ là super-admin.
                'role' => $role instanceof Role ? $role->value : $role,
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
}
