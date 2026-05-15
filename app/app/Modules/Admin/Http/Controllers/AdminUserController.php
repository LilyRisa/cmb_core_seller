<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * /api/v1/admin/users — super-admin liệt kê user toàn hệ thống. SPEC 0020.
 */
class AdminUserController extends Controller
{
    /** GET /api/v1/admin/users */
    public function index(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');
        $onlyAdmin = $request->boolean('is_super_admin');
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));

        $query = User::query()->orderByDesc('created_at');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('email', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }
        if ($onlyAdmin) {
            $query->where('is_super_admin', true);
        }
        $page = $query->paginate($perPage);

        $userIds = collect($page->items())->pluck('id')->all();
        $memberships = TenantUser::query()->whereIn('user_id', $userIds)
            ->get()->groupBy('user_id');
        $tenantIds = $memberships->flatten()->pluck('tenant_id')->unique()->all();
        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get()->keyBy('id');

        $rows = collect($page->items())->map(function (User $u) use ($memberships, $tenants) {
            $userTenants = ($memberships->get($u->id) ?? collect())->map(function (TenantUser $m) use ($tenants) {
                $t = $tenants->get($m->tenant_id);

                return $t ? [
                    'id' => $t->id, 'name' => $t->name, 'slug' => $t->slug,
                    'role' => $m->role->value ?? $m->role,
                ] : null;
            })->filter()->values();

            return [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                'is_super_admin' => (bool) $u->is_super_admin,
                'tenants' => $userTenants->all(),
                'created_at' => $u->created_at?->toIso8601String(),
            ];
        })->all();

        return response()->json([
            'data' => $rows,
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }
}
