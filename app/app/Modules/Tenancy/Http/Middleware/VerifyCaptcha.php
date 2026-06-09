<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Middleware;

use Closure;
use CMBcoreSeller\Modules\Tenancy\Services\CaptchaVerifier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chặn bot/brute-force ở form công khai (register/login/forgot) — SPEC 2026-06-10.
 * Token từ body `captcha_token` hoặc header `cf-turnstile-response`. Fail ⇒
 * `422 CAPTCHA_FAILED`. `captcha.enabled=false` ⇒ pass-through (CaptchaVerifier).
 */
class VerifyCaptcha
{
    public function __construct(private CaptchaVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->verifier->enabled()) {
            return $next($request);
        }

        $token = (string) ($request->input('captcha_token')
            ?: $request->header('cf-turnstile-response', ''));

        if (! $this->verifier->verify($token, $request->ip())) {
            return response()->json([
                'error' => [
                    'code' => 'CAPTCHA_FAILED',
                    'message' => 'Xác minh chống bot thất bại. Vui lòng thử lại.',
                ],
            ], 422);
        }

        return $next($request);
    }
}
