<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * /api/v1/admin/audit-logs — search audit log xuyên tenant (SPEC 0023 §3.8).
 * Filter: action (LIKE), user_id, tenant_id, from/to date, q (LIKE trên changes JSON).
 */
class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $query = AuditLog::query()->withoutGlobalScope(TenantScope::class)->orderByDesc('id');

        if ($action = $request->query('action')) {
            // Allow wildcard `*` (vd `admin.*`).
            $like = str_replace('*', '%', (string) $action);
            $query->where('action', 'like', $like);
        }
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', (int) $userId);
        }
        if ($tenantId = $request->query('tenant_id')) {
            $query->where('tenant_id', (int) $tenantId);
        }
        if ($from = $request->query('from')) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('created_at', '<=', $to);
        }
        if ($q = $request->query('q')) {
            $query->where('changes', 'like', '%'.$q.'%');
        }

        $page = $query->paginate($perPage);

        // Eager-load human-readable user + tenant labels.
        $userIds = collect($page->items())->pluck('user_id')->filter()->unique()->values();
        $tenantIds = collect($page->items())->pluck('tenant_id')->filter()->unique()->values();
        $users = User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');
        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get(['id', 'name', 'slug'])->keyBy('id');

        $rows = collect($page->items())->map(fn (AuditLog $a) => [
            'id' => $a->id,
            'tenant_id' => $a->tenant_id,
            'tenant' => $a->tenant_id ? ($tenants->get($a->tenant_id) ? ['id' => $tenants[$a->tenant_id]->id, 'name' => $tenants[$a->tenant_id]->name, 'slug' => $tenants[$a->tenant_id]->slug] : null) : null,
            'user_id' => $a->user_id,
            'user' => $a->user_id ? ($users->get($a->user_id) ? ['id' => $users[$a->user_id]->id, 'name' => $users[$a->user_id]->name, 'email' => $users[$a->user_id]->email] : null) : null,
            'action' => $a->action,
            'auditable_type' => $a->auditable_type,
            'auditable_id' => $a->auditable_id,
            'changes' => $a->changes,
            'ip' => $a->ip,
            'created_at' => $a->created_at->toIso8601String(),
        ])->all();

        return response()->json([
            'data' => $rows,
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }
}
