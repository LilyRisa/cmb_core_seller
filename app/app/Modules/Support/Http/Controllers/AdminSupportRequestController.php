<?php

namespace CMBcoreSeller\Modules\Support\Http\Controllers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Support\Models\SupportRequest;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * /api/v1/admin/support-requests — super-admin xem & trả lời yêu cầu CSKH XUYÊN tenant.
 *
 * `SupportRequest` có BelongsToTenant (global scope) ⇒ phải withoutGlobalScope để
 * admin thấy của mọi tenant. Trả kèm nhãn tenant + người gửi.
 */
class AdminSupportRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $query = SupportRequest::query()->withoutGlobalScope(TenantScope::class)->orderByDesc('id');

        if ($status = $request->query('status')) {
            $query->where('status', (string) $status);
        }
        if ($tenantId = $request->query('tenant_id')) {
            $query->where('tenant_id', (int) $tenantId);
        }
        if ($q = $request->query('q')) {
            $query->where('question', 'like', '%'.$q.'%');
        }

        $page = $query->paginate($perPage);

        $tenantIds = collect($page->items())->pluck('tenant_id')->filter()->unique()->values();
        $userIds = collect($page->items())->pluck('user_id')->filter()->unique()->values();
        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get(['id', 'name', 'slug'])->keyBy('id');
        $users = User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $rows = collect($page->items())->map(fn (SupportRequest $r) => [
            'id' => $r->id,
            'tenant_id' => $r->tenant_id,
            'tenant' => $tenants->get($r->tenant_id)
                ? ['id' => $tenants[$r->tenant_id]->id, 'name' => $tenants[$r->tenant_id]->name]
                : null,
            'user' => $r->user_id && $users->get($r->user_id)
                ? ['id' => $users[$r->user_id]->id, 'name' => $users[$r->user_id]->name, 'email' => $users[$r->user_id]->email]
                : null,
            'question' => $r->question,
            'status' => $r->status,
            'answer' => $r->answer,
            'answered_at' => $r->answered_at?->toIso8601String(),
            'created_at' => $r->created_at?->toIso8601String(),
        ])->all();

        return response()->json([
            'data' => $rows,
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    /** Trả lời 1 yêu cầu — set answer + status=answered + audit. */
    public function answer(string $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'answer' => ['required', 'string', 'max:8000'],
        ]);

        $req = SupportRequest::query()->withoutGlobalScope(TenantScope::class)->findOrFail((int) $id);

        $req->forceFill([
            'answer' => (string) $data['answer'],
            'status' => SupportRequest::STATUS_ANSWERED,
            'answered_by' => Auth::guard('admin_web')->id(),
            'answered_at' => now(),
        ])->save();

        AuditLog::record('support.request.answer', null, ['support_request_id' => $req->id, 'tenant_id' => $req->tenant_id]);

        return response()->json(['data' => $this->present($req)]);
    }

    /** Đóng 1 yêu cầu (không cần trả lời). */
    public function close(string $id): JsonResponse
    {
        $req = SupportRequest::query()->withoutGlobalScope(TenantScope::class)->findOrFail((int) $id);
        $req->forceFill(['status' => SupportRequest::STATUS_CLOSED])->save();

        AuditLog::record('support.request.close', null, ['support_request_id' => $req->id, 'tenant_id' => $req->tenant_id]);

        return response()->json(['data' => $this->present($req)]);
    }

    /** @return array<string,mixed> */
    private function present(SupportRequest $r): array
    {
        return [
            'id' => $r->id,
            'status' => $r->status,
            'answer' => $r->answer,
            'answered_at' => $r->answered_at?->toIso8601String(),
        ];
    }
}
