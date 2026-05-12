<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /** GET /api/v1/tenants — tenants the current user belongs to. */
    public function index(Request $request): JsonResponse
    {
        $tenants = $request->user()->tenants()->get()->map(fn (Tenant $t) => [
            'id' => $t->getKey(),
            'name' => $t->name,
            'slug' => $t->slug,
            'status' => $t->status,
            'role' => $t->pivot->role instanceof Role ? $t->pivot->role->value : $t->pivot->role,
        ]);

        return response()->json(['data' => $tenants->values()]);
    }

    /** POST /api/v1/tenants — create a new tenant (caller becomes owner). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $tenant = Tenant::create(['name' => $data['name']]);
        $tenant->users()->attach($request->user()->getKey(), ['role' => Role::Owner->value]);

        return response()->json(['data' => [
            'id' => $tenant->getKey(), 'name' => $tenant->name, 'slug' => $tenant->slug, 'role' => Role::Owner->value,
        ]], 201);
    }

    /** GET /api/v1/tenant — the current tenant (requires X-Tenant-Id). */
    public function show(CurrentTenant $current): JsonResponse
    {
        $tenant = $current->getOrFail();

        return response()->json(['data' => $this->tenantPayload($tenant, $current)]);
    }

    /** PATCH /api/v1/tenant — update workspace info (name / slug / settings). owner/admin only. See SPEC 0011. */
    public function update(Request $request, CurrentTenant $current): JsonResponse
    {
        abort_unless($current->can('tenant.settings'), 403, 'Bạn không có quyền sửa thông tin gian hàng.');
        $tenant = $current->getOrFail();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/', 'unique:tenants,slug,'.$tenant->getKey()],
            'settings' => ['sometimes', 'array'],
        ]);
        if (array_key_exists('settings', $data)) {
            // merge so a partial settings patch doesn't wipe other keys
            $data['settings'] = array_replace((array) ($tenant->settings ?? []), $data['settings']);
        }
        $tenant->forceFill($data)->save();
        AuditLog::record('tenant.updated', $tenant, ['fields' => array_keys($data)]);

        return response()->json(['data' => $this->tenantPayload($tenant->fresh(), $current)]);
    }

    /** @return array<string,mixed> */
    private function tenantPayload(Tenant $tenant, CurrentTenant $current): array
    {
        return [
            'id' => $tenant->getKey(),
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'status' => $tenant->status,
            'settings' => $tenant->settings,
            'current_role' => $current->role()?->value,
        ];
    }

    /** GET /api/v1/tenant/members */
    public function members(Request $request, CurrentTenant $current): JsonResponse
    {
        $this->authorizeManageMembers($current);

        $members = $current->getOrFail()->users()->get()->map(fn (User $u) => [
            'id' => $u->getKey(),
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->pivot->role instanceof Role ? $u->pivot->role->value : $u->pivot->role,
        ]);

        return response()->json(['data' => $members->values()]);
    }

    /**
     * POST /api/v1/tenant/members — attach an existing user as a member.
     * (Email-invitation flow with pending invites is a later slice.)
     */
    public function addMember(Request $request, CurrentTenant $current): JsonResponse
    {
        $this->authorizeManageMembers($current);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string', 'in:'.implode(',', array_map(fn (Role $r) => $r->value, Role::cases()))],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json([
                'error' => ['code' => 'USER_NOT_FOUND', 'message' => 'Chưa có tài khoản với email này. Luồng mời qua email sẽ bổ sung sau.'],
            ], 422);
        }

        $tenant = $current->getOrFail();

        if ($tenant->users()->where('users.id', $user->getKey())->exists()) {
            return response()->json(['error' => ['code' => 'ALREADY_MEMBER', 'message' => 'Người này đã là thành viên.']], 409);
        }

        $tenant->users()->attach($user->getKey(), ['role' => $data['role']]);
        AuditLog::record('tenant.member.added', $user, ['role' => $data['role']]);

        return response()->json(['data' => [
            'id' => $user->getKey(), 'name' => $user->name, 'email' => $user->email, 'role' => $data['role'],
        ]], 201);
    }

    protected function authorizeManageMembers(CurrentTenant $current): void
    {
        abort_unless(
            in_array($current->role(), [Role::Owner, Role::Admin], true),
            403,
            'Chỉ chủ sở hữu / quản trị mới quản lý thành viên.'
        );
    }
}
