<?php

namespace CMBcoreSeller\Modules\Notifications\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Override Laravel default `verified` middleware (`EnsureEmailIsVerified`) — mặc
 * định redirect HTML, ta cần JSON envelope (`docs/05-api/conventions.md` §3).
 *
 * Gắn vào group `tenant` ở `routes/api.php` ⇒ chặn user chưa verify access tenant
 * endpoints. Endpoint ngoài (`/auth/me`, `/auth/email/verify/*`, `/tenants`) vẫn
 * cho qua để user thấy banner + bấm resend.
 */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Chưa đăng nhập.'],
            ], 401);
        }

        // SPEC 0022 — `App\Models\User` implements `MustVerifyEmail`. Mọi guard ở
        // app này resolve về cùng model nên không cần check `instanceof`.
        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'error' => [
                    'code' => 'EMAIL_NOT_VERIFIED',
                    'message' => 'Vui lòng xác thực email trước khi sử dụng tính năng này.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
