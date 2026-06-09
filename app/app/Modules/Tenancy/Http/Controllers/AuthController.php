<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Events\TenantCreated;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\Concerns\ResolvesAuthUserPayload;
use CMBcoreSeller\Modules\Tenancy\Http\Controllers\Concerns\ResolvesLoginIdentifier;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Rules\NotDisposableEmail;
use CMBcoreSeller\Modules\Tenancy\Services\TenantRoleProvisioner;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * SPA cookie-based auth (Sanctum stateful). See docs/05-api/conventions.md §2
 * and docs/05-api/webhooks-and-oauth.md.
 */
class AuthController extends Controller
{
    use ResolvesAuthUserPayload, ResolvesLoginIdentifier;

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', new NotDisposableEmail],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'tenant_name' => ['nullable', 'string', 'max:255'],
        ]);

        [$user, $tenant] = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $tenant = Tenant::create(['name' => $data['tenant_name'] ?? ($data['name'].' Shop')]);
            $roles = app(TenantRoleProvisioner::class)->seedDefaults($tenant);
            $tenant->users()->attach($user->getKey(), [
                'role' => Role::Owner->value,
                'role_id' => $roles[Role::Owner->value]->getKey(),
            ]);

            return [$user, $tenant];
        });

        // Tenant tạo xong ⇒ phát event để Billing khởi động trial 14 ngày (SPEC 0018 §3.1).
        TenantCreated::dispatch($tenant);

        // SPEC 0022 — fire `Registered` event ⇒ Laravel listener `SendEmailVerificationNotification`
        // tự gọi `$user->sendEmailVerificationNotification()` (override ở User dùng
        // `VerifyEmailNotification` branded). Notification implement ShouldQueue ⇒
        // enqueue vào queue `notifications`.
        event(new Registered($user));

        $this->startSession($request, $user);

        return response()->json(['data' => $this->userPayload($user)], 201);
    }

    /** [public] Cấu hình CAPTCHA cho FE render widget (site_key không nhạy cảm). */
    public function captchaConfig(): JsonResponse
    {
        return response()->json(['data' => [
            'enabled' => (bool) config('captcha.enabled', false),
            'provider' => (string) config('captcha.provider', 'turnstile'),
            'site_key' => (string) config('captcha.site_key', ''),
        ]]);
    }

    public function login(Request $request): JsonResponse
    {
        // `login` accepts an email or a sub-account username; `email` kept for back-compat.
        $data = $request->validate([
            'login' => ['required_without:email', 'string', 'max:255'],
            'email' => ['required_without:login', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $user = $this->resolveLoginUser((string) ($data['login'] ?? $data['email'] ?? ''));
        if (! $user || ! Hash::check($data['password'], (string) $user->password)) {
            return response()->json([
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Tài khoản hoặc mật khẩu không đúng.'],
            ], 422);
        }

        $this->startSession($request, $user, (bool) ($data['remember'] ?? false));

        return response()->json(['data' => $this->userPayload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(null, 204);
    }

    /** Log the user into the web (session) guard when a session exists (SPA flow). */
    protected function startSession(Request $request, User $user, bool $remember = false): void
    {
        if (! $request->hasSession()) {
            return;
        }

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userPayload($request->user())]);
    }

    /** PATCH /api/v1/auth/profile — update own name / email / password. See SPEC 0011. */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->getKey()],
            'current_password' => ['required_with:password,email', 'nullable', 'string'],
            'password' => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);
        if ((isset($data['password']) || isset($data['email'])) && ! Hash::check((string) ($data['current_password'] ?? ''), (string) $user->password)) {
            return response()->json(['error' => ['code' => 'INVALID_PASSWORD', 'message' => 'Mật khẩu hiện tại không đúng.']], 422);
        }
        $update = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'password' => isset($data['password']) ? Hash::make($data['password']) : null,
        ], fn ($v) => $v !== null);
        $user->forceFill($update)->save();

        return response()->json(['data' => $this->userPayload($user->fresh())]);
    }
}
