<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * `/api/v1/admin/users` — super-admin quản lý tenant user toàn hệ thống.
 *
 * Spec 2026-05-17 — mở rộng từ SPEC 0020 (index có sẵn): thêm show / update /
 * reset-password / suspend / reactivate. Tenant user suspend = set
 * `users.suspended_at` (EnsureTenant middleware chặn).
 */
class AdminUserController extends Controller
{
    /** GET /api/v1/admin/users */
    public function index(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));

        $query = User::query()->orderByDesc('created_at');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('email', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }
        $page = $query->paginate($perPage);

        $userIds = collect($page->items())->pluck('id')->all();
        $memberships = TenantUser::query()->whereIn('user_id', $userIds)
            ->get()->groupBy('user_id');
        $tenantIds = $memberships->flatten()->pluck('tenant_id')->unique()->all();
        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get()->keyBy('id');

        $rows = collect($page->items())->map(function (User $u) use ($memberships, $tenants) {
            return $this->present($u, $memberships, $tenants);
        })->all();

        return response()->json([
            'data' => $rows,
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    /** GET /api/v1/admin/users/{id} */
    public function show(int $id): JsonResponse
    {
        $u = User::query()->findOrFail($id);
        $memberships = TenantUser::query()->where('user_id', $u->id)->get();
        $tenants = Tenant::query()->whereIn('id', $memberships->pluck('tenant_id'))->get()->keyBy('id');

        return response()->json([
            'data' => array_merge($this->present($u, collect([$u->id => $memberships]), $tenants), [
                'email_verified_at' => $u->email_verified_at?->toIso8601String(),
                'suspended_at' => $u->suspended_at?->toIso8601String(),
            ]),
        ]);
    }

    /** PATCH /api/v1/admin/users/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $u = User::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($u->id)],
        ]);
        $u->fill($data)->save();
        AuditLog::record('admin.user.update', $u, ['changes' => $data]);

        return response()->json(['data' => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
        ]]);
    }

    /** POST /api/v1/admin/users/{id}/reset-password */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $u = User::query()->findOrFail($id);
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ]);
        $u->forceFill(['password' => $data['password']])->save();
        AuditLog::record('admin.user.reset_password', $u);

        return response()->json(['data' => ['ok' => true]]);
    }

    /** POST /api/v1/admin/users/{id}/suspend */
    public function suspend(int $id): JsonResponse
    {
        $u = User::query()->findOrFail($id);
        if ($u->suspended_at === null) {
            $u->forceFill(['suspended_at' => now()])->save();
        }
        AuditLog::record('admin.user.suspend', $u);

        return response()->json(['data' => [
            'id' => $u->id,
            'suspended_at' => $u->suspended_at?->toIso8601String(),
        ]]);
    }

    /** POST /api/v1/admin/users/{id}/reactivate */
    public function reactivate(int $id): JsonResponse
    {
        $u = User::query()->findOrFail($id);
        if ($u->suspended_at !== null) {
            $u->forceFill(['suspended_at' => null])->save();
        }
        AuditLog::record('admin.user.reactivate', $u);

        return response()->json(['data' => ['id' => $u->id, 'suspended_at' => null]]);
    }

    /** @return array<string, mixed> */
    private function present(User $u, Collection $memberships, Collection $tenants): array
    {
        $userTenants = ($memberships->get($u->id) ?? collect())->map(function (TenantUser $m) use ($tenants) {
            $t = $tenants->get($m->tenant_id);

            return $t ? [
                'id' => $t->id, 'name' => $t->name, 'slug' => $t->slug,
                'role' => $m->role->value ?? $m->role,
            ] : null;
        })->filter()->values()->all();

        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'tenants' => $userTenants,
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}
