<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Tenancy\Http\Requests\RoleWriteRequest;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\TenantRole;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use CMBcoreSeller\Modules\Tenancy\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Custom roles & the permission catalog (SPEC 0031). Mutations require
 * `team.manage`; the catalog is readable by any member (to render the UI).
 * Route-model binding for {role} is tenant-scoped via TenantRole's global scope.
 */
class RoleController extends Controller
{
    /** GET /api/v1/tenant/permissions — feature-grouped permission catalog. */
    public function permissions(): JsonResponse
    {
        return response()->json(['data' => PermissionCatalog::groups()]);
    }

    /** GET /api/v1/tenant/roles */
    public function index(): JsonResponse
    {
        Gate::authorize('team.manage');

        $roles = TenantRole::query()->orderByDesc('is_owner')->orderBy('name')->get();

        return response()->json(['data' => $roles->map(fn (TenantRole $r) => $this->payload($r))->values()]);
    }

    /** POST /api/v1/tenant/roles */
    public function store(RoleWriteRequest $request): JsonResponse
    {
        Gate::authorize('team.manage');
        $data = $request->validated();

        $role = TenantRole::create([
            'name' => $data['name'],
            'permissions' => array_values(array_unique($data['permissions'])),
            'is_owner' => false,
            'is_system' => false,
        ]);
        AuditLog::record('tenant.role.created', $role, ['name' => $role->name]);

        return response()->json(['data' => $this->payload($role)], 201);
    }

    /** PUT /api/v1/tenant/roles/{role} */
    public function update(RoleWriteRequest $request, int $role): JsonResponse
    {
        Gate::authorize('team.manage');
        $role = TenantRole::query()->findOrFail($role);
        abort_if($role->is_owner, 403, 'Không thể sửa vai trò chủ sở hữu.');
        $data = $request->validated();

        $role->update([
            'name' => $data['name'],
            'permissions' => array_values(array_unique($data['permissions'])),
        ]);
        AuditLog::record('tenant.role.updated', $role, ['name' => $role->name]);

        return response()->json(['data' => $this->payload($role->fresh())]);
    }

    /** DELETE /api/v1/tenant/roles/{role} */
    public function destroy(int $role): JsonResponse
    {
        Gate::authorize('team.manage');
        $role = TenantRole::query()->findOrFail($role);
        abort_if($role->is_owner, 403, 'Không thể xoá vai trò chủ sở hữu.');

        if ($this->membersCount($role) > 0) {
            return response()->json(['error' => [
                'code' => 'ROLE_IN_USE',
                'message' => 'Vai trò đang được gán cho thành viên. Hãy đổi vai trò của họ trước khi xoá.',
            ]], 409);
        }

        $role->delete();
        AuditLog::record('tenant.role.deleted', $role, ['name' => $role->name]);

        return response()->json(null, 204);
    }

    /** @return array<string,mixed> */
    private function payload(TenantRole $role): array
    {
        return [
            'id' => $role->getKey(),
            'name' => $role->name,
            'permissions' => $role->is_owner ? ['*'] : (array) $role->permissions,
            'is_owner' => $role->is_owner,
            'is_system' => $role->is_system,
            'members_count' => $this->membersCount($role),
        ];
    }

    private function membersCount(TenantRole $role): int
    {
        return TenantUser::query()->where('role_id', $role->getKey())->count();
    }
}
