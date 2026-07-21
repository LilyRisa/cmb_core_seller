# Facebook Pixel + CAPI & Growth Attribution Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Nhúng Facebook Pixel vào mọi trang chưa đăng nhập, báo sự kiện đăng ký (`CompleteRegistration`) qua Conversions API dedup với Pixel, bắt UTM first-touch lúc đăng ký, và cho admin xem/báo cáo tenant đăng ký từ nguồn UTM nào → có lên gói trả phí không.

**Architecture:** Pixel ID/CAPI token cấu hình qua `/admin/settings` (group `growth` mới trong `SystemSettingsCatalog`, đọc qua `system_setting()` — DB + cache, không `.env`). Base Pixel code nhúng 1 chỗ trong `app.blade.php` (mọi route user-facing dùng chung shell này). FE bắt UTM first-touch vào `localStorage`, gắn kèm lúc đăng ký. Backend lưu vào cột mới `tenants.acquisition` (json), rồi 1 listener queued (`TenantCreated` — cùng cơ chế Billing dùng để khởi động trial) gọi Meta Graph API `/​{pixel_id}/events` để báo `CompleteRegistration`, dedup với Pixel qua `event_id` chung. Admin có filter theo `utm_source` trên danh sách tenant + trang báo cáo `/admin/growth` gom nhóm theo nguồn.

**Tech Stack:** Laravel 11 (PHP), React 18 + TypeScript + Vite + Ant Design, TanStack Query, Meta Graph API v25.0 (Conversions API).

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/`, không phải repo root.
- Namespace `CMBcoreSeller\` map tới `app/app/`.
- Dùng `config()`/`system_setting()`, **không bao giờ** `env()` ngoài file config. Pixel ID/CAPI token là setting động qua DB (`system_setting()`), KHÔNG đọc `.env` ở runtime.
- Modules giao tiếp qua `Contracts/` hoặc domain event — không `use` thẳng `Services/` module khác. `Tenancy` là module nền, được phép tự nghe event của chính nó.
- Mọi business table có `tenant_id` + `BelongsToTenant` (global scope) — riêng bảng `tenants` không áp dụng (chính nó là gốc scope). Query cross-tenant trong Admin module phải `withoutGlobalScope(TenantScope::class)`.
- Job/listener queued phải dùng tên queue đã khai trong `config/horizon.php` (supervisor thật) — **không tự đặt tên queue mới** (gotcha đã xảy ra nhiều lần: job vào queue không ai lắng nghe = kẹt vĩnh viễn). Dùng `billing` (đã wired, cùng chỗ `StartTrialSubscription` nghe `TenantCreated`).
- Money = integer VND. Timestamp ISO-8601 UTC.
- Không có JS test runner trong repo (đã xác nhận qua memory dự án) — verify FE bằng `npm run lint && npm run typecheck && npm run build`, không viết `*.test.tsx`.
- Quality gate cuối cùng (từ `app/`): `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run lint && npm run typecheck && npm run build`.
- Response envelope API: `{ "data": ..., "meta": ... }` / lỗi `{ "error": {...} }`. Mới endpoint phải thêm vào `docs/05-api/endpoints.md`.
- UI: icon dùng `@ant-design/icons` (không emoji); nhóm lựa chọn nhỏ dùng `Radio.Group`/`Segmented`, tránh `Select` khi có thể.

---

### Task 1: Growth settings catalog (backend) — Facebook Pixel/CAPI config keys

**Files:**
- Modify: `app/app/Modules/Settings/Support/SystemSettingsCatalog.php`
- Modify: `app/tests/Unit/Settings/SystemSettingsCatalogTest.php`

**Interfaces:**
- Produces: 4 catalog keys readable via `system_setting('growth.facebook.enabled'|'growth.facebook.pixel_id'|'growth.facebook.capi_access_token'|'growth.facebook.test_event_code', $default)` — dùng ở Task 3 (Blade) và Task 5 (`FacebookCapiReporter`).

- [ ] **Step 1: Cập nhật test trước (sẽ FAIL cho tới khi catalog đổi)**

Trong `app/tests/Unit/Settings/SystemSettingsCatalogTest.php`, sửa 3 chỗ:

```php
    public function test_all_groups_present(): void
    {
        $all = SystemSettingsCatalog::all();
        $this->assertNotEmpty($all);
        $groups = collect($all)->pluck('group')->unique()->values()->all();
        sort($groups);
        $this->assertSame(['ai', 'branding', 'fulfillment', 'growth', 'mail', 'marketplace', 'push', 'sync'], $groups);
    }

    public function test_count_is_71(): void
    {
        // branding 5 + mail 8 + marketplace 14 + fulfillment 17 + sync 11 + push 3 + ai 9 + growth 4.
        // growth 4 = facebook.{enabled,pixel_id,capi_access_token,test_event_code} (SPEC 2026-07-22).
        $this->assertCount(71, SystemSettingsCatalog::all());
    }

    public function test_secret_count_is_14(): void
    {
        // mail.password + tiktok×2 + lazada×2 + shopee×3 + r2×2 + push.vapid_private_key
        // + help_assistant chat_api_key + help_assistant embedding_api_key + growth.facebook.capi_access_token.
        $secrets = collect(SystemSettingsCatalog::all())->where('is_secret', true)->keys()->all();
        $this->assertCount(14, $secrets);
    }
```

(Chỉ đổi tên method + nội dung 3 method này; giữ nguyên các method còn lại trong file.)

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run (từ `app/`): `php artisan test --filter=SystemSettingsCatalogTest`
Expected: FAIL — `test_all_groups_present` thiếu `'growth'`, `test_count_is_71` đếm ra 67, `test_secret_count_is_14` đếm ra 13.

- [ ] **Step 3: Thêm group `growth` vào catalog**

Trong `app/app/Modules/Settings/Support/SystemSettingsCatalog.php`, thêm khối sau vào cuối mảng trả về của `all()` (sau khối `// ── Transcribe ghi âm ...` hiện có, trước dấu `];` đóng mảng):

```php
            // ── Tăng trưởng — Facebook Pixel + Conversions API (SPEC 2026-07-22) ──
            // Nhúng Pixel ở app.blade.php (mọi trang) + báo CompleteRegistration lúc đăng ký
            // (Tenancy\Listeners\ReportSignupToMetaCapi). KHÔNG dùng .env ở prod — admin nhập
            // tay qua /admin/settings (tab "Tăng trưởng").
            'growth.facebook.enabled' => [
                'group' => 'growth', 'type' => 'bool', 'is_secret' => false,
                'env' => 'FACEBOOK_PIXEL_ENABLED', 'label' => 'Facebook Pixel — Bật',
                'description' => 'Bật để nhúng Pixel vào mọi trang + gửi sự kiện CompleteRegistration qua Conversions API.',
            ],
            'growth.facebook.pixel_id' => [
                'group' => 'growth', 'type' => 'string', 'is_secret' => false,
                'env' => 'FACEBOOK_PIXEL_ID', 'label' => 'Facebook Pixel ID',
            ],
            'growth.facebook.capi_access_token' => [
                'group' => 'growth', 'type' => 'string', 'is_secret' => true,
                'env' => 'FACEBOOK_CAPI_ACCESS_TOKEN', 'label' => 'Conversions API — Access Token',
            ],
            'growth.facebook.test_event_code' => [
                'group' => 'growth', 'type' => 'string', 'is_secret' => false,
                'env' => 'FACEBOOK_CAPI_TEST_EVENT_CODE', 'label' => 'Conversions API — Test Event Code',
                'description' => 'Điền tạm khi soi ở tab "Test Events" trên Meta Events Manager, xoá khi chạy thật.',
            ],
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php artisan test --filter=SystemSettingsCatalogTest`
Expected: PASS (6 tests, bao gồm 3 test không đổi + 3 test vừa sửa).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Settings/Support/SystemSettingsCatalog.php app/tests/Unit/Settings/SystemSettingsCatalogTest.php
git commit -m "feat(settings): thêm nhóm cấu hình growth.facebook (Pixel ID + CAPI token)"
```

---

### Task 2: Admin Settings FE — tab "Tăng trưởng"

**Files:**
- Modify: `app/resources/js/admin/lib/systemSettings.tsx`
- Modify: `app/resources/js/admin/pages/settings/SystemSettingsPage.tsx`

**Interfaces:**
- Consumes: `SettingRow`, `useSystemSettings(group)` từ Task 1 (đã tồn tại, chỉ mở rộng union type `SettingGroup`).

- [ ] **Step 1: Mở rộng `SettingGroup` union**

Trong `app/resources/js/admin/lib/systemSettings.tsx`, dòng 6:

```ts
export type SettingGroup = 'branding' | 'mail' | 'marketplace' | 'fulfillment' | 'sync' | 'push' | 'ai' | 'growth';
```

- [ ] **Step 2: Thêm tab vào `GROUPS`**

Trong `app/resources/js/admin/pages/settings/SystemSettingsPage.tsx`, mảng `GROUPS` (dòng 14-22), thêm entry cuối:

```ts
const GROUPS: { value: SettingGroup; label: string }[] = [
    { value: 'branding', label: 'Thương hiệu' },
    { value: 'mail', label: 'Email' },
    { value: 'marketplace', label: 'Marketplace' },
    { value: 'fulfillment', label: 'Vận hành' },
    { value: 'sync', label: 'Đồng bộ' },
    { value: 'push', label: 'Thông báo' },
    { value: 'ai', label: 'AI' },
    { value: 'growth', label: 'Tăng trưởng' },
];
```

- [ ] **Step 3: Typecheck**

Run (từ `app/`): `npm run typecheck`
Expected: không lỗi TS mới.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/lib/systemSettings.tsx app/resources/js/admin/pages/settings/SystemSettingsPage.tsx
git commit -m "feat(admin): thêm tab Tăng trưởng vào Cài đặt hệ thống"
```

---

### Task 3: Migration `tenants.acquisition` + Tenant model

**Files:**
- Create: `app/app/Modules/Tenancy/Database/Migrations/2026_07_22_100000_add_acquisition_to_tenants.php`
- Modify: `app/app/Modules/Tenancy/Models/Tenant.php`

**Interfaces:**
- Produces: `Tenant::$acquisition` (array|null, cast) — dùng bởi Task 4 (AuthController), Task 5-6 (CAPI reporter/listener), Task 7 (Admin filter), Task 8 (Growth service).

- [ ] **Step 1: Tạo migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tenants.acquisition — dữ liệu UTM/fbclid/fbp/fbc/landing_page bắt được lúc đăng ký
 * (first-touch, ghi 1 lần, bất biến). Tách khỏi `settings` (settings = hành vi tenant
 * tự chỉnh). Dùng cho báo cáo Growth attribution + báo cáo Conversions API Meta
 * (SPEC 2026-07-22-facebook-pixel-capi-growth-attribution-design.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('acquisition')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('acquisition');
        });
    }
};
```

- [ ] **Step 2: Chạy migration trên DB test/dev**

Run (từ `app/`): `php artisan migrate`
Expected: `2026_07_22_100000_add_acquisition_to_tenants ... DONE`.

- [ ] **Step 3: Thêm cast vào `Tenant` model**

Trong `app/app/Modules/Tenancy/Models/Tenant.php`, thêm docblock property (sau dòng `@property array<string,mixed>|null $settings` — dòng 21):

```php
 * @property array<string,mixed>|null $settings
 * @property array<string,mixed>|null $acquisition UTM/fbclid/fbp/fbc lúc đăng ký (first-touch, bất biến)
```

Và sửa `$casts` (dòng 35-37):

```php
    protected $casts = [
        'settings' => 'array',
        'acquisition' => 'array',
    ];
```

Lưu ý: **không** thêm `acquisition` vào `$fillable` — luôn set qua `forceFill()` từ `AuthController::register()` (Task 4), tránh mass-assignment ngoài ý muốn.

- [ ] **Step 4: Verify không vỡ test hiện có**

Run: `php artisan test --filter=AuthTenantTest`
Expected: PASS (không đổi hành vi cũ).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Tenancy/Database/Migrations/2026_07_22_100000_add_acquisition_to_tenants.php app/app/Modules/Tenancy/Models/Tenant.php
git commit -m "feat(tenancy): thêm cột tenants.acquisition lưu UTM/fbclid lúc đăng ký"
```

---

### Task 4: `AuthController::register` — bắt & lưu acquisition

**Files:**
- Modify: `app/app/Modules/Tenancy/Http/Controllers/AuthController.php:30-73`
- Create: `app/tests/Feature/Tenancy/AcquisitionCaptureTest.php`

**Interfaces:**
- Consumes: `Tenant::acquisition` cast (Task 3).
- Produces: request body `POST /api/v1/auth/register` chấp nhận thêm `event_id?: string`, `acquisition?: {utm_source, utm_medium, utm_campaign, utm_content, utm_term, fbclid, fbp, fbc, landing_page, referrer}` (tất cả optional). Tenant tạo ra có `acquisition` chứa các field trên + `ip`, `user_agent`, `captured_at` (server-observed).

- [ ] **Step 1: Viết test trước (FAIL vì cột/logic chưa xử lý)**

Tạo `app/tests/Feature/Tenancy/AcquisitionCaptureTest.php`:

```php
<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcquisitionCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_stores_utm_and_server_observed_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van B',
            'email' => 'b@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'event_id' => 'evt-123',
            'acquisition' => [
                'utm_source' => 'facebook',
                'utm_campaign' => 'summer_sale',
                'fbclid' => 'FBCLID_ABC',
                'landing_page' => '/pricing',
            ],
        ], ['User-Agent' => 'TestAgent/1.0']);

        $response->assertCreated();

        $tenant = Tenant::where('name', 'Nguyen Van B Shop')->firstOrFail();
        $this->assertSame('facebook', $tenant->acquisition['utm_source']);
        $this->assertSame('summer_sale', $tenant->acquisition['utm_campaign']);
        $this->assertSame('FBCLID_ABC', $tenant->acquisition['fbclid']);
        $this->assertSame('evt-123', $tenant->acquisition['event_id']);
        $this->assertSame('TestAgent/1.0', $tenant->acquisition['user_agent']);
        $this->assertNotEmpty($tenant->acquisition['ip']);
        $this->assertNotEmpty($tenant->acquisition['captured_at']);
    }

    public function test_register_without_acquisition_still_succeeds(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van C',
            'email' => 'c@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();
        $tenant = Tenant::where('name', 'Nguyen Van C Shop')->firstOrFail();
        $this->assertNull($tenant->acquisition['utm_source'] ?? null);
        $this->assertNotEmpty($tenant->acquisition['captured_at']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AcquisitionCaptureTest`
Expected: FAIL — `acquisition` null (controller chưa lưu gì).

- [ ] **Step 3: Sửa `AuthController::register()`**

Trong `app/app/Modules/Tenancy/Http/Controllers/AuthController.php`, thay toàn bộ method `register()` (dòng 30-73):

```php
    public function register(Request $request): JsonResponse
    {
        // Chuẩn hoá TRƯỚC validate để rule `unique` so khớp đúng với dữ liệu đã lowercase trong DB
        // (User::email mutator) — tránh lọt trùng email chỉ khác hoa/thường rồi vỡ unique constraint.
        if ($request->filled('email')) {
            $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', new NotDisposableEmail],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'tenant_name' => ['nullable', 'string', 'max:255'],
            // SPEC 2026-07-22 — first-touch UTM/fbclid bắt ở FE (localStorage), gắn lúc submit.
            'event_id' => ['nullable', 'string', 'max:64'],
            'acquisition' => ['nullable', 'array'],
            'acquisition.utm_source' => ['nullable', 'string', 'max:255'],
            'acquisition.utm_medium' => ['nullable', 'string', 'max:255'],
            'acquisition.utm_campaign' => ['nullable', 'string', 'max:255'],
            'acquisition.utm_content' => ['nullable', 'string', 'max:255'],
            'acquisition.utm_term' => ['nullable', 'string', 'max:255'],
            'acquisition.fbclid' => ['nullable', 'string', 'max:255'],
            'acquisition.fbp' => ['nullable', 'string', 'max:255'],
            'acquisition.fbc' => ['nullable', 'string', 'max:255'],
            'acquisition.landing_page' => ['nullable', 'string', 'max:255'],
            'acquisition.referrer' => ['nullable', 'string', 'max:255'],
        ]);

        // `ip`/`user_agent` quan sát PHÍA SERVER lúc request này — không tin giá trị client gửi lên.
        $acquisition = array_merge($data['acquisition'] ?? [], [
            'event_id' => $data['event_id'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'captured_at' => now()->toIso8601String(),
        ]);

        [$user, $tenant] = DB::transaction(function () use ($data, $acquisition) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $tenant = Tenant::create(['name' => $data['tenant_name'] ?? ($data['name'].' Shop')]);
            $tenant->forceFill(['acquisition' => $acquisition])->save();
            $roles = app(TenantRoleProvisioner::class)->seedDefaults($tenant);
            $tenant->users()->attach($user->getKey(), [
                'role' => Role::Owner->value,
                'role_id' => $roles[Role::Owner->value]->getKey(),
            ]);

            return [$user, $tenant];
        });

        // Tenant tạo xong ⇒ phát event để Billing khởi động trial 14 ngày (SPEC 0018 §3.1)
        // và Tenancy tự báo CompleteRegistration về Meta CAPI nếu đã cấu hình (SPEC 2026-07-22).
        TenantCreated::dispatch($tenant);

        // SPEC 0022 — fire `Registered` event ⇒ Laravel listener `SendEmailVerificationNotification`
        // tự gọi `$user->sendEmailVerificationNotification()` (override ở User dùng
        // `VerifyEmailNotification` branded). Notification implement ShouldQueue ⇒
        // enqueue vào queue `notifications`.
        event(new Registered($user));

        $this->startSession($request, $user);

        return response()->json(['data' => $this->userPayload($user)], 201);
    }
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php artisan test --filter=AcquisitionCaptureTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Verify test cũ vẫn PASS**

Run: `php artisan test --filter=AuthTenantTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Tenancy/Http/Controllers/AuthController.php app/tests/Feature/Tenancy/AcquisitionCaptureTest.php
git commit -m "feat(tenancy): AuthController::register lưu acquisition (UTM/fbclid + ip/user-agent server-side)"
```

---

### Task 5: `FacebookCapiReporter` service — gọi Meta Conversions API

**Files:**
- Create: `app/app/Modules/Tenancy/Services/FacebookCapiReporter.php`
- Create: `app/tests/Feature/Tenancy/FacebookCapiReporterTest.php`

**Interfaces:**
- Consumes: `system_setting('growth.facebook.*', ...)` (Task 1), `Tenant::acquisition` (Task 3).
- Produces: `FacebookCapiReporter::reportCompleteRegistration(Tenant $tenant, string $email): bool` — dùng bởi Task 6 (listener). Trả `true` nếu đã gửi thành công HOẶC đã gửi trước đó (idempotent no-op); `false` nếu bỏ qua (tắt tính năng/thiếu cấu hình) hoặc gửi lỗi.

- [ ] **Step 1: Viết test trước (FAIL — class chưa tồn tại)**

Tạo `app/tests/Feature/Tenancy/FacebookCapiReporterTest.php`:

```php
<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Services\FacebookCapiReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookCapiReporterTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(array $acquisition = []): Tenant
    {
        $tenant = Tenant::create(['name' => 'CapiShop']);
        $tenant->forceFill(['acquisition' => $acquisition])->save();

        return $tenant->fresh();
    }

    public function test_sends_complete_registration_event_and_marks_reported(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        app(SystemSettingService::class)->set('growth.facebook.enabled', true);
        app(SystemSettingService::class)->set('growth.facebook.pixel_id', 'PIXEL_1');
        app(SystemSettingService::class)->set('growth.facebook.capi_access_token', 'TOKEN_1');

        $tenant = $this->makeTenant([
            'event_id' => 'evt-1', 'fbp' => 'fb.1.111.222', 'ip' => '1.2.3.4', 'user_agent' => 'UA',
        ]);

        $sent = app(FacebookCapiReporter::class)->reportCompleteRegistration($tenant, 'Owner@Example.com');

        $this->assertTrue($sent);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://graph.facebook.com/v25.0/PIXEL_1/events'
                && $body['data'][0]['event_name'] === 'CompleteRegistration'
                && $body['data'][0]['event_id'] === 'evt-1'
                && $body['data'][0]['user_data']['em'][0] === hash('sha256', 'owner@example.com')
                && $body['data'][0]['user_data']['fbp'] === 'fb.1.111.222'
                && $body['access_token'] === 'TOKEN_1';
        });
        $this->assertNotEmpty($tenant->fresh()->acquisition['capi_reported_at'] ?? null);
    }

    public function test_skips_when_disabled(): void
    {
        Http::fake();
        app(SystemSettingService::class)->set('growth.facebook.enabled', false);
        $tenant = $this->makeTenant();

        $sent = app(FacebookCapiReporter::class)->reportCompleteRegistration($tenant, 'owner@example.com');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_idempotent_when_already_reported(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        app(SystemSettingService::class)->set('growth.facebook.enabled', true);
        app(SystemSettingService::class)->set('growth.facebook.pixel_id', 'PIXEL_1');
        app(SystemSettingService::class)->set('growth.facebook.capi_access_token', 'TOKEN_1');
        $tenant = $this->makeTenant(['capi_reported_at' => now()->toIso8601String()]);

        $sent = app(FacebookCapiReporter::class)->reportCompleteRegistration($tenant, 'owner@example.com');

        $this->assertTrue($sent);
        Http::assertNothingSent();
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=FacebookCapiReporterTest`
Expected: FAIL — `Class "CMBcoreSeller\Modules\Tenancy\Services\FacebookCapiReporter" not found`.

- [ ] **Step 3: Viết `FacebookCapiReporter`**

Tạo `app/app/Modules/Tenancy/Services/FacebookCapiReporter.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Services;

use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Báo sự kiện `CompleteRegistration` về Meta Conversions API khi tenant mới đăng ký
 * (SPEC 2026-07-22-facebook-pixel-capi-growth-attribution-design.md).
 *
 * Xác minh qua tài liệu chính chủ Meta (Playwright, 2026-07-22):
 *   - Endpoint: POST https://graph.facebook.com/v25.0/{pixel_id}/events, body {data:[...]},
 *     `access_token` gửi kèm trong body (cùng convention với FacebookPageConnector::reportPurchase
 *     đã có trong Messaging module).
 *   - `em` (email) bắt buộc hash SHA-256(lowercase(trim(email))), dạng list<string>.
 *   - Dedup với Pixel qua cặp (event_id, event_name) khớp `fbq('track', name, data, {eventID})`.
 *
 * Cấu hình 100% qua `system_setting('growth.facebook.*')` (KHÔNG đọc .env) — chưa cấu hình
 * (tắt hoặc thiếu pixel_id/token) ⇒ no-op, trả false. Idempotent qua
 * `tenant->acquisition['capi_reported_at']`. Best-effort: lỗi HTTP chỉ log warning, không throw
 * (không được làm hỏng luồng đăng ký).
 */
class FacebookCapiReporter
{
    private const GRAPH_VERSION = 'v25.0';

    public function reportCompleteRegistration(Tenant $tenant, string $email): bool
    {
        if (! (bool) system_setting('growth.facebook.enabled', false)) {
            return false;
        }
        $pixelId = (string) system_setting('growth.facebook.pixel_id', '');
        $token = (string) system_setting('growth.facebook.capi_access_token', '');
        if ($pixelId === '' || $token === '') {
            return false;
        }

        $acquisition = (array) ($tenant->acquisition ?? []);
        if (! empty($acquisition['capi_reported_at'])) {
            return true; // đã gửi trước đó — idempotent no-op.
        }

        $eventId = (string) ($acquisition['event_id'] ?: 'tenant-'.$tenant->getKey());
        $event = [
            'event_name' => 'CompleteRegistration',
            'event_time' => $tenant->created_at->getTimestamp(),
            'event_id' => $eventId,
            'event_source_url' => (string) ($acquisition['landing_page'] ?: config('app.url')),
            'action_source' => 'website',
            'user_data' => array_filter([
                'em' => [hash('sha256', mb_strtolower(trim($email)))],
                'client_ip_address' => $acquisition['ip'] ?? null,
                'client_user_agent' => $acquisition['user_agent'] ?? null,
                'fbp' => $acquisition['fbp'] ?? null,
                'fbc' => $acquisition['fbc'] ?? null,
            ], fn ($v) => $v !== null),
        ];

        $payload = ['data' => [$event], 'access_token' => $token];
        $testCode = (string) system_setting('growth.facebook.test_event_code', '');
        if ($testCode !== '') {
            $payload['test_event_code'] = $testCode;
        }

        $res = Http::post('https://graph.facebook.com/'.self::GRAPH_VERSION."/{$pixelId}/events", $payload);

        if (! $res->successful()) {
            Log::warning('tenancy.growth.fb_capi_report.failed', [
                'tenant_id' => $tenant->getKey(), 'status' => $res->status(), 'body' => $res->body(),
            ]);

            return false;
        }

        $tenant->forceFill([
            'acquisition' => array_merge($acquisition, ['capi_reported_at' => now()->toIso8601String()]),
        ])->save();

        return true;
    }
}
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php artisan test --filter=FacebookCapiReporterTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Tenancy/Services/FacebookCapiReporter.php app/tests/Feature/Tenancy/FacebookCapiReporterTest.php
git commit -m "feat(tenancy): FacebookCapiReporter — gửi CompleteRegistration qua Meta Conversions API"
```

---

### Task 6: `ReportSignupToMetaCapi` listener — nối vào `TenantCreated`

**Files:**
- Create: `app/app/Modules/Tenancy/Listeners/ReportSignupToMetaCapi.php`
- Modify: `app/app/Modules/Tenancy/TenancyServiceProvider.php`
- Create: `app/tests/Feature/Tenancy/RegisterReportsToMetaCapiTest.php`

**Interfaces:**
- Consumes: `FacebookCapiReporter::reportCompleteRegistration()` (Task 5), `TenantCreated` event (đã có sẵn), `Tenant::users()` relation với pivot `role` (đã có sẵn).

- [ ] **Step 1: Viết test trước (FAIL — listener chưa nối)**

Tạo `app/tests/Feature/Tenancy/RegisterReportsToMetaCapiTest.php`:

```php
<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * QUEUE_CONNECTION=sync trong phpunit.xml ⇒ listener `ShouldQueue` chạy ngay trong
 * cùng request lúc test — không cần giả lập queue riêng.
 */
class RegisterReportsToMetaCapiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_reports_complete_registration_when_growth_pixel_enabled(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        app(SystemSettingService::class)->set('growth.facebook.enabled', true);
        app(SystemSettingService::class)->set('growth.facebook.pixel_id', 'PIXEL_1');
        app(SystemSettingService::class)->set('growth.facebook.capi_access_token', 'TOKEN_1');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van D', 'email' => 'd@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertCreated();

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v25.0/PIXEL_1/events'
            && $request->data()['data'][0]['user_data']['em'][0] === hash('sha256', 'd@example.com'));
    }

    public function test_register_does_not_call_meta_when_growth_pixel_disabled(): void
    {
        Http::fake();
        app(SystemSettingService::class)->set('growth.facebook.enabled', false);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van E', 'email' => 'e@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertCreated();

        Http::assertNothingSent();
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=RegisterReportsToMetaCapiTest`
Expected: FAIL trên `test_register_reports_complete_registration_when_growth_pixel_enabled` (`Http::assertNothingSent` implicit — không có request nào được gửi vì chưa có listener).

- [ ] **Step 3: Viết listener**

Tạo `app/app/Modules/Tenancy/Listeners/ReportSignupToMetaCapi.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Listeners;

use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Events\TenantCreated;
use CMBcoreSeller\Modules\Tenancy\Services\FacebookCapiReporter;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Khi tenant mới tạo ⇒ báo sự kiện CompleteRegistration về Meta Conversions API (đo lường
 * hiệu quả quảng cáo Facebook dẫn khách đăng ký — SPEC 2026-07-22).
 *
 * Best-effort: `FacebookCapiReporter` tự no-op nếu chưa cấu hình Pixel/CAPI. Queue `billing`
 * — TÁI DÙNG queue đã wired trong Horizon supervisor (cùng chỗ `StartTrialSubscription` nghe
 * event này) thay vì tự đặt tên queue mới không ai lắng nghe.
 */
class ReportSignupToMetaCapi implements ShouldQueue
{
    public string $queue = 'billing';

    public int $tries = 3;

    public function __construct(protected FacebookCapiReporter $reporter) {}

    public function handle(TenantCreated $event): void
    {
        $tenant = $event->tenant;
        $owner = $tenant->users()->wherePivot('role', Role::Owner->value)->first();
        if ($owner === null || ! $owner->email) {
            return;
        }

        $this->reporter->reportCompleteRegistration($tenant, $owner->email);
    }
}
```

- [ ] **Step 4: Đăng ký listener trong `TenancyServiceProvider`**

Trong `app/app/Modules/Tenancy/TenancyServiceProvider.php`, thêm import và `Event::listen`:

```php
use CMBcoreSeller\Modules\Tenancy\Events\TenantCreated;
use CMBcoreSeller\Modules\Tenancy\Listeners\LogUserLogin;
use CMBcoreSeller\Modules\Tenancy\Listeners\ReportSignupToMetaCapi;
```

Trong `boot()`, ngay sau dòng `Event::listen(Login::class, LogUserLogin::class);`:

```php
        // Ghi lịch sử đăng nhập nhân viên tenant — chỉ guard `web` (lọc trong listener).
        Event::listen(Login::class, LogUserLogin::class);

        // SPEC 2026-07-22 — báo CompleteRegistration về Meta Conversions API (best-effort, no-op
        // nếu chưa cấu hình Pixel ở /admin/settings).
        Event::listen(TenantCreated::class, ReportSignupToMetaCapi::class);
```

- [ ] **Step 5: Chạy test, xác nhận PASS**

Run: `php artisan test --filter=RegisterReportsToMetaCapiTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Verify không vỡ test Billing (cùng nghe `TenantCreated`)**

Run: `php artisan test --filter=Billing`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Tenancy/Listeners/ReportSignupToMetaCapi.php app/app/Modules/Tenancy/TenancyServiceProvider.php app/tests/Feature/Tenancy/RegisterReportsToMetaCapiTest.php
git commit -m "feat(tenancy): nối ReportSignupToMetaCapi vào TenantCreated"
```

---

### Task 7: Admin — expose `acquisition` + filter `utm_source` trên danh sách tenant

**Files:**
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminTenantController.php`
- Create: `app/tests/Feature/Admin/AdminTenantAcquisitionFilterTest.php`

**Interfaces:**
- Produces: `GET /api/v1/admin/tenants?utm_source=...` filter; mỗi tenant summary có thêm field `acquisition` (toàn bộ object `Tenant::acquisition`, dùng ở cả list và detail vì `show()` merge từ `summary()`).

- [ ] **Step 1: Viết test trước (FAIL)**

Tạo `app/tests/Feature/Admin/AdminTenantAcquisitionFilterTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantAcquisitionFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_tenants_by_utm_source(): void
    {
        $admin = AdminUser::factory()->create();
        $fb = Tenant::create(['name' => 'Shop FB']);
        $fb->forceFill(['acquisition' => ['utm_source' => 'facebook', 'utm_campaign' => 'summer']])->save();
        Tenant::create(['name' => 'Shop Direct']);

        $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/tenants?utm_source=facebook')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Shop FB')
            ->assertJsonPath('data.0.acquisition.utm_source', 'facebook')
            ->assertJsonPath('data.0.acquisition.utm_campaign', 'summer');
    }

    public function test_tenant_without_acquisition_returns_null(): void
    {
        $admin = AdminUser::factory()->create();
        Tenant::create(['name' => 'Shop Direct']);

        $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/tenants')
            ->assertOk()
            ->assertJsonPath('data.0.acquisition', null);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AdminTenantAcquisitionFilterTest`
Expected: FAIL — `acquisition` không có trong response, filter `utm_source` bị bỏ qua (trả cả 2 tenant).

- [ ] **Step 3: Sửa `AdminTenantController`**

Trong `app/app/Modules/Admin/Http/Controllers/AdminTenantController.php`, method `index()` (dòng 45-82), thêm filter ngay sau khối `if ($suspended) {...}` (dòng 63-65):

```php
        if ($suspended) {
            $query->where('status', 'suspended');
        }
        if ($utmSource = (string) $request->query('utm_source', '')) {
            $query->where('acquisition->utm_source', $utmSource);
        }
```

Trong method `summary()` (dòng 405-436), thêm field vào mảng trả về, ngay sau `'status' => $tenant->status,`:

```php
            'status' => $tenant->status,
            'acquisition' => $tenant->acquisition,
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `php artisan test --filter=AdminTenantAcquisitionFilterTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Verify test tenant list cũ vẫn PASS**

Run: `php artisan test --filter=AdminTenantSearchTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/AdminTenantController.php app/tests/Feature/Admin/AdminTenantAcquisitionFilterTest.php
git commit -m "feat(admin): filter tenant theo utm_source + expose acquisition"
```

---

### Task 8: `AdminGrowthService` + `AdminGrowthController` + route

**Files:**
- Create: `app/app/Modules/Admin/Services/AdminGrowthService.php`
- Create: `app/app/Modules/Admin/Http/Controllers/AdminGrowthController.php`
- Modify: `app/app/Modules/Admin/Http/routes.php`
- Create: `app/tests/Feature/Admin/AdminGrowthAttributionTest.php`

**Interfaces:**
- Consumes: `Tenant::acquisition` (Task 3), `Invoice::STATUS_PAID`, `Subscription::STATUS_ACTIVE`/`CYCLE_TRIAL` (đã có sẵn trong Billing module).
- Produces: `GET /api/v1/admin/growth/attribution?from=&to=&group_by=utm_source|utm_campaign|utm_medium` → `{ data: [{source, signups, paid, conversion_rate, revenue_vnd}] }`.

- [ ] **Step 1: Viết test trước (FAIL — route/class chưa tồn tại)**

Tạo `app/tests/Feature/Admin/AdminGrowthAttributionTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGrowthAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_signups_and_paid_conversions_by_utm_source(): void
    {
        $admin = AdminUser::factory()->create();
        $plan = Plan::query()->create([
            'code' => 'starter', 'name' => 'Starter', 'is_active' => true, 'sort_order' => 1,
            'price_monthly' => 190_000, 'price_yearly' => 1_900_000, 'currency' => 'VND',
            'trial_days' => 14, 'limits' => [], 'features' => [],
        ]);

        // Nguồn "facebook": 1 đăng ký, đã lên gói trả phí (subscription active non-trial).
        $fbTenant = Tenant::create(['name' => 'FB Shop']);
        $fbTenant->forceFill(['acquisition' => ['utm_source' => 'facebook']])->save();
        Subscription::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $fbTenant->id, 'plan_id' => $plan->id, 'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        Invoice::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $fbTenant->id, 'subscription_id' => 0, 'code' => 'INV-FB-1',
            'status' => Invoice::STATUS_PAID, 'period_start' => now()->format('Y-m-d'),
            'period_end' => now()->addMonth()->format('Y-m-d'),
            'subtotal' => 190_000, 'tax' => 0, 'total' => 190_000, 'currency' => 'VND',
            'due_at' => now(), 'paid_at' => now(),
        ]);

        // Không có utm — nhóm "Không xác định", chưa lên gói.
        Tenant::create(['name' => 'Direct Shop']);

        $response = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/growth/attribution');

        $response->assertOk();
        $rows = collect($response->json('data'))->keyBy('source');
        $this->assertSame(1, $rows['facebook']['signups']);
        $this->assertSame(1, $rows['facebook']['paid']);
        $this->assertSame(100.0, $rows['facebook']['conversion_rate']);
        $this->assertSame(190_000, $rows['facebook']['revenue_vnd']);
        $this->assertSame(1, $rows['Không xác định']['signups']);
        $this->assertSame(0, $rows['Không xác định']['paid']);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AdminGrowthAttributionTest`
Expected: FAIL — route `admin/growth/attribution` 404.

- [ ] **Step 3: Viết `AdminGrowthService`**

Tạo `app/app/Modules/Admin/Services/AdminGrowthService.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Services;

use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;

/**
 * Báo cáo tăng trưởng theo nguồn UTM — dùng ở `/admin/growth`
 * (SPEC 2026-07-22-facebook-pixel-capi-growth-attribution-design.md §5).
 *
 * Gom nhóm bằng PHP (không `selectRaw` JSON path) vì cú pháp trích JSON khác nhau giữa
 * SQLite (dev/test, `json_extract`) và Postgres (prod, `->>`) — Laravel không có API JSON
 * aggregate cross-driver. Quy mô tenant của SaaS này đủ nhỏ để nhóm an toàn trong bộ nhớ.
 */
class AdminGrowthService
{
    /**
     * @return list<array{source:string, signups:int, paid:int, conversion_rate:float, revenue_vnd:int}>
     */
    public function attribution(string $groupBy, ?Carbon $from, ?Carbon $to): array
    {
        $tenants = Tenant::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->get(['id', 'acquisition', 'created_at']);

        if ($tenants->isEmpty()) {
            return [];
        }

        $ids = $tenants->pluck('id')->all();

        $revenueByTenant = Invoice::query()->withoutGlobalScope(TenantScope::class)
            ->where('status', Invoice::STATUS_PAID)
            ->whereIn('tenant_id', $ids)
            ->selectRaw('tenant_id, SUM(total) as total')
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $paidByActiveSubscription = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->whereIn('tenant_id', $ids)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('billing_cycle', '!=', Subscription::CYCLE_TRIAL)
            ->pluck('tenant_id');

        $paidTenantIds = collect($revenueByTenant->keys())->merge($paidByActiveSubscription)->unique();

        $groups = $tenants->groupBy(function (Tenant $t) use ($groupBy) {
            $value = (string) (($t->acquisition ?? [])[$groupBy] ?? '');

            return $value !== '' ? $value : 'Không xác định';
        });

        return $groups->map(function ($group, string $source) use ($paidTenantIds, $revenueByTenant) {
            $tenantIds = $group->pluck('id');
            $signups = $tenantIds->count();
            $paid = $tenantIds->intersect($paidTenantIds)->count();
            $revenue = (int) $tenantIds->map(fn ($id) => (int) ($revenueByTenant[$id] ?? 0))->sum();

            return [
                'source' => $source,
                'signups' => $signups,
                'paid' => $paid,
                'conversion_rate' => $signups > 0 ? round($paid / $signups * 100, 1) : 0.0,
                'revenue_vnd' => $revenue,
            ];
        })->values()->sortByDesc('signups')->values()->all();
    }
}
```

- [ ] **Step 4: Viết `AdminGrowthController`**

Tạo `app/app/Modules/Admin/Http/Controllers/AdminGrowthController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Modules\Admin\Services\AdminGrowthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

/** /api/v1/admin/growth/* — báo cáo tăng trưởng theo nguồn UTM (SPEC 2026-07-22). */
class AdminGrowthController extends Controller
{
    public function __construct(protected AdminGrowthService $service) {}

    /** GET /api/v1/admin/growth/attribution */
    public function attribution(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'group_by' => ['nullable', 'string', 'in:utm_source,utm_campaign,utm_medium'],
        ]);

        $rows = $this->service->attribution(
            $data['group_by'] ?? 'utm_source',
            isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null,
            isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : null,
        );

        return response()->json(['data' => $rows]);
    }
}
```

- [ ] **Step 5: Thêm route**

Trong `app/app/Modules/Admin/Http/routes.php`, thêm import:

```php
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminGrowthController;
```

Thêm route trong block `Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])->prefix('api/v1/admin')->group(...)`, ngay sau khối `// --- Dashboard overview ...`:

```php
        // --- Growth attribution (SPEC 2026-07-22) ---
        Route::get('growth/attribution', [AdminGrowthController::class, 'attribution'])
            ->name('admin.growth.attribution');
```

- [ ] **Step 6: Chạy test, xác nhận PASS**

Run: `php artisan test --filter=AdminGrowthAttributionTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Admin/Services/AdminGrowthService.php app/app/Modules/Admin/Http/Controllers/AdminGrowthController.php app/app/Modules/Admin/Http/routes.php app/tests/Feature/Admin/AdminGrowthAttributionTest.php
git commit -m "feat(admin): báo cáo Growth attribution theo utm_source/campaign/medium"
```

---

### Task 9: Cập nhật `docs/05-api/endpoints.md`

**Files:**
- Modify: `docs/05-api/endpoints.md`

- [ ] **Step 1: Thêm dòng cho endpoint mới**

Trong bảng admin (gần dòng 373, sau dòng `GET /api/v1/admin/tenants`), thêm ghi chú filter mới bằng cách nối vào mô tả dòng đó (không tách dòng riêng, tránh phá format bảng): sau `query: q, over_quota (1), suspended (1), page, per_page≤100` thêm `, utm_source (lọc theo acquisition->utm_source)`; và field `TenantSummary` thêm `acquisition:{utm_source,...}|null`.

Thêm dòng mới ngay sau (trong cùng bảng admin, gần các dòng tenants):

```
| GET | `/api/v1/admin/growth/attribution` | web + `auth:admin_web` | query: `from` (date), `to` (date), `group_by` (`utm_source`\|`utm_campaign`\|`utm_medium`, mặc định `utm_source`) | `{ data:[{source,signups,paid,conversion_rate,revenue_vnd}] }` — gom nhóm tenant theo UTM đăng ký; `paid` = có invoice `status=paid` HOẶC subscription `active` non-trial; `revenue_vnd` = tổng invoice paid. `source="Không xác định"` khi tenant không có UTM. SPEC 2026-07-22. |
```

- [ ] **Step 2: Commit**

```bash
git add docs/05-api/endpoints.md
git commit -m "docs(api): thêm endpoint GET /admin/growth/attribution + filter utm_source"
```

---

### Task 10: FE — `lib/acquisition.ts` (bắt UTM first-touch)

**Files:**
- Create: `app/resources/js/lib/acquisition.ts`

**Interfaces:**
- Produces: `captureAcquisition(search: string, pathname: string): void`, `readAcquisition(): AcquisitionData`, `clearAcquisition(): void`, `readFacebookCookies(): {fbp?: string; fbc?: string}` — dùng ở Task 13 (`app.tsx`) và Task 14 (`RegisterPage.tsx`).

- [ ] **Step 1: Viết file**

Tạo `app/resources/js/lib/acquisition.ts`:

```ts
/**
 * Bắt UTM/fbclid first-touch lúc khách ghé trang public lần đầu (SPEC
 * 2026-07-22-facebook-pixel-capi-growth-attribution-design.md §3) — cùng pattern với
 * `lib/extRedirect.ts`. Chỉ ghi localStorage nếu CHƯA có sẵn (first-touch: giữ nguồn
 * quảng cáo đầu tiên, không bị ghi đè bởi lượt ghé thăm sau).
 */
const STORAGE_KEY = 'cmb_acquisition_v1';

export interface AcquisitionData {
    utm_source?: string;
    utm_medium?: string;
    utm_campaign?: string;
    utm_content?: string;
    utm_term?: string;
    fbclid?: string;
    landing_page?: string;
    referrer?: string;
}

const UTM_FIELDS: Array<keyof AcquisitionData> = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid'];

export function captureAcquisition(search: string, pathname: string): void {
    if (localStorage.getItem(STORAGE_KEY)) {
        return;
    }
    const params = new URLSearchParams(search);
    const data: AcquisitionData = {};
    for (const field of UTM_FIELDS) {
        const v = params.get(field);
        if (v) data[field] = v;
    }
    if (Object.keys(data).length === 0) {
        return;
    }
    data.landing_page = pathname;
    if (document.referrer) data.referrer = document.referrer;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

export function readAcquisition(): AcquisitionData {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return {};
    try {
        return JSON.parse(raw) as AcquisitionData;
    } catch {
        return {};
    }
}

export function clearAcquisition(): void {
    localStorage.removeItem(STORAGE_KEY);
}

function readCookie(name: string): string | undefined {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : undefined;
}

/** `_fbp`/`_fbc` do chính script Meta Pixel tự set (base code trong app.blade.php). */
export function readFacebookCookies(): { fbp?: string; fbc?: string } {
    return { fbp: readCookie('_fbp'), fbc: readCookie('_fbc') };
}
```

- [ ] **Step 2: Typecheck**

Run (từ `app/`): `npm run typecheck`
Expected: không lỗi.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/acquisition.ts
git commit -m "feat(fe): lib/acquisition — bắt UTM/fbclid first-touch lúc ghé trang public"
```

---

### Task 11: FE — `lib/pixel.ts` (`usePixelPageview` hook)

**Files:**
- Create: `app/resources/js/lib/pixel.ts`

**Interfaces:**
- Produces: `usePixelPageview(): void` (hook, dùng ở Task 13); ambient type `Window.fbq` dùng ở Task 14.

- [ ] **Step 1: Viết file**

Tạo `app/resources/js/lib/pixel.ts`:

```ts
import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';

declare global {
    interface Window {
        fbq?: (...args: unknown[]) => void;
    }
}

/**
 * Path public/pre-auth cho phép bắn thêm PageView khi đổi route trong SPA (base Pixel
 * code ở app.blade.php chỉ tự bắn 1 PageView lúc load cứng đầu tiên — SPA dùng React
 * Router nên các lượt điều hướng sau không reload trang). KHÔNG bắn cho route trong app
 * đã đăng nhập — tránh lẫn hành vi nội bộ khách hàng vào tài khoản quảng cáo Meta.
 */
const PUBLIC_PIXEL_PATHS = ['/', '/pricing', '/tools', '/api-docs', '/download', '/login', '/register'];

export function usePixelPageview(): void {
    const location = useLocation();
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false; // base Pixel code đã bắn 1 lần lúc load cứng — bỏ qua lần đầu.
            return;
        }
        if (!PUBLIC_PIXEL_PATHS.includes(location.pathname) || typeof window.fbq !== 'function') {
            return;
        }
        window.fbq('track', 'PageView');
    }, [location.pathname]);
}
```

- [ ] **Step 2: Typecheck**

Run: `npm run typecheck`
Expected: không lỗi.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/pixel.ts
git commit -m "feat(fe): lib/pixel — usePixelPageview cho route public/pre-auth"
```

---

### Task 12: Blade — nhúng base Pixel code

**Files:**
- Modify: `app/resources/views/app.blade.php`

**Interfaces:**
- Consumes: `system_setting('growth.facebook.enabled'|'growth.facebook.pixel_id', ...)` (Task 1).

- [ ] **Step 1: Thêm block Pixel**

Trong `app/resources/views/app.blade.php`, thêm ngay trước dòng `@vite(['resources/js/app.tsx'])` (dòng 39):

```blade
    @php($fbPixelId = system_setting('growth.facebook.enabled', false) ? system_setting('growth.facebook.pixel_id') : null)
    @if($fbPixelId)
        {{-- Meta Pixel base code (SPEC 2026-07-22) — chỉ nhúng khi admin đã bật + cấu hình
             Pixel ID ở /admin/settings (tab "Tăng trưởng"). Set cookie _fbp/_fbc dùng chung
             cho Conversions API (xem lib/acquisition.ts readFacebookCookies). --}}
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $fbPixelId }}');
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
          src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1"
        /></noscript>
    @endif
    @vite(['resources/js/app.tsx'])
```

- [ ] **Step 2: Verify Blade hợp lệ**

Run (từ `app/`): `php artisan view:cache && php artisan view:clear`
Expected: không lỗi cú pháp Blade (`view:cache` compile toàn bộ view, sẽ báo lỗi nếu `@if`/`@php` sai).

- [ ] **Step 3: Commit**

```bash
git add app/resources/views/app.blade.php
git commit -m "feat(fe): nhúng base Meta Pixel code vào app.blade.php (điều kiện system_setting)"
```

---

### Task 13: FE — wire `usePixelPageview` + `captureAcquisition` vào `app.tsx`

**Files:**
- Modify: `app/resources/js/app.tsx`

**Interfaces:**
- Consumes: `usePixelPageview()` (Task 11), `captureAcquisition()` (Task 10).

- [ ] **Step 1: Thêm import**

Trong `app/resources/js/app.tsx`, thêm sau dòng `import { useAuth } from '@/lib/auth';` (dòng 16):

```tsx
import { useEffect } from 'react';
import { usePixelPageview } from '@/lib/pixel';
import { captureAcquisition } from '@/lib/acquisition';
```

(Gộp `useEffect` vào import `react` sẵn có ở dòng 1 nếu ESLint báo trùng import — kiểm tra dòng 1 hiện là `import React from 'react';`; sửa thành `import React, { useEffect } from 'react';` và bỏ dòng `import { useEffect } from 'react';` riêng vừa thêm.)

- [ ] **Step 2: Gọi hook trong `Root()`**

Trong `function Root()` (dòng 41-78), ngay sau `const shell = prefs.ui_shell;`:

```tsx
function Root() {
    const { isLoading } = useAuth();
    const prefs = useUserPreferences();
    const shell = prefs.ui_shell;

    useEffect(() => {
        captureAcquisition(window.location.search, window.location.pathname);
    }, []);
    usePixelPageview();

    return (
```

- [ ] **Step 3: Typecheck + build**

Run: `npm run typecheck && npm run build`
Expected: không lỗi.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/app.tsx
git commit -m "feat(fe): wire usePixelPageview + captureAcquisition vào Root()"
```

---

### Task 14: FE — `RegisterPage.tsx` gắn acquisition/event_id + bắn `CompleteRegistration`

**Files:**
- Modify: `app/resources/js/pages/RegisterPage.tsx:75-79`
- Modify: `app/resources/js/lib/auth.tsx:70-87`

**Interfaces:**
- Consumes: `readAcquisition()`, `readFacebookCookies()`, `clearAcquisition()` (Task 10), `window.fbq` (Task 11).

- [ ] **Step 1: Mở rộng type `vars` của `useRegister`**

Trong `app/resources/js/lib/auth.tsx`, sửa `mutationFn` của `useRegister()` (dòng 70-87):

```tsx
export function useRegister() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: {
            name: string;
            email: string;
            password: string;
            password_confirmation: string;
            tenant_name?: string;
            captcha_token?: string;
            event_id?: string;
            acquisition?: Record<string, string | undefined>;
        }) => {
            await ensureCsrf();
            const { data } = await api.post<{ data: AuthUser }>('/auth/register', vars);
            return data.data;
        },
        onSuccess: (user) => qc.setQueryData(['me'], user),
    });
}
```

- [ ] **Step 2: Sửa `RegisterPage.tsx`**

Trong `app/resources/js/pages/RegisterPage.tsx`, thêm import (sau dòng `import { captureExtRedirect } from '@/lib/extRedirect';`, dòng 18):

```tsx
import { readAcquisition, readFacebookCookies, clearAcquisition } from '@/lib/acquisition';
```

Sửa `onFinish` của `<Form>` (dòng 78):

```tsx
                        onFinish={(v) => {
                            const eventId = crypto.randomUUID();
                            const acquisition = { ...readAcquisition(), ...readFacebookCookies() };
                            register.mutate(
                                { ...v, captcha_token: captchaToken, event_id: eventId, acquisition },
                                {
                                    onSuccess: () => {
                                        if (typeof window.fbq === 'function') {
                                            window.fbq('track', 'CompleteRegistration', {}, { eventID: eventId });
                                        }
                                        clearAcquisition();
                                        navigate('/dashboard');
                                    },
                                },
                            );
                        }}
```

- [ ] **Step 3: Typecheck + build**

Run: `npm run typecheck && npm run build`
Expected: không lỗi.

- [ ] **Step 4: Verify backend test liên quan vẫn PASS (payload mới FE gửi khớp validation backend Task 4)**

Run (từ `app/`): `php artisan test --filter=AcquisitionCaptureTest`
Expected: PASS (không đổi — chỉ xác nhận lại vì đây là điểm nối FE/BE quan trọng nhất của tính năng).

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/pages/RegisterPage.tsx app/resources/js/lib/auth.tsx
git commit -m "feat(fe): RegisterPage gắn acquisition/event_id + bắn CompleteRegistration"
```

---

### Task 15: Admin FE — cột "Nguồn" + filter `utm_source` trên `AdminTenantsPage`

**Files:**
- Modify: `app/resources/js/admin/lib/admin.tsx`
- Modify: `app/resources/js/admin/pages/tenants/AdminTenantsPage.tsx`

**Interfaces:**
- Consumes: `acquisition` field từ `GET /admin/tenants` (Task 7).
- Produces: `AdminTenantSummary.acquisition`, `AdminTenantsFilters.utm_source` — dùng ở Task 16 (drill-down từ Growth page).

- [ ] **Step 1: Thêm type + filter param trong `admin/lib/admin.tsx`**

Sau `export interface AdminTenantSummary { ... }` (dòng 28-38), thêm interface mới và field:

```ts
export interface TenantAcquisition {
    utm_source?: string | null;
    utm_medium?: string | null;
    utm_campaign?: string | null;
    utm_content?: string | null;
    utm_term?: string | null;
    fbclid?: string | null;
    landing_page?: string | null;
    referrer?: string | null;
    captured_at?: string | null;
    capi_reported_at?: string | null;
}

export interface AdminTenantSummary {
    id: number;
    name: string;
    slug: string;
    code: string;
    status: string;
    created_at: string | null;
    owner: AdminOwner | null;
    subscription: AdminSubscription | null;
    usage: { channel_accounts: { used: number; limit: number; over: boolean } };
    acquisition: TenantAcquisition | null;
}
```

Sửa `AdminTenantsFilters` và `useAdminTenants` (dòng 217-231):

```ts
export interface AdminTenantsFilters { q?: string; over_quota?: boolean; suspended?: boolean; utm_source?: string; page?: number; per_page?: number }

export function useAdminTenants(filters: AdminTenantsFilters = {}) {
    return useQuery({
        queryKey: ['admin', 'tenants', filters],
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            if (filters.q) params.q = filters.q;
            if (filters.over_quota) params.over_quota = 1;
            if (filters.suspended) params.suspended = 1;
            if (filters.utm_source) params.utm_source = filters.utm_source;
            if (filters.page) params.page = filters.page;
            if (filters.per_page) params.per_page = filters.per_page;
            const { data } = await api.get<Paginated<AdminTenantSummary>>('/admin/tenants', { params });
            return data;
        },
```

(Giữ nguyên phần còn lại của hook.)

- [ ] **Step 2: Thêm state + input filter + cột trong `AdminTenantsPage.tsx`**

Thêm import `useLocation` và đọc `presetUtmSource` từ router state (khớp Task 16). Sửa đầu component (dòng 26-39):

```tsx
export function AdminTenantsPage() {
    const navigate = useNavigate();
    const location = useLocation();
    const presetUtmSource = (location.state as { presetUtmSource?: string } | null)?.presetUtmSource;
    const [q, setQ] = useState('');
    const [kind, setKind] = useState<FilterKind>('all');
    const [utmSource, setUtmSource] = useState(presetUtmSource ?? '');
    const [page, setPage] = useState(1);
    const [selectedRowKeys, setSelectedRowKeys] = useState<Key[]>([]);
    const [selectedRows, setSelectedRows] = useState<AdminTenantSummary[]>([]);

    const filters = useMemo(() => ({
        q: q.trim() || undefined,
        over_quota: kind === 'over_quota',
        suspended: kind === 'suspended',
        utm_source: utmSource.trim() || undefined,
        page, per_page: 30,
    }), [q, kind, utmSource, page]);
```

Thêm import `useLocation` vào dòng import `react-router-dom` (dòng 3):

```tsx
import { useLocation, useNavigate } from 'react-router-dom';
```

Thêm cột "Nguồn" trong mảng `columns` (sau cột `plan`, trước cột `channels` — dòng 77-85):

```tsx
        {
            title: 'Nguồn', key: 'acquisition', width: 150,
            render: (_v, r) => r.acquisition?.utm_source ? (
                <Tooltip title={r.acquisition.utm_campaign ? `Chiến dịch: ${r.acquisition.utm_campaign}` : undefined}>
                    <Tag>{r.acquisition.utm_source}</Tag>
                </Tooltip>
            ) : <Typography.Text type="secondary">—</Typography.Text>,
        },
```

Cập nhật `TABLE_SCROLL_X` (dòng 21-24) — cộng thêm 150 cho cột mới:

```tsx
// Tổng chiều rộng cột thực tế cho scroll={{x}}: 48 (checkbox) + 240 (Gian hàng) + 220
// (Chủ sở hữu) + 150 (Xác minh email) + 180 (Gói) + 150 (Nguồn) + 180 (Gian hàng đã kết nối)
// + 160 (Trạng thái) = 1328, làm tròn lên 1330 cho phần đệm border/padding của ô.
const TABLE_SCROLL_X = 1330;
```

Thêm ô input filter trong JSX (ngay sau `<Radio.Group ... options={KIND_OPTIONS} />` trong `<Space>` đầu, dòng 130-137):

```tsx
                <Space size={12} wrap style={{ marginBottom: 12 }}>
                    <Input prefix={<SearchOutlined />} placeholder="Tìm theo tên / slug" allowClear
                        value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }}
                        style={{ width: 280 }} />
                    <Radio.Group value={kind} optionType="button" buttonStyle="solid"
                        onChange={(e) => { setKind(e.target.value as FilterKind); setPage(1); }}
                        options={KIND_OPTIONS} />
                    <Input placeholder="Lọc theo utm_source" allowClear
                        value={utmSource} onChange={(e) => { setUtmSource(e.target.value); setPage(1); }}
                        style={{ width: 200 }} />
                </Space>
```

- [ ] **Step 3: Typecheck + build**

Run: `npm run typecheck && npm run build`
Expected: không lỗi.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/lib/admin.tsx app/resources/js/admin/pages/tenants/AdminTenantsPage.tsx
git commit -m "feat(admin): cột Nguồn + filter utm_source trên danh sách tenant"
```

---

### Task 16: Admin FE — trang `AdminGrowthPage` + route + sidebar

**Files:**
- Create: `app/resources/js/admin/pages/growth/AdminGrowthPage.tsx`
- Modify: `app/resources/js/admin/lib/admin.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx`
- Modify: `app/resources/js/admin/AdminLayout.tsx`

**Interfaces:**
- Consumes: `GET /admin/growth/attribution` (Task 8), điều hướng sang `/admin/tenants` với `state.presetUtmSource` (khớp Task 15).

- [ ] **Step 1: Thêm hook `useAdminGrowthAttribution` trong `admin/lib/admin.tsx`**

Thêm cuối file (`app/resources/js/admin/lib/admin.tsx`):

```ts
export interface GrowthAttributionRow {
    source: string;
    signups: number;
    paid: number;
    conversion_rate: number;
    revenue_vnd: number;
}

export interface GrowthAttributionFilters {
    from?: string;
    to?: string;
    group_by?: 'utm_source' | 'utm_campaign' | 'utm_medium';
}

export function useAdminGrowthAttribution(filters: GrowthAttributionFilters = {}) {
    return useQuery({
        queryKey: ['admin', 'growth', 'attribution', filters],
        queryFn: async () => {
            const { data } = await api.get<{ data: GrowthAttributionRow[] }>('/admin/growth/attribution', { params: filters });
            return data.data;
        },
    });
}
```

- [ ] **Step 2: Viết `AdminGrowthPage.tsx`**

Tạo `app/resources/js/admin/pages/growth/AdminGrowthPage.tsx`:

```tsx
import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, DatePicker, Radio, Space, Table, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { useAdminGrowthAttribution, type GrowthAttributionFilters, type GrowthAttributionRow } from '@admin/lib/admin';
import { formatMoney } from '@/lib/format';

const { RangePicker } = DatePicker;

const GROUP_BY_OPTIONS: Array<{ value: GrowthAttributionFilters['group_by']; label: string }> = [
    { value: 'utm_source', label: 'Nguồn (utm_source)' },
    { value: 'utm_campaign', label: 'Chiến dịch (utm_campaign)' },
    { value: 'utm_medium', label: 'Kênh (utm_medium)' },
];

export function AdminGrowthPage() {
    const navigate = useNavigate();
    const [groupBy, setGroupBy] = useState<GrowthAttributionFilters['group_by']>('utm_source');
    const [range, setRange] = useState<[dayjs.Dayjs, dayjs.Dayjs] | null>(null);

    const filters = useMemo<GrowthAttributionFilters>(() => ({
        group_by: groupBy,
        from: range?.[0]?.format('YYYY-MM-DD'),
        to: range?.[1]?.format('YYYY-MM-DD'),
    }), [groupBy, range]);

    const { data, isLoading } = useAdminGrowthAttribution(filters);

    const columns: ColumnsType<GrowthAttributionRow> = [
        { title: 'Nguồn', dataIndex: 'source', key: 'source' },
        { title: 'Đăng ký', dataIndex: 'signups', key: 'signups', width: 100 },
        { title: 'Đã lên gói', dataIndex: 'paid', key: 'paid', width: 110 },
        {
            title: 'Tỉ lệ chuyển đổi', dataIndex: 'conversion_rate', key: 'conversion_rate', width: 140,
            render: (v: number) => `${v}%`,
        },
        {
            title: 'Doanh thu', dataIndex: 'revenue_vnd', key: 'revenue_vnd', width: 160,
            render: (v: number) => formatMoney(v),
        },
    ];

    return (
        <div>
            <PageHeader
                title="Tăng trưởng — Nguồn đăng ký"
                subtitle="Gom nhóm tenant theo UTM lúc đăng ký, đối chiếu với gói trả phí hiện tại. Nhấp 1 dòng để xem danh sách tenant thuộc nguồn đó."
            />

            <Card styles={{ body: { padding: 12 } }}>
                <Space size={12} wrap style={{ marginBottom: 12 }}>
                    <Radio.Group value={groupBy} optionType="button" buttonStyle="solid"
                        onChange={(e) => setGroupBy(e.target.value)}
                        options={GROUP_BY_OPTIONS} />
                    <RangePicker
                        value={range}
                        onChange={(v) => setRange(v as [dayjs.Dayjs, dayjs.Dayjs] | null)}
                        placeholder={['Từ ngày đăng ký', 'Đến ngày đăng ký']}
                    />
                </Space>

                <Table<GrowthAttributionRow>
                    rowKey="source"
                    columns={columns}
                    dataSource={data ?? []}
                    loading={isLoading}
                    onRow={(r) => ({
                        onClick: () => navigate('/admin/tenants', {
                            state: groupBy === 'utm_source' ? { presetUtmSource: r.source } : undefined,
                        }),
                        style: { cursor: groupBy === 'utm_source' ? 'pointer' : 'default' },
                    })}
                    pagination={false}
                    size="middle"
                />
                {groupBy !== 'utm_source' && (
                    <Typography.Text type="secondary" style={{ display: 'block', marginTop: 8 }}>
                        Điều hướng sang danh sách tenant chỉ hỗ trợ khi gom nhóm theo utm_source.
                    </Typography.Text>
                )}
            </Card>
        </div>
    );
}
```

- [ ] **Step 3: Đăng ký route**

Trong `app/resources/js/admin/AdminApp.tsx`, thêm import:

```tsx
import { AdminGrowthPage } from './pages/growth/AdminGrowthPage';
```

Thêm route ngay sau `<Route path="tenants" element={<AdminTenantsPage />} />`:

```tsx
                    <Route path="growth" element={<AdminGrowthPage />} />
```

- [ ] **Step 4: Thêm menu sidebar**

Trong `app/resources/js/admin/AdminLayout.tsx`, thêm import icon (dòng 9-30, thêm vào danh sách):

```tsx
    FunnelPlotOutlined,
```

Thêm leaf vào group `'KHÁCH HÀNG'` (dòng 46-55), ngay sau `{ key: '/admin/tenants', ... }`:

```tsx
            { key: '/admin/tenants', icon: <ShopOutlined />, label: 'Tenants' },
            { key: '/admin/growth', icon: <FunnelPlotOutlined />, label: 'Tăng trưởng' },
```

- [ ] **Step 5: Typecheck + build**

Run (từ `app/`): `npm run typecheck && npm run build`
Expected: không lỗi.

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/admin/pages/growth/AdminGrowthPage.tsx app/resources/js/admin/lib/admin.tsx app/resources/js/admin/AdminApp.tsx app/resources/js/admin/AdminLayout.tsx
git commit -m "feat(admin): trang Growth attribution + route + menu sidebar"
```

---

### Task 17: Full verification pass

**Files:** không tạo/sửa file — chỉ chạy quality gate toàn repo.

- [ ] **Step 1: PHP format**

Run (từ `app/`): `vendor/bin/pint --test`
Expected: PASS (không file nào cần format lại). Nếu FAIL, chạy `vendor/bin/pint` rồi commit riêng.

- [ ] **Step 2: PHPStan**

Run: `vendor/bin/phpstan analyse`
Expected: PASS (level 5, không lỗi mới ngoài `phpstan-baseline.neon`).

- [ ] **Step 3: Toàn bộ PHPUnit**

Run: `php artisan test`
Expected: PASS toàn bộ (bao gồm các test đã biết fail từ trước theo `test-verify-baseline` — không có test MỚI nào fail liên quan tới thay đổi trong plan này).

- [ ] **Step 4: Frontend lint + typecheck + build**

Run: `npm run lint && npm run typecheck && npm run build`
Expected: PASS.

- [ ] **Step 5: Ghi chú vận hành (không phải code) — xác nhận với người vận hành trước khi coi tính năng "xong"**

Tính năng chỉ THỰC SỰ hoạt động sau khi super-admin vào `/admin/settings` → tab "Tăng trưởng" → điền `Pixel ID` thật + `Access Token` CAPI thật + bật `growth.facebook.enabled`. Trước đó mọi code đều no-op an toàn (không nhúng script, không gọi Graph API) — đúng thiết kế INERT-until-configured. Khuyến nghị bật `test_event_code` trước, soi tab "Test Events" trên Meta Events Manager bằng 1 lượt đăng ký thử, xác nhận nhận được cả sự kiện Pixel (Browser) lẫn CAPI (Server) đã dedup thành 1 dòng, rồi mới xoá `test_event_code` để chạy thật.

- [ ] **Step 6: Commit cuối (nếu Step 1 phát sinh thay đổi format)**

```bash
git add -A
git commit -m "chore: pint format pass"
```

(Bỏ qua nếu Step 1 đã PASS ngay từ đầu — không tạo commit rỗng.)
