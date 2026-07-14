# Sửa bộ đếm AI + Trang chi tiết tenant chuyên sâu Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sửa 2 bug bộ đếm lượt AI (race condition + thiếu ghi feature), và xây trang admin chi tiết
tenant (`/admin/tenants/:id`) thay Drawer hiện tại, thêm: cộng/trừ hạn mức AI + lịch sử, số lượng SKU,
thống kê đơn theo ngày, lịch xử lý đơn, audit log đầy đủ, lịch sử đăng nhập nhân viên tenant.

**Architecture:** Backend mở rộng `AdminTenantController`/`AdminTenantService` theo đúng pattern hiện có
(mọi action nhạy cảm bắt buộc `reason` ≥10 ký tự + ghi `AuditLog::query()->create()`). Frontend thay
`AdminTenantDrawer` bằng route trang riêng, tái dùng toàn bộ hook cũ + thêm hook mới trong
`app/resources/js/admin/lib/admin.tsx`.

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/`.
- Ghi AuditLog trong `AdminTenantService` dùng NGUYÊN VĂN pattern đã có: `AuditLog::query()->create([
  'tenant_id' => ..., 'user_id' => $adminUserId, 'action' => 'admin.xxx', ...])` — KHÔNG dùng
  `AuditLog::record()` helper (khác pattern, dự án hiện tại dùng cách thủ công trong file này).
- Mọi action nhạy cảm (trừ/cộng hạn mức AI) bắt buộc `reason` (`min:10`) qua `requireReason()` đã có.
- Không thêm Gate ability admin mới — hệ thống admin hiện phẳng, 1 tầng.
- Trước khi coi bất kỳ task nào "xong", chạy đúng lệnh test của task đó và xác nhận PASS.

---

## Task 1: Sửa race condition `AiCreditService` + fix `ShopHealthAnalysisService`

**Files:**
- Modify: `app/app/Modules/Billing/Services/AiCreditService.php`
- Modify: `app/app/Modules/Channels/Services/ShopHealthAnalysisService.php:35`
- Test: `app/tests/Unit/Billing/AiCreditServiceLockingTest.php`

**Interfaces:**
- Không đổi chữ ký public method nào (`consume`, `record`, `grantPurchase`, `summary`, `wallet`,
  `canUse`, `available`, `aiEnabled`, `unlimited`, `monthlyAllowance` giữ nguyên) — chỉ đổi cách ghi DB
  bên trong để atomic. Task 2 phụ thuộc `wallet()`/`grantPurchase()` giữ nguyên hành vi.

- [ ] **Step 1: Viết test thất bại trước** (`AiCreditServiceLockingTest.php`)

```php
<?php

namespace Tests\Unit\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\AiCreditWallet;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\AiCreditService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiCreditServiceLockingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    /** record() phải bọc trong transaction có lockForUpdate trên ai_credit_wallets. */
    public function test_record_locks_wallet_row_for_update(): void
    {
        app(AiCreditService::class)->record($this->tenant->getKey(), 1, 'suggest');

        // Xác nhận query log có câu SELECT ... FOR UPDATE trên ai_credit_wallets khi record() chạy lần 2
        // (lần 1 firstOrCreate tạo mới, chưa chắc có lock — lần 2 chắc chắn phải load qua đường có lock).
        DB::enableQueryLog();
        app(AiCreditService::class)->record($this->tenant->getKey(), 1, 'suggest');
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $hasLock = collect($log)->contains(fn ($q) => str_contains(strtolower($q['query']), 'ai_credit_wallets')
            && str_contains(strtolower($q['query']), 'for update'));
        $this->assertTrue($hasLock, 'record() phải khoá row ai_credit_wallets bằng lockForUpdate() trong transaction.');
    }

    /** countUsage() không được để mất lượt đếm khi firstOrCreate đụng unique index (race). */
    public function test_count_usage_survives_concurrent_first_create_collision(): void
    {
        $tenantId = $this->tenant->getKey();
        $ym = (int) now()->format('Ym');

        // Giả lập race: row đã tồn tại NGAY TRƯỚC khi record() chạy (mô phỏng process khác đã tạo trước).
        AiUsageCounter::withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)->create([
            'tenant_id' => $tenantId, 'user_id' => 0, 'period_ym' => $ym, 'feature' => 'suggest', 'count' => 5,
        ]);

        app(AiCreditService::class)->record($tenantId, 1, 'suggest', 0);

        $row = AiUsageCounter::withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantId)->where('user_id', 0)->where('period_ym', $ym)->where('feature', 'suggest')->first();
        $this->assertSame(6, $row->count, 'Lượt đếm phải cộng dồn đúng, không bị mất do race trên firstOrCreate.');
    }
}
```

- [ ] **Step 2: Chạy test — phải FAIL**

Run: `cd app && php artisan test --filter=AiCreditServiceLockingTest`
Expected: test 1 FAIL (chưa có `lockForUpdate` trong query log). Test 2 nhiều khả năng đã PASS sẵn
(bug 1 mất lượt chỉ xảy ra khi 2 process THẬT chạy song song, không phải khi row đã có sẵn trước — đây
là test hồi quy phòng ngừa, không nhất thiết FAIL ở bước RED, ghi rõ trong report nếu vậy).

- [ ] **Step 3: Sửa `AiCreditService.php`**

Thêm import đầu file:
```php
use Illuminate\Support\Facades\DB;
```

Sửa `wallet()` — thêm tham số nội bộ để tái dùng trong transaction đã có lock (giữ nguyên chữ ký public,
thêm 1 private helper mới thay vì đổi `wallet()` — vì `wallet()` được gọi cả ở nơi KHÔNG cần lock, vd
`available()`/`summary()` chỉ đọc):

```php
    /** wallet() có khoá row (FOR UPDATE) — dùng trong mọi nhánh ghi (consume/record/grantPurchase/deduct). */
    private function lockedWallet(int $tenantId): AiCreditWallet
    {
        /** @var AiCreditWallet $w */
        $w = AiCreditWallet::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['tenant_id' => $tenantId],
            ['purchased_balance' => 0, 'period_used' => 0, 'period_anchor' => now()->startOfDay()],
        );
        $w = AiCreditWallet::withoutGlobalScope(TenantScope::class)->where('id', $w->id)->lockForUpdate()->first();
        if ($w->period_anchor === null || $w->period_anchor->format('Y-m') !== now()->format('Y-m')) {
            $w->forceFill(['period_used' => 0, 'period_anchor' => now()->startOfDay()])->save();
        }

        return $w;
    }
```

Sửa `consume()` — bọc toàn bộ thân hàm (sau guard `aiEnabled`/`unlimited`) trong transaction, đổi
`$this->wallet($tenantId)` thành `$this->lockedWallet($tenantId)`:

```php
    public function consume(int $tenantId, int $n = 1): void
    {
        if (! $this->aiEnabled($tenantId)) {
            throw AiCreditException::unavailable();
        }
        if ($this->unlimited($tenantId)) {
            return;
        }
        DB::transaction(function () use ($tenantId, $n) {
            $w = $this->lockedWallet($tenantId);
            $allowanceLeft = max(0, $this->monthlyAllowance($tenantId) - $w->period_used);
            if ($allowanceLeft + $w->purchased_balance < $n) {
                throw AiCreditException::exhausted();
            }
            $fromAllowance = min($n, $allowanceLeft);
            $w->forceFill([
                'period_used' => $w->period_used + $fromAllowance,
                'purchased_balance' => $w->purchased_balance - ($n - $fromAllowance),
            ])->save();
        });
    }
```

Sửa `record()` — tách phần ghi wallet vào transaction riêng (giữ `countUsage()` gọi TRƯỚC, ngoài
transaction của wallet — 2 bảng độc lập, không cần chung 1 transaction):

```php
    public function record(int $tenantId, int $n = 1, ?string $feature = null, ?int $userId = null): void
    {
        if ($n <= 0) {
            return;
        }

        $this->countUsage($tenantId, $n, $feature, $userId);

        if (! $this->aiEnabled($tenantId) || $this->unlimited($tenantId)) {
            return;
        }
        DB::transaction(function () use ($tenantId, $n) {
            $w = $this->lockedWallet($tenantId);
            $allowanceLeft = max(0, $this->monthlyAllowance($tenantId) - $w->period_used);
            $fromAllowance = min($n, $allowanceLeft);
            $fromPurchase = min($n - $fromAllowance, $w->purchased_balance);
            if ($fromAllowance === 0 && $fromPurchase === 0) {
                return;
            }
            $w->forceFill([
                'period_used' => $w->period_used + $fromAllowance,
                'purchased_balance' => $w->purchased_balance - $fromPurchase,
            ])->save();
        });
    }
```

Sửa `countUsage()` — bọc trong transaction, dùng `lockForUpdate` sau `firstOrCreate` để tránh race trên
increment (giữ `catch (\Throwable)` nhưng để LỖI THẬT bị nuốt ít hơn — chỉ nuốt lỗi ngoài transaction,
KHÔNG nuốt trong lúc tính toán):

```php
    private function countUsage(int $tenantId, int $n, ?string $feature, ?int $userId): void
    {
        try {
            $uid = $userId ?? Auth::id() ?? 0;
            $ym = (int) now()->format('Ym');
            $feat = $feature ?? 'other';

            DB::transaction(function () use ($tenantId, $uid, $ym, $feat, $n) {
                $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                    ['tenant_id' => $tenantId, 'user_id' => (int) $uid, 'period_ym' => $ym, 'feature' => $feat],
                    ['count' => 0],
                );
                $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)
                    ->where('id', $row->id)->lockForUpdate()->first();
                $row->increment('count', $n);
            });
        } catch (\Throwable) {
            // Đếm lỗi không được phép làm vỡ luồng AI.
        }
    }
```

Sửa `grantPurchase()` — dùng `lockedWallet()`:

```php
    public function grantPurchase(int $tenantId, int $amount): int
    {
        return DB::transaction(function () use ($tenantId, $amount) {
            $w = $this->lockedWallet($tenantId);
            $new = min(AiCreditWallet::PURCHASE_MAX_BALANCE, $w->purchased_balance + $amount);
            $added = $new - $w->purchased_balance;
            $w->forceFill(['purchased_balance' => $new])->save();

            return $added;
        });
    }
```

`wallet()` giữ NGUYÊN như cũ (không lock) — vẫn dùng cho `available()`/`summary()`/`canUse()` (chỉ đọc,
không cần khoá).

- [ ] **Step 4: Sửa `ShopHealthAnalysisService.php:35`**

Tìm dòng:
```php
$this->credits->consume($tenantId, 1);
```
Đổi thành:
```php
$this->credits->record($tenantId, 1, 'shop_health');
```

- [ ] **Step 5: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=AiCreditServiceLockingTest`
Expected: PASS (2 test).

- [ ] **Step 6: Chạy regression toàn bộ test Billing hiện có**

Run: `cd app && php artisan test --filter=Billing`
Expected: PASS toàn bộ — không phá bất kỳ hành vi `consume`/`record`/`grantPurchase`/`summary` nào.

- [ ] **Step 7: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Billing/Services/AiCreditService.php app/Modules/Channels/Services/ShopHealthAnalysisService.php && vendor/bin/phpstan analyse app/Modules/Billing/Services/AiCreditService.php app/Modules/Channels/Services/ShopHealthAnalysisService.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Billing/Services/AiCreditService.php app/app/Modules/Channels/Services/ShopHealthAnalysisService.php app/tests/Unit/Billing/AiCreditServiceLockingTest.php
git commit -m "fix(billing): khoá row ai_credit_wallets/ai_usage_counters chống mất lượt đếm AI + fix shop_health không ghi feature"
```

---

## Task 2: `AiCreditService::deduct()` + lịch sử dùng theo tháng + endpoint admin cộng/trừ

**Files:**
- Modify: `app/app/Modules/Billing/Services/AiCreditService.php` (thêm `deduct()`)
- Modify: `app/app/Modules/Billing/Services/AiUsageReportService.php` (thêm `breakdownForTenant()`)
- Modify: `app/app/Modules/Billing/Contracts/AiUsageReporter.php` (thêm method vào interface)
- Modify: `app/app/Modules/Admin/Services/AdminTenantService.php` (thêm `adjustAiCredit()`)
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminTenantController.php` (thêm action `adjustAiCredit`
  + thêm `ai_credit`/`ai_usage_history` vào response `show()`)
- Modify: `app/app/Modules/Admin/Http/routes.php` (thêm route)
- Test: `app/tests/Feature/Admin/AdminTenantAiCreditAdjustTest.php`

**Interfaces:**
- Consumes: `AiCreditService::summary()` (đã có), Task 1's `lockedWallet()` (private, tái dùng nội bộ).
- Produces: `AiCreditService::deduct(int $tenantId, int $amount): int` (trả số THỰC trừ được, sàn 0);
  `AiUsageReportService::breakdownForTenant(int $tenantId): array{all_time:int, by_month:array, by_feature:array}`;
  endpoint `POST /api/v1/admin/tenants/{tid}/ai-credit/adjust` body `{amount:int, reason:string}` — Task 5/6
  (FE) gọi đúng path + body shape này.

- [ ] **Step 1: Viết test thất bại trước** (`AdminTenantAiCreditAdjustTest.php`)

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\AiCreditWallet;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantAiCreditAdjustTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->admin = AdminUser::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    public function test_admin_can_grant_ai_credit(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->postJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/ai-credit/adjust", [
                'amount' => 300, 'reason' => 'Tặng thêm do lỗi hệ thống tuần trước',
            ])->assertOk()
            ->assertJsonPath('data.purchased_balance', 300);

        $w = AiCreditWallet::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenant->getKey())->first();
        $this->assertSame(300, $w->purchased_balance);

        $audit = AuditLog::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())->where('action', 'admin.ai_credit.adjust')->first();
        $this->assertNotNull($audit);
        $this->assertSame(300, $audit->changes['amount']);
    }

    public function test_admin_can_deduct_ai_credit_floored_at_zero(): void
    {
        AiCreditWallet::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'purchased_balance' => 100, 'period_used' => 0,
        ]);

        $this->actingAs($this->admin, 'admin_web')
            ->postJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/ai-credit/adjust", [
                'amount' => -500, 'reason' => 'Thu hồi do tặng nhầm tuần trước',
            ])->assertOk()
            ->assertJsonPath('data.purchased_balance', 0);
    }

    public function test_requires_reason_at_least_10_chars(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->postJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/ai-credit/adjust", [
                'amount' => 100, 'reason' => 'ngắn',
            ])->assertStatus(422);
    }
}
```

- [ ] **Step 2: Chạy test — phải FAIL**

Run: `cd app && php artisan test --filter=AdminTenantAiCreditAdjustTest`
Expected: FAIL — route chưa tồn tại (404).

- [ ] **Step 3: Thêm `deduct()` vào `AiCreditService.php`** (đặt ngay sau `grantPurchase()`)

```php
    /** Trừ credit MUA (sàn 0, không âm). Trả số thực trừ được (dương). */
    public function deduct(int $tenantId, int $amount): int
    {
        return DB::transaction(function () use ($tenantId, $amount) {
            $w = $this->lockedWallet($tenantId);
            $new = max(0, $w->purchased_balance - $amount);
            $removed = $w->purchased_balance - $new;
            $w->forceFill(['purchased_balance' => $new])->save();

            return $removed;
        });
    }
```

- [ ] **Step 4: Thêm `breakdownForTenant()` vào `AiUsageReportService.php` + interface**

Trong `app/app/Modules/Billing/Contracts/AiUsageReporter.php`, thêm khai báo method (đọc file trước để
biết đúng cú pháp interface hiện có, thêm dòng tương tự `breakdownForUser`):
```php
    /** @return array{all_time:int, by_month:list<array{period_ym:int,count:int}>, by_feature:list<array{feature:string,count:int}>} */
    public function breakdownForTenant(int $tenantId): array;
```

Trong `AiUsageReportService.php`, thêm method (copy gần như nguyên `breakdownForUser`, đổi where):
```php
    public function breakdownForTenant(int $tenantId): array
    {
        $base = AiUsageCounter::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId);

        $byMonth = (clone $base)
            ->selectRaw('period_ym, SUM(count) as count')
            ->groupBy('period_ym')->orderByDesc('period_ym')->get()
            ->map(fn ($r) => ['period_ym' => (int) $r->period_ym, 'count' => (int) $r->count])->all();

        $byFeature = (clone $base)
            ->selectRaw('feature, SUM(count) as count')
            ->groupBy('feature')->orderByDesc('count')->get()
            ->map(fn ($r) => ['feature' => (string) $r->feature, 'count' => (int) $r->count])->all();

        return [
            'all_time' => array_sum(array_column($byFeature, 'count')),
            'by_month' => $byMonth,
            'by_feature' => $byFeature,
        ];
    }
```

- [ ] **Step 5: Thêm `AdminTenantService::adjustAiCredit()`**

Thêm import đầu file: `use CMBcoreSeller\Modules\Billing\Services\AiCreditService;` — thêm vào
constructor `protected AiCreditService $aiCredit` (thêm tham số mới vào `__construct` hiện có, giữ các
tham số cũ nguyên vị trí, container tự inject).

Thêm method (đặt cạnh `setFeatureOverrides`):
```php
    /**
     * SPEC 2026-07-15 — admin cộng/trừ credit AI mua thêm tay. `$amount` dương=cộng, âm=trừ.
     * Trừ sàn ở 0 (không âm); cộng chặn trần 5000 (đúng logic AiCreditService::grantPurchase).
     *
     * @return array{purchased_balance:int, applied:int}
     */
    public function adjustAiCredit(Tenant $tenant, int $amount, string $reason, int $adminUserId): array
    {
        $this->requireReason($reason);
        $tenantId = (int) $tenant->getKey();
        $before = $this->aiCredit->summary($tenantId)['purchased_balance'];

        $applied = $amount >= 0
            ? $this->aiCredit->grantPurchase($tenantId, $amount)
            : -$this->aiCredit->deduct($tenantId, abs($amount));

        $after = $this->aiCredit->summary($tenantId)['purchased_balance'];

        AuditLog::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $adminUserId,
            'action' => 'admin.ai_credit.adjust',
            'changes' => [
                'amount' => $amount, 'applied' => $applied,
                'balance_before' => $before, 'balance_after' => $after,
                'reason' => $reason,
            ],
            'ip' => request()->ip(),
        ]);

        return ['purchased_balance' => $after, 'applied' => $applied];
    }
```

- [ ] **Step 6: Thêm action + route trong `AdminTenantController.php`**

Thêm import: `use CMBcoreSeller\Modules\Billing\Services\AiCreditService;` và
`use CMBcoreSeller\Modules\Billing\Services\AiUsageReportService;` — thêm vào constructor.

Thêm action (đặt cạnh `featureOverrides`):
```php
    /** POST /api/v1/admin/tenants/{tid}/ai-credit/adjust */
    public function adjustAiCredit(Request $request, int $tid, AiCreditService $aiCredit): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        $tenant = Tenant::query()->findOrFail($tid);
        $result = $this->service->adjustAiCredit($tenant, (int) $data['amount'], $data['reason'], (int) $request->user()->getKey());

        return response()->json(['data' => $result + $aiCredit->summary($tid)]);
    }
```

Trong `show()`, thêm vào mảng response (sau `'vouchers_redeemed' => ...`):
```php
            'ai_credit' => $aiCredit->summary($tenant->getKey()),
            'ai_usage_history' => $aiUsageReport->breakdownForTenant($tenant->getKey()),
```
(cần thêm `AiCreditService $aiCredit, AiUsageReportService $aiUsageReport` vào tham số method `show()`
— Laravel tự inject qua route model binding style method injection).

Trong `app/app/Modules/Admin/Http/routes.php`, thêm route (cạnh
`admin.tenants.feature-overrides`):
```php
        Route::post('tenants/{tid}/ai-credit/adjust', [AdminTenantController::class, 'adjustAiCredit'])
            ->whereNumber('tid')->name('admin.tenants.ai-credit.adjust');
```

- [ ] **Step 7: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=AdminTenantAiCreditAdjustTest`
Expected: PASS (3 test).

- [ ] **Step 8: Regression + quality gate**

Run: `cd app && php artisan test --filter=AdminTenant && vendor/bin/pint --test app/Modules/Billing/Services/AiCreditService.php app/Modules/Billing/Services/AiUsageReportService.php app/Modules/Billing/Contracts/AiUsageReporter.php app/Modules/Admin/Services/AdminTenantService.php app/Modules/Admin/Http/Controllers/AdminTenantController.php && vendor/bin/phpstan analyse app/Modules/Billing/Services/AiCreditService.php app/Modules/Billing/Services/AiUsageReportService.php app/Modules/Admin/Services/AdminTenantService.php app/Modules/Admin/Http/Controllers/AdminTenantController.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/app/Modules/Billing/Services/AiCreditService.php app/app/Modules/Billing/Services/AiUsageReportService.php app/app/Modules/Billing/Contracts/AiUsageReporter.php app/app/Modules/Admin/Services/AdminTenantService.php app/app/Modules/Admin/Http/Controllers/AdminTenantController.php app/app/Modules/Admin/Http/routes.php app/tests/Feature/Admin/AdminTenantAiCreditAdjustTest.php
git commit -m "feat(admin,billing): cộng/trừ hạn mức AI tay + lịch sử dùng theo tháng cho tenant"
```

---

## Task 3: Lịch sử đăng nhập nhân viên tenant

**Files:**
- Create: `app/app/Modules/Tenancy/Database/Migrations/2026_07_15_100000_create_user_login_events_table.php`
- Create: `app/app/Modules/Tenancy/Models/UserLoginEvent.php`
- Create: `app/app/Modules/Tenancy/Listeners/LogUserLogin.php`
- Modify: `app/app/Modules/Tenancy/TenancyServiceProvider.php`
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminTenantController.php` (endpoint mới)
- Modify: `app/app/Modules/Admin/Http/routes.php`
- Test: `app/tests/Feature/Tenancy/LogUserLoginTest.php`
- Test: `app/tests/Feature/Admin/AdminTenantLoginHistoryTest.php`

**Interfaces:**
- Produces: bảng `user_login_events(id, user_id, ip_address, user_agent, logged_in_at, created_at)`;
  endpoint `GET /api/v1/admin/tenants/{tid}/login-history?page=1` — Task 6 (FE) gọi đúng path này, trả
  `Paginated<{user_id,name,email,ip_address,user_agent,logged_in_at}>`.

- [ ] **Step 1: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lịch sử đăng nhập nhân viên tenant (guard `web`, KHÔNG bao gồm admin_web — admin có audit riêng).
 * Design 2026-07-15. Không gắn tenant_id trực tiếp — user có thể thuộc nhiều tenant qua tenant_users,
 * trang admin join qua đó để lọc theo tenant đang xem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_login_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_login_events');
    }
};
```

- [ ] **Step 2: Viết model**

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 1 lần đăng nhập thành công của user (guard `web`) — xem `LogUserLogin`.
 *
 * @property int $id
 * @property int $user_id
 * @property ?string $ip_address
 * @property ?string $user_agent
 * @property \Carbon\Carbon $logged_in_at
 */
class UserLoginEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'ip_address', 'user_agent', 'logged_in_at'];

    protected function casts(): array
    {
        return ['logged_in_at' => 'datetime'];
    }
}
```

- [ ] **Step 3: Viết listener**

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Listeners;

use CMBcoreSeller\Modules\Tenancy\Models\UserLoginEvent;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

/**
 * Ghi lịch sử đăng nhập CHỈ guard `web` (nhân viên tenant) — admin_web có audit log riêng, không lẫn.
 * Design 2026-07-15.
 */
class LogUserLogin
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        UserLoginEvent::query()->create([
            'user_id' => $event->user->getAuthIdentifier(),
            'ip_address' => Request::ip(),
            'user_agent' => (string) Request::header('User-Agent'),
            'logged_in_at' => now(),
        ]);
    }
}
```

- [ ] **Step 4: Đăng ký listener trong `TenancyServiceProvider.php`**

Thêm import: `use CMBcoreSeller\Modules\Tenancy\Listeners\LogUserLogin;` và
`use Illuminate\Auth\Events\Login;` và `use Illuminate\Support\Facades\Event;` — trong `boot()`, thêm:
```php
        Event::listen(Login::class, LogUserLogin::class);
```

- [ ] **Step 5: Viết test thất bại trước** (`LogUserLoginTest.php`)

```php
<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Models\UserLoginEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LogUserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_guard_login_creates_login_event(): void
    {
        $user = User::factory()->create();

        Auth::guard('web')->login($user);

        $this->assertSame(1, UserLoginEvent::query()->where('user_id', $user->getKey())->count());
    }

    public function test_other_guard_login_does_not_create_login_event(): void
    {
        $user = User::factory()->create();

        Auth::guard('sanctum')->login($user);

        $this->assertSame(0, UserLoginEvent::query()->where('user_id', $user->getKey())->count());
    }
}
```

**Lưu ý implementer:** nếu guard `sanctum` không hỗ trợ `login()` (stateless) trong môi trường test, đổi
test 2 sang dùng 1 guard khác đã đăng ký trong `config/auth.php` (vd `admin_web`) — mục tiêu chỉ là
chứng minh listener LỌC đúng theo `$event->guard`, không quan trọng guard cụ thể nào miễn khác `web`.

- [ ] **Step 6: Chạy test — phải PASS sau khi Step 1-4 hoàn tất**

Run: `cd app && php artisan test --filter=LogUserLoginTest`
Expected: PASS (2 test).

- [ ] **Step 7: Thêm endpoint admin `login-history`**

Trong `AdminTenantController.php`, thêm action (đặt cuối file trước các private method):
```php
    /** GET /api/v1/admin/tenants/{tid}/login-history */
    public function loginHistory(Request $request, int $tid): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($tid);
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));

        $page = \CMBcoreSeller\Modules\Tenancy\Models\UserLoginEvent::query()
            ->whereIn('user_id', TenantUser::query()->where('tenant_id', $tenant->getKey())->pluck('user_id'))
            ->with('user:id,name,email')
            ->orderByDesc('logged_in_at')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($page->items())->map(fn ($e) => [
                'user_id' => $e->user_id, 'name' => $e->user?->name, 'email' => $e->user?->email,
                'ip_address' => $e->ip_address, 'user_agent' => $e->user_agent,
                'logged_in_at' => $e->logged_in_at->toIso8601String(),
            ])->all(),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }
```

Cần thêm quan hệ `user()` vào `UserLoginEvent` model (Step 2 file) trước khi dùng `with('user:...')`:
```php
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\CMBcoreSeller\Models\User::class);
    }
```

Route trong `routes.php` (cạnh `admin.tenants.show`):
```php
        Route::get('tenants/{tid}/login-history', [AdminTenantController::class, 'loginHistory'])
            ->whereNumber('tid')->name('admin.tenants.login-history');
```

- [ ] **Step 8: Viết test endpoint** (`AdminTenantLoginHistoryTest.php`)

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\UserLoginEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantLoginHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_login_history_for_tenant_members_only(): void
    {
        $admin = AdminUser::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $member = User::factory()->create(['name' => 'NV A']);
        $tenant->users()->attach($member->getKey(), ['role' => Role::Staff->value]);
        $outsider = User::factory()->create();

        UserLoginEvent::query()->create(['user_id' => $member->getKey(), 'ip_address' => '1.2.3.4', 'logged_in_at' => now()]);
        UserLoginEvent::query()->create(['user_id' => $outsider->getKey(), 'ip_address' => '9.9.9.9', 'logged_in_at' => now()]);

        $res = $this->actingAs($admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$tenant->getKey()}/login-history")
            ->assertOk();

        $rows = $res->json('data');
        $this->assertCount(1, $rows);
        $this->assertSame('NV A', $rows[0]['name']);
    }
}
```

- [ ] **Step 9: Chạy lại toàn bộ test — phải PASS**

Run: `cd app && php artisan test --filter=LogUserLoginTest && php artisan test --filter=AdminTenantLoginHistoryTest`
Expected: PASS (2 + 1 test).

- [ ] **Step 10: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Tenancy/Models/UserLoginEvent.php app/Modules/Tenancy/Listeners/LogUserLogin.php app/Modules/Tenancy/TenancyServiceProvider.php app/Modules/Admin/Http/Controllers/AdminTenantController.php && vendor/bin/phpstan analyse app/Modules/Tenancy/Models/UserLoginEvent.php app/Modules/Tenancy/Listeners/LogUserLogin.php`
Expected: PASS.

- [ ] **Step 11: Commit**

```bash
git add app/app/Modules/Tenancy/Database/Migrations/2026_07_15_100000_create_user_login_events_table.php app/app/Modules/Tenancy/Models/UserLoginEvent.php app/app/Modules/Tenancy/Listeners/LogUserLogin.php app/app/Modules/Tenancy/TenancyServiceProvider.php app/app/Modules/Admin/Http/Controllers/AdminTenantController.php app/app/Modules/Admin/Http/routes.php app/tests/Feature/Tenancy/LogUserLoginTest.php app/tests/Feature/Admin/AdminTenantLoginHistoryTest.php
git commit -m "feat(tenancy,admin): ghi + xem lịch sử đăng nhập nhân viên tenant"
```

---

## Task 4: Số lượng SKU + thống kê đơn theo ngày + lịch xử lý đơn + audit log đầy đủ

**Files:**
- Modify: `app/app/Modules/Admin/Services/AdminTenantService.php` (thêm `dailyOrderCounts()`)
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminTenantController.php` (3 action mới + sửa `show()`)
- Modify: `app/app/Modules/Admin/Http/routes.php`
- Test: `app/tests/Feature/Admin/AdminTenantDeepStatsTest.php`

**Interfaces:**
- Produces:
  - `show()` response thêm field `sku_count: int`.
  - `GET /api/v1/admin/tenants/{tid}/orders/daily-stats?days=30` → `{data: [{date, count, grand_total_sum}]}`.
  - `GET /api/v1/admin/tenants/{tid}/order-status-history?page=1` → `Paginated<{order_id,order_number,from_status,to_status,source,changed_at}>`.
  - `GET /api/v1/admin/tenants/{tid}/audit-logs?page=1` → `Paginated<AdminAuditEntry>` (KHÔNG lọc theo
    `action like 'admin.%'` — trả TẤT CẢ log của tenant).
  - Task 6 (FE) gọi đúng 3 path trên.

- [ ] **Step 1: Viết test thất bại trước** (`AdminTenantDeepStatsTest.php`)

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantDeepStatsTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
    }

    public function test_show_includes_sku_count(): void
    {
        $wh = Warehouse::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'name' => 'Kho', 'is_default' => true,
        ]);
        Sku::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'sku_code' => 'A1', 'name' => 'X',
            'warehouse_id' => $wh->getKey(), 'stock_on_hand' => 1, 'stock_reserved' => 0,
        ]);

        $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}")
            ->assertOk()->assertJsonPath('data.sku_count', 1);
    }

    public function test_daily_order_stats_groups_by_day(): void
    {
        Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'status' => 'processing',
            'raw_status' => 'X', 'currency' => 'VND', 'grand_total' => 100000, 'item_total' => 100000,
            'tags' => [], 'shipping_address' => [], 'placed_at' => now(),
        ]);

        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/orders/daily-stats?days=7")
            ->assertOk();

        $rows = $res->json('data');
        $this->assertGreaterThanOrEqual(1, count($rows));
        $this->assertSame(1, collect($rows)->firstWhere('date', now()->format('Y-m-d'))['count']);
    }

    public function test_order_status_history_lists_changes(): void
    {
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'source' => 'manual', 'status' => 'processing',
            'order_number' => 'M-1', 'raw_status' => 'X', 'currency' => 'VND',
            'grand_total' => 100000, 'item_total' => 100000, 'tags' => [], 'shipping_address' => [],
        ]);
        OrderStatusHistory::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'from_status' => null, 'to_status' => 'processing', 'source' => 'user', 'changed_at' => now(),
        ]);

        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/order-status-history")
            ->assertOk();

        $this->assertSame('M-1', $res->json('data.0.order_number'));
    }

    public function test_audit_logs_endpoint_returns_all_actions_not_just_admin(): void
    {
        AuditLog::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'user_id' => 1,
            'action' => 'orders.status.change', 'ip' => '127.0.0.1',
        ]);
        AuditLog::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'user_id' => $this->admin->getKey(),
            'action' => 'admin.tenant.suspend', 'ip' => '127.0.0.1',
        ]);

        $res = $this->actingAs($this->admin, 'admin_web')
            ->getJson("/api/v1/admin/tenants/{$this->tenant->getKey()}/audit-logs")
            ->assertOk();

        $this->assertCount(2, $res->json('data'));
    }
}
```

- [ ] **Step 2: Chạy test — phải FAIL**

Run: `cd app && php artisan test --filter=AdminTenantDeepStatsTest`
Expected: FAIL — `sku_count` thiếu trong response, 3 route còn lại 404.

- [ ] **Step 3: Thêm `sku_count` vào `show()` trong `AdminTenantController.php`**

Thêm import: `use CMBcoreSeller\Modules\Inventory\Models\Sku;`. Trong `show()`, thêm dòng tính + thêm
vào mảng trả về:
```php
        $skuCount = Sku::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->count();
```
Thêm `'sku_count' => $skuCount,` vào mảng `array_merge($this->summary($tenant), [...])`.

- [ ] **Step 4: Thêm `AdminTenantService::dailyOrderCounts()`**

Thêm import: `use CMBcoreSeller\Modules\Orders\Models\Order;`. Thêm method:
```php
    /**
     * Đếm đơn theo ngày (mọi source), N ngày gần nhất. `placed_at` ưu tiên, fallback `created_at`
     * nếu null (đơn chưa set placed_at).
     *
     * @return list<array{date:string, count:int, grand_total_sum:int}>
     */
    public function dailyOrderCounts(int $tenantId, int $days = 30): array
    {
        $since = now()->subDays(max(1, min(365, $days)))->startOfDay();

        $rows = Order::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->selectRaw('DATE(COALESCE(placed_at, created_at)) as d, COUNT(*) as cnt, SUM(grand_total) as total')
            ->where(function ($q) use ($since) {
                $q->where('placed_at', '>=', $since)->orWhere(function ($q2) use ($since) {
                    $q2->whereNull('placed_at')->where('created_at', '>=', $since);
                });
            })
            ->groupBy('d')->orderByDesc('d')->get();

        return $rows->map(fn ($r) => [
            'date' => (string) $r->d, 'count' => (int) $r->cnt, 'grand_total_sum' => (int) $r->total,
        ])->all();
    }
```

- [ ] **Step 5: Thêm 3 action trong `AdminTenantController.php`**

```php
    /** GET /api/v1/admin/tenants/{tid}/orders/daily-stats */
    public function dailyOrderStats(Request $request, int $tid): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($tid);
        $days = max(1, min(365, (int) $request->query('days', 30)));

        return response()->json(['data' => $this->service->dailyOrderCounts($tenant->getKey(), $days)]);
    }

    /** GET /api/v1/admin/tenants/{tid}/order-status-history */
    public function orderStatusHistory(Request $request, int $tid): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($tid);
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $page = \CMBcoreSeller\Modules\Orders\Models\OrderStatusHistory::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->with('order:id,order_number')
            ->orderByDesc('changed_at')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($page->items())->map(fn ($h) => [
                'order_id' => $h->order_id, 'order_number' => $h->order?->order_number,
                'from_status' => $h->from_status, 'to_status' => $h->to_status,
                'raw_status' => $h->raw_status, 'source' => $h->source,
                'changed_at' => optional($h->changed_at)->toIso8601String(),
            ])->all(),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    /** GET /api/v1/admin/tenants/{tid}/audit-logs — TOÀN BỘ log (không lọc admin.%), phân trang. */
    public function auditLogs(Request $request, int $tid): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($tid);
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $page = AuditLog::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'data' => collect($page->items())->map(fn (AuditLog $a) => [
                'id' => $a->id, 'action' => $a->action, 'user_id' => $a->user_id,
                'admin_user_id' => $a->admin_user_id,
                'changes' => $a->changes, 'ip' => $a->ip,
                'created_at' => optional($a->created_at)->toIso8601String(),
            ])->all(),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }
```

**Lưu ý implementer:** cần quan hệ `order()` trên `OrderStatusHistory` model nếu chưa có (kiểm tra file
trước khi thêm — nếu đã có `belongsTo(Order::class)` thì bỏ qua bước thêm).

- [ ] **Step 6: Thêm 3 route trong `routes.php`**

```php
        Route::get('tenants/{tid}/orders/daily-stats', [AdminTenantController::class, 'dailyOrderStats'])
            ->whereNumber('tid')->name('admin.tenants.orders.daily-stats');
        Route::get('tenants/{tid}/order-status-history', [AdminTenantController::class, 'orderStatusHistory'])
            ->whereNumber('tid')->name('admin.tenants.order-status-history');
        Route::get('tenants/{tid}/audit-logs', [AdminTenantController::class, 'auditLogs'])
            ->whereNumber('tid')->name('admin.tenants.audit-logs');
```

- [ ] **Step 7: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=AdminTenantDeepStatsTest`
Expected: PASS (4 test).

- [ ] **Step 8: Regression + quality gate**

Run: `cd app && php artisan test --filter=AdminTenant && vendor/bin/pint --test app/Modules/Admin/Services/AdminTenantService.php app/Modules/Admin/Http/Controllers/AdminTenantController.php && vendor/bin/phpstan analyse app/Modules/Admin/Services/AdminTenantService.php app/Modules/Admin/Http/Controllers/AdminTenantController.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/app/Modules/Admin/Services/AdminTenantService.php app/app/Modules/Admin/Http/Controllers/AdminTenantController.php app/app/Modules/Admin/Http/routes.php app/tests/Feature/Admin/AdminTenantDeepStatsTest.php
git commit -m "feat(admin): số lượng SKU + thống kê đơn theo ngày + lịch xử lý đơn + audit log đầy đủ cho tenant"
```

---

## Task 5: Frontend — hooks mới trong `admin.tsx`

**Files:**
- Modify: `app/resources/js/admin/lib/admin.tsx`

**Interfaces:**
- Consumes: mọi endpoint Task 2/3/4.
- Produces: `useAdminTenantAiCreditAdjust()`, `useAdminTenantAiUsageHistory` (field trong
  `AdminTenantDetail`, không cần hook riêng — dữ liệu đã nằm trong `useAdminTenant`),
  `useAdminTenantDailyOrderStats(tenantId, days)`, `useAdminTenantOrderStatusHistory(tenantId, page)`,
  `useAdminTenantAuditLogs(tenantId, page)`, `useAdminTenantLoginHistory(tenantId, page)` — Task 6 (trang
  chi tiết) gọi đúng tên + tham số các hook này.

- [ ] **Step 1: Mở rộng `AdminTenantDetail` + thêm interface mới** (chèn sau `export interface AdminTenantDetail extends AdminTenantSummary { ... }` hiện có, dòng ~102-110)

```ts
export interface AdminAiCreditSummary {
    enabled: boolean; unlimited: boolean; monthly_allowance: number;
    period_used: number; purchased_balance: number; available: number | null;
}

export interface AdminAiUsageHistory {
    all_time: number;
    by_month: Array<{ period_ym: number; count: number }>;
    by_feature: Array<{ feature: string; count: number }>;
}
```

Sửa `AdminTenantDetail` thêm 2 field:
```ts
export interface AdminTenantDetail extends AdminTenantSummary {
    channel_accounts: AdminChannelAccount[];
    ad_accounts: AdminAdAccount[];
    members: AdminMember[];
    recent_admin_actions: AdminAuditEntry[];
    invoices: AdminInvoice[];
    payments: AdminPayment[];
    vouchers_redeemed: AdminVoucherRedemptionRow[];
    sku_count: number;
    ai_credit: AdminAiCreditSummary;
    ai_usage_history: AdminAiUsageHistory;
}
```

- [ ] **Step 2: Thêm hook cộng/trừ AI credit** (đặt cạnh `useAdminChangePlan`)

```ts
export function useAdminTenantAiCreditAdjust() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; amount: number; reason: string }) => {
            const { data } = await api.post<{ data: AdminAiCreditSummary & { applied: number } }>(
                `/admin/tenants/${vars.tenantId}/ai-credit/adjust`,
                { amount: vars.amount, reason: vars.reason },
            );
            return data.data;
        },
        onSuccess: (_d, vars) => qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] }),
    });
}
```

- [ ] **Step 3: Thêm 3 hook đọc dữ liệu phân trang mới** (đặt cuối phần tenant hooks, trước "// SPEC 0023 — vouchers")

```ts
export function useAdminTenantDailyOrderStats(tenantId: number | null, days = 30) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', tenantId, 'daily-stats', days],
        enabled: tenantId != null,
        queryFn: async () => {
            const { data } = await api.get<{ data: Array<{ date: string; count: number; grand_total_sum: number }> }>(
                `/admin/tenants/${tenantId}/orders/daily-stats`, { params: { days } },
            );
            return data.data;
        },
    });
}

export interface AdminOrderStatusHistoryRow {
    order_id: number; order_number: string | null;
    from_status: string | null; to_status: string; raw_status: string | null;
    source: string; changed_at: string | null;
}

export function useAdminTenantOrderStatusHistory(tenantId: number | null, page = 1) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', tenantId, 'order-status-history', page],
        enabled: tenantId != null,
        queryFn: async () => {
            const { data } = await api.get<Paginated<AdminOrderStatusHistoryRow>>(
                `/admin/tenants/${tenantId}/order-status-history`, { params: { page } },
            );
            return data;
        },
        placeholderData: (p) => p,
    });
}

export interface AdminFullAuditEntry extends AdminAuditEntry { admin_user_id: number | null }

export function useAdminTenantAuditLogs(tenantId: number | null, page = 1) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', tenantId, 'audit-logs', page],
        enabled: tenantId != null,
        queryFn: async () => {
            const { data } = await api.get<Paginated<AdminFullAuditEntry>>(
                `/admin/tenants/${tenantId}/audit-logs`, { params: { page } },
            );
            return data;
        },
        placeholderData: (p) => p,
    });
}

export interface AdminLoginHistoryRow {
    user_id: number; name: string | null; email: string | null;
    ip_address: string | null; user_agent: string | null; logged_in_at: string;
}

export function useAdminTenantLoginHistory(tenantId: number | null, page = 1) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', tenantId, 'login-history', page],
        enabled: tenantId != null,
        queryFn: async () => {
            const { data } = await api.get<Paginated<AdminLoginHistoryRow>>(
                `/admin/tenants/${tenantId}/login-history`, { params: { page } },
            );
            return data;
        },
        placeholderData: (p) => p,
    });
}
```

- [ ] **Step 4: Kiểm tra kiểu — không có test JS trong dự án, chỉ chạy typecheck**

Run: `cd app && npm run typecheck`
Expected: PASS — 0 lỗi TypeScript (đặc biệt kiểm `Paginated<T>` generic đã dùng đúng, các field mới
khớp response backend Task 2-4).

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/admin/lib/admin.tsx
git commit -m "feat(admin-fe): hook mới cho hạn mức AI, thống kê đơn, audit log, lịch sử đăng nhập tenant"
```

---

## Task 6: Trang chi tiết tenant (`AdminTenantDetailPage`) — thay Drawer

**Files:**
- Create: `app/resources/js/admin/pages/tenants/AdminTenantDetailPage.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx` (thêm route)
- Modify: `app/resources/js/admin/pages/tenants/AdminTenantsPage.tsx` (đổi click row → navigate)

**Interfaces:**
- Consumes: mọi hook Task 5 + hook cũ (`useAdminTenant`, `useAdminChangePlan`, `useAdminSuspendTenant`,
  `useAdminReactivateTenant`, `useAdminDeleteChannel`).
- Produces: route `/admin/tenants/:id`.

- [ ] **Step 1: Đọc toàn bộ `AdminTenantDrawer.tsx` hiện có trước khi viết trang mới** — implementer PHẢI
  đọc file này đầy đủ (không chỉ đoạn đã trích trong brief) để copy chính xác phần UI cộng/trừ gói,
  suspend/reactivate, xoá kênh, feature override đã có — brief này KHÔNG lặp lại toàn bộ JSX đó, chỉ mô
  tả cấu trúc Tab cần có. Không được bỏ sót bất kỳ hành động nào đang có trong Drawer.

- [ ] **Step 2: Viết `AdminTenantDetailPage.tsx`**

Cấu trúc trang (dùng `useParams` lấy `:id` từ URL, `PageHeader` hiện có, `Tabs` antd):

```tsx
import { useParams, useNavigate } from 'react-router-dom';
import { useState } from 'react';
import { Alert, Button, Card, Descriptions, Space, Spin, Table, Tabs, Tag, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import {
    useAdminTenant, useAdminTenantAiCreditAdjust, useAdminTenantDailyOrderStats,
    useAdminTenantOrderStatusHistory, useAdminTenantAuditLogs, useAdminTenantLoginHistory,
} from '@admin/lib/admin';
import { formatDateTimeSeconds } from '@/lib/format';

/**
 * Trang chi tiết 1 tenant cho super-admin (design 2026-07-15) — thay AdminTenantDrawer.
 * Tab: Tổng quan (gói/suspend — copy nguyên từ Drawer cũ) · Kênh kết nối · Quảng cáo · Thành viên ·
 * Hạn mức AI (mới) · SKU & đơn hàng (mới) · Audit log đầy đủ (mới) · Lịch sử đăng nhập (mới).
 */
export function AdminTenantDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const tenantId = id ? Number(id) : null;
    const { data: t, isLoading } = useAdminTenant(tenantId);

    if (isLoading || !t) {
        return <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>;
    }

    return (
        <div>
            <PageHeader
                title={t.name}
                subtitle={t.slug}
                extra={<Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/admin/tenants')}>Quay lại danh sách</Button>}
            />
            <Tabs
                defaultActiveKey="overview"
                items={[
                    { key: 'overview', label: 'Tổng quan', children: <OverviewTab t={t} /> },
                    { key: 'channels', label: 'Kênh kết nối', children: <ChannelsTab t={t} /> },
                    { key: 'ads', label: 'Quảng cáo', children: <AdsTab t={t} /> },
                    { key: 'members', label: 'Thành viên', children: <MembersTab t={t} /> },
                    { key: 'ai', label: 'Hạn mức AI', children: <AiCreditTab tenantId={tenantId!} t={t} /> },
                    { key: 'orders', label: 'SKU & đơn hàng', children: <OrdersStatsTab tenantId={tenantId!} skuCount={t.sku_count} /> },
                    { key: 'audit', label: 'Audit log đầy đủ', children: <AuditLogTab tenantId={tenantId!} /> },
                    { key: 'logins', label: 'Lịch sử đăng nhập', children: <LoginHistoryTab tenantId={tenantId!} /> },
                ]}
            />
        </div>
    );
}
```

**Lưu ý implementer — đây là phần cần bạn tự viết đầy đủ, không phải chỉ khung sườn ở trên:**
- `OverviewTab`, `ChannelsTab`, `AdsTab`, `MembersTab` — port (chuyển) NGUYÊN VẸN nội dung + hành vi từ
  4 tab tương ứng trong `AdminTenantDrawer.tsx` (bấm suspend/reactivate/đổi gói/xoá kênh phải hoạt động
  y hệt, dùng đúng các hook cũ `useAdminChangePlan`, `useAdminSuspendTenant`, `useAdminReactivateTenant`,
  `useAdminDeleteChannel`, `useAdminPlans` đã import sẵn trong Drawer cũ).
- `AiCreditTab` (MỚI): hiện `Descriptions` cho `t.ai_credit` (enabled/unlimited/monthly_allowance/
  period_used/purchased_balance/available), 1 `Table` cho `t.ai_usage_history.by_month` (period_ym +
  count) và `by_feature`, 1 form nhỏ (input số `amount` dương/âm + textarea `reason` ≥10 ký tự) gọi
  `useAdminTenantAiCreditAdjust()` — theo đúng pattern `modal.confirm` + validate reason đã thấy ở
  `onSuspend` trong Drawer cũ.
- `OrdersStatsTab` (MỚI): hiện `skuCount` (1 số/Tag), gọi `useAdminTenantDailyOrderStats(tenantId, 30)`
  vẽ 1 `Table` đơn giản (date/count/grand_total_sum) — KHÔNG cần chart phức tạp (bảng là đủ, tránh thêm
  thư viện chart mới), và gọi `useAdminTenantOrderStatusHistory(tenantId, page)` với `Table` phân trang
  (order_number, from_status→to_status, source, changed_at).
- `AuditLogTab` (MỚI): `Table` phân trang dùng `useAdminTenantAuditLogs`, cột action/user_id hoặc
  admin_user_id/changes (hiện dạng JSON rút gọn trong `<pre>` hoặc `Descriptions`)/ip/created_at.
- `LoginHistoryTab` (MỚI): `Table` phân trang dùng `useAdminTenantLoginHistory`, cột name/email/
  ip_address/user_agent/logged_in_at.

- [ ] **Step 3: Thêm route trong `AdminApp.tsx`**

Thêm import:
```tsx
import { AdminTenantDetailPage } from './pages/tenants/AdminTenantDetailPage';
```
Thêm route NGAY TRƯỚC dòng `<Route path="tenants" element={<AdminTenantsPage />} />`:
```tsx
                    <Route path="tenants/:id" element={<AdminTenantDetailPage />} />
```
(đặt route cụ thể hơn — `tenants/:id` — TRƯỚC `tenants` không bắt buộc về thứ tự trong react-router v6
vì match theo path pattern, nhưng giữ thứ tự này cho dễ đọc.)

- [ ] **Step 4: Sửa `AdminTenantsPage.tsx` — đổi click row từ mở Drawer sang điều hướng trang**

Thêm import: `import { useNavigate } from 'react-router-dom';`. Trong component, thêm
`const navigate = useNavigate();`. Sửa dòng:
```tsx
                    onRow={(r) => ({ onClick: () => setOpenTenantId(r.id), style: { cursor: 'pointer' } })}
```
thành:
```tsx
                    onRow={(r) => ({ onClick: () => navigate(`/admin/tenants/${r.id}`), style: { cursor: 'pointer' } })}
```
Xoá `openTenantId` state (`useState<number | null>(null)`) và dòng
`<AdminTenantDrawer tenantId={openTenantId} onClose={() => setOpenTenantId(null)} />` cùng import
`AdminTenantDrawer` — trang list không còn dùng Drawer nữa.

**Kiểm tra trước khi xoá file `AdminTenantDrawer.tsx`:** grep toàn repo `AdminTenantDrawer` — nếu KHÔNG
còn nơi nào import (ngoài chính nó), xoá hẳn file (tránh code chết). Nếu còn dùng ở nơi khác, giữ file,
chỉ bỏ import ở `AdminTenantsPage.tsx`.

- [ ] **Step 5: Kiểm tra kiểu + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: PASS — 0 lỗi.

- [ ] **Step 6: Kiểm tra thủ công qua trình duyệt (nếu môi trường cho phép chạy `composer dev`)**

Vào `/admin/tenants`, bấm 1 dòng tenant → xác nhận điều hướng sang `/admin/tenants/{id}` (không phải mở
Drawer), đủ 8 tab, các hành động cũ (suspend/đổi gói/xoá kênh) vẫn hoạt động, 4 tab mới hiển thị đúng dữ
liệu. Nếu môi trường không chạy được (không có DB/docker), ghi rõ trong report là chưa kiểm tra được
bằng mắt — không tự nhận đã test UI khi chưa thực sự chạy.

- [ ] **Step 7: Commit**

```bash
git add app/resources/js/admin/pages/tenants/AdminTenantDetailPage.tsx app/resources/js/admin/AdminApp.tsx app/resources/js/admin/pages/tenants/AdminTenantsPage.tsx
git commit -m "feat(admin-fe): trang chi tiết tenant riêng thay Drawer, thêm tab hạn mức AI/SKU-đơn hàng/audit log/lịch sử đăng nhập"
```

(Nếu Step 4 xác nhận `AdminTenantDrawer.tsx` không còn dùng ở đâu, thêm `git rm
app/resources/js/admin/pages/tenants/AdminTenantDrawer.tsx` vào cùng commit này.)

---

## Self-Review

**1. Spec coverage:**
- §A (2 bug AI) → Task 1. ✓
- §B1 (hạn mức AI: hiện tại + lịch sử tháng + cộng/trừ + lịch sử admin) → Task 2 (cộng/trừ dùng
  `AuditLog` sẵn có làm lịch sử, không tạo bảng riêng — đúng spec). ✓
- §B2 (SKU count) → Task 4. ✓
- §B3 (danh sách kênh/page) → đã có sẵn, KHÔNG cần task riêng — chỉ hiển thị lại ở Task 6. ✓
- §B4 (đơn theo ngày) → Task 4. ✓
- §B5 (lịch xử lý đơn) → Task 4. ✓
- §B6 (audit log đầy đủ) → Task 4. ✓
- §B7 (lịch sử đăng nhập) → Task 3. ✓
- Trang riêng thay Drawer → Task 6. ✓

**2. Placeholder scan:** không còn TBD/TODO. Task 6 có 2 chỗ "Lưu ý implementer" mô tả PHẦN VIỆC CẦN TỰ
LÀM (port JSX cũ + viết 4 tab mới) thay vì code đầy đủ — đây là quyết định có chủ đích (JSX Drawer cũ dài
~300+ dòng, chép nguyên vào brief sẽ phình plan không cần thiết); đã ghi rõ TIÊU CHÍ chấp nhận (không bỏ
sót hành động nào, dùng đúng hook cũ) thay vì mơ hồ.

**3. Type consistency:** `AdminAiCreditSummary`/`AdminAiUsageHistory` (Task 5) khớp đúng shape
`AiCreditService::summary()`/`AiUsageReportService::breakdownForTenant()` (Task 2). `AdminOrderStatusHistoryRow`/
`AdminFullAuditEntry`/`AdminLoginHistoryRow` (Task 5) khớp đúng field JSON trả về từ 3 action tương ứng
(Task 3/4). `deduct()`/`grantPurchase()` cùng chữ ký `(int $tenantId, int $amount): int` xuyên Task 1→2.
