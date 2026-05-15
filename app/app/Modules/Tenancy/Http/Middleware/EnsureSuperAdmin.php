<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SPEC 0020 — chỉ user có `users.is_super_admin=true` mới đi qua. Áp lên route `/api/v1/admin/*`.
 * KHÔNG yêu cầu tenant (admin xuyên suốt mọi tenant).
 * Phải chạy SAU `auth:sanctum`.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Chưa đăng nhập.']], 401);
        }
        if (! $user->isSuperAdmin()) {
            return response()->json(['error' => [
                'code' => 'SUPER_ADMIN_REQUIRED',
                'message' => 'Chỉ admin hệ thống mới có quyền truy cập.',
            ]], 403);
        }

        return $next($request);
    }
}
