# Pro Trial Mode + SePay Checkout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Gỡ gói `test_unlimited` (dời mọi tenant về `starter`), thay "beta mode" bằng "Chế độ trải nghiệm Pro" (admin bật/tắt + set thời gian, tenant tự đăng ký 1 tháng/1 lần vĩnh viễn), thêm modal điều khoản không hoàn lại dùng chung, và hoàn thiện luồng thanh toán SePay end-to-end + UI.

**Architecture:** Backend Laravel 11 modular (`Modules/Billing`, `Modules/Admin`, `Integrations/Payments/SePay`); frontend React + Ant Design (bundle tenant `app.tsx` + admin `admin.tsx`). Cấu hình chế độ trải nghiệm lưu qua `system_setting()` (catalog-registered); trạng thái "đã dùng trải nghiệm" lưu ở bảng mới `pro_trial_grants` (UNIQUE tenant_id). Thanh toán SePay đã có connector (QR VietQR + webhook sao kê → `InvoicePaid` → `ActivateSubscription`); phần thiếu là UI hiển thị QR + poll trạng thái + điều khoản.

**Tech Stack:** PHP 8 / Laravel 11, PHPUnit (feature tests, `RefreshDatabase`), React 18 + TypeScript + Ant Design + TanStack Query, Pint + PHPStan (level 5).

## Global Constraints

- Mọi lệnh PHP/Node chạy từ thư mục `app/` (không phải repo root).
- Namespace PSR-4 `CMBcoreSeller\` → `app/app/`.
- Money = integer VND. Timestamps ISO-8601 UTC. Response envelope `{ "data": ..., "meta": ... }` / `{ "error": {...} }`.
- Mọi bảng nghiệp vụ có `tenant_id` + trait `BelongsToTenant`; truy vấn cross-tenant phải `withoutGlobalScope(TenantScope::class)`.
- Migration idempotent; webhook idempotent; sync jobs idempotent.
- Bất biến: `subscriptions_one_alive_per_tenant` (partial unique) — mỗi tenant chỉ 1 subscription status ∈ {trialing, active, past_due}. Đổi gói phải cancel sub cũ TRƯỚC khi tạo mới, trong transaction.
- UI: icon `@ant-design/icons` (không emoji); ít lựa chọn dùng `Radio.Group`/`Segmented` (không `Select`); giới hạn "không giới hạn" hiển thị `-1`.
- User-facing strings tiếng Việt; code/DB/route tiếng Anh.
- Quality gate (chạy từ `app/`): `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run lint && npm run typecheck && npm run build`.
- Hằng số dùng lại: `Plan::CODE_TRIAL='trial'`, `CODE_STARTER='starter'`, `CODE_PRO='pro'`, `CODE_BUSINESS='business'`, `Plan::CODES=[trial,starter,pro,business]`. `Subscription::STATUS_ACTIVE/STATUS_TRIALING/STATUS_CANCELLED/STATUS_EXPIRED`, `ALIVE_STATUSES=[trialing,active,past_due]`, `CYCLE_MONTHLY/CYCLE_YEARLY/CYCLE_TRIAL`.

---

## Phase 1 — Gỡ `test_unlimited` + dời về `starter`

### Task 1: Migration repoint sub alive `test_unlimited` → `starter` + deactivate plan

**Files:**
- Create: `app/app/Modules/Billing/Database/Migrations/2026_07_10_100001_repoint_test_unlimited_to_starter.php`
- Test: `app/tests/Feature/Billing/RepointTestUnlimitedTest.php`

**Interfaces:**
- Produces: migration `up()` chuyển mọi subscription alive trên plan `test_unlimited` sang `starter` (cancel cũ + tạo mới active monthly), set `plans.is_active=false` cho `test_unlimited`. Không xoá row plan.

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RepointTestUnlimitedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    private function makeTestUnlimitedPlan(): Plan
    {
        return Plan::query()->create([
            'code' => 'test_unlimited', 'name' => 'Test', 'description' => '',
            'is_active' => true, 'sort_order' => 99,
            'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'VND', 'trial_days' => 0,
            'limits' => ['max_channel_accounts' => -1], 'features' => ['ai' => true],
        ]);
    }

    public function test_migration_repoints_alive_test_unlimited_subs_to_starter_and_deactivates_plan(): void
    {
        $plan = $this->makeTestUnlimitedPlan();
        $tenant = Tenant::create(['name' => 'Test Shop']);
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addYears(50),
        ]);

        Artisan::call('migrate', ['--path' => 'app/Modules/Billing/Database/Migrations/2026_07_10_100001_repoint_test_unlimited_to_starter.php', '--force' => true]);

        $alive = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->whereIn('status', Subscription::ALIVE_STATUSES)
            ->with('plan')->first();
        $this->assertNotNull($alive);
        $this->assertSame('starter', $alive->plan->code);
        $this->assertFalse((bool) Plan::query()->where('code', 'test_unlimited')->value('is_active'));
    }
}
```

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run (từ `app/`): `php artisan test --filter=RepointTestUnlimitedTest`
Expected: FAIL — migration file chưa tồn tại (Path not found) hoặc plan vẫn active.

- [ ] **Step 3: Viết migration**

```php
<?php

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $old = Plan::query()->where('code', 'test_unlimited')->first();
        $starter = Plan::query()->where('code', Plan::CODE_STARTER)->first();
        if ($old === null || $starter === null) {
            return; // môi trường chưa có gói — no-op an toàn.
        }

        Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('plan_id', $old->getKey())
            ->whereIn('status', Subscription::ALIVE_STATUSES)
            ->orderBy('id')
            ->each(function (Subscription $sub) use ($starter) {
                // Idempotent: nếu tenant đã có sub starter alive thì chỉ cancel cái test.
                $hasStarterAlive = Subscription::query()->withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $sub->tenant_id)
                    ->where('plan_id', $starter->getKey())
                    ->whereIn('status', Subscription::ALIVE_STATUSES)->exists();

                DB::transaction(function () use ($sub, $starter, $hasStarterAlive) {
                    $sub->forceFill([
                        'status' => Subscription::STATUS_CANCELLED,
                        'cancelled_at' => now(), 'cancel_at' => now(), 'ended_at' => now(),
                    ])->save();

                    if (! $hasStarterAlive) {
                        Subscription::query()->create([
                            'tenant_id' => $sub->tenant_id,
                            'plan_id' => $starter->getKey(),
                            'status' => Subscription::STATUS_ACTIVE,
                            'billing_cycle' => Subscription::CYCLE_MONTHLY,
                            'current_period_start' => now(),
                            'current_period_end' => now()->addMonth(),
                            'meta' => ['migrated_from' => 'test_unlimited'],
                        ]);
                    }
                });
            });

        $old->forceFill(['is_active' => false])->save();
    }

    public function down(): void
    {
        // Không khôi phục — one-way migration.
    }
};
```

- [ ] **Step 4: Chạy test xác nhận PASS**

Run: `php artisan test --filter=RepointTestUnlimitedTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Billing/Database/Migrations/2026_07_10_100001_repoint_test_unlimited_to_starter.php app/tests/Feature/Billing/RepointTestUnlimitedTest.php
git commit -m "feat(billing): migration dời sub test_unlimited về starter + deactivate plan"
```

---

### Task 2: Gỡ toàn bộ code beta-mode + chặn gán `test_unlimited` + lọc PublicPlan

**Files:**
- Modify: `app/app/Modules/Billing/Services/SubscriptionService.php` (bỏ nhánh beta trong `startTrial` L40-48; xoá `betaModeOn()` L73-76 và `createBetaUnlimited()` L79-96)
- Modify: `app/app/Modules/Billing/Services/SubscriptionExpiryService.php` (xoá Step 0 L39-54 + không gọi `betaModeOn`)
- Modify: `app/app/Modules/Billing/BillingServiceProvider.php` (bỏ `use ...BetaModeCommand` L5 và `BetaModeCommand::class` L60)
- Delete: `app/app/Modules/Billing/Console/BetaModeCommand.php`
- Delete: `app/app/Modules/Billing/Database/Seeders/TestUnlimitedPlanSeeder.php`
- Modify: `app/database/seeders/DatabaseSeeder.php` (bỏ import L7 + `$this->call(TestUnlimitedPlanSeeder::class)` L25-26)
- Modify: `app/app/Modules/Billing/Database/Migrations/2026_06_06_120001_upsert_spec_0032_plans.php` (bỏ import + `(new TestUnlimitedPlanSeeder)->run()` L26)
- Modify: `app/app/Modules/Billing/Database/Migrations/2026_06_06_130001_resync_plan_features.php` (bỏ import + `(new TestUnlimitedPlanSeeder)->run()`)
- Modify: `app/app/Modules/Billing/Database/Migrations/2026_06_09_100002_resync_plan_features_tiktok_ads.php` (tương tự)
- Modify: `app/app/Modules/Billing/Database/Migrations/2026_06_30_100002_resync_plan_features_einvoice.php` (tương tự)
- Modify: `app/app/Modules/Billing/Database/Seeders/BillingPlanSeeder.php` (bỏ `{@see TestUnlimitedPlanSeeder}` L21 trong docblock để không import module chéo)
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminTenantController.php` (`changePlan` validate — chặn `test_unlimited`)
- Modify: `app/app/Modules/Admin/Services/AdminTenantService.php` (bỏ nhánh `test_unlimited` 365*50 L96-103)
- Modify: `app/app/Modules/Billing/Http/Controllers/PublicPlanController.php` (lọc `Plan::CODES`)
- Delete: `app/tests/Feature/Billing/SubscriptionBetaModeTest.php`
- Rewrite: `app/tests/Feature/Admin/AdminSetTestPlanTest.php` → assert admin KHÔNG gán được `test_unlimited`
- Modify: `app/tests/Feature/Billing/AiCreditServiceTest.php` (thay seeding `test_unlimited` bằng plan unlimited-AI inline)
- Modify: `app/tests/Feature/Marketing/CampaignAiInsightApiTest.php` (thay `test_unlimited` bằng `pro`)
- Test: `app/tests/Feature/Billing/PublicPlansTest.php` (bổ sung assert không lộ `test_unlimited`)

**Interfaces:**
- Consumes: migration Task 1.
- Produces: `SubscriptionService` không còn `betaModeOn()`/`createBetaUnlimited()`; `startTrial()` luôn cấp trial. `PublicPlanController::index` chỉ trả plan có `code ∈ Plan::CODES`. Admin `changePlan` trả 422 khi `plan_code='test_unlimited'`.

- [ ] **Step 1: Viết test thất bại — admin không gán được test_unlimited + public không lộ**

Rewrite `app/tests/Feature/Admin/AdminSetTestPlanTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Services\AdminTenantService;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminSetTestPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_admin_cannot_assign_test_unlimited_plan(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $this->expectException(ValidationException::class);
        app(AdminTenantService::class)->changePlan($tenant, 'test_unlimited', 'monthly', 'Thử gán gói test', 1);
    }
}
```

Bổ sung vào `app/tests/Feature/Billing/PublicPlansTest.php` một test (thêm method vào class có sẵn):

```php
public function test_public_plans_never_exposes_internal_codes(): void
{
    \CMBcoreSeller\Modules\Billing\Models\Plan::query()->create([
        'code' => 'test_unlimited', 'name' => 'Test', 'description' => '',
        'is_active' => true, 'sort_order' => 99,
        'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'VND', 'trial_days' => 0,
        'limits' => [], 'features' => [],
    ]);

    $codes = collect($this->getJson('/api/v1/public/plans')->assertOk()->json('data'))
        ->pluck('code')->all();
    $this->assertNotContains('test_unlimited', $codes);
}
```

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run: `php artisan test --filter=AdminSetTestPlanTest`
Run: `php artisan test --filter=PublicPlansTest`
Expected: FAIL — `changePlan` hiện chấp nhận test_unlimited; PublicPlan trả cả test_unlimited.

- [ ] **Step 3: Sửa code — chặn gán + lọc public + gỡ beta**

`AdminTenantController::changePlan` — đổi rule `plan_code` (L165):

```php
'plan_code' => ['required', 'string', 'max:32', 'not_in:test_unlimited'],
```

`AdminTenantService::changePlan` — thay khối L96-103 bằng:

```php
$days = match ($cycle) {
    Subscription::CYCLE_YEARLY => 365,
    Subscription::CYCLE_MONTHLY => 30,
    default => max((int) $plan->trial_days, 14),
};
```

`PublicPlanController::index` L14 — thêm điều kiện code:

```php
$plans = Plan::query()->where('is_active', true)
    ->whereIn('code', Plan::CODES)
    ->orderBy('sort_order')->get()
```

`SubscriptionService::startTrial` — xoá khối L40-48 (nhánh `if ($this->betaModeOn())`), giữ phần tạo trial. Xoá luôn method `betaModeOn()` (L73-76) và `createBetaUnlimited()` (L79-96) và import `Plan` nếu không còn dùng (kiểm tra: `startTrial`/`ensureTrialFallback` vẫn dùng `Plan::CODE_TRIAL` ⇒ giữ import).

`SubscriptionExpiryService::run` — xoá Step 0 (L39-54, khối `if (! $this->subscriptions->betaModeOn())`).

`BillingServiceProvider` — xoá dòng `use ...\Console\BetaModeCommand;` (L5) và `BetaModeCommand::class,` khỏi mảng commands (L60).

Xoá file: `BetaModeCommand.php`, `TestUnlimitedPlanSeeder.php`, `SubscriptionBetaModeTest.php`.

`DatabaseSeeder.php` — xoá import L7 + 2 dòng L25-26.

4 migration `2026_06_06_120001`, `2026_06_06_130001`, `2026_06_09_100002`, `2026_06_30_100002` — bỏ dòng `use ...TestUnlimitedPlanSeeder;` và `(new TestUnlimitedPlanSeeder)->run();` trong mỗi file (giữ `(new BillingPlanSeeder)->run();` và các resync khác).

`BillingPlanSeeder.php` — sửa docblock L21 bỏ `{@see TestUnlimitedPlanSeeder}` (thay bằng chữ thường "gói nội bộ" để tránh pint auto-import module chéo — theo [[pint-see-adds-imports-module-rule]]).

- [ ] **Step 4: Sửa 2 test còn phụ thuộc test_unlimited**

`AiCreditServiceTest.php` — bỏ `use ...TestUnlimitedPlanSeeder;` và `$this->seed(TestUnlimitedPlanSeeder::class);` (L25). Sửa method `test_unlimited_test_plan_never_consumes` (L103) để tạo plan unlimited-AI inline:

```php
public function test_unlimited_test_plan_never_consumes(): void
{
    $plan = \CMBcoreSeller\Modules\Billing\Models\Plan::query()->create([
        'code' => 'internal_unlimited_ai', 'name' => 'Internal', 'description' => '',
        'is_active' => false, 'sort_order' => 98,
        'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'VND', 'trial_days' => 0,
        'limits' => ['ai_credits_monthly' => -1], 'features' => ['ai' => true],
    ]);
    $this->subscribe($plan->code);
    // ... phần assert giữ nguyên như bản cũ (never consumes) ...
}
```

(Xem thân method cũ để giữ nguyên assert; chỉ thay nguồn plan từ seeder sang plan inline có `ai_credits_monthly=-1`.)

`CampaignAiInsightApiTest.php` — bỏ `use ...TestUnlimitedPlanSeeder;` và `$this->seed(TestUnlimitedPlanSeeder::class);` (L39). Đổi L42 `->where('code', 'test_unlimited')` thành `->where('code', 'pro')` (pro có full feature theo BillingPlanSeeder).

- [ ] **Step 5: Chạy test xác nhận PASS + toàn bộ Billing/Admin**

Run: `php artisan test --filter=AdminSetTestPlanTest`
Run: `php artisan test --filter=PublicPlansTest`
Run: `php artisan test tests/Feature/Billing tests/Feature/Admin`
Expected: PASS (trừ các fail nền có sẵn không liên quan — xem [[test-verify-baseline]]).

- [ ] **Step 6: Pint + PHPStan**

Run: `vendor/bin/pint app/Modules/Billing app/Modules/Admin && vendor/bin/phpstan analyse`
Expected: no new errors.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(billing): gỡ beta-mode/test_unlimited khỏi code + chặn gán + lọc public plans"
```

---

### Task 3: FE — bỏ `test_unlimited` khỏi dropdown admin

**Files:**
- Modify: `app/resources/js/admin/pages/tenants/AdminTenantDrawer.tsx:196-201` (bỏ mục `test_unlimited` khỏi `PLAN_OPTIONS`)

- [ ] **Step 1: Sửa `PLAN_OPTIONS`**

```tsx
const PLAN_OPTIONS = [
  { value: 'trial', label: 'Dùng thử' },
  { value: 'starter', label: 'Cơ bản' },
  { value: 'pro', label: 'Chuyên nghiệp' },
];
```

(Danh sách thực tế đã lấy động từ `useAdminPlans().data` lọc `is_active`; `test_unlimited` giờ `is_active=false` nên cũng không xuất hiện qua nguồn động.)

- [ ] **Step 2: Typecheck + lint + build**

Run (từ `app/`): `npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/admin/pages/tenants/AdminTenantDrawer.tsx
git commit -m "feat(admin-ui): bỏ test_unlimited khỏi dropdown đổi gói"
```

---

## Phase 2 — Chế độ trải nghiệm Pro (backend)

### Task 4: Đăng ký catalog keys + `ProTrialSettings` accessor + config terms version

**Files:**
- Modify: `app/app/Modules/Settings/Support/SystemSettingsCatalog.php` (thêm 4 key `billing.pro_trial.*` vào group `sync`)
- Create: `app/app/Modules/Billing/Support/ProTrialSettings.php`
- Modify: `app/config/billing.php` (thêm `refund_terms_version`)
- Test: `app/tests/Feature/Billing/ProTrialSettingsTest.php`

**Interfaces:**
- Produces: `ProTrialSettings::enabled(): bool`, `durationDays(): int`, `windowStart(): ?CarbonImmutable`, `windowEnd(): ?CarbonImmutable`, `windowOpen(): bool`. Config `config('billing.refund_terms_version')` (string).

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Support\ProTrialSettings;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_when_unset(): void
    {
        $this->assertFalse(ProTrialSettings::enabled());
        $this->assertSame(30, ProTrialSettings::durationDays());
        $this->assertNull(ProTrialSettings::windowStart());
    }

    public function test_reads_from_system_setting(): void
    {
        $svc = app(SystemSettingService::class);
        $svc->set('billing.pro_trial.enabled', true);
        $svc->set('billing.pro_trial.duration_days', 45);

        $this->assertTrue(ProTrialSettings::enabled());
        $this->assertSame(45, ProTrialSettings::durationDays());
    }

    public function test_window_open_respects_bounds(): void
    {
        $svc = app(SystemSettingService::class);
        $svc->set('billing.pro_trial.window_start', now()->subDay()->toDateString());
        $svc->set('billing.pro_trial.window_end', now()->addDay()->toDateString());
        $this->assertTrue(ProTrialSettings::windowOpen());

        $svc->set('billing.pro_trial.window_end', now()->subHour()->toDateString());
        $this->assertFalse(ProTrialSettings::windowOpen());
    }
}
```

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run: `php artisan test --filter=ProTrialSettingsTest`
Expected: FAIL — class `ProTrialSettings` chưa tồn tại; `system_setting` trả default vì key chưa trong catalog.

- [ ] **Step 3: Thêm 4 key vào catalog**

Trong `SystemSettingsCatalog::all()`, thêm (ngay sau `billing.over_quota_grace_hours`, trong group `sync`):

```php
'billing.pro_trial.enabled' => [
    'group' => 'sync', 'type' => 'bool', 'is_secret' => false,
    'env' => 'BILLING_PRO_TRIAL_ENABLED', 'label' => 'Chế độ trải nghiệm Pro — Bật',
    'description' => 'Bật để thành viên (cũ & mới) tự đăng ký trải nghiệm gói Pro. Mỗi tenant chỉ 1 lần vĩnh viễn.',
],
'billing.pro_trial.duration_days' => [
    'group' => 'sync', 'type' => 'int', 'is_secret' => false,
    'env' => 'BILLING_PRO_TRIAL_DURATION_DAYS', 'label' => 'Trải nghiệm Pro — Số ngày',
    'description' => 'Thời lượng mỗi tenant được dùng Pro trải nghiệm (mặc định 30).',
],
'billing.pro_trial.window_start' => [
    'group' => 'sync', 'type' => 'string', 'is_secret' => false,
    'env' => 'BILLING_PRO_TRIAL_WINDOW_START', 'label' => 'Trải nghiệm Pro — Mở từ (YYYY-MM-DD)',
    'description' => 'Ngày bắt đầu mở đăng ký. Trống = không giới hạn cạnh này.',
],
'billing.pro_trial.window_end' => [
    'group' => 'sync', 'type' => 'string', 'is_secret' => false,
    'env' => 'BILLING_PRO_TRIAL_WINDOW_END', 'label' => 'Trải nghiệm Pro — Đóng đến (YYYY-MM-DD)',
    'description' => 'Ngày kết thúc mở đăng ký. Trống = không giới hạn cạnh này.',
],
```

- [ ] **Step 4: Tạo `ProTrialSettings`**

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Support;

use Carbon\CarbonImmutable;

/**
 * Đọc cấu hình "Chế độ trải nghiệm Pro" từ system_setting (catalog-registered).
 * Nguồn duy nhất cho eligibility + admin UI.
 */
class ProTrialSettings
{
    public const DEFAULT_DAYS = 30;

    public static function enabled(): bool
    {
        return (bool) system_setting('billing.pro_trial.enabled', false);
    }

    public static function durationDays(): int
    {
        $days = (int) system_setting('billing.pro_trial.duration_days', self::DEFAULT_DAYS);

        return $days > 0 ? $days : self::DEFAULT_DAYS;
    }

    public static function windowStart(): ?CarbonImmutable
    {
        $v = system_setting('billing.pro_trial.window_start');

        return $v ? CarbonImmutable::parse((string) $v)->startOfDay() : null;
    }

    public static function windowEnd(): ?CarbonImmutable
    {
        $v = system_setting('billing.pro_trial.window_end');

        return $v ? CarbonImmutable::parse((string) $v)->endOfDay() : null;
    }

    public static function windowOpen(): bool
    {
        $now = CarbonImmutable::now();
        $start = self::windowStart();
        $end = self::windowEnd();
        if ($start !== null && $now->lt($start)) {
            return false;
        }
        if ($end !== null && $now->gt($end)) {
            return false;
        }

        return true;
    }
}
```

- [ ] **Step 5: Thêm config terms version**

Trong `app/config/billing.php`, thêm key ở mảng gốc:

```php
'refund_terms_version' => env('BILLING_REFUND_TERMS_VERSION', 'refund-v1'),
```

- [ ] **Step 6: Chạy test xác nhận PASS**

Run: `php artisan test --filter=ProTrialSettingsTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Settings/Support/SystemSettingsCatalog.php app/app/Modules/Billing/Support/ProTrialSettings.php app/config/billing.php app/tests/Feature/Billing/ProTrialSettingsTest.php
git commit -m "feat(billing): cấu hình chế độ trải nghiệm Pro qua system_setting + config terms version"
```

---

### Task 5: Bảng + model `pro_trial_grants`

**Files:**
- Create: `app/app/Modules/Billing/Database/Migrations/2026_07_10_100002_create_pro_trial_grants_table.php`
- Create: `app/app/Modules/Billing/Models/ProTrialGrant.php`
- Test: `app/tests/Feature/Billing/ProTrialGrantModelTest.php`

**Interfaces:**
- Produces: bảng `pro_trial_grants(id, tenant_id UNIQUE, granted_at, expires_at, previous_plan_id, previous_cycle, previous_period_end, terms_accepted_at, terms_version, reverted_at, timestamps)`. Model `ProTrialGrant` (BelongsToTenant, fillable đủ, casts date).

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialGrantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_id_is_unique(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        ProTrialGrant::query()->create([
            'tenant_id' => $tenant->getKey(), 'granted_at' => now(),
            'expires_at' => now()->addMonth(), 'terms_accepted_at' => now(),
            'terms_version' => 'refund-v1',
        ]);

        $this->expectException(QueryException::class);
        ProTrialGrant::query()->withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'granted_at' => now(),
            'expires_at' => now()->addMonth(), 'terms_accepted_at' => now(),
            'terms_version' => 'refund-v1',
        ]);
    }
}
```

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run: `php artisan test --filter=ProTrialGrantModelTest`
Expected: FAIL — bảng/model chưa tồn tại.

- [ ] **Step 3: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pro_trial_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique(); // 1 lần/tenant vĩnh viễn
            $table->timestamp('granted_at');
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('previous_plan_id')->nullable();
            $table->string('previous_cycle', 16)->nullable();
            $table->timestamp('previous_period_end')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->string('terms_version', 32);
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_trial_grants');
    }
};
```

- [ ] **Step 4: Viết model**

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProTrialGrant extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'granted_at', 'expires_at', 'previous_plan_id',
        'previous_cycle', 'previous_period_end', 'terms_accepted_at',
        'terms_version', 'reverted_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'previous_period_end' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'reverted_at' => 'datetime',
        ];
    }
}
```

> Kiểm chứng namespace trait `BelongsToTenant`: xem `use` ở đầu `Subscription.php` (Task setup đã xác nhận trait tồn tại). Nếu path là `...Tenancy\Models\Concerns\BelongsToTenant` thì dùng đúng path đó — copy từ `Subscription.php`.

- [ ] **Step 5: Chạy test xác nhận PASS**

Run: `php artisan test --filter=ProTrialGrantModelTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Billing/Database/Migrations/2026_07_10_100002_create_pro_trial_grants_table.php app/app/Modules/Billing/Models/ProTrialGrant.php app/tests/Feature/Billing/ProTrialGrantModelTest.php
git commit -m "feat(billing): bảng + model pro_trial_grants (unique tenant, 1 lần vĩnh viễn)"
```

---

### Task 6: `ProTrialService::eligibility()` + endpoint GET

**Files:**
- Create: `app/app/Modules/Billing/Services/ProTrialService.php`
- Modify: `app/app/Modules/Billing/Http/Controllers/BillingController.php` (thêm method `proTrialEligibility`)
- Modify: `app/app/Modules/Billing/Http/routes.php` (route GET `pro-trial/eligibility`)
- Test: `app/tests/Feature/Billing/ProTrialEligibilityTest.php`

**Interfaces:**
- Produces: `ProTrialService::eligibility(int $tenantId): array` → `{ eligible: bool, reason: ?string, duration_days: int, ends_preview: ?string }`. Endpoint `GET /api/v1/billing/pro-trial/eligibility` (quyền `billing.manage`) trả `{ data: {...} }`.

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(SystemSettingService::class)->set('billing.pro_trial.enabled', true);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_eligible_when_enabled_and_not_used(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.duration_days', 30);
    }

    public function test_not_eligible_when_disabled(): void
    {
        app(SystemSettingService::class)->set('billing.pro_trial.enabled', false);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.reason', 'mode_off');
    }

    public function test_not_eligible_when_already_used(): void
    {
        ProTrialGrant::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'granted_at' => now(),
            'expires_at' => now()->addMonth(), 'terms_accepted_at' => now(), 'terms_version' => 'refund-v1',
        ]);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.reason', 'already_used');
    }
}
```

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run: `php artisan test --filter=ProTrialEligibilityTest`
Expected: FAIL — route/service chưa tồn tại (404).

- [ ] **Step 3: Viết `ProTrialService` (phần eligibility)**

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use CMBcoreSeller\Modules\Billing\Support\ProTrialSettings;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;

class ProTrialService
{
    public function __construct(protected SubscriptionService $subscriptions) {}

    /** @return array{eligible:bool,reason:?string,duration_days:int,ends_preview:?string} */
    public function eligibility(int $tenantId): array
    {
        $days = ProTrialSettings::durationDays();
        $base = ['eligible' => false, 'reason' => null, 'duration_days' => $days, 'ends_preview' => null];

        if (! ProTrialSettings::enabled()) {
            return [...$base, 'reason' => 'mode_off'];
        }
        if (! ProTrialSettings::windowOpen()) {
            return [...$base, 'reason' => 'window_closed'];
        }
        $used = ProTrialGrant::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->exists();
        if ($used) {
            return [...$base, 'reason' => 'already_used'];
        }
        $current = $this->subscriptions->currentFor($tenantId);
        $code = $current?->plan?->code;
        if ($code !== null && ! in_array($code, [Plan::CODE_TRIAL, Plan::CODE_STARTER], true)) {
            return [...$base, 'reason' => 'plan_too_high'];
        }

        return [
            'eligible' => true, 'reason' => null, 'duration_days' => $days,
            'ends_preview' => Carbon::now()->addDays($days)->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 4: Thêm endpoint + route**

`BillingController` — thêm method (theo mẫu `abort_unless(...->can('billing.manage'))` như `checkout`):

```php
public function proTrialEligibility(Request $request): JsonResponse
{
    abort_unless((bool) $request->user()?->can('billing.manage'), 403);
    $tenantId = (int) $this->currentTenant->id();

    return response()->json(['data' => app(ProTrialService::class)->eligibility($tenantId)]);
}
```

(Import `use CMBcoreSeller\Modules\Billing\Services\ProTrialService;` ở đầu controller.)

`routes.php` — trong group billing, thêm:

```php
Route::get('pro-trial/eligibility', [BillingController::class, 'proTrialEligibility'])->name('billing.pro-trial.eligibility');
```

- [ ] **Step 5: Chạy test xác nhận PASS**

Run: `php artisan test --filter=ProTrialEligibilityTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Billing/Services/ProTrialService.php app/app/Modules/Billing/Http/Controllers/BillingController.php app/app/Modules/Billing/Http/routes.php app/tests/Feature/Billing/ProTrialEligibilityTest.php
git commit -m "feat(billing): ProTrialService.eligibility + GET /billing/pro-trial/eligibility"
```

---

### Task 7: `ProTrialService::register()` + endpoint POST (kèm terms)

**Files:**
- Modify: `app/app/Modules/Billing/Services/ProTrialService.php` (thêm `register`)
- Create: `app/app/Modules/Billing/Http/Requests/ProTrialRegisterRequest.php`
- Modify: `app/app/Modules/Billing/Http/Controllers/BillingController.php` (method `proTrialRegister`)
- Modify: `app/app/Modules/Billing/Http/routes.php` (POST `pro-trial/register`, throttle)
- Test: `app/tests/Feature/Billing/ProTrialRegisterTest.php`

**Interfaces:**
- Consumes: `ProTrialService::eligibility` (Task 6).
- Produces: `ProTrialService::register(int $tenantId, string $termsVersion): Subscription` — tạo `ProTrialGrant`, cancel sub cũ, tạo sub Pro active `now + durationDays`, `meta.pro_trial=true` + `revert_*`. Endpoint `POST /api/v1/billing/pro-trial/register` body `{terms_accepted, terms_version}`.

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialRegisterTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        app(SystemSettingService::class)->set('billing.pro_trial.enabled', true);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_register_activates_pro_and_records_grant(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', ['terms_accepted' => true, 'terms_version' => 'refund-v1'])
            ->assertOk()
            ->assertJsonPath('data.plan_code', 'pro');

        $sub = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->where('status', Subscription::STATUS_ACTIVE)
            ->with('plan')->latest('id')->first();
        $this->assertSame('pro', $sub->plan->code);
        $this->assertTrue((bool) ($sub->meta['pro_trial'] ?? false));
        $this->assertDatabaseHas('pro_trial_grants', ['tenant_id' => $this->tenant->getKey(), 'terms_version' => 'refund-v1']);
    }

    public function test_register_rejects_without_terms(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', ['terms_accepted' => false, 'terms_version' => 'refund-v1'])
            ->assertStatus(422);
    }

    public function test_register_only_once(): void
    {
        $payload = ['terms_accepted' => true, 'terms_version' => 'refund-v1'];
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', $payload)->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'PRO_TRIAL_NOT_ELIGIBLE');
    }
}
```

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run: `php artisan test --filter=ProTrialRegisterTest`
Expected: FAIL — route chưa tồn tại.

- [ ] **Step 3: Viết FormRequest**

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProTrialRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('billing.manage');
    }

    public function rules(): array
    {
        return [
            'terms_accepted' => ['required', 'accepted'],
            'terms_version' => ['required', 'string', 'max:32'],
        ];
    }
}
```

- [ ] **Step 4: Thêm `register()` vào `ProTrialService`**

```php
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// ...

public function register(int $tenantId, string $termsVersion): Subscription
{
    return DB::transaction(function () use ($tenantId, $termsVersion) {
        $elig = $this->eligibility($tenantId);
        if (! $elig['eligible']) {
            throw ValidationException::withMessages([
                'plan' => 'Chưa đủ điều kiện đăng ký trải nghiệm.',
            ])->status(422);
        }

        $pro = Plan::query()->where('code', Plan::CODE_PRO)->where('is_active', true)->firstOrFail();
        $current = $this->subscriptions->currentFor($tenantId);
        $days = ProTrialSettings::durationDays();
        $now = now();

        ProTrialGrant::query()->create([
            'tenant_id' => $tenantId,
            'granted_at' => $now,
            'expires_at' => $now->copy()->addDays($days),
            'previous_plan_id' => $current?->plan_id,
            'previous_cycle' => $current?->billing_cycle,
            'previous_period_end' => $current?->current_period_end,
            'terms_accepted_at' => $now,
            'terms_version' => $termsVersion,
        ]);

        if ($current !== null) {
            $current->forceFill([
                'status' => Subscription::STATUS_CANCELLED,
                'cancelled_at' => $now, 'cancel_at' => $now, 'ended_at' => $now,
            ])->save();
        }

        return Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $pro->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addDays($days),
            'meta' => [
                'pro_trial' => true,
                'revert_plan_id' => $current?->plan_id,
                'revert_cycle' => $current?->billing_cycle,
                'revert_period_end' => $current?->current_period_end?->toIso8601String(),
            ],
        ]);
    });
}
```

> Lưu ý mã lỗi: FE mong `error.code = 'PRO_TRIAL_NOT_ELIGIBLE'`. Laravel map `ValidationException` → code `VALIDATION_FAILED` mặc định (xem `bootstrap/app.php`). Để trả code chuyên biệt, controller bắt và trả JSON error thủ công (Step 5).

- [ ] **Step 5: Thêm endpoint + route**

`BillingController`:

```php
public function proTrialRegister(ProTrialRegisterRequest $request): JsonResponse
{
    $tenantId = (int) $this->currentTenant->id();
    try {
        $sub = app(ProTrialService::class)->register($tenantId, (string) $request->validated('terms_version'));
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['error' => [
            'code' => 'PRO_TRIAL_NOT_ELIGIBLE',
            'message' => 'Chưa đủ điều kiện đăng ký trải nghiệm Pro.',
        ]], 422);
    }

    return response()->json(['data' => $this->subscriptionResource($sub->fresh(['plan']))]);
}
```

> `subscriptionResource()` — tái dùng cách serialize subscription hiện có trong controller (method `subscription()` L60 trả về resource). Nếu chưa có helper riêng, dùng đúng Resource mà `subscription()` dùng (`new SubscriptionResource($sub)`), import tương ứng. Xác minh tên Resource trong `subscription()` khi implement.

`routes.php`:

```php
Route::post('pro-trial/register', [BillingController::class, 'proTrialRegister'])
    ->middleware('throttle:10,1')->name('billing.pro-trial.register');
```

(Import `use CMBcoreSeller\Modules\Billing\Http\Requests\ProTrialRegisterRequest;` trong controller.)

- [ ] **Step 6: Chạy test xác nhận PASS**

Run: `php artisan test --filter=ProTrialRegisterTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Billing/Services/ProTrialService.php app/app/Modules/Billing/Http/Requests/ProTrialRegisterRequest.php app/app/Modules/Billing/Http/Controllers/BillingController.php app/app/Modules/Billing/Http/routes.php app/tests/Feature/Billing/ProTrialRegisterTest.php
git commit -m "feat(billing): đăng ký trải nghiệm Pro 1 lần/tenant + điều khoản"
```

---

### Task 8: Hết hạn trải nghiệm → về gói trước đó

**Files:**
- Modify: `app/app/Modules/Billing/Services/SubscriptionExpiryService.php` (thêm nhánh revert cho sub `meta.pro_trial`)
- Test: `app/tests/Feature/Billing/ProTrialRevertTest.php`

**Interfaces:**
- Consumes: sub Pro có `meta.pro_trial=true` (Task 7) + `ProTrialGrant` (Task 5).
- Produces: `SubscriptionExpiryService::run()` khi gặp sub pro_trial `current_period_end < now` → expire + tạo sub trên `revert_plan_id` (hoặc trial fallback) + set `pro_trial_grants.reverted_at`.

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionExpiryService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialRevertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
    }

    public function test_expired_pro_trial_reverts_to_previous_plan(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $trial = Plan::query()->where('code', Plan::CODE_TRIAL)->first();
        $pro = Plan::query()->where('code', Plan::CODE_PRO)->first();

        ProTrialGrant::query()->create([
            'tenant_id' => $tenant->getKey(), 'granted_at' => now()->subMonth(),
            'expires_at' => now()->subDay(), 'previous_plan_id' => $trial->getKey(),
            'previous_cycle' => 'trial', 'previous_period_end' => now()->addYears(50),
            'terms_accepted_at' => now()->subMonth(), 'terms_version' => 'refund-v1',
        ]);
        Subscription::query()->create([
            'tenant_id' => $tenant->getKey(), 'plan_id' => $pro->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now()->subMonth(), 'current_period_end' => now()->subDay(),
            'meta' => ['pro_trial' => true, 'revert_plan_id' => $trial->getKey(), 'revert_cycle' => 'trial', 'revert_period_end' => now()->addYears(50)->toIso8601String()],
        ]);

        app(SubscriptionExpiryService::class)->run();

        $alive = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->whereIn('status', Subscription::ALIVE_STATUSES)
            ->with('plan')->first();
        $this->assertSame('trial', $alive->plan->code);
        $this->assertNotNull(ProTrialGrant::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->value('reverted_at'));
    }
}
```

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run: `php artisan test --filter=ProTrialRevertTest`
Expected: FAIL — sub pro_trial hiện bị Step 2 hạ thẳng về trial (đúng plan tình cờ), nhưng `reverted_at` null ⇒ assert fail. (Nếu previous là starter thì còn sai plan.)

- [ ] **Step 3: Sửa `SubscriptionExpiryService`**

Trong khối Step 2 (L77-93, active/past_due hết kỳ), thay closure để phân nhánh pro_trial. Thay đoạn `->each(function (Subscription $sub) ...)` bằng:

```php
->each(function (Subscription $sub) use (&$expired, &$fallback) {
    DB::transaction(function () use ($sub, &$expired, &$fallback) {
        $sub->forceFill(['status' => Subscription::STATUS_EXPIRED, 'ended_at' => now()])->save();
        $expired++;

        if (($sub->meta['pro_trial'] ?? false) === true) {
            $this->revertProTrial($sub);

            return;
        }
        if ($this->createTrialFallbackIfMissing((int) $sub->tenant_id)) {
            $fallback++;
        }
    });
});
```

Thêm method mới trong class:

```php
use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use Illuminate\Support\Carbon;

// ...

/** Sub trải nghiệm Pro hết hạn ⇒ khôi phục gói trước đó (hoặc trial fallback). */
protected function revertProTrial(Subscription $sub): void
{
    $meta = $sub->meta ?? [];
    $planId = $meta['revert_plan_id'] ?? null;
    $cycle = $meta['revert_cycle'] ?? Subscription::CYCLE_TRIAL;
    $plan = $planId ? Plan::query()->find($planId) : Plan::query()->where('code', Plan::CODE_TRIAL)->first();

    ProTrialGrant::query()->withoutGlobalScope(TenantScope::class)
        ->where('tenant_id', $sub->tenant_id)->update(['reverted_at' => now()]);

    if ($plan === null) {
        $this->createTrialFallbackIfMissing((int) $sub->tenant_id);

        return;
    }

    // Gói trial ⇒ khôi phục vĩnh viễn; gói trả phí ⇒ dùng period_end cũ (nếu quá khứ, kỳ sau sẽ hạ trial).
    $isTrial = $plan->code === Plan::CODE_TRIAL || $cycle === Subscription::CYCLE_TRIAL;
    $periodEnd = $isTrial
        ? now()->addYears(50)
        : (isset($meta['revert_period_end']) ? Carbon::parse($meta['revert_period_end']) : now());

    Subscription::query()->create([
        'tenant_id' => $sub->tenant_id,
        'plan_id' => $plan->getKey(),
        'status' => Subscription::STATUS_ACTIVE,
        'billing_cycle' => $cycle,
        'current_period_start' => now(),
        'current_period_end' => $periodEnd,
        'meta' => ['reverted_from_pro_trial' => true],
    ]);
}
```

- [ ] **Step 4: Chạy test xác nhận PASS**

Run: `php artisan test --filter=ProTrialRevertTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Billing/Services/SubscriptionExpiryService.php app/tests/Feature/Billing/ProTrialRevertTest.php
git commit -m "feat(billing): hết hạn trải nghiệm Pro tự về gói trước đó"
```

---

### Task 9: Admin endpoints `pro-trial-settings` (GET/PUT)

**Files:**
- Create: `app/app/Modules/Admin/Http/Controllers/AdminProTrialController.php`
- Modify: `app/app/Modules/Admin/Http/routes.php` (2 route trong group admin)
- Test: `app/tests/Feature/Admin/AdminProTrialSettingsTest.php`

**Interfaces:**
- Produces: `GET /api/v1/admin/pro-trial-settings` → `{data:{enabled,duration_days,window_start,window_end}}`; `PUT` cùng path nhận `{enabled,duration_days,window_start?,window_end?}` → lưu qua `SystemSettingService::set` + audit.

- [ ] **Step 1: Viết test thất bại** (dùng khuôn admin auth — xem test admin sẵn có để lấy helper đăng nhập `admin_web`)

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Billing\Support\ProTrialSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProTrialSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        return AdminUser::query()->create([
            'username' => 'root', 'email' => 'root@x.local', 'name' => 'Root',
            'password' => bcrypt('secret'), 'is_active' => true,
        ]);
    }

    public function test_update_and_read_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin_web')
            ->putJson('/api/v1/admin/pro-trial-settings', [
                'enabled' => true, 'duration_days' => 30,
                'window_start' => '2026-07-10', 'window_end' => '2026-08-10',
            ])->assertOk();

        $this->assertTrue(ProTrialSettings::enabled());
        $this->assertSame(30, ProTrialSettings::durationDays());

        $this->actingAs($admin, 'admin_web')
            ->getJson('/api/v1/admin/pro-trial-settings')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.duration_days', 30);
    }
}
```

> Xác minh cách acting-as admin trong test suite hiện có (`tests/Feature/Admin/*`): nếu dùng `actingAs($admin, 'admin_web')` chưa đủ (một số route dùng guard `web`), copy đúng helper login từ `AdminTenantControllerTest` hoặc test admin gần nhất.

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run: `php artisan test --filter=AdminProTrialSettingsTest`
Expected: FAIL — route/controller chưa tồn tại.

- [ ] **Step 3: Viết controller**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Billing\Support\ProTrialSettings;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProTrialController extends Controller
{
    public function __construct(protected SystemSettingService $settings) {}

    public function show(): JsonResponse
    {
        return response()->json(['data' => [
            'enabled' => ProTrialSettings::enabled(),
            'duration_days' => ProTrialSettings::durationDays(),
            'window_start' => optional(ProTrialSettings::windowStart())->toDateString(),
            'window_end' => optional(ProTrialSettings::windowEnd())->toDateString(),
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'window_start' => ['nullable', 'date_format:Y-m-d'],
            'window_end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:window_start'],
        ]);

        $adminId = (int) $request->user()->getKey();
        $this->settings->set('billing.pro_trial.enabled', $data['enabled'], $adminId);
        $this->settings->set('billing.pro_trial.duration_days', $data['duration_days'], $adminId);
        $this->settings->set('billing.pro_trial.window_start', $data['window_start'] ?? '', $adminId);
        $this->settings->set('billing.pro_trial.window_end', $data['window_end'] ?? '', $adminId);

        AuditLog::query()->create([
            'tenant_id' => null,
            'user_id' => $adminId,
            'action' => 'admin.pro_trial.settings',
            'auditable_type' => 'system_setting',
            'auditable_id' => 0,
            'changes' => $data,
            'ip' => $request->ip(),
        ]);

        return $this->show();
    }
}
```

> Kiểm chứng: `SystemSettingService::set` với chuỗi rỗng `''` cho window — `system_setting()` trả `''` (falsy) ⇒ `ProTrialSettings::windowStart()` coi như null. Nếu `set('')` bị validate chặn (catalog type string cho phép rỗng), OK. Nếu cần "xoá hẳn" dùng `forget()` thay vì set rỗng — xác minh khi implement, ưu tiên set `''`.

- [ ] **Step 4: Thêm routes** (trong group `middleware(['web','auth:admin_web','throttle:60,1'])->prefix('api/v1/admin')`, cạnh nhóm plans):

```php
Route::get('pro-trial-settings', [AdminProTrialController::class, 'show'])->name('admin.pro-trial.show');
Route::put('pro-trial-settings', [AdminProTrialController::class, 'update'])->name('admin.pro-trial.update');
```

(Import `use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminProTrialController;`.)

- [ ] **Step 5: Chạy test xác nhận PASS**

Run: `php artisan test --filter=AdminProTrialSettingsTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/AdminProTrialController.php app/app/Modules/Admin/Http/routes.php app/tests/Feature/Admin/AdminProTrialSettingsTest.php
git commit -m "feat(admin): endpoint cấu hình chế độ trải nghiệm Pro"
```

---

## Phase 3 — Điều khoản không hoàn lại cho thanh toán thật

### Task 10: `checkout` yêu cầu `terms_accepted` + lưu vào invoice meta

**Files:**
- Modify: `app/app/Modules/Billing/Http/Controllers/BillingController.php` (`checkout` — thêm validate terms)
- Modify: `app/app/Modules/Billing/Services/BillingService.php` (`createUpgradeInvoice` — nhận + lưu terms vào `invoices.meta`)
- Test: `app/tests/Feature/Billing/CheckoutTermsTest.php`

**Interfaces:**
- Consumes: endpoint checkout hiện có.
- Produces: `createUpgradeInvoice(..., ?string $termsVersion = null, ?string $termsAcceptedAt = null)` lưu `meta.terms_version` + `meta.terms_accepted_at`. Checkout thiếu `terms_accepted` → 422.

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTermsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_checkout_requires_terms_accepted(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly', 'gateway' => 'sepay',
                'terms_accepted' => false, 'terms_version' => 'refund-v1',
            ])->assertStatus(422);
    }

    public function test_checkout_records_terms_in_invoice_meta(): void
    {
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly', 'gateway' => 'sepay',
                'terms_accepted' => true, 'terms_version' => 'refund-v1',
            ])->assertCreated();

        $code = $resp->json('data.invoice.code');
        $meta = \CMBcoreSeller\Modules\Billing\Models\Invoice::query()->where('code', $code)->value('meta');
        $this->assertSame('refund-v1', $meta['terms_version'] ?? null);
        $this->assertNotNull($meta['terms_accepted_at'] ?? null);
    }
}
```

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run: `php artisan test --filter=CheckoutTermsTest`
Expected: FAIL — checkout chưa validate terms, meta chưa có terms.

- [ ] **Step 3: Sửa `checkout` validate** (L99-104 thêm 2 rule):

```php
'terms_accepted' => ['required', 'accepted'],
'terms_version' => ['required', 'string', 'max:32'],
```

Và truyền xuống service (L120):

```php
$invoice = $this->billing->createUpgradeInvoice(
    $tenantId, $data['plan_code'], $data['cycle'], $data['voucher_code'] ?? null, $userId,
    $data['terms_version'], now()->toIso8601String(),
);
```

- [ ] **Step 4: Sửa `createUpgradeInvoice`** — thêm 2 tham số + merge vào meta (L114-132):

```php
public function createUpgradeInvoice(
    int $tenantId, string $planCode, string $cycle,
    ?string $voucherCode = null, ?int $userId = null,
    ?string $termsVersion = null, ?string $termsAcceptedAt = null,
): Invoice {
    // ... giữ nguyên guards ...
    // trong mảng tạo Invoice, đổi meta:
    'meta' => array_filter([
        'plan_code' => $plan->code,
        'cycle' => $cycle,
        'terms_version' => $termsVersion,
        'terms_accepted_at' => $termsAcceptedAt,
    ], fn ($v) => $v !== null),
```

- [ ] **Step 5: Chạy test xác nhận PASS + regression checkout cũ**

Run: `php artisan test --filter=CheckoutTermsTest`
Run: `php artisan test --filter=BillingApiTest --filter=SePayWebhookTest`
Expected: CheckoutTermsTest PASS. Lưu ý: `BillingApiTest`/`SePayWebhookTest` gọi checkout KHÔNG có `terms_accepted` ⇒ sẽ 422. **Cập nhật các test đó**: thêm `'terms_accepted' => true, 'terms_version' => 'refund-v1'` vào mọi payload checkout hợp lệ (BillingApiTest L72-76, L93-96, L114-117 giữ nguyên phần reject; SePayWebhookTest L195-197). Chạy lại tới xanh.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Billing/Http/Controllers/BillingController.php app/app/Modules/Billing/Services/BillingService.php app/tests/Feature/Billing/CheckoutTermsTest.php app/tests/Feature/Billing/BillingApiTest.php app/tests/Feature/Billing/SePayWebhookTest.php
git commit -m "feat(billing): checkout bắt buộc đồng ý điều khoản không hoàn + lưu mốc"
```

---

## Phase 4 — Frontend: điều khoản + SePay QR + poll + trải nghiệm Pro

### Task 11: FE types + hooks billing (eligibility, register, CheckoutSession mở rộng, poll invoice)

**Files:**
- Modify: `app/resources/js/lib/billing.tsx`

**Interfaces:**
- Produces: type `CheckoutSession` mở rộng (`account_no?, account_name?, bank_code?, memo?, amount?, expires_at?`); hooks `useProTrialEligibility()`, `useRegisterProTrial()`, `useInvoicePolling(invoiceId, enabled)`. Hằng `REFUND_TERMS_VERSION = 'refund-v1'`.

- [ ] **Step 1: Mở rộng type `CheckoutSession`** (L125):

```tsx
export interface CheckoutSession {
  method: string;
  message?: string;
  redirect_url?: string;
  qr_url?: string;
  account_no?: string;
  account_name?: string;
  bank_code?: string;
  memo?: string;
  amount?: number;
  expires_at?: number;
}

export const REFUND_TERMS_VERSION = 'refund-v1';

export interface ProTrialEligibility {
  eligible: boolean;
  reason: string | null;
  duration_days: number;
  ends_preview: string | null;
}
```

- [ ] **Step 2: Thêm hooks** (cuối file, theo mẫu `useScopedApi`):

```tsx
export function useProTrialEligibility() {
  const tenantId = useCurrentTenantId();
  const api = useScopedApi();

  return useQuery({
    queryKey: ['billing', tenantId, 'pro-trial-eligibility'],
    enabled: api != null,
    queryFn: async () => {
      const { data } = await api!.get<{ data: ProTrialEligibility }>('/billing/pro-trial/eligibility');
      return data.data;
    },
  });
}

export function useRegisterProTrial() {
  const tenantId = useCurrentTenantId();
  const api = useScopedApi();
  const qc = useQueryClient();

  return useMutation({
    mutationFn: async (terms_version: string) => {
      const { data } = await api!.post<{ data: Subscription }>('/billing/pro-trial/register', {
        terms_accepted: true,
        terms_version,
      });
      return data.data;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ['billing', tenantId] }),
  });
}

export function useInvoicePolling(invoiceId: number | null, enabled: boolean) {
  const tenantId = useCurrentTenantId();
  const api = useScopedApi();

  return useQuery({
    queryKey: ['billing', tenantId, 'invoice', invoiceId],
    enabled: api != null && enabled && invoiceId != null,
    refetchInterval: (query) =>
      (query.state.data as Invoice | undefined)?.status === 'paid' ? false : 4000,
    queryFn: async () => {
      const { data } = await api!.get<{ data: Invoice }>(`/billing/invoices/${invoiceId}`);
      return data.data;
    },
  });
}
```

Cập nhật `useCheckout` gửi kèm terms (L188-189):

```tsx
mutationFn: async (payload: {
  plan_code: PlanCode; cycle: 'monthly' | 'yearly';
  gateway: 'sepay' | 'vnpay' | 'momo'; voucher_code?: string;
}) => {
  const { data } = await api!.post<{ data: CheckoutResult }>('/billing/checkout', {
    ...payload,
    terms_accepted: true,
    terms_version: REFUND_TERMS_VERSION,
  });
  return data.data;
},
```

- [ ] **Step 3: Typecheck**

Run (từ `app/`): `npm run typecheck`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/lib/billing.tsx
git commit -m "feat(billing-ui): hooks trải nghiệm Pro + poll invoice + terms trong checkout"
```

---

### Task 12: Component `RefundPolicyModal`

**Files:**
- Create: `app/resources/js/components/billing/RefundPolicyModal.tsx`

**Interfaces:**
- Produces: `<RefundPolicyModal open mode="trial"|"payment" loading onCancel onAccept />`. Nút xác nhận disabled tới khi tick checkbox đồng ý.

- [ ] **Step 1: Viết component**

```tsx
import { Alert, Checkbox, Modal, Typography } from 'antd';
import { useEffect, useState } from 'react';

const { Paragraph } = Typography;

interface Props {
  open: boolean;
  mode: 'trial' | 'payment';
  loading?: boolean;
  onCancel: () => void;
  onAccept: () => void;
}

export default function RefundPolicyModal({ open, mode, loading, onCancel, onAccept }: Props) {
  const [agreed, setAgreed] = useState(false);
  useEffect(() => {
    if (!open) setAgreed(false);
  }, [open]);

  const isTrial = mode === 'trial';

  return (
    <Modal
      open={open}
      title="Điều khoản sử dụng"
      okText={isTrial ? 'Đăng ký trải nghiệm' : 'Tiếp tục thanh toán'}
      cancelText="Huỷ"
      confirmLoading={loading}
      okButtonProps={{ disabled: !agreed }}
      onCancel={onCancel}
      onOk={onAccept}
      destroyOnClose
    >
      <Alert
        type="warning"
        showIcon
        message="Chính sách không hoàn tiền"
        description={
          isTrial
            ? 'Gói Pro trải nghiệm áp dụng 1 lần duy nhất cho mỗi tài khoản. Khi hết thời gian trải nghiệm, hệ thống tự động chuyển về gói trước đó. Khoản thanh toán (nếu có) không được hoàn lại.'
            : 'Khoản thanh toán mua/nâng cấp gói không được hoàn lại sau khi giao dịch hoàn tất. Vui lòng kiểm tra kỹ trước khi thanh toán.'
        }
        style={{ marginBottom: 16 }}
      />
      <Paragraph type="secondary" style={{ fontSize: 13 }}>
        Bằng việc tiếp tục, bạn xác nhận đã đọc và đồng ý với điều khoản nêu trên.
      </Paragraph>
      <Checkbox checked={agreed} onChange={(e) => setAgreed(e.target.checked)}>
        Tôi đã đọc và đồng ý với điều khoản không hoàn lại.
      </Checkbox>
    </Modal>
  );
}
```

- [ ] **Step 2: Typecheck + lint**

Run: `npm run typecheck && npm run lint`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/components/billing/RefundPolicyModal.tsx
git commit -m "feat(billing-ui): RefundPolicyModal điều khoản không hoàn (dùng chung)"
```

---

### Task 13: `CheckoutModal` (QR SePay + poll) + tích hợp vào `SettingsPlanPage`

**Files:**
- Create: `app/resources/js/components/billing/CheckoutModal.tsx`
- Modify: `app/resources/js/pages/SettingsPlanPage.tsx`

**Interfaces:**
- Consumes: `useInvoicePolling` (Task 11), `RefundPolicyModal` (Task 12), `CheckoutSession` mở rộng.
- Produces: `<CheckoutModal open session invoiceId onClose />` hiển thị QR + TK + memo + amount, poll tới khi `paid`. `SettingsPlanPage` có nút "Đăng ký trải nghiệm Pro" (khi eligible) + luồng terms → checkout/CheckoutModal.

- [ ] **Step 1: Viết `CheckoutModal`**

```tsx
import { Alert, Descriptions, Modal, Result, Space, Spin, Typography } from 'antd';
import { CheckCircleOutlined } from '@ant-design/icons';
import { CheckoutSession, useInvoicePolling } from '@/lib/billing';

const { Paragraph, Text } = Typography;

interface Props {
  open: boolean;
  session: CheckoutSession | null;
  invoiceId: number | null;
  onClose: () => void;
  onPaid?: () => void;
}

const vnd = (n?: number) => (n ? new Intl.NumberFormat('vi-VN').format(n) + '₫' : '');

export default function CheckoutModal({ open, session, invoiceId, onClose, onPaid }: Props) {
  const invoiceQ = useInvoicePolling(invoiceId, open);
  const paid = invoiceQ.data?.status === 'paid';

  if (paid) onPaid?.();

  return (
    <Modal open={open} title="Thanh toán" footer={null} onCancel={onClose} destroyOnClose width={460}>
      {paid ? (
        <Result
          status="success"
          icon={<CheckCircleOutlined />}
          title="Thanh toán thành công"
          subTitle="Gói của bạn đã được kích hoạt."
        />
      ) : session ? (
        <Space direction="vertical" style={{ width: '100%' }} size="middle">
          {session.qr_url && (
            <div style={{ textAlign: 'center' }}>
              <img src={session.qr_url} alt="QR thanh toán" style={{ maxWidth: '100%', width: 240 }} />
            </div>
          )}
          <Descriptions column={1} size="small" bordered>
            <Descriptions.Item label="Ngân hàng">{session.bank_code}</Descriptions.Item>
            <Descriptions.Item label="Số tài khoản">
              <Text copyable>{session.account_no}</Text>
            </Descriptions.Item>
            <Descriptions.Item label="Chủ tài khoản">{session.account_name}</Descriptions.Item>
            <Descriptions.Item label="Số tiền">{vnd(session.amount)}</Descriptions.Item>
            <Descriptions.Item label="Nội dung CK">
              <Text copyable>{session.memo}</Text>
            </Descriptions.Item>
          </Descriptions>
          <Alert
            type="info"
            showIcon
            message={
              <Space>
                <Spin size="small" />
                Đang chờ xác nhận chuyển khoản… Gói sẽ tự kích hoạt sau khi nhận tiền.
              </Space>
            }
          />
          <Paragraph type="secondary" style={{ fontSize: 12 }}>
            Chuyển khoản đúng nội dung <Text strong>{session.memo}</Text> để hệ thống tự đối soát.
          </Paragraph>
        </Space>
      ) : null}
    </Modal>
  );
}
```

> Lưu ý: gọi `onPaid?.()` trong render là side-effect — bọc trong `useEffect` khi implement để tránh cảnh báo React (theo dõi `paid`).

- [ ] **Step 2: Tích hợp `SettingsPlanPage`**

Thêm import `RefundPolicyModal`, `CheckoutModal`, hooks `useProTrialEligibility`, `useRegisterProTrial`, `REFUND_TERMS_VERSION`. Trong "Gói hiện tại" hoặc "Các gói có sẵn":
- Nếu `eligibilityQ.data?.eligible`, hiện nút `Button` "Đăng ký trải nghiệm Pro (30 ngày)" → mở `RefundPolicyModal mode="trial"` → `onAccept` gọi `registerProTrial.mutateAsync(REFUND_TERMS_VERSION)` → `message.success` + refetch subscription.
- Sửa luồng upgrade hiện có: thay vì `okText="Tạo hoá đơn & thanh toán"` gọi thẳng `submitCheckout`, chèn `RefundPolicyModal mode="payment"` trước; `onAccept` gọi `checkout.mutate(...)`, `onSuccess` lưu `res.checkout` + `res.invoice.id` và mở `CheckoutModal`.

Đoạn state thêm:

```tsx
const [trialTermsOpen, setTrialTermsOpen] = useState(false);
const [payTermsOpen, setPayTermsOpen] = useState(false);
const [checkoutSession, setCheckoutSession] = useState<CheckoutSession | null>(null);
const [checkoutInvoiceId, setCheckoutInvoiceId] = useState<number | null>(null);
const eligibilityQ = useProTrialEligibility();
const registerProTrial = useRegisterProTrial();

const submitCheckout = () => {
  checkout.mutate(
    { plan_code: upgradePlan!.code as PlanCode, cycle, gateway },
    {
      onSuccess: (res) => {
        setPayTermsOpen(false);
        setUpgradeOpen(false);
        setCheckoutSession(res.checkout);
        setCheckoutInvoiceId(res.invoice.id);
        message.success('Đã tạo hoá đơn — quét QR / chuyển khoản để hoàn tất.');
      },
      onError: (e) => message.error(errorMessage(e)),
    },
  );
};

const acceptTrial = async () => {
  try {
    await registerProTrial.mutateAsync(REFUND_TERMS_VERSION);
    setTrialTermsOpen(false);
    message.success('Đã kích hoạt gói Pro trải nghiệm!');
    subQ.refetch();
  } catch (e) {
    message.error(errorMessage(e));
  }
};
```

JSX thêm (cuối component):

```tsx
<RefundPolicyModal open={trialTermsOpen} mode="trial" loading={registerProTrial.isPending}
  onCancel={() => setTrialTermsOpen(false)} onAccept={acceptTrial} />
<RefundPolicyModal open={payTermsOpen} mode="payment" loading={checkout.isPending}
  onCancel={() => setPayTermsOpen(false)} onAccept={submitCheckout} />
<CheckoutModal open={checkoutSession !== null} session={checkoutSession} invoiceId={checkoutInvoiceId}
  onClose={() => { setCheckoutSession(null); setCheckoutInvoiceId(null); }}
  onPaid={() => { subQ.refetch(); invoicesQ.refetch(); }} />
```

Nút trải nghiệm (ví dụ cạnh card gói Pro):

```tsx
{eligibilityQ.data?.eligible && (
  <Button type="dashed" icon={<CrownOutlined />} onClick={() => setTrialTermsOpen(true)}>
    Đăng ký trải nghiệm Pro ({eligibilityQ.data.duration_days} ngày)
  </Button>
)}
```

Sửa nút "Nâng cấp" trong upgrade modal: đổi `onOk={submitCheckout}` thành `onOk={() => { setUpgradeOpen(false); setPayTermsOpen(true); }}` (mở terms trước), và bỏ Alert "gateway đang xây" (L338-341) vì SePay giờ chạy thật.

`import` cần thêm: `CheckoutSession, PlanCode, useProTrialEligibility, useRegisterProTrial, REFUND_TERMS_VERSION` từ `@/lib/billing`; `errorMessage` từ `@/lib/api`; component mới.

- [ ] **Step 3: Typecheck + lint + build**

Run (từ `app/`): `npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/components/billing/CheckoutModal.tsx app/resources/js/pages/SettingsPlanPage.tsx
git commit -m "feat(billing-ui): QR SePay + poll + trải nghiệm Pro + terms trong trang gói"
```

---

### Task 14: Admin FE — khối cấu hình chế độ trải nghiệm trong `AdminPlansPage`

**Files:**
- Modify: `app/resources/js/admin/lib/admin.tsx` (hooks `useAdminProTrialSettings`, `useAdminUpdateProTrialSettings` + type)
- Modify: `app/resources/js/admin/pages/tenants/AdminPlansPage.tsx` (khối "Chế độ trải nghiệm Pro")

**Interfaces:**
- Consumes: endpoint `GET/PUT /admin/pro-trial-settings` (Task 9).
- Produces: card cấu hình với `Switch` bật/tắt, `InputNumber` số ngày, 2 `DatePicker` khoảng mở; nút Lưu.

- [ ] **Step 1: Thêm hooks + type vào `admin.tsx`**

```tsx
export interface AdminProTrialSettings {
  enabled: boolean;
  duration_days: number;
  window_start: string | null;
  window_end: string | null;
}

export function useAdminProTrialSettings() {
  return useQuery({
    queryKey: ['admin', 'pro-trial-settings'],
    queryFn: async () => {
      const { data } = await api.get<{ data: AdminProTrialSettings }>('/admin/pro-trial-settings');
      return data.data;
    },
  });
}

export function useAdminUpdateProTrialSettings() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: AdminProTrialSettings) => {
      const { data } = await api.put<{ data: AdminProTrialSettings }>('/admin/pro-trial-settings', payload);
      return data.data;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'pro-trial-settings'] }),
  });
}
```

- [ ] **Step 2: Thêm khối UI vào `AdminPlansPage`** (một `Card` phía trên bảng gói):

```tsx
function ProTrialConfigCard() {
  const { message } = App.useApp();
  const q = useAdminProTrialSettings();
  const save = useAdminUpdateProTrialSettings();
  const [form] = Form.useForm();

  useEffect(() => {
    if (q.data) {
      form.setFieldsValue({
        enabled: q.data.enabled,
        duration_days: q.data.duration_days,
        window_start: q.data.window_start ? dayjs(q.data.window_start) : null,
        window_end: q.data.window_end ? dayjs(q.data.window_end) : null,
      });
    }
  }, [q.data, form]);

  const submit = async () => {
    const v = await form.validateFields();
    await save.mutateAsync({
      enabled: !!v.enabled,
      duration_days: Number(v.duration_days),
      window_start: v.window_start ? v.window_start.format('YYYY-MM-DD') : null,
      window_end: v.window_end ? v.window_end.format('YYYY-MM-DD') : null,
    });
    message.success('Đã lưu cấu hình trải nghiệm Pro.');
  };

  return (
    <Card title="Chế độ trải nghiệm Pro" style={{ marginBottom: 16 }}>
      <Form form={form} layout="inline">
        <Form.Item name="enabled" label="Bật" valuePropName="checked">
          <Switch />
        </Form.Item>
        <Form.Item name="duration_days" label="Số ngày" rules={[{ required: true }]}>
          <InputNumber min={1} max={365} />
        </Form.Item>
        <Form.Item name="window_start" label="Mở từ">
          <DatePicker format="YYYY-MM-DD" />
        </Form.Item>
        <Form.Item name="window_end" label="Đến">
          <DatePicker format="YYYY-MM-DD" />
        </Form.Item>
        <Form.Item>
          <Button type="primary" onClick={submit} loading={save.isPending}>Lưu</Button>
        </Form.Item>
      </Form>
    </Card>
  );
}
```

Render `<ProTrialConfigCard />` ngay trên bảng gói trong `AdminPlansPage`. Thêm import `DatePicker, InputNumber, Switch, Form` (antd), `dayjs`, `useEffect`, và các hook mới.

- [ ] **Step 3: Typecheck + lint + build**

Run: `npm run typecheck && npm run lint && npm run build`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/lib/admin.tsx app/resources/js/admin/pages/tenants/AdminPlansPage.tsx
git commit -m "feat(admin-ui): cấu hình chế độ trải nghiệm Pro trong trang Gói"
```

---

## Phase 5 — Verify end-to-end

### Task 15: Verify toàn luồng + quality gate

**Files:** (không tạo mới — chạy kiểm thử)

- [ ] **Step 1: Full backend test**

Run (từ `app/`): `php artisan test tests/Feature/Billing tests/Feature/Admin`
Expected: các test mới PASS; không hồi quy (ngoài fail nền có sẵn — [[test-verify-baseline]]).

- [ ] **Step 2: SePay webhook end-to-end** (đã có `SePayWebhookTest`) — xác nhận flow checkout(terms) → webhook → paid → activate vẫn xanh:

Run: `php artisan test --filter=SePayWebhookTest`
Expected: PASS.

- [ ] **Step 3: Pint + PHPStan**

Run: `vendor/bin/pint --test && vendor/bin/phpstan analyse`
Expected: PASS (không lỗi mới).

- [ ] **Step 4: Frontend quality gate**

Run: `npm run lint && npm run typecheck && npm run build`
Expected: PASS.

- [ ] **Step 5: Verify thủ công qua skill `verify`** (drive luồng thật):
  - Admin bật chế độ trải nghiệm (30 ngày) ở `/admin/plans`.
  - Tenant vào `/settings/plan`: thấy nút "Đăng ký trải nghiệm Pro" → modal điều khoản → tick → kích hoạt ngay → gói hiện tại = Pro.
  - Đăng ký lần 2 → nút biến mất / báo đã dùng.
  - Nâng cấp gói thật: nút → modal điều khoản → tiếp tục → CheckoutModal hiện QR SePay + memo; (giả lập webhook) → modal chuyển "Thành công".

- [ ] **Step 6: Cập nhật docs endpoints** — thêm 3 endpoint mới vào `docs/05-api/endpoints.md` (pro-trial/eligibility, pro-trial/register, admin/pro-trial-settings) và ghi chú checkout thêm `terms_accepted`.

- [ ] **Step 7: Commit tổng kết**

```bash
git add docs/05-api/endpoints.md
git commit -m "docs(api): endpoint trải nghiệm Pro + terms checkout"
```

---

## Self-Review (đã chạy)

- **Spec coverage:** A (gỡ test_unlimited) → Task 1-3. B (chế độ trải nghiệm) → Task 4-9. C (terms) → Task 10 (BE) + Task 12/13 (FE). D (SePay) → Task 11/13 + verify Task 15. Admin config → Task 9/14. ✔
- **Placeholder scan:** không TODO/TBD; mọi code step có code thật. Vài chỗ "xác minh khi implement" (namespace trait `BelongsToTenant`, tên `SubscriptionResource`, helper login admin test) là điểm cần khớp code hiện có — đã chỉ rõ file nguồn để copy, không phải placeholder logic.
- **Type consistency:** `ProTrialSettings` (enabled/durationDays/windowStart/windowEnd/windowOpen) dùng nhất quán ở Task 4/6/9. `CheckoutSession` mở rộng dùng ở Task 11/13. `pro_trial_grants` cột dùng nhất quán Task 5/7/8. `meta.pro_trial`/`revert_*` set ở Task 7, đọc ở Task 8. Mã lỗi `PRO_TRIAL_NOT_ELIGIBLE` khớp Task 7 (BE) ↔ Task 7 test. ✔
