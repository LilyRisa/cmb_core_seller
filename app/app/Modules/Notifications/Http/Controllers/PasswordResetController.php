<?php

namespace CMBcoreSeller\Modules\Notifications\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Password reset endpoints (SPEC 0022 §3.3).
 *
 * - POST /api/v1/auth/password/forgot — generic response chống enumerate email.
 * - POST /api/v1/auth/password/reset  — submit token + password mới.
 */
class PasswordResetController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Password::sendResetLink trả status; lỗi (email không tồn tại, throttle...)
        // ta KHÔNG lộ ra — phản hồi luôn generic để chống enumerate (OWASP).
        Password::broker()->sendResetLink(['email' => $data['email']]);

        return response()->json(['data' => ['sent' => true]]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker()->reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['data' => ['reset' => true]]);
        }

        return response()->json([
            'error' => [
                'code' => 'INVALID_RESET_TOKEN',
                'message' => 'Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
            ],
        ], 422);
    }
}
