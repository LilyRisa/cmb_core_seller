<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Middleware;

use Closure;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
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

        // Spec 2026-05-17 — super-admin có thể tạm khoá user qua `/admin/users/{id}/suspend`
        // (set `users.suspended_at`). User bị khoá không vào được tenant routes.
        if (($user->suspended_at ?? null) !== null) {
            return response()->json(['error' => [
                'code' => 'USER_SUSPENDED',
                'message' => 'Tài khoản đã bị tạm khoá. Vui lòng liên hệ hỗ trợ.',
            ]], 403);
        }

        // API key bên thứ 3 (SPEC 2026-06-26): token gắn cứng `tenant_id` ⇒ ép tenant theo token (khóa key
        // đúng shop, BỎ QUA header X-Tenant-Id). Cookie SPA / TransientToken không có tenant_id ⇒ giữ luồng cũ.
        // Chỉ xét token khi request dùng Bearer (token auth) — cookie SPA dùng TransientToken (không có getAttribute).
        $token = $request->bearerToken() ? $user->currentAccessToken() : null;
        $tokenTenantId = $token instanceof PersonalAccessToken ? ($token->getAttribute('tenant_id') ?: null) : null;

        // Header là kênh chính; query param `X-Tenant-Id` cho phép `<a href download>` (browser không gửi
        // header custom khi mở link/blob). Session là fallback cuối (đã thuộc tenant nào ở request trước).
        $tenantId = $tokenTenantId
            ?: $request->header('X-Tenant-Id')
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
