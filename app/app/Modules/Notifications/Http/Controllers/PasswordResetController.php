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
        // Chuẩn hoá lowercase để khớp User::email đã lưu lowercase — không thì gõ khác hoa/thường
        // lúc "quên mật khẩu" sẽ không bao giờ tìm ra user (dù response vẫn generic nên im lặng).
        $email = mb_strtolower(trim($data['email']));

        // Password::sendResetLink trả status; lỗi (email không tồn tại, throttle...)
        // ta KHÔNG lộ ra — phản hồi luôn generic để chống enumerate (OWASP).
        Password::broker()->sendResetLink(['email' => $email]);

        return response()->json(['data' => ['sent' => true]]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            // Policy đồng bộ với /auth/register: ≥8 ký tự, có chữ hoa + chữ thường + chữ số + ký tự đặc biệt.
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ]);
        // Phải chuẩn hoá GIỐNG HỆT bước forgot() ở trên để broker tìm đúng user/token.
        $data['email'] = mb_strtolower(trim($data['email']));

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
