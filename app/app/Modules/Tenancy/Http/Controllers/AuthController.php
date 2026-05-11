<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
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
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'tenant_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $tenant = Tenant::create(['name' => $data['tenant_name'] ?? ($data['name'].' Shop')]);
            $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

            return $user;
        });

        $this->startSession($request, $user);

        return response()->json(['data' => $this->userPayload($user)], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::guard('web')->validate(['email' => $data['email'], 'password' => $data['password']])) {
            return response()->json([
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Email hoặc mật khẩu không đúng.'],
            ], 422);
        }

        /** @var User $user */
        $user = User::where('email', $data['email'])->firstOrFail();

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

    /**
     * @return array<string, mixed>
     */
    protected function userPayload(User $user): array
    {
        $user->load('tenants');

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'tenants' => $user->tenants->map(fn (Tenant $t) => [
                'id' => $t->getKey(),
                'name' => $t->name,
                'slug' => $t->slug,
                'role' => $t->pivot->role instanceof Role ? $t->pivot->role->value : $t->pivot->role,
            ])->values(),
        ];
    }
}
