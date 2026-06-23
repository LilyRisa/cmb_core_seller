<?php

namespace CMBcoreSeller\Modules\Notifications\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Email verification endpoints (SPEC 0022).
 *
 * - GET  /api/v1/auth/email/verify/{id}/{hash}  — signed URL; redirect SPA.
 * - POST /api/v1/auth/email/verify/resend       — resend (auth:sanctum, throttle 6/60).
 */
class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $redirectBase = rtrim((string) config('notifications.frontend_url', config('app.url')), '/');
        $successUrl = $redirectBase.'/email-verified?status=success';
        $alreadyUrl = $redirectBase.'/email-verified?status=already';
        $invalidUrl = $redirectBase.'/email-verified?status=invalid';
        $expiredUrl = $redirectBase.'/email-verified?status=expired';

        if (! $request->hasValidSignature()) {
            // Tách "hết hạn" (chữ ký đúng, quá expires) khỏi "sai chữ ký" để UX rõ ràng
            // và để log lộ ra sự cố proxy (scheme=http khi đáng lẽ https ⇒ chữ ký luôn sai).
            $expiresTs = (int) $request->query('expires');
            $expired = $expiresTs > 0 && Carbon::now()->getTimestamp() > $expiresTs;

            Log::warning('auth.email.verify.signature_failed', [
                'reason' => $expired ? 'expired' : 'bad_signature',
                'user_id' => $id,
                'scheme' => $request->getScheme(),
                'host' => $request->getHost(),
                'secure' => $request->isSecure(),
            ]);

            return redirect()->away($expired ? $expiredUrl : $invalidUrl);
        }

        /** @var User|null $user */
        $user = User::query()->find($id);

        if (! $user) {
            return redirect()->away($invalidUrl);
        }

        if (! hash_equals(sha1((string) $user->getEmailForVerification()), (string) $hash)) {
            return redirect()->away($invalidUrl);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->away($alreadyUrl);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->away($successUrl);
    }

    public function resend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'data' => ['sent' => false, 'reason' => 'already_verified'],
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['data' => ['sent' => true]]);
    }
}
