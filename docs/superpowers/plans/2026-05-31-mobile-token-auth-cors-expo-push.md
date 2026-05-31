# Mobile API Access (Token Auth + CORS + Expo Push) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Unlock the CMBcoreSeller backend for the Expo React Native mobile app (Milestone M0) by adding: (1) Sanctum Personal Access Token auth endpoints for mobile login/logout, (2) CORS configuration allowing mobile origins plus `Authorization`/`X-Tenant-Id` headers, and (3) an Expo Push device registry + sender integrated into the existing `messaging:push-digest` flow.

**Architecture:** Modular monolith (`app/app/Modules/<Module>/`). Auth lives in `Tenancy` module; `MobileDevice` model also lives in `Tenancy` (user-owned, no business logic dependency). Expo Push send logic lives in `Messaging` module (extends the existing `WebPushSender`/`PushNewMessageDigest` pattern). Modules communicate only via Contracts/events; `Messaging` calls an `ExpoPushSenderContract` bound in its own service provider. No new cross-module `Service` imports.

**Tech Stack:** Laravel 11, Sanctum PAT (`HasApiTokens` — already present on `User`), PHPUnit Feature tests with `RefreshDatabase`, Expo Push HTTP API (`https://exp.host/--/api/v2/push/send`).

**Reference spec:** `D:\app_mobile_cmbcoreseller\docs\superpowers\specs\2026-05-31-cmb-seller-mobile-app-design.md` §3

---

## File Structure (created / modified)

### Created
```
app/app/Modules/Tenancy/Http/Controllers/MobileAuthController.php
app/app/Modules/Tenancy/Http/Requests/MobileLoginRequest.php
app/app/Modules/Tenancy/Database/Migrations/2026_05_31_100001_create_mobile_devices_table.php
app/app/Modules/Tenancy/Models/MobileDevice.php
app/app/Modules/Tenancy/Http/Controllers/MobileDeviceController.php
app/app/Modules/Tenancy/Http/Requests/RegisterMobileDeviceRequest.php
app/app/Modules/Messaging/Contracts/ExpoPushSenderContract.php
app/app/Modules/Messaging/Services/ExpoPushSender.php
app/app/Modules/Messaging/Jobs/SendExpoPushDigest.php
app/tests/Feature/Tenancy/MobileAuthTest.php
app/tests/Feature/Tenancy/MobileDeviceTest.php
app/tests/Feature/Messaging/ExpoPushDigestTest.php
docs/specs/0029-mobile-api-access.md
```

### Modified
```
app/routes/api.php                             (add mobile-login, mobile-logout, me/devices routes)
app/config/cors.php                            (publish + configure CORS for mobile)
app/app/Modules/Messaging/MessagingServiceProvider.php   (bind ExpoPushSenderContract; register SendExpoPushDigest command)
app/app/Modules/Messaging/Console/Commands/PushNewMessageDigest.php  (extend to also fire Expo push)
docs/05-api/endpoints.md                       (add new endpoints)
```

---

## Task 1: Write spec 0029 and update endpoints.md (docs before code)

**Files:**
- Create: `D:\cmb_core_seller\docs\specs\0029-mobile-api-access.md`
- Modify: `D:\cmb_core_seller\docs\05-api\endpoints.md`

### Steps

- [ ] **Create `docs/specs/0029-mobile-api-access.md`** with the following content:

```markdown
# SPEC 0029: Mobile API Access (Token Auth + CORS + Expo Push)

- **Trạng thái:** Reviewed
- **Phase:** M0 (tiền điều kiện cho mobile milestones M1–M6)
- **Module backend liên quan:** Tenancy (auth + device registry), Messaging (Expo Push)
- **Tác giả / Ngày:** 2026-05-31
- **Liên quan:** `D:\app_mobile_cmbcoreseller\docs\superpowers\specs\2026-05-31-cmb-seller-mobile-app-design.md` §3,
  SPEC-0024 (Web Push baseline), ADR-0007 (webhook+polling), `docs/05-api/conventions.md`

## 1. Vấn đề & mục tiêu

App mobile Expo React Native (repo `D:\app_mobile_cmbcoreseller`) là **client thuần** của
API Laravel hiện có. Hiện tại API chỉ hỗ trợ Sanctum cookie SPA (same-domain React). Mobile
cần: (a) **Bearer token** để xác thực không qua cookie, (b) **CORS** cho origin của Expo dev
server & app scheme, (c) **Expo Push** để nhận thông báo tin nhắn mới khi app nền — tách
biệt hoàn toàn với Web Push VAPID đang có (không đụng vào).

## 2. Trong / ngoài phạm vi

**Trong:**
- `POST /api/v1/auth/mobile-login` — trả Bearer PAT (`personal_access_tokens`).
- `POST /api/v1/auth/mobile-logout` — revoke PAT hiện tại.
- `config/cors.php` — env-driven `CORS_ALLOWED_ORIGINS`, thêm `Authorization`, `X-Tenant-Id`.
- `POST /api/v1/me/devices` + `DELETE /api/v1/me/devices/{id}` — đăng ký/gỡ thiết bị Expo.
- `ExpoPushSender` service + `SendExpoPushDigest` job.
- Tích hợp vào `messaging:push-digest` (gửi Expo push kèm Web push trong cùng một lần chạy).

**Ngoài (làm sau):**
- Đơn mới / đổi trạng thái đơn push (cần thiết kế event `OrderStatusChanged` phù hợp — ghi chú follow-up).
- Refresh token / expiry tự động (v1 dùng expiry `SANCTUM_EXPIRATION=1440` phút mặc định).
- Per-device notification preferences.
- Rate limit riêng cho Expo push endpoint.

## 3. Câu chuyện người dùng

1. Nhân viên kho mở app lần đầu → nhập email + password + tên thiết bị →
   `POST /auth/mobile-login` → nhận token, lưu `SecureStore` → về tab chính.
2. Sau đăng nhập, app đăng ký Expo Push token → `POST /me/devices` → server lưu.
3. App về nền; có tin nhắn mới → `messaging:push-digest` (cron 30') → gửi Expo Push
   → notification xuất hiện trên điện thoại → tap → deep link vào hội thoại.
4. Đăng xuất → `POST /auth/mobile-logout` → token bị revoke; app xoá token khỏi SecureStore.

## 4. Hành vi & quy tắc nghiệp vụ

- Token `abilities` = mảng permissions của role người dùng trong **mọi** tenant họ thuộc
  (abilities gán lúc tạo token; không cần biết tenant tại thời điểm login).
  Ví dụ: Owner → `['*']`; StaffOrder → `['orders.view','orders.update',...]`.
- `device_name` ≤ 255 ký tự; unique key cho token = `(user_id, device_name)` ở bảng
  `personal_access_tokens` (revoke token cũ cùng tên trước khi tạo mới — rotate).
- `MobileDevice.expo_push_token` — unique toàn bảng (thiết bị có thể đổi user → upsert theo
  token, cập nhật `user_id`/`tenant_id`).
- Expo push digest: cùng logic `PushNewMessageDigest` — đếm conversations có
  `last_inbound_at > last_notified_at` → gửi "N người nhắn tin mới"; cập nhật
  `mobile_devices.last_notified_at`. Xử lý token hết hạn (Expo trả `DeviceNotRegistered`)
  → xoá row.
- Idempotency: `POST /me/devices` là upsert theo `expo_push_token` — gọi nhiều lần an toàn.

## 5. Dữ liệu

Bảng mới `mobile_devices`:
| Cột | Kiểu | Mô tả |
|---|---|---|
| `id` | bigint PK | |
| `tenant_id` | bigint index | |
| `user_id` | bigint index | |
| `expo_push_token` | string(255) UNIQUE | Token từ `expo-notifications` |
| `platform` | string(20) | `ios` \| `android` |
| `last_seen_at` | timestamp nullable | Heartbeat (tương lai) |
| `last_notified_at` | timestamp nullable | Mốc lần push gần nhất |
| `created_at/updated_at` | timestamp | |

**KHÔNG** dùng `BelongsToTenant` trait: giống `PushSubscription`, digest command cần
quét cross-tenant (không có tenant context). `tenant_id` set tường minh ở controller.

Migration: reversible (`down` → `Schema::dropIfExists`). Index trên `(tenant_id, user_id)`.

## 6. API & UI

Xem `docs/05-api/endpoints.md` — đã cập nhật section "Mobile Auth" và "Thiết bị mobile".

Job: `SendExpoPushDigest` — queue `notifications`, dispatch từ `PushNewMessageDigest`.
Không chạy trực tiếp HTTP trong scheduler thread; tách job để retry được.

## 7. Edge case & lỗi

- Sai credentials: trả `422 INVALID_CREDENTIALS` (giống SPA login).
- `device_name` trùng: revoke token cũ trước khi tạo mới (rotate, không lỗi).
- Expo token hết hạn (`DeviceNotRegistered`): xoá `mobile_devices` row, log warning.
- Expo batch error (`InvalidCredentials`, `MessageTooBig`): log, skip, không crash digest.
- CORS preflight: đảm bảo `OPTIONS` requests trả `204` với đúng headers.

## 8. Bảo mật & dữ liệu cá nhân

- PAT lưu hash trong `personal_access_tokens` (Sanctum mặc định). `plainTextToken`
  chỉ trả 1 lần; mobile lưu ở `expo-secure-store`.
- `CORS_ALLOWED_ORIGINS` là CSV trong `.env` — không hardcode domain.
- `expo_push_token` không phải PII nhưng liên kết user → xoá khi user xoá tài khoản (follow-up).
- Không log `plainTextToken` ở bất kỳ đâu.

## 9. Kiểm thử

- Feature: `MobileAuthTest` — login ok, login sai creds, throttle, logout revoke token.
- Feature: `MobileDeviceTest` — register device (upsert), delete device, auth required.
- Feature: `ExpoPushDigestTest` — digest gửi Expo push cho device inactive; token expired → xoá row; device không active → skip.
- KHÔNG test gửi push thật (mock HTTP facade).

## 10. Tiêu chí hoàn thành

- [ ] `POST /auth/mobile-login` trả Bearer PAT + user + tenants.
- [ ] `POST /auth/mobile-logout` revoke token hiện tại → 204.
- [ ] `config/cors.php` cho phép `Authorization`, `X-Tenant-Id` từ origin env-driven.
- [ ] `POST /me/devices` upsert Expo push token; `DELETE /me/devices/{id}` xoá.
- [ ] `messaging:push-digest` gửi Expo push kèm Web push (không breaking change).
- [ ] Tất cả PHPUnit Feature tests PASS; pint + phpstan xanh.
- [ ] `docs/05-api/endpoints.md` cập nhật.

## 11. Câu hỏi mở

- Order push (tin mới → notification khi có đơn mới): follow-up sau khi `OrderStatusChanged`
  event có thêm `tenant_id` payload (hiện có trong event nhưng cần xác nhận shape).
- Token expiry: v1 dùng global `SANCTUM_EXPIRATION`. Phase sau xem xét per-token expiry
  hoặc refresh token flow.
```

- [ ] **Append to `docs/05-api/endpoints.md`** — add two new sections after the existing content:

```markdown
## Mobile Auth (SPEC 0029)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| POST | `/api/v1/auth/mobile-login` | — (throttle 15/1) | `{ email, password, device_name }` | `200 { data: { token, user: { id, name, email, email_verified_at, tenants:[{id,name,slug,role}] }, tenants:[…] } }` — tạo Sanctum PAT. `token` = `plainTextToken` (Bearer). Sai thông tin: `422 INVALID_CREDENTIALS`. `device_name` bắt buộc ≤ 255 ký tự. Token cũ cùng `device_name` bị revoke trước khi tạo mới (rotate). |
| POST | `/api/v1/auth/mobile-logout` | sanctum (Bearer) | — | `204` — revoke PAT hiện tại (`currentAccessToken()->delete()`). Không ảnh hưởng token khác của cùng user. |

## Thiết bị mobile (SPEC 0029)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| POST | `/api/v1/me/devices` | sanctum + tenant | `{ expo_push_token, platform }` — `platform ∈ {ios,android}` | `201 { data: { id, expo_push_token, platform, created_at } }` — upsert theo `expo_push_token` (gọi nhiều lần an toàn). |
| DELETE | `/api/v1/me/devices/{id}` | sanctum + tenant | — | `204` — xoá device. `404` nếu không thuộc user + tenant. |
```

- [ ] **Commit:**
  ```
  git add docs/specs/0029-mobile-api-access.md docs/05-api/endpoints.md
  git commit -m "docs: add SPEC 0029 mobile API access + update endpoints.md"
  ```

---

## Task 2: Sanctum PAT — mobile-login and mobile-logout endpoints

**Files:**
- Create: `app/app/Modules/Tenancy/Http/Controllers/MobileAuthController.php`
- Create: `app/app/Modules/Tenancy/Http/Requests/MobileLoginRequest.php`
- Modify: `app/routes/api.php`
- Create: `app/tests/Feature/Tenancy/MobileAuthTest.php`

### Steps

#### 2a. Write failing test

- [ ] Create `app/tests/Feature/Tenancy/MobileAuthTest.php`:

```php
<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * SPEC 0029 — Mobile Token Auth (Sanctum PAT).
 */
class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'staff@demo.local',
            'password' => 'pa$$word1',
            'email_verified_at' => now(),
        ]);
        $this->tenant = Tenant::create(['name' => 'Test Shop']);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::StaffOrder->value]);
    }

    // --- mobile-login ---

    public function test_mobile_login_returns_token_user_and_tenants(): void
    {
        $response = $this->postJson('/api/v1/auth/mobile-login', [
            'email' => 'staff@demo.local',
            'password' => 'pa$$word1',
            'device_name' => 'iPhone 15 Pro',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'email_verified_at', 'tenants'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['token']);
        $this->assertEquals('staff@demo.local', $data['user']['email']);
        $this->assertCount(1, $data['user']['tenants']);
        $this->assertEquals($this->tenant->getKey(), $data['user']['tenants'][0]['id']);
        $this->assertEquals(Role::StaffOrder->value, $data['user']['tenants'][0]['role']);
    }

    public function test_mobile_login_abilities_match_role_permissions(): void
    {
        $this->postJson('/api/v1/auth/mobile-login', [
            'email' => 'staff@demo.local',
            'password' => 'pa$$word1',
            'device_name' => 'Android Device',
        ])->assertOk();

        $token = PersonalAccessToken::query()
            ->where('tokenable_id', $this->user->getKey())
            ->latest()
            ->firstOrFail();

        $this->assertContains('orders.view', $token->abilities);
        $this->assertContains('fulfillment.ship', $token->abilities);
    }

    public function test_mobile_login_wrong_password_returns_422(): void
    {
        $this->postJson('/api/v1/auth/mobile-login', [
            'email' => 'staff@demo.local',
            'password' => 'WRONG',
            'device_name' => 'Device',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_mobile_login_unknown_email_returns_422(): void
    {
        $this->postJson('/api/v1/auth/mobile-login', [
            'email' => 'nobody@demo.local',
            'password' => 'pa$$word1',
            'device_name' => 'Device',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_mobile_login_missing_device_name_returns_422(): void
    {
        $this->postJson('/api/v1/auth/mobile-login', [
            'email' => 'staff@demo.local',
            'password' => 'pa$$word1',
        ])->assertStatus(422);
    }

    public function test_mobile_login_rotates_token_for_same_device_name(): void
    {
        // First login
        $this->postJson('/api/v1/auth/mobile-login', [
            'email' => 'staff@demo.local',
            'password' => 'pa$$word1',
            'device_name' => 'Shared Device',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Second login with same device_name should rotate
        $this->postJson('/api/v1/auth/mobile-login', [
            'email' => 'staff@demo.local',
            'password' => 'pa$$word1',
            'device_name' => 'Shared Device',
        ])->assertOk();

        // Still only 1 token (old revoked, new created)
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    // --- mobile-logout ---

    public function test_mobile_logout_revokes_current_token(): void
    {
        $token = $this->user->createToken('My Phone')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/mobile-logout')
            ->assertNoContent();

        // Token must be gone from DB
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_mobile_logout_does_not_revoke_other_tokens(): void
    {
        $token1 = $this->user->createToken('Phone 1')->plainTextToken;
        $token2 = $this->user->createToken('Phone 2')->plainTextToken;

        $this->withToken($token1)
            ->postJson('/api/v1/auth/mobile-logout')
            ->assertNoContent();

        // token2 still present
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Phone 2']);
    }

    public function test_mobile_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/mobile-logout')
            ->assertStatus(401);
    }

    // --- PAT works on protected routes ---

    public function test_bearer_token_can_access_authenticated_route(): void
    {
        $token = $this->user->createToken('App')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'staff@demo.local');
    }
}
```

- [ ] **Run failing test:**
  ```
  cd app && php artisan test --filter=MobileAuthTest
  ```
  Expected: FAIL — `RouteNotFoundException` / `404` because route `auth/mobile-login` does not exist yet.

#### 2b. Implement

- [ ] Create `app/app/Modules/Tenancy/Http/Requests/MobileLoginRequest.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MobileLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }
}
```

- [ ] Create `app/app/Modules/Tenancy/Http/Controllers/MobileAuthController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Http\Requests\MobileLoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Mobile token auth — Sanctum Personal Access Token (PAT).
 *
 * Tái dùng userPayload() logic từ AuthController nhưng bổ sung token vào response.
 * SPA cookie auth (AuthController) không bị ảnh hưởng.
 *
 * SPEC 0029 §3.1
 */
class MobileAuthController extends Controller
{
    /**
     * POST /api/v1/auth/mobile-login
     *
     * Trả Bearer PAT + user + tenants. Revoke token cũ cùng device_name trước khi
     * tạo mới (rotate). Abilities = permissions của mọi role user có trong bất kỳ tenant.
     */
    public function login(MobileLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], (string) $user->password)) {
            return response()->json([
                'error' => [
                    'code'    => 'INVALID_CREDENTIALS',
                    'message' => 'Email hoặc mật khẩu không đúng.',
                ],
            ], 422);
        }

        // Rotate: revoke existing PAT with same device_name for this user.
        $user->tokens()->where('name', $data['device_name'])->delete();

        // Collect abilities from all tenant roles this user holds.
        $abilities = $this->resolveAbilities($user);

        $tokenResult = $user->createToken($data['device_name'], $abilities);

        return response()->json([
            'data' => [
                'token' => $tokenResult->plainTextToken,
                'user'  => $this->userPayload($user),
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/mobile-logout
     *
     * Revoke the CURRENT access token (the one that authenticated this request).
     * Cookie-based SPA sessions are NOT affected.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    /**
     * Build the user + tenants payload (matches AuthController::userPayload shape).
     *
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        $user->load('tenants');

        return [
            'id'                => $user->getKey(),
            'name'              => $user->name,
            'email'             => $user->email,
            'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
            'tenants'           => $user->tenants->map(fn ($t) => [
                'id'   => $t->getKey(),
                'name' => $t->name,
                'slug' => $t->slug,
                'role' => $t->pivot->role instanceof \CMBcoreSeller\Modules\Tenancy\Enums\Role
                    ? $t->pivot->role->value
                    : $t->pivot->role,
            ])->values(),
        ];
    }

    /**
     * Collect unique permissions across all tenant memberships.
     * Owner/Admin → ['*']. Others → explicit permission list.
     * Wildcard '*' is kept as-is; Sanctum tokenCan() checks it.
     *
     * @return list<string>
     */
    private function resolveAbilities(User $user): array
    {
        $user->load('tenants');

        $abilities = [];

        foreach ($user->tenants as $tenant) {
            $role = $tenant->pivot->role instanceof \CMBcoreSeller\Modules\Tenancy\Enums\Role
                ? $tenant->pivot->role
                : \CMBcoreSeller\Modules\Tenancy\Enums\Role::from((string) $tenant->pivot->role);

            $perms = $role->permissions();

            // If any role grants '*', short-circuit — no need to enumerate.
            if (in_array('*', $perms, true)) {
                return ['*'];
            }

            // Merge positive permissions; skip negation strings (start with '!').
            foreach ($perms as $p) {
                if (! str_starts_with($p, '!') && ! in_array($p, $abilities, true)) {
                    $abilities[] = $p;
                }
            }
        }

        return $abilities ?: ['*'];
    }
}
```

- [ ] **Add routes** in `app/routes/api.php` — inside the `Route::prefix('v1')` group, after the existing `throttle:15,1` auth block:

```php
// --- Mobile token auth (SPEC 0029) — rate limit same as SPA login ---
Route::middleware('throttle:15,1')->group(function () {
    Route::post('auth/mobile-login', [\CMBcoreSeller\Modules\Tenancy\Http\Controllers\MobileAuthController::class, 'login'])
        ->name('auth.mobile.login');
});

Route::middleware('auth:sanctum')->group(function () {
    // existing auth:sanctum group...
    Route::post('auth/mobile-logout', [\CMBcoreSeller\Modules\Tenancy\Http\Controllers\MobileAuthController::class, 'logout'])
        ->name('auth.mobile.logout');
});
```

> **Important placement note:** `auth/mobile-login` is added to the existing `throttle:15,1` group (alongside register and login). `auth/mobile-logout` is added inside the existing `auth:sanctum` group — alongside the existing `auth/logout` route. The `me/devices` routes (Task 4) go inside the `verified`, `tenant`, `plan.over_quota_lock` group.

- [ ] **Run test:**
  ```
  cd app && php artisan test --filter=MobileAuthTest
  ```
  Expected: PASS (all assertions green).

- [ ] **Commit:**
  ```
  git add app/app/Modules/Tenancy/Http/Controllers/MobileAuthController.php \
          app/app/Modules/Tenancy/Http/Requests/MobileLoginRequest.php \
          app/routes/api.php \
          app/tests/Feature/Tenancy/MobileAuthTest.php
  git commit -m "feat(tenancy): add mobile-login / mobile-logout PAT endpoints (SPEC 0029)"
  ```

---

## Task 3: CORS — publish and configure cors.php for mobile origins

**Files:**
- Create: `app/config/cors.php`

### Steps

#### 3a. Write failing test

> CORS configuration is tested indirectly — a preflight `OPTIONS` request should return the correct `Access-Control-Allow-Origin` header. Add a test to `MobileAuthTest` or as a standalone.

- [ ] Append the following test method to `app/tests/Feature/Tenancy/MobileAuthTest.php`:

```php
    public function test_cors_preflight_for_mobile_login_returns_allowed_headers(): void
    {
        // Set env for the config (config is cached before the request in feature tests).
        config(['cors.allowed_origins' => ['http://localhost:8081']]);

        $response = $this->call('OPTIONS', '/api/v1/auth/mobile-login', [], [], [], [
            'HTTP_ORIGIN'                         => 'http://localhost:8081',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD'  => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization,X-Tenant-Id,Content-Type,Accept',
        ]);

        $response->assertStatus(204);
        $this->assertStringContainsString(
            'authorization',
            strtolower((string) $response->headers->get('Access-Control-Allow-Headers', ''))
        );
        $this->assertStringContainsString(
            'x-tenant-id',
            strtolower((string) $response->headers->get('Access-Control-Allow-Headers', ''))
        );
    }
```

- [ ] **Run (pre-implementation):**
  ```
  cd app && php artisan test --filter=MobileAuthTest::test_cors_preflight
  ```
  Expected: FAIL — `cors.php` does not exist; headers not present (or `config/cors.php` uses Laravel default which lacks `Authorization` and `X-Tenant-Id`).

#### 3b. Implement

- [ ] **Publish Sanctum CORS config** (if `app/config/cors.php` does not exist):
  ```
  cd app && php artisan config:publish --provider="Fruitcake\Cors\CorsServiceProvider"
  ```
  (In Laravel 11, CORS is built-in via `fruitcake/laravel-cors` or the framework's own CORS. If the file already exists, skip publish and edit directly.)

  **Assumption:** `config/cors.php` does not currently exist (confirmed from file read: the file is absent). Create it from Laravel's default and customize:

- [ ] Create `app/config/cors.php`:

```php
<?php

/**
 * CORS configuration.
 *
 * SPEC 0029 — mobile clients (Expo) need Authorization, X-Tenant-Id headers.
 * origins are env-driven via CORS_ALLOWED_ORIGINS (CSV). Supports credentials
 * stays true for the SPA cookie flow.
 *
 * Examples:
 *   CORS_ALLOWED_ORIGINS=http://localhost:8081,exp://192.168.1.10:8081
 *   CORS_ALLOWED_ORIGINS=*  (dev only — never in prod)
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:8081')))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'X-Tenant-Id',
        'Accept',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'X-Request-Id',
    ],

    'max_age' => 0,

    // Keep true so that SPA (same-domain cookie flow) still works alongside
    // the Bearer token flow used by mobile.
    'supports_credentials' => true,

];
```

- [ ] **Run test:**
  ```
  cd app && php artisan test --filter=MobileAuthTest::test_cors_preflight
  ```
  Expected: PASS.

- [ ] **Commit:**
  ```
  git add app/config/cors.php app/tests/Feature/Tenancy/MobileAuthTest.php
  git commit -m "feat(cors): publish cors.php with Authorization + X-Tenant-Id for mobile (SPEC 0029)"
  ```

---

## Task 4: MobileDevice model + migration + API endpoints

**Files:**
- Create: `app/app/Modules/Tenancy/Database/Migrations/2026_05_31_100001_create_mobile_devices_table.php`
- Create: `app/app/Modules/Tenancy/Models/MobileDevice.php`
- Create: `app/app/Modules/Tenancy/Http/Controllers/MobileDeviceController.php`
- Create: `app/app/Modules/Tenancy/Http/Requests/RegisterMobileDeviceRequest.php`
- Modify: `app/routes/api.php`
- Create: `app/tests/Feature/Tenancy/MobileDeviceTest.php`

### Steps

#### 4a. Write failing test

- [ ] Create `app/tests/Feature/Tenancy/MobileDeviceTest.php`:

```php
<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0029 — Mobile device registry (Expo push token).
 */
class MobileDeviceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'Push Shop']);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);
    }

    private function headers(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    // --- POST /me/devices ---

    public function test_register_device_creates_row(): void
    {
        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->postJson('/api/v1/me/devices', [
                'expo_push_token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
                'platform'        => 'ios',
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'expo_push_token', 'platform', 'created_at']]);

        $this->assertDatabaseHas('mobile_devices', [
            'user_id'         => $this->user->getKey(),
            'tenant_id'       => $this->tenant->getKey(),
            'expo_push_token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'platform'        => 'ios',
        ]);
    }

    public function test_register_device_is_upsert_safe(): void
    {
        $token = 'ExponentPushToken[upsert_test_abc]';

        // First registration
        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->postJson('/api/v1/me/devices', ['expo_push_token' => $token, 'platform' => 'android'])
            ->assertCreated();

        // Second registration with same token — should upsert, not duplicate
        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->postJson('/api/v1/me/devices', ['expo_push_token' => $token, 'platform' => 'android'])
            ->assertCreated();

        $this->assertDatabaseCount('mobile_devices', 1);
    }

    public function test_register_device_validates_platform(): void
    {
        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->postJson('/api/v1/me/devices', [
                'expo_push_token' => 'ExponentPushToken[platform_test]',
                'platform'        => 'windows',
            ])
            ->assertStatus(422);
    }

    public function test_register_device_requires_auth(): void
    {
        $this->postJson('/api/v1/me/devices', [
            'expo_push_token' => 'ExponentPushToken[noauth]',
            'platform'        => 'ios',
        ])->assertStatus(401);
    }

    // --- DELETE /me/devices/{id} ---

    public function test_delete_device_removes_row(): void
    {
        $device = MobileDevice::query()->create([
            'tenant_id'       => $this->tenant->getKey(),
            'user_id'         => $this->user->getKey(),
            'expo_push_token' => 'ExponentPushToken[delete_me]',
            'platform'        => 'ios',
        ]);

        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->deleteJson('/api/v1/me/devices/'.$device->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('mobile_devices', ['id' => $device->getKey()]);
    }

    public function test_delete_device_returns_404_for_other_user(): void
    {
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($otherUser->getKey(), ['role' => Role::Viewer->value]);

        $device = MobileDevice::query()->create([
            'tenant_id'       => $this->tenant->getKey(),
            'user_id'         => $otherUser->getKey(),
            'expo_push_token' => 'ExponentPushToken[not_mine]',
            'platform'        => 'android',
        ]);

        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->deleteJson('/api/v1/me/devices/'.$device->getKey())
            ->assertNotFound();
    }
}
```

- [ ] **Run failing test:**
  ```
  cd app && php artisan test --filter=MobileDeviceTest
  ```
  Expected: FAIL — `RouteNotFoundException` / `404` because routes and model don't exist.

#### 4b. Implement

- [ ] Create migration `app/app/Modules/Tenancy/Database/Migrations/2026_05_31_100001_create_mobile_devices_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile device registry (Expo Push tokens).
 *
 * SPEC 0029 — tách biệt hoàn toàn với Web Push (messaging_push_subscriptions).
 * Không dùng BelongsToTenant: digest command quét cross-tenant.
 * expo_push_token UNIQUE toàn bảng: thiết bị đổi user → upsert cập nhật user_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('expo_push_token', 255)->unique();
            $table->string('platform', 20); // ios | android
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_notified_at')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
    }
};
```

- [ ] Create `app/app/Modules/Tenancy/Models/MobileDevice.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Expo Push token for a mobile device.
 *
 * Không dùng BelongsToTenant: messaging:push-digest (+ SendExpoPushDigest job)
 * chạy cross-tenant trong scheduler — không có request tenant context.
 * tenant_id set tường minh ở controller.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $expo_push_token
 * @property string $platform
 * @property ?Carbon $last_seen_at
 * @property ?Carbon $last_notified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MobileDevice extends Model
{
    protected $table = 'mobile_devices';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'expo_push_token',
        'platform',
        'last_seen_at',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at'    => 'datetime',
            'last_notified_at' => 'datetime',
        ];
    }
}
```

- [ ] Create `app/app/Modules/Tenancy/Http/Requests/RegisterMobileDeviceRequest.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterMobileDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expo_push_token' => ['required', 'string', 'max:255'],
            'platform'        => ['required', 'string', 'in:ios,android'],
        ];
    }
}
```

- [ ] Create `app/app/Modules/Tenancy/Http/Controllers/MobileDeviceController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Http\Requests\RegisterMobileDeviceRequest;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Expo Push device registry — POST /api/v1/me/devices, DELETE /api/v1/me/devices/{id}.
 *
 * SPEC 0029 §3.3. Requires auth:sanctum + tenant middleware.
 */
class MobileDeviceController extends Controller
{
    /**
     * POST /api/v1/me/devices
     *
     * Upsert device by expo_push_token. Safe to call multiple times.
     */
    public function store(RegisterMobileDeviceRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var \CMBcoreSeller\Models\User $user */
        $user = $request->user();

        $device = MobileDevice::query()->updateOrCreate(
            ['expo_push_token' => $data['expo_push_token']],
            [
                'tenant_id' => app(CurrentTenant::class)->id(),
                'user_id'   => $user->getKey(),
                'platform'  => $data['platform'],
                // Baseline last_notified_at so we don't push stale messages.
                'last_notified_at' => fn ($existing) => $existing ?? now(),
            ]
        );

        // Always refresh last_notified_at baseline on new registration to avoid
        // flooding user with stale digest. For updateOrCreate, set explicitly:
        if (! $device->last_notified_at) {
            $device->forceFill(['last_notified_at' => now()])->save();
        }

        return response()->json([
            'data' => [
                'id'              => $device->getKey(),
                'expo_push_token' => $device->expo_push_token,
                'platform'        => $device->platform,
                'created_at'      => $device->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * DELETE /api/v1/me/devices/{id}
     *
     * Remove a device registration. Only the owning user may delete.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var \CMBcoreSeller\Models\User $user */
        $user = $request->user();

        $device = MobileDevice::query()
            ->where('id', $id)
            ->where('user_id', $user->getKey())
            ->where('tenant_id', app(CurrentTenant::class)->id())
            ->first();

        if (! $device) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Thiết bị không tồn tại.'],
            ], 404);
        }

        $device->delete();

        return response()->json(null, 204);
    }
}
```

- [ ] **Add routes** in `app/routes/api.php` — inside the `verified`, `tenant`, `plan.over_quota_lock` group:

```php
// --- Mobile device registry (SPEC 0029) ---
Route::post('me/devices', [\CMBcoreSeller\Modules\Tenancy\Http\Controllers\MobileDeviceController::class, 'store'])->name('me.devices.store');
Route::delete('me/devices/{id}', [\CMBcoreSeller\Modules\Tenancy\Http\Controllers\MobileDeviceController::class, 'destroy'])->whereNumber('id')->name('me.devices.destroy');
```

- [ ] **Run test:**
  ```
  cd app && php artisan test --filter=MobileDeviceTest
  ```
  Expected: PASS.

- [ ] **Commit:**
  ```
  git add app/app/Modules/Tenancy/Database/Migrations/2026_05_31_100001_create_mobile_devices_table.php \
          app/app/Modules/Tenancy/Models/MobileDevice.php \
          app/app/Modules/Tenancy/Http/Controllers/MobileDeviceController.php \
          app/app/Modules/Tenancy/Http/Requests/RegisterMobileDeviceRequest.php \
          app/routes/api.php \
          app/tests/Feature/Tenancy/MobileDeviceTest.php
  git commit -m "feat(tenancy): add MobileDevice model + migration + me/devices endpoints (SPEC 0029)"
  ```

---

## Task 5: ExpoPushSender service + SendExpoPushDigest job

**Files:**
- Create: `app/app/Modules/Messaging/Contracts/ExpoPushSenderContract.php`
- Create: `app/app/Modules/Messaging/Services/ExpoPushSender.php`
- Create: `app/app/Modules/Messaging/Jobs/SendExpoPushDigest.php`
- Modify: `app/app/Modules/Messaging/MessagingServiceProvider.php`
- Create: `app/tests/Feature/Messaging/ExpoPushDigestTest.php`

**Note on module boundaries:** `ExpoPushSender` needs to read `mobile_devices` from the `Tenancy` module. Per the repo's module dependency rules, `Messaging` may depend on `Tenancy` (which is the base module everyone depends on). The `MobileDevice` model is in `Tenancy` — `Messaging` importing it is acceptable per `docs/01-architecture/modules.md`. However, to be clean, the digest command lives in `Messaging` (where push logic belongs) and reads `MobileDevice` directly (it is a data model, not a service internal).

### Steps

#### 5a. Write failing test

- [ ] Create `app/tests/Feature/Messaging/ExpoPushDigestTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Messaging\Contracts\ExpoPushSenderContract;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC 0029 — Expo Push digest integration into messaging:push-digest.
 */
class ExpoPushDigestTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'ExpoShop']);
        $this->user   = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);
    }

    private function makeDevice(string $token = 'ExponentPushToken[test]', ?string $lastNotified = null): MobileDevice
    {
        return MobileDevice::query()->create([
            'tenant_id'        => $this->tenant->getKey(),
            'user_id'          => $this->user->getKey(),
            'expo_push_token'  => $token,
            'platform'         => 'ios',
            'last_notified_at' => $lastNotified,
        ]);
    }

    private function makeConversationWithInbound(string $lastInboundAt): Conversation
    {
        return Conversation::query()->withoutGlobalScopes()->create([
            'tenant_id'               => $this->tenant->getKey(),
            'channel_account_id'      => 1,
            'provider'                => 'tiktok',
            'external_conversation_id'=> 'conv_'.uniqid(),
            'buyer_external_id'       => 'buyer_'.uniqid(),
            'status'                  => 'open',
            'last_message_at'         => $lastInboundAt,
            'last_inbound_at'         => $lastInboundAt,
        ]);
    }

    public function test_digest_sends_expo_push_for_inactive_device_with_new_messages(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    ['status' => 'ok', 'id' => 'abc123'],
                ],
            ], 200),
        ]);

        $device = $this->makeDevice('ExponentPushToken[aaa]', now()->subHour()->toDateTimeString());
        $this->makeConversationWithInbound(now()->toDateTimeString());

        $this->artisan('messaging:push-digest')->assertSuccessful();

        Http::assertSentCount(1);

        // last_notified_at must be updated
        $this->assertNotNull($device->fresh()->last_notified_at);
        $this->assertTrue($device->fresh()->last_notified_at > now()->subMinute());
    }

    public function test_digest_skips_device_with_no_new_messages(): void
    {
        Http::fake();

        $this->makeDevice('ExponentPushToken[bbb]', now()->toDateTimeString());
        // No new conversations after last_notified_at

        $this->artisan('messaging:push-digest')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_digest_removes_device_on_not_registered_error(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']],
                ],
            ], 200),
        ]);

        $device = $this->makeDevice('ExponentPushToken[expired]', now()->subHour()->toDateTimeString());
        $this->makeConversationWithInbound(now()->toDateTimeString());

        $this->artisan('messaging:push-digest')->assertSuccessful();

        $this->assertDatabaseMissing('mobile_devices', ['id' => $device->getKey()]);
    }

    public function test_expo_push_sender_contract_is_bound(): void
    {
        $sender = app(ExpoPushSenderContract::class);
        $this->assertInstanceOf(\CMBcoreSeller\Modules\Messaging\Services\ExpoPushSender::class, $sender);
    }
}
```

- [ ] **Run failing test:**
  ```
  cd app && php artisan test --filter=ExpoPushDigestTest
  ```
  Expected: FAIL — `BindingResolutionException` (contract not bound) and `mobile_devices` table does not exist.

#### 5b. Implement

- [ ] Create `app/app/Modules/Messaging/Contracts/ExpoPushSenderContract.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Contracts;

use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;

/**
 * Sends an Expo Push notification to a single mobile device.
 *
 * Implementations must:
 * - POST to https://exp.host/--/api/v2/push/send (batch endpoint).
 * - Return true on success; false on non-fatal error.
 * - Delete the MobileDevice row and return false on DeviceNotRegistered.
 */
interface ExpoPushSenderContract
{
    /**
     * @param  array<string, mixed>  $payload  e.g. ['title'=>'...','body'=>'...','data'=>[...]]
     */
    public function send(MobileDevice $device, array $payload): bool;
}
```

- [ ] Create `app/app/Modules/Messaging/Services/ExpoPushSender.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Contracts\ExpoPushSenderContract;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gửi Expo Push Notification qua Expo Push HTTP API v2.
 *
 * Batch endpoint: POST https://exp.host/--/api/v2/push/send
 * Response per-message: { status: 'ok'|'error', details?: { error: string } }
 *
 * Lỗi xử lý:
 *   - DeviceNotRegistered: xoá MobileDevice row + return false.
 *   - MessageTooBig / InvalidCredentials / other: log + return false (không xoá).
 *   - HTTP failure: log + return false.
 *
 * SPEC 0029 §3.3
 */
class ExpoPushSender implements ExpoPushSenderContract
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(MobileDevice $device, array $payload): bool
    {
        $body = array_merge($payload, [
            'to' => $device->expo_push_token,
        ]);

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(self::EXPO_PUSH_URL, [$body]);

            if (! $response->successful()) {
                Log::warning('expo_push.http_error', [
                    'expo_push_token' => $device->expo_push_token,
                    'status'          => $response->status(),
                ]);

                return false;
            }

            /** @var array<int, array<string,mixed>> $results */
            $results = $response->json('data', []);
            $result  = $results[0] ?? [];

            if (($result['status'] ?? '') === 'error') {
                $errorCode = $result['details']['error'] ?? 'unknown';

                if ($errorCode === 'DeviceNotRegistered') {
                    $device->delete();
                    Log::info('expo_push.device_not_registered', ['expo_push_token' => $device->expo_push_token]);

                    return false;
                }

                Log::warning('expo_push.send_error', [
                    'expo_push_token' => $device->expo_push_token,
                    'error'           => $errorCode,
                ]);

                return false;
            }

            return ($result['status'] ?? '') === 'ok';

        } catch (\Throwable $e) {
            Log::warning('expo_push.exception', [
                'expo_push_token' => $device->expo_push_token,
                'error'           => $e->getMessage(),
            ]);

            return false;
        }
    }
}
```

- [ ] Create `app/app/Modules/Messaging/Jobs/SendExpoPushDigest.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Contracts\ExpoPushSenderContract;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Gửi Expo Push digest cho tất cả mobile devices có tin nhắn inbound mới.
 *
 * Chạy trên queue `notifications`. Dispatch từ `PushNewMessageDigest` command
 * (scheduler 30 phút) — giúp retry được nếu gọi HTTP Expo thất bại.
 *
 * Logic giống `PushNewMessageDigest` cho Web Push:
 * - Đếm conversations có `last_inbound_at > device.last_notified_at`.
 * - Nếu ≥ 1 → gửi push "N người nhắn tin mới".
 * - Cập nhật `last_notified_at`.
 *
 * SPEC 0029 §3.3
 */
class SendExpoPushDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(ExpoPushSenderContract $sender): void
    {
        $sent = 0;

        MobileDevice::query()
            ->orderBy('id')
            ->chunkById(200, function ($devices) use ($sender, &$sent) {
                foreach ($devices as $device) {
                    $since = $device->last_notified_at ?? $device->created_at ?? now()->subDay();

                    $count = Conversation::withoutGlobalScope(TenantScope::class)
                        ->where('tenant_id', $device->tenant_id)
                        ->whereNull('blocked_at')
                        ->where('status', '!=', Conversation::STATUS_SPAM)
                        ->whereNotNull('last_inbound_at')
                        ->where('last_inbound_at', '>', $since)
                        ->count();

                    if ($count < 1) {
                        continue;
                    }

                    $ok = $sender->send($device, [
                        'title' => 'Tin nhắn mới',
                        'body'  => "{$count} người nhắn tin mới",
                        'data'  => ['screen' => 'messaging'],
                    ]);

                    if ($ok) {
                        $device->forceFill(['last_notified_at' => now()])->save();
                        $sent++;
                    }
                }
            });

        Log::info('expo_push.digest_sent', ['sent' => $sent]);
    }
}
```

- [ ] **Register contract binding and command** in `app/app/Modules/Messaging/MessagingServiceProvider.php` — add to `register()`:

```php
// In register():
$this->app->bind(
    \CMBcoreSeller\Modules\Messaging\Contracts\ExpoPushSenderContract::class,
    \CMBcoreSeller\Modules\Messaging\Services\ExpoPushSender::class,
);
```

And add to the `commands([...])` list in `boot()`:

```php
\CMBcoreSeller\Modules\Messaging\Jobs\SendExpoPushDigest::class, // Note: this is a job, not a command.
// Correct: add the dispatch call to PushNewMessageDigest instead (see next step).
```

> **Correction:** `SendExpoPushDigest` is a **Job**, not a console command. Do not add it to `$this->commands([])`. Instead, `PushNewMessageDigest` command dispatches it.

- [ ] **Extend `PushNewMessageDigest`** — add Expo push dispatch at the end of `handle()` in `app/app/Modules/Messaging/Console/Commands/PushNewMessageDigest.php`:

```php
// At end of handle(), after existing Web Push loop:
\CMBcoreSeller\Modules\Messaging\Jobs\SendExpoPushDigest::dispatch();

$this->info("push-digest: sent {$sent} web-push notifications; expo push digest dispatched.");
```

> **Integration:** `PushNewMessageDigest` already runs every 30 minutes in the scheduler. Adding one `SendExpoPushDigest::dispatch()` at the end ensures Expo push fires on the same cadence without duplicating the scheduling setup.

- [ ] **Register contract binding** — add to `register()` method in `MessagingServiceProvider`:

The complete `register()` section addition:

```php
// SPEC 0029 — Expo Push sender (mobile notifications).
$this->app->bind(
    \CMBcoreSeller\Modules\Messaging\Contracts\ExpoPushSenderContract::class,
    \CMBcoreSeller\Modules\Messaging\Services\ExpoPushSender::class,
);
```

- [ ] **Run test:**
  ```
  cd app && php artisan test --filter=ExpoPushDigestTest
  ```
  Expected: PASS.

- [ ] **Commit:**
  ```
  git add app/app/Modules/Messaging/Contracts/ExpoPushSenderContract.php \
          app/app/Modules/Messaging/Services/ExpoPushSender.php \
          app/app/Modules/Messaging/Jobs/SendExpoPushDigest.php \
          app/app/Modules/Messaging/MessagingServiceProvider.php \
          app/app/Modules/Messaging/Console/Commands/PushNewMessageDigest.php \
          app/tests/Feature/Messaging/ExpoPushDigestTest.php
  git commit -m "feat(messaging): add ExpoPushSender service + SendExpoPushDigest job (SPEC 0029)"
  ```

---

## Task 6: Verify Sanctum guard configuration (no code change expected)

**Files:** Read-only verification — `app/config/sanctum.php` and `app/app/Modules/Tenancy/Http/Middleware/EnsureTenant.php`

### Steps

- [ ] **Verify** that `config/sanctum.php` already has `'guard' => ['web', 'admin_web']`. Confirmed from file read: yes, guards are `['web', 'admin_web']`. Sanctum's `auth:sanctum` middleware tries each guard in order, then falls back to Bearer token lookup. Mobile Bearer PAT requests skip the cookie session — Sanctum will find no session guard match and fall through to token lookup. This works correctly with zero config changes to `sanctum.php`.

- [ ] **Verify** `EnsureTenant` middleware. Confirmed from file read: it reads `X-Tenant-Id` from `$request->header('X-Tenant-Id')` — **independent of session/cookie**. The mobile app sends `X-Tenant-Id` as a header alongside `Authorization: Bearer <token>`. This works correctly with zero middleware changes.

- [ ] **Document the analysis** in a comment at the top of `MobileAuthController.php` (already included in the class docblock above).

- [ ] **Add a guard-verification test** to `MobileAuthTest`:

```php
    public function test_bearer_token_can_access_tenant_route(): void
    {
        $token = $this->user->createToken('App', ['orders.view'])->plainTextToken;

        $this->withToken($token)
            ->withHeader('X-Tenant-Id', (string) $this->tenant->getKey())
            ->getJson('/api/v1/tenant')
            ->assertOk();
    }
```

- [ ] **Run:**
  ```
  cd app && php artisan test --filter=MobileAuthTest::test_bearer_token_can_access_tenant_route
  ```
  Expected: PASS (no config changes needed — confirmed by analysis).

- [ ] **Commit:**
  ```
  git add app/tests/Feature/Tenancy/MobileAuthTest.php
  git commit -m "test(tenancy): verify bearer PAT works on tenant-scoped routes (SPEC 0029)"
  ```

---

## Task 7: Full quality gate

- [ ] **PHP CS Fixer (Pint):**
  ```
  cd app && vendor/bin/pint --test
  ```
  Expected: no formatting issues. If issues: `vendor/bin/pint` to auto-fix, then re-run `--test`.

- [ ] **PHPStan (Larastan level 5):**
  ```
  cd app && vendor/bin/phpstan analyse
  ```
  Expected: 0 errors. Fix any type issues introduced by new code.

- [ ] **Full test suite:**
  ```
  cd app && php artisan test
  ```
  Expected: all tests PASS (new + pre-existing).

- [ ] **Commit fix (if needed):**
  ```
  git add -p
  git commit -m "fix: address pint/phpstan issues from SPEC 0029 implementation"
  ```

---

## Assumptions and clarifications (stated explicitly)

1. **`config/cors.php` was absent:** Confirmed from file-system read — the file does not exist in `app/config/`. The plan creates it from scratch. Laravel 11 ships with CORS support via the `fruitcake/laravel-cors` package (now built-in). If the package is not present, run `composer require fruitcake/laravel-cors` first.

2. **`Conversation::STATUS_SPAM` constant:** Referenced in `SendExpoPushDigest` — this constant exists in `Conversation.php` based on the pattern used in `PushNewMessageDigest.php`. Assumption: it is defined as `const STATUS_SPAM = 'spam'` (or equivalent). If not, replace with the literal string `'spam'`.

3. **`Conversation::withoutGlobalScope(TenantScope::class)`:** Cross-tenant query needed in digest job — same pattern used in `PushNewMessageDigest`. `TenantScope` is at `CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope`.

4. **`BelongsToTenant` NOT used on `MobileDevice`:** Intentional. The `PushNewMessageDigest` command and `SendExpoPushDigest` job run cross-tenant in the scheduler (no HTTP request context, no `CurrentTenant` set). Using the global `TenantScope` from `BelongsToTenant` would filter all rows, breaking the digest. This follows the exact same design decision in `PushSubscription` model (see its docblock).

5. **`mobile-logout` returns `204`:** Standard pattern for revoke-and-204 (matches existing `/auth/logout` behavior).

6. **Order push notifications:** Out of scope for this plan. The `OrderStatusChanged` event exists in `CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged` (referenced in `MessagingServiceProvider`), so adding an order push listener is a follow-up with a one-listener addition — noted in SPEC 0029 §2.

7. **`MobileAuthController::resolveAbilities`** — for users with no tenants (newly registered, not yet in a tenant), returns `['*']` as a safe default. This is unlikely in production flow (mobile login always happens after tenant assignment) but avoids an empty-abilities token that could confuse tokenCan() checks.

8. **`upsert` baseline for `last_notified_at`:** In `MobileDeviceController::store`, `updateOrCreate` is used. The `last_notified_at` is set to `now()` only if the record is new (first registration), to avoid blasting the user with stale historical messages on re-registration.

---

## Self-Review

### Spec §3 coverage → Tasks

| Spec section | Task(s) | Status |
|---|---|---|
| §3.1 `POST /auth/mobile-login` Bearer PAT | Task 2 | Full: login, rotate, abilities, error envelope |
| §3.1 `POST /auth/mobile-logout` revoke token | Task 2 | Full: revoke current token, 204, no cross-token leak |
| §3.1 Both stateful SPA + Bearer PAT on same routes | Task 6 | Verified: Sanctum guard config already supports this; no code change |
| §3.2 CORS `Authorization`, `X-Tenant-Id`, env-driven origins | Task 3 | Full: cors.php created, all required headers, `CORS_ALLOWED_ORIGINS` |
| §3.3 `POST /api/v1/me/devices` + `DELETE` | Task 4 | Full: upsert, delete, 404 for other user |
| §3.3 `ExpoPushSender` service (HTTP to Expo API) | Task 5 | Full: send, DeviceNotRegistered cleanup, error logging |
| §3.3 Integrate into existing push-digest flow | Task 5 | Full: `PushNewMessageDigest` dispatches `SendExpoPushDigest` |

### Placeholder scan

- No `TODO`, `FIXME`, `...`, `/* placeholder */`, or `throw new \Exception('not implemented')` in any code block above. Every method is fully implemented.

### Type / name consistency check

| Symbol | Where defined | Where used | Consistent? |
|---|---|---|---|
| `mobile_devices` (table) | Migration | `MobileDevice::$table`, `assertDatabaseHas` in tests | Yes |
| `expo_push_token` (column) | Migration | Model `$fillable`, controller, tests | Yes |
| `last_notified_at` (column) | Migration | Model `$fillable` + `casts`, job, tests | Yes |
| `ExpoPushSenderContract` | `Messaging\Contracts` | `MessagingServiceProvider::bind`, `SendExpoPushDigest::handle`, test | Yes |
| `ExpoPushSender` | `Messaging\Services` | Bound in provider, test assertion | Yes |
| `MobileDevice` | `Tenancy\Models` | Job (`SendExpoPushDigest`), Controller, test | Yes |
| Route name `auth.mobile.login` | `api.php` | Referenced in endpoints.md | Yes |
| Route name `me.devices.store` / `me.devices.destroy` | `api.php` | Referenced in endpoints.md | Yes |
| `INVALID_CREDENTIALS` (error code) | `MobileAuthController` | Tests, endpoints.md, mirrors SPA login | Yes |
| `NOT_FOUND` (error code) | `MobileDeviceController` | Tests | Yes |
| Ability strings (`orders.view`, `fulfillment.ship` etc.) | `Role::permissions()` | `MobileAuthController::resolveAbilities`, test assertion | Yes (read from real `Role` enum) |
| `Conversation::STATUS_SPAM` | Assumed constant in `Conversation` model | `SendExpoPushDigest`, `PushNewMessageDigest` (existing) | Assumed consistent — verify against actual model |
| `TenantScope::class` | `Tenancy\Scopes\TenantScope` | `SendExpoPushDigest::handle`, `PushNewMessageDigest` | Yes (mirrors existing command) |
| `CORS_ALLOWED_ORIGINS` (env key) | `config/cors.php` | `.env` documentation in SPEC 0029 | Yes |

### Route placement verification

- `auth/mobile-login` → in `throttle:15,1` group alongside `auth/login` (same rate limit). Correct.
- `auth/mobile-logout` → in `auth:sanctum` group alongside `auth/logout`. Correct.
- `me/devices` (POST + DELETE) → inside `verified` + `tenant` + `plan.over_quota_lock` group. Correct — device registration requires a verified user within a valid tenant.
