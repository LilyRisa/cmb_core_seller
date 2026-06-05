<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Services\TenantRoleProvisioner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /** GET /api/v1/tenants — tenants the current user belongs to. */
    public function index(Request $request): JsonResponse
    {
        $tenants = $request->user()->tenants()->get()->map(function (Model $t): array {
            $pivot = $t->getRelation('pivot');

            return [
                'id' => $t->getKey(),
                'name' => $t->getAttribute('name'),
                'slug' => $t->getAttribute('slug'),
                'code' => $t->getAttribute('code'),
                'status' => $t->getAttribute('status'),
                'role' => $pivot instanceof Model ? $pivot->getAttribute('role') : null,
            ];
        });

        return response()->json(['data' => $tenants->values()]);
    }

    /** POST /api/v1/tenants — create a new tenant (caller becomes owner). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $tenant = Tenant::create(['name' => $data['name']]);
        $roles = app(TenantRoleProvisioner::class)->seedDefaults($tenant);
        $tenant->users()->attach($request->user()->getKey(), [
            'role' => Role::Owner->value,
            'role_id' => $roles[Role::Owner->value]->getKey(),
        ]);

        return response()->json(['data' => [
            'id' => $tenant->getKey(), 'name' => $tenant->name, 'slug' => $tenant->slug, 'code' => $tenant->code, 'role' => Role::Owner->value,
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
        $role = $current->roleModel();

        return [
            'id' => $tenant->getKey(),
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'code' => $tenant->code,
            'status' => $tenant->status,
            'settings' => $tenant->settings,
            'current_role' => $role !== null ? $role->name : $current->role()?->value,
            'current_role_id' => $role?->getKey(),
            'can_manage_team' => $current->can('team.manage'),
        ];
    }
}
