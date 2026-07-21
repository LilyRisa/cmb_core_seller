<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantRole;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Tenant members (SPEC 0031). Add an existing email user, or create an email-less
 * sub-account "{name}@{shop code}" with an owner-set password. Assigning the owner
 * role here is forbidden (ownership transfer is a separate, owner-only flow).
 * All mutations require `team.manage`.
 */
class MemberController extends Controller
{
    /** GET /api/v1/tenant/members */
    public function index(CurrentTenant $current): JsonResponse
    {
        Gate::authorize('team.manage');

        $members = $current->getOrFail()->users()->get()->map(function (Model $u): array {
            $pivot = $u->getRelation('pivot');
            $roleId = $pivot instanceof TenantUser ? $pivot->getAttribute('role_id') : null;

            return $this->memberPayload($u, $roleId !== null ? (int) $roleId : null);
        });

        return response()->json(['data' => $members->values()]);
    }

    /** POST /api/v1/tenant/members — mode=email (existing user) | mode=sub (new sub-account). */
    public function store(Request $request, CurrentTenant $current): JsonResponse
    {
        Gate::authorize('team.manage');
        $tenant = $current->getOrFail();

        return ($request->input('mode') === 'sub')
            ? $this->createSubAccount($request, $tenant)
            : $this->addExisting($request, $tenant);
    }

    /** PUT /api/v1/tenant/members/{user} — change a member's role. */
    public function update(Request $request, CurrentTenant $current, User $user): JsonResponse
    {
        Gate::authorize('team.manage');
        $tenant = $current->getOrFail();
        $membership = $this->membershipOrFail($tenant, $user);

        $roleId = $this->validateAssignableRole($request, $tenant);

        if ($this->isOwnerRole((int) $membership->getAttribute('role_id'))) {
            abort(403, 'Không thể đổi vai trò của chủ sở hữu.');
        }

        $tenant->users()->updateExistingPivot($user->getKey(), [
            'role_id' => $roleId,
            'role' => TenantRole::query()->whereKey($roleId)->value('name'),
        ]);
        AuditLog::record('tenant.member.role_updated', $user, ['role_id' => $roleId]);

        return response()->json(['data' => $this->memberPayload($user, $roleId)]);
    }

    /** DELETE /api/v1/tenant/members/{user} — remove from the tenant. */
    public function destroy(CurrentTenant $current, User $user): JsonResponse
    {
        Gate::authorize('team.manage');
        $tenant = $current->getOrFail();
        $membership = $this->membershipOrFail($tenant, $user);

        if ($this->isOwnerRole((int) $membership->getAttribute('role_id'))) {
            abort(403, 'Không thể gỡ chủ sở hữu khỏi gian hàng.');
        }

        $tenant->users()->detach($user->getKey());
        AuditLog::record('tenant.member.removed', $user, []);

        return response()->json(null, 204);
    }

    /** POST /api/v1/tenant/members/{user}/reset-password — reset a sub-account password. */
    public function resetPassword(Request $request, CurrentTenant $current, User $user): JsonResponse
    {
        Gate::authorize('team.manage');
        $tenant = $current->getOrFail();
        $this->membershipOrFail($tenant, $user);

        if (! $user->getAttribute('is_sub_account')) {
            return response()->json(['error' => [
                'code' => 'NOT_SUB_ACCOUNT',
                'message' => 'Chỉ đặt lại mật khẩu cho tài khoản phụ.',
            ]], 422);
        }

        $data = $request->validate(['password' => ['required', 'string', 'min:6']]);
        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        AuditLog::record('tenant.member.password_reset', $user, []);

        return response()->json(null, 204);
    }

    private function createSubAccount(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'integer'],
        ]);
        $roleId = $this->ensureAssignableRole((int) $data['role_id'], $tenant);

        $username = $this->uniqueUsername($data['name'], (string) $tenant->code);
        $user = User::create([
            'name' => $data['name'],
            'email' => null,
            'username' => $username,
            'password' => Hash::make($data['password']),
            'is_sub_account' => true,
            'created_by_user_id' => $request->user()?->getKey(),
        ]);
        // No email to verify ⇒ mark verified so `verified` middleware lets the sub-account in.
        $user->forceFill(['email_verified_at' => now()])->save();

        $tenant->users()->attach($user->getKey(), [
            'role_id' => $roleId,
            'role' => TenantRole::query()->whereKey($roleId)->value('name'),
        ]);
        AuditLog::record('tenant.member.created_sub', $user, ['username' => $username]);

        return response()->json(['data' => $this->memberPayload($user, $roleId)], 201);
    }

    private function addExisting(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role_id' => ['required', 'integer'],
        ]);
        $roleId = $this->ensureAssignableRole((int) $data['role_id'], $tenant);

        $user = User::query()->where('email', mb_strtolower(trim($data['email'])))->first();
        if (! $user) {
            return response()->json(['error' => [
                'code' => 'USER_NOT_FOUND',
                'message' => 'Chưa có tài khoản với email này. Luồng mời qua email sẽ bổ sung sau.',
            ]], 422);
        }
        if ($tenant->users()->where('users.id', $user->getKey())->exists()) {
            return response()->json(['error' => ['code' => 'ALREADY_MEMBER', 'message' => 'Người này đã là thành viên.']], 409);
        }

        $tenant->users()->attach($user->getKey(), [
            'role_id' => $roleId,
            'role' => TenantRole::query()->whereKey($roleId)->value('name'),
        ]);
        AuditLog::record('tenant.member.added', $user, ['role_id' => $roleId]);

        return response()->json(['data' => $this->memberPayload($user, $roleId)], 201);
    }

    /** Validate a role_id from the request body belongs to the tenant and is assignable. */
    private function validateAssignableRole(Request $request, Tenant $tenant): int
    {
        $data = $request->validate(['role_id' => ['required', 'integer']]);

        return $this->ensureAssignableRole((int) $data['role_id'], $tenant);
    }

    /** The role must belong to this tenant and not be the owner role. */
    private function ensureAssignableRole(int $roleId, Tenant $tenant): int
    {
        $role = TenantRole::query()->whereKey($roleId)->where('tenant_id', $tenant->getKey())->first();
        abort_if($role === null, 422, 'Vai trò không hợp lệ.');
        abort_if($role->is_owner, 422, 'Không thể gán vai trò chủ sở hữu.');

        return $roleId;
    }

    private function isOwnerRole(int $roleId): bool
    {
        return $roleId > 0 && TenantRole::query()->whereKey($roleId)->where('is_owner', true)->exists();
    }

    private function membershipOrFail(Tenant $tenant, User $user): TenantUser
    {
        $membership = TenantUser::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('user_id', $user->getKey())
            ->first();
        abort_if($membership === null, 404, 'Không tìm thấy thành viên trong gian hàng.');

        return $membership;
    }

    private function uniqueUsername(string $name, string $code): string
    {
        $base = preg_replace('/[^a-z0-9._-]/', '', Str::lower($name)) ?: 'user';
        $username = $base.'@'.$code;
        $i = 1;
        while (User::query()->where('username', $username)->exists()) {
            $username = $base.(++$i).'@'.$code;
        }

        return $username;
    }

    /** @return array<string,mixed> */
    private function memberPayload(Model $user, ?int $roleId): array
    {
        $roleName = $roleId !== null ? TenantRole::query()->whereKey($roleId)->value('name') : null;

        return [
            'id' => $user->getKey(),
            'name' => $user->getAttribute('name'),
            'email' => $user->getAttribute('email'),
            'username' => $user->getAttribute('username'),
            'is_sub_account' => (bool) $user->getAttribute('is_sub_account'),
            'role_id' => $roleId,
            'role_name' => $roleName,
        ];
    }
}
