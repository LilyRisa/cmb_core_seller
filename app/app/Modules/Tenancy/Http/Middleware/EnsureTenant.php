<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Middleware;

use Closure;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for the request from the `X-Tenant-Id` header and
 * verifies the authenticated user is a member. Sets CurrentTenant.
 * Apply after `auth:sanctum`.
 */
class EnsureTenant
{
    public function __construct(protected CurrentTenant $currentTenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Chưa đăng nhập.']], 401);
        }

        // Header là kênh chính; query param `X-Tenant-Id` cho phép `<a href download>` (browser không gửi
        // header custom khi mở link/blob). Session là fallback cuối (đã thuộc tenant nào ở request trước).
        $tenantId = $request->header('X-Tenant-Id')
            ?: $request->query('X-Tenant-Id')
            ?: ($request->hasSession() ? $request->session()->get('current_tenant_id') : null);

        if (! $tenantId) {
            return response()->json(['error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Thiếu tenant (header X-Tenant-Id).']], 400);
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()->find($tenantId);

        /** @var TenantUser|null $membership */
        $membership = $tenant
            ? TenantUser::query()->where('tenant_id', $tenant->getKey())->where('user_id', $user->getKey())->first()
            : null;

        if (! $tenant || ! $membership) {
            return response()->json(['error' => ['code' => 'TENANT_FORBIDDEN', 'message' => 'Bạn không thuộc tenant này.']], 403);
        }

        // SPEC 0020 — tenant bị admin tạm khoá (vi phạm điều khoản, hỗ trợ điều tra…).
        // Super-admin vẫn vào `/admin/*` được vì routes admin không qua middleware này.
        if (($tenant->status ?? 'active') === 'suspended') {
            return response()->json(['error' => [
                'code' => 'TENANT_SUSPENDED',
                'message' => 'Gian hàng đang tạm khoá. Vui lòng liên hệ hỗ trợ.',
            ]], 403);
        }

        $this->currentTenant->set($tenant, $membership);

        if ($request->hasSession()) {
            $request->session()->put('current_tenant_id', $tenant->getKey());
        }

        return $next($request);
    }
}
