<?php

namespace CMBcoreSeller\Modules\Notifications\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        if (! $request->hasValidSignature()) {
            return redirect()->away($invalidUrl);
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
