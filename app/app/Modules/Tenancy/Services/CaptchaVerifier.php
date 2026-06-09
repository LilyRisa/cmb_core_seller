<?php

namespace CMBcoreSeller\Modules\Tenancy\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Xác minh CAPTCHA Cloudflare Turnstile (SPEC 2026-06-10). Khi `captcha.enabled`
 * false ⇒ luôn pass (dev/test). Lỗi mạng/timeout ⇒ coi như fail (an toàn — chống bot).
 */
class CaptchaVerifier
{
    public function enabled(): bool
    {
        return (bool) config('captcha.enabled', false)
            && (string) config('captcha.secret', '') !== '';
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }
        $token = (string) $token;
        if ($token === '') {
            return false;
        }

        try {
            $res = Http::timeout(10)->asForm()->post((string) config('captcha.verify_url'), array_filter([
                'secret' => (string) config('captcha.secret'),
                'response' => $token,
                'remoteip' => $ip,
            ]));

            return $res->successful() && (bool) $res->json('success', false);
        } catch (\Throwable $e) {
            Log::warning('captcha.verify_failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
