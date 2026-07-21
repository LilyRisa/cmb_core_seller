# Admin Redesign — Phase 1: Tổng quan/Báo cáo Overview Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the placeholder `AdminDashboardPage` greeting card with a real overview page —
tenant growth, revenue, ops/CS, and AI usage — backed by one new aggregation endpoint.

**Architecture:** One new read-only backend endpoint (`GET /api/v1/admin/dashboard/overview`)
served by a new `AdminDashboardOverviewService` (Admin module) that reads directly from Billing,
Tenancy, and Support module models — matching the existing precedent in
`AdminTenantController.php`, which already imports models from Billing/Channels/Inventory/
Marketing/Orders directly. AI-usage numbers go through the existing `AiUsageReporter` contract
(Admin depends on the contract, not the concrete Billing service — per that contract's own
doc-comment rule), extended with two new read methods. Frontend: one new TanStack Query hook +
a `AdminDashboardPage.tsx` rebuild using `recharts` (already an installed dependency — used
elsewhere at `resources/js/pages/DashboardPage.tsx`, no new package needed).

**Tech Stack:** Laravel 11 (Eloquent, PHPUnit), React 18, TanStack Query, Ant Design 5, `recharts`.

## Global Constraints

- Money fields are integer VND, no floats (per `CLAUDE.md` API conventions).
- Dates bucketed using `app_display_tz()` (`app/app/Support/helpers.php`) so "this month"/"last
  30 days" match the UTC+7 business day the admin sees, not raw UTC boundaries — see
  [[timezone-architecture-utc-store-hcm-display]].
- Response envelope is `{ "data": ... }` (standard convention, normalized in `bootstrap/app.php`
  — the controller only needs to return `response()->json(['data' => ...])`).
- Cross-tenant admin queries must `withoutGlobalScope(TenantScope::class)` on any model using the
  `BelongsToTenant` trait (`Subscription`, `Invoice`, `AiUsageCounter`, `SupportConversation`) —
  otherwise the query silently returns zero rows (global tenant scope has no current tenant in the
  admin guard). `Tenant` and `Plan` and `Voucher` are not tenant-scoped, no `withoutGlobalScope`
  needed on those.
- Run all PHP commands from `app/` (per `CLAUDE.md`).
- **AI-usage-block correction from the design spec:** the spec's §6.3 only sketched
  `topTenantsByUsageThisMonth`; this plan also adds `totalCallsThisMonth()` to the same contract,
  because summing only the top-5 tenants would under-count the system-wide total whenever more
  than 5 tenants have AI usage this month — the spec flagged this exact area as an
  "implementation-time" decision (§9), this is that decision.

---

### Task 1: Backend — overview aggregation endpoint

**Files:**
- Modify: `app/app/Modules/Billing/Contracts/AiUsageReporter.php` (add 2 methods)
- Modify: `app/app/Modules/Billing/Services/AiUsageReportService.php` (implement them)
- Create: `app/app/Modules/Admin/Services/AdminDashboardOverviewService.php`
- Create: `app/app/Modules/Admin/Http/Controllers/AdminDashboardController.php`
- Modify: `app/app/Modules/Admin/Http/routes.php` (add 1 route)
- Create: `app/tests/Feature/Admin/AdminDashboardOverviewTest.php`

**Interfaces:**
- Consumes: `CMBcoreSeller\Modules\Billing\Models\{Plan,Subscription,Invoice,Voucher,AiUsageCounter}`,
  `CMBcoreSeller\Modules\Support\Models\SupportConversation`,
  `CMBcoreSeller\Modules\Tenancy\Models\{Tenant,AuditLog}`,
  `CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope`, `app_display_tz()` helper (autoloaded,
  no import needed), the existing `AiUsageReporter` contract binding
  (`BillingServiceProvider.php:42` — `bind(AiUsageReporter::class, AiUsageReportService::class)`,
  unchanged by this task).
- Produces (for Task 2 / frontend to consume): `GET /api/v1/admin/dashboard/overview` returning
  ```jsonc
  {
    "data": {
      "tenants": {
        "active_total": 0,
        "by_plan": [{ "plan_code": "starter", "plan_name": "Starter", "count": 0 }],
        "new_by_day": [{ "date": "2026-07-01", "count": 0 }],
        "trial_ending_soon": [{ "tenant_id": 0, "tenant_name": "", "trial_ends_at": "2026-07-25T00:00:00+00:00" }]
      },
      "revenue": {
        "mrr_estimate": 0,
        "invoices_this_month": { "paid_count": 0, "paid_total": 0, "pending_count": 0, "pending_total": 0 },
        "revenue_by_month": [{ "period_ym": 202607, "total": 0 }],
        "active_vouchers": 0
      },
      "support": {
        "open_count": 0,
        "avg_resolution_hours": 0,
        "recent_audit_log": [{ "action": "admin.tenant.suspend", "actor": "Admin #1", "at": "2026-07-21T03:00:00+00:00" }]
      },
      "ai_usage": { "calls_this_month": 0, "top_tenants": [{ "tenant_id": 0, "tenant_name": "", "calls_this_month": 0 }] }
    }
  }
  ```

- [ ] **Step 1: Write the failing feature test**

Create `app/tests/Feature/Admin/AdminDashboardOverviewTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_aggregates_tenants_revenue_support_and_ai_usage(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        $starter = Plan::query()->create([
            'code' => 'starter', 'name' => 'Starter', 'is_active' => true, 'sort_order' => 1,
            'price_monthly' => 190_000, 'price_yearly' => 1_900_000, 'currency' => 'VND',
            'trial_days' => 14, 'limits' => [], 'features' => [],
        ]);
        $pro = Plan::query()->create([
            'code' => 'pro', 'name' => 'Pro', 'is_active' => true, 'sort_order' => 2,
            'price_monthly' => 270_000, 'price_yearly' => 2_700_000, 'currency' => 'VND',
            'trial_days' => 14, 'limits' => [], 'features' => [],
        ]);

        $t1 = Tenant::factory()->create(['status' => 'active']);
        $t2 = Tenant::factory()->create(['status' => 'active']);
        $t3 = Tenant::factory()->create(['status' => 'active']);

        // t1: active Starter (monthly) ⇒ MRR += 190_000.
        $sub1 = Subscription::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t1->id, 'plan_id' => $starter->id, 'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        // t2: active Pro (yearly) ⇒ MRR += 2_700_000/12 = 225_000.
        $sub2 = Subscription::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t2->id, 'plan_id' => $pro->id, 'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_YEARLY,
            'current_period_start' => now(), 'current_period_end' => now()->addYear(),
        ]);
        // t3: trialing Starter, trial ends in 3 days ⇒ trial_ending_soon.
        Subscription::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t3->id, 'plan_id' => $starter->id, 'status' => Subscription::STATUS_TRIALING,
            'billing_cycle' => Subscription::CYCLE_TRIAL, 'trial_ends_at' => now()->addDays(3),
            'current_period_start' => now(), 'current_period_end' => now()->addDays(14),
        ]);

        // Hoá đơn tháng này: 1 paid 190_000, 1 pending 270_000.
        Invoice::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t1->id, 'subscription_id' => $sub1->id, 'code' => 'INV-TEST-0001',
            'status' => Invoice::STATUS_PAID, 'period_start' => now(), 'period_end' => now()->addMonth(),
            'subtotal' => 190_000, 'tax' => 0, 'total' => 190_000, 'currency' => 'VND',
            'due_at' => now(), 'paid_at' => now(),
        ]);
        Invoice::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t2->id, 'subscription_id' => $sub2->id, 'code' => 'INV-TEST-0002',
            'status' => Invoice::STATUS_PENDING, 'period_start' => now(), 'period_end' => now()->addMonth(),
            'subtotal' => 270_000, 'tax' => 0, 'total' => 270_000, 'currency' => 'VND',
            'due_at' => now()->addDays(3),
        ]);

        Voucher::query()->create([
            'code' => 'TEST10', 'name' => 'Test 10%', 'kind' => Voucher::KIND_PERCENT, 'value' => 10,
            'max_redemptions' => -1, 'redemption_count' => 0, 'is_active' => true,
        ]);

        SupportConversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t1->id, 'status' => SupportConversation::STATUS_OPEN,
        ]);
        $closedConv = SupportConversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t2->id, 'status' => SupportConversation::STATUS_CLOSED, 'closed_at' => now(),
        ]);
        // created_at không nằm trong $fillable của SupportConversation ⇒ set qua forceFill.
        $closedConv->forceFill(['created_at' => now()->subHours(2)])->save();

        $ym = (int) now()->format('Ym');
        AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t1->id, 'user_id' => 0, 'period_ym' => $ym, 'feature' => 'messaging', 'count' => 3,
        ]);
        AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $t2->id, 'user_id' => 0, 'period_ym' => $ym, 'feature' => 'messaging', 'count' => 5,
        ]);

        $resp = $this->getJson('/api/v1/admin/dashboard/overview')->assertOk();

        $resp->assertJsonPath('data.tenants.active_total', 3);
        $resp->assertJsonPath('data.revenue.mrr_estimate', 190_000 + 225_000);
        $resp->assertJsonPath('data.revenue.invoices_this_month.paid_count', 1);
        $resp->assertJsonPath('data.revenue.invoices_this_month.paid_total', 190_000);
        $resp->assertJsonPath('data.revenue.invoices_this_month.pending_count', 1);
        $resp->assertJsonPath('data.revenue.invoices_this_month.pending_total', 270_000);
        $resp->assertJsonPath('data.revenue.active_vouchers', 1);
        $resp->assertJsonPath('data.support.open_count', 1);
        $resp->assertJsonPath('data.support.avg_resolution_hours', 2.0);
        $resp->assertJsonPath('data.ai_usage.calls_this_month', 8);
        $resp->assertJsonPath('data.ai_usage.top_tenants.0.tenant_id', $t2->id);
        $resp->assertJsonPath('data.ai_usage.top_tenants.0.calls_this_month', 5);

        $byPlan = collect($resp->json('data.tenants.by_plan'));
        $this->assertSame(1, $byPlan->firstWhere('plan_code', 'starter')['count']);
        $this->assertSame(1, $byPlan->firstWhere('plan_code', 'pro')['count']);

        $trialSoon = collect($resp->json('data.tenants.trial_ending_soon'));
        $this->assertTrue($trialSoon->contains('tenant_id', $t3->id));
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

```bash
php artisan test tests/Feature/Admin/AdminDashboardOverviewTest.php
```
Expected: FAIL — route `/api/v1/admin/dashboard/overview` doesn't exist yet (404 / route not
found), or class-not-found if run before Step 3.

- [ ] **Step 3: Extend the `AiUsageReporter` contract**

In `app/app/Modules/Billing/Contracts/AiUsageReporter.php`, add after the existing
`breakdownForTenant` method (before the closing `}`):

```php
    /**
     * Top N tenant theo lượt gọi AI tháng hiện tại (nhiều→ít). Dùng cho dashboard admin.
     *
     * @return list<array{tenant_id:int, calls_this_month:int}>
     */
    public function topTenantsByUsageThisMonth(int $limit): array;

    /** Tổng lượt gọi AI toàn hệ thống (mọi tenant) tháng hiện tại. Dùng cho dashboard admin. */
    public function totalCallsThisMonth(): int;
```

- [ ] **Step 4: Implement the two new methods**

In `app/app/Modules/Billing/Services/AiUsageReportService.php`, add after `breakdownForTenant`
(before the closing `}`), and add the `AiUsageCounter` model import already present in this file
(check `use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;` is already at the top — it is):

```php
    public function topTenantsByUsageThisMonth(int $limit): array
    {
        $ym = (int) now()->format('Ym');
        $rows = AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->selectRaw('tenant_id, SUM(count) as calls_this_month')
            ->where('period_ym', $ym)
            ->groupBy('tenant_id')
            ->orderByDesc('calls_this_month')
            ->limit($limit)
            ->toBase()
            ->get();

        return $rows->map(fn ($r) => [
            'tenant_id' => (int) $r->tenant_id,
            'calls_this_month' => (int) $r->calls_this_month,
        ])->all();
    }

    public function totalCallsThisMonth(): int
    {
        $ym = (int) now()->format('Ym');

        return (int) AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->where('period_ym', $ym)
            ->sum('count');
    }
```

- [ ] **Step 5: Write `AdminDashboardOverviewService`**

Create `app/app/Modules/Admin/Services/AdminDashboardOverviewService.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Services;

use CMBcoreSeller\Modules\Billing\Contracts\AiUsageReporter;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;

/**
 * Số liệu tổng hợp cho trang "Tổng quan" admin — docs/superpowers/specs/
 * 2026-07-21-admin-panel-ux-redesign-design.md §6. Đọc trực tiếp model của Billing/Support/Tenancy
 * (giống pattern sẵn có ở AdminTenantController), riêng lượt gọi AI đi qua contract
 * `AiUsageReporter` (Admin không chạm bảng ai_usage_counters trực tiếp, theo doc-comment của
 * contract đó).
 */
class AdminDashboardOverviewService
{
    public function __construct(private AiUsageReporter $aiUsage) {}

    public function overview(): array
    {
        return [
            'tenants' => $this->tenants(),
            'revenue' => $this->revenue(),
            'support' => $this->support(),
            'ai_usage' => $this->aiUsageBlock(),
        ];
    }

    private function tenants(): array
    {
        $tz = app_display_tz();

        $activeTotal = Tenant::query()->where('status', 'active')->count();

        $byPlan = Subscription::withoutGlobalScope(TenantScope::class)
            ->whereIn('subscriptions.status', Subscription::ALIVE_STATUSES)
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->selectRaw('plans.code as plan_code, plans.name as plan_name, COUNT(*) as count')
            ->groupBy('plans.code', 'plans.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['plan_code' => $r->plan_code, 'plan_name' => $r->plan_name, 'count' => (int) $r->count])
            ->all();

        $from = Carbon::now($tz)->subDays(29)->startOfDay();
        $byDay = [];
        for ($i = 0; $i < 30; $i++) {
            $byDay[$from->clone()->addDays($i)->format('Y-m-d')] = 0;
        }
        Tenant::query()
            ->where('created_at', '>=', $from->clone()->setTimezone('UTC'))
            ->get(['created_at'])
            ->each(function ($t) use (&$byDay, $tz) {
                $d = $t->created_at->copy()->setTimezone($tz)->format('Y-m-d');
                if (isset($byDay[$d])) {
                    $byDay[$d]++;
                }
            });
        $newByDay = collect($byDay)->map(fn ($count, $date) => ['date' => $date, 'count' => $count])->values()->all();

        $trialEndingSoon = Subscription::withoutGlobalScope(TenantScope::class)
            ->with('tenant:id,name')
            ->where('status', Subscription::STATUS_TRIALING)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [Carbon::now(), Carbon::now()->addDays(7)])
            ->orderBy('trial_ends_at')
            ->get()
            ->map(fn (Subscription $s) => [
                'tenant_id' => $s->tenant_id,
                'tenant_name' => $s->tenant?->name ?? '—',
                'trial_ends_at' => $s->trial_ends_at?->toIso8601String(),
            ])
            ->all();

        return [
            'active_total' => $activeTotal,
            'by_plan' => $byPlan,
            'new_by_day' => $newByDay,
            'trial_ending_soon' => $trialEndingSoon,
        ];
    }

    private function revenue(): array
    {
        $tz = app_display_tz();

        $mrr = (int) Subscription::withoutGlobalScope(TenantScope::class)
            ->whereIn('subscriptions.status', Subscription::ALIVE_STATUSES)
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->get(['subscriptions.billing_cycle', 'plans.price_monthly', 'plans.price_yearly'])
            ->sum(fn ($r) => $r->billing_cycle === Subscription::CYCLE_YEARLY
                ? intdiv((int) $r->price_yearly, 12)
                : (int) $r->price_monthly);

        $monthStart = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');
        $monthEnd = Carbon::now($tz)->endOfMonth()->setTimezone('UTC');
        $byStatus = Invoice::withoutGlobalScope(TenantScope::class)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(total), 0) as total_sum')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $paid = $byStatus->get(Invoice::STATUS_PAID);
        $pending = $byStatus->get(Invoice::STATUS_PENDING);

        $revenueByMonth = Invoice::withoutGlobalScope(TenantScope::class)
            ->where('status', Invoice::STATUS_PAID)
            ->where('paid_at', '>=', Carbon::now($tz)->subMonths(11)->startOfMonth()->setTimezone('UTC'))
            ->get(['paid_at', 'total'])
            ->groupBy(fn ($inv) => (int) $inv->paid_at->copy()->setTimezone($tz)->format('Ym'))
            ->map(fn ($group, $ym) => ['period_ym' => (int) $ym, 'total' => (int) $group->sum('total')])
            ->sortBy('period_ym')
            ->values()
            ->all();

        $activeVouchers = Voucher::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', Carbon::now()))
            ->count();

        return [
            'mrr_estimate' => $mrr,
            'invoices_this_month' => [
                'paid_count' => (int) ($paid->cnt ?? 0),
                'paid_total' => (int) ($paid->total_sum ?? 0),
                'pending_count' => (int) ($pending->cnt ?? 0),
                'pending_total' => (int) ($pending->total_sum ?? 0),
            ],
            'revenue_by_month' => $revenueByMonth,
            'active_vouchers' => $activeVouchers,
        ];
    }

    private function support(): array
    {
        $openCount = SupportConversation::withoutGlobalScope(TenantScope::class)
            ->where('status', SupportConversation::STATUS_OPEN)
            ->count();

        $avgResolutionHours = (float) (SupportConversation::withoutGlobalScope(TenantScope::class)
            ->where('status', SupportConversation::STATUS_CLOSED)
            ->whereNotNull('closed_at')
            ->get(['created_at', 'closed_at'])
            ->avg(fn ($c) => $c->created_at->diffInMinutes($c->closed_at) / 60) ?? 0);

        $recentAuditLog = AuditLog::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'action', 'admin_user_id', 'user_id', 'created_at'])
            ->map(fn (AuditLog $a) => [
                'action' => $a->action,
                'actor' => $a->admin_user_id ? "Admin #{$a->admin_user_id}" : ($a->user_id ? "User #{$a->user_id}" : '—'),
                'at' => $a->created_at?->toIso8601String(),
            ])
            ->all();

        return [
            'open_count' => $openCount,
            'avg_resolution_hours' => round($avgResolutionHours, 1),
            'recent_audit_log' => $recentAuditLog,
        ];
    }

    private function aiUsageBlock(): array
    {
        $totalCalls = $this->aiUsage->totalCallsThisMonth();
        $topTenants = $this->aiUsage->topTenantsByUsageThisMonth(5);
        $tenantNames = Tenant::query()
            ->whereIn('id', array_column($topTenants, 'tenant_id'))
            ->pluck('name', 'id');

        return [
            'calls_this_month' => $totalCalls,
            'top_tenants' => array_map(fn ($r) => [
                'tenant_id' => $r['tenant_id'],
                'tenant_name' => $tenantNames[$r['tenant_id']] ?? '—',
                'calls_this_month' => $r['calls_this_month'],
            ], $topTenants),
        ];
    }
}
```

- [ ] **Step 6: Write the thin controller**

Create `app/app/Modules/Admin/Http/Controllers/AdminDashboardController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Modules\Admin\Services\AdminDashboardOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/** GET /api/v1/admin/dashboard/overview — số liệu trang "Tổng quan" admin (SPEC 2026-07-21). */
class AdminDashboardController extends Controller
{
    public function overview(AdminDashboardOverviewService $service): JsonResponse
    {
        return response()->json(['data' => $service->overview()]);
    }
}
```

- [ ] **Step 7: Register the route**

In `app/app/Modules/Admin/Http/routes.php`, add the import near the other `use
CMBcoreSeller\Modules\Admin\Http\Controllers\...;` lines (alphabetical, after `AdminBroadcastController`):

```php
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminDashboardController;
```

Then add the route inside the existing `Route::middleware(['web', 'auth:admin_web',
'throttle:60,1'])->prefix('api/v1/admin')->group(function () { ... })` block, right after the
`// --- Tenants (SPEC 0020) ---` section's routes end and before `// --- Tenant operations
(SPEC 0023) ---` (i.e. insert as its own new section right after the tenants block, around line 73
of the current file):

```php
        // --- Dashboard overview (redesign 2026-07-21) ---
        Route::get('dashboard/overview', [AdminDashboardController::class, 'overview'])
            ->name('admin.dashboard.overview');
```

- [ ] **Step 8: Run the test and confirm it passes**

```bash
php artisan test tests/Feature/Admin/AdminDashboardOverviewTest.php
```
Expected: PASS (1 test, all assertions green).

- [ ] **Step 9: Static analysis and format check**

```bash
vendor/bin/pint --test app/Modules/Billing/Contracts/AiUsageReporter.php app/Modules/Billing/Services/AiUsageReportService.php app/Modules/Admin/Services/AdminDashboardOverviewService.php app/Modules/Admin/Http/Controllers/AdminDashboardController.php app/Modules/Admin/Http/routes.php tests/Feature/Admin/AdminDashboardOverviewTest.php
vendor/bin/phpstan analyse app/Modules/Billing/Contracts/AiUsageReporter.php app/Modules/Billing/Services/AiUsageReportService.php app/Modules/Admin/Services/AdminDashboardOverviewService.php app/Modules/Admin/Http/Controllers/AdminDashboardController.php
```
Expected: both succeed with no new errors (if Pint reports formatting diffs, run
`vendor/bin/pint` without `--test` to auto-fix, then re-run `--test`).

- [ ] **Step 10: Full backend regression check**

```bash
php artisan test --filter=Billing
php artisan test --filter=Admin
```
Expected: no new failures beyond the pre-existing baseline (see [[test-verify-baseline]] — some
Billing tests already fail on `main` for unrelated stale-price reasons; confirm the failure list
is unchanged from before this task, not that everything is green).

- [ ] **Step 11: Commit**

```bash
git add app/Modules/Billing/Contracts/AiUsageReporter.php app/Modules/Billing/Services/AiUsageReportService.php app/Modules/Admin/Services/AdminDashboardOverviewService.php app/Modules/Admin/Http/Controllers/AdminDashboardController.php app/Modules/Admin/Http/routes.php tests/Feature/Admin/AdminDashboardOverviewTest.php
git commit -m "feat(admin): endpoint tổng hợp số liệu Tổng quan (tenant/doanh thu/CSKH/AI)"
```

---

### Task 2: Frontend — rebuild `AdminDashboardPage`

**Files:**
- Create: `app/resources/js/admin/lib/adminDashboard.tsx`
- Modify: `app/resources/js/admin/pages/AdminDashboardPage.tsx` (full rewrite — currently 29 lines,
  a placeholder greeting card, see current content in the file itself)

**Interfaces:**
- Consumes: `GET /api/v1/admin/dashboard/overview` (Task 1's exact response shape above), `@/lib/api`
  (`api` axios instance — same import convention as `admin/lib/admin.tsx`), `recharts`
  (`ResponsiveContainer`, `BarChart`, `Bar`, `LineChart`, `Line`, `XAxis`, `YAxis`, `CartesianGrid`,
  `Tooltip`, already a project dependency, used at `resources/js/pages/DashboardPage.tsx`).
- Produces: nothing consumed by later phases — this is a leaf page.

- [ ] **Step 1: Write the data hook**

Create `app/resources/js/admin/lib/adminDashboard.tsx`:

```tsx
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface AdminOverview {
    tenants: {
        active_total: number;
        by_plan: Array<{ plan_code: string; plan_name: string; count: number }>;
        new_by_day: Array<{ date: string; count: number }>;
        trial_ending_soon: Array<{ tenant_id: number; tenant_name: string; trial_ends_at: string }>;
    };
    revenue: {
        mrr_estimate: number;
        invoices_this_month: { paid_count: number; paid_total: number; pending_count: number; pending_total: number };
        revenue_by_month: Array<{ period_ym: number; total: number }>;
        active_vouchers: number;
    };
    support: {
        open_count: number;
        avg_resolution_hours: number;
        recent_audit_log: Array<{ action: string; actor: string; at: string }>;
    };
    ai_usage: {
        calls_this_month: number;
        top_tenants: Array<{ tenant_id: number; tenant_name: string; calls_this_month: number }>;
    };
}

export function useAdminOverview() {
    return useQuery({
        queryKey: ['admin', 'dashboard', 'overview'],
        queryFn: async () => {
            const { data } = await api.get<{ data: AdminOverview }>('/admin/dashboard/overview');
            return data.data;
        },
        staleTime: 60_000,
    });
}
```

- [ ] **Step 2: Rewrite the page**

Replace the full content of `app/resources/js/admin/pages/AdminDashboardPage.tsx`:

```tsx
// Spec 2026-07-21 (redesign) — trang "Tổng quan" thật, thay placeholder chào mừng cũ.
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §6.

import { Alert, Card, Col, Row, Statistic, Table, Tag, Typography } from 'antd';
import {
    ShopOutlined, DollarOutlined, SolutionOutlined, ApiOutlined,
} from '@ant-design/icons';
import {
    Bar, BarChart, CartesianGrid, Line, LineChart, ResponsiveContainer, Tooltip as ReTooltip, XAxis, YAxis,
} from 'recharts';
import { Link } from 'react-router-dom';
import { useAdminOverview } from '../lib/adminDashboard';
import { useAdminMe } from '../lib/adminAuth';

const fmtVnd = (n: number) => `${n.toLocaleString('vi-VN')} đ`;

export function AdminDashboardPage() {
    const { data: me } = useAdminMe();
    const { data, isLoading, isError } = useAdminOverview();

    return (
        <div>
            <Typography.Title level={4} style={{ marginTop: 0 }}>Xin chào, {me?.name}</Typography.Title>

            {isError && (
                <Alert type="error" showIcon style={{ marginBottom: 16 }} message="Không tải được số liệu tổng quan." />
            )}

            <Row gutter={16} style={{ marginBottom: 16 }}>
                <Col span={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Tenant hoạt động" value={data?.tenants.active_total ?? 0} prefix={<ShopOutlined />} />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card loading={isLoading}>
                        <Statistic title="MRR ước tính" value={data?.revenue.mrr_estimate ?? 0} formatter={(v) => fmtVnd(Number(v))} prefix={<DollarOutlined />} />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Yêu cầu CSKH đang mở" value={data?.support.open_count ?? 0} prefix={<SolutionOutlined />} />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Lượt gọi AI tháng này" value={data?.ai_usage.calls_this_month ?? 0} prefix={<ApiOutlined />} />
                    </Card>
                </Col>
            </Row>

            <Row gutter={16} style={{ marginBottom: 16 }}>
                <Col span={12}>
                    <Card title="Tenant mới (30 ngày)" loading={isLoading}>
                        <div style={{ height: 220 }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={data?.tenants.new_by_day ?? []}>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                    <XAxis dataKey="date" tick={{ fontSize: 10 }} interval={4} />
                                    <YAxis allowDecimals={false} width={30} />
                                    <ReTooltip />
                                    <Bar dataKey="count" fill="#2563EB" radius={[4, 4, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </Card>
                </Col>
                <Col span={12}>
                    <Card title="Phân bố theo gói" loading={isLoading}>
                        <Table
                            size="small" rowKey="plan_code" pagination={false}
                            dataSource={data?.tenants.by_plan ?? []}
                            columns={[
                                { title: 'Gói', dataIndex: 'plan_name' },
                                { title: 'Số tenant', dataIndex: 'count', width: 100 },
                            ]}
                        />
                        {(data?.tenants.trial_ending_soon.length ?? 0) > 0 && (
                            <>
                                <Typography.Text strong style={{ display: 'block', marginTop: 16 }}>
                                    Sắp hết trial (7 ngày tới)
                                </Typography.Text>
                                <Table
                                    size="small" rowKey="tenant_id" pagination={false} style={{ marginTop: 8 }}
                                    dataSource={data?.tenants.trial_ending_soon ?? []}
                                    columns={[
                                        {
                                            title: 'Tenant', dataIndex: 'tenant_name',
                                            render: (v: string, r) => <Link to={`/admin/tenants/${r.tenant_id}`}>{v}</Link>,
                                        },
                                        {
                                            title: 'Hết hạn', dataIndex: 'trial_ends_at',
                                            render: (v: string) => new Date(v).toLocaleDateString('vi-VN'),
                                        },
                                    ]}
                                />
                            </>
                        )}
                    </Card>
                </Col>
            </Row>

            <Row gutter={16} style={{ marginBottom: 16 }}>
                <Col span={12}>
                    <Card title="Doanh thu 12 tháng (đã thu)" loading={isLoading}>
                        <div style={{ height: 220 }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={data?.revenue.revenue_by_month ?? []}>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                    <XAxis dataKey="period_ym" tick={{ fontSize: 10 }} />
                                    <YAxis width={50} tickFormatter={(v: number) => `${(v / 1_000_000).toFixed(0)}tr`} />
                                    <ReTooltip formatter={(v: number) => fmtVnd(v)} />
                                    <Line type="monotone" dataKey="total" stroke="#10B981" strokeWidth={2} dot={false} />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                        <Typography.Paragraph type="secondary" style={{ marginTop: 8, marginBottom: 0 }}>
                            Hoá đơn tháng này: {data?.revenue.invoices_this_month.paid_count ?? 0} đã thu
                            ({fmtVnd(data?.revenue.invoices_this_month.paid_total ?? 0)}) ·{' '}
                            {data?.revenue.invoices_this_month.pending_count ?? 0} chờ thu
                            ({fmtVnd(data?.revenue.invoices_this_month.pending_total ?? 0)}) ·{' '}
                            {data?.revenue.active_vouchers ?? 0} voucher đang hoạt động.
                        </Typography.Paragraph>
                    </Card>
                </Col>
                <Col span={12}>
                    <Card title="Hoạt động gần đây (audit log)" loading={isLoading}>
                        <Table
                            size="small" rowKey={(r) => `${r.action}-${r.at}`} pagination={false}
                            dataSource={data?.support.recent_audit_log ?? []}
                            columns={[
                                { title: 'Hành động', dataIndex: 'action', render: (v: string) => <Tag>{v}</Tag> },
                                { title: 'Người thực hiện', dataIndex: 'actor', width: 120 },
                                {
                                    title: 'Lúc', dataIndex: 'at', width: 140,
                                    render: (v: string) => new Date(v).toLocaleString('vi-VN'),
                                },
                            ]}
                        />
                        <Typography.Paragraph type="secondary" style={{ marginTop: 8, marginBottom: 0 }}>
                            Thời gian xử lý CSKH trung bình: {data?.support.avg_resolution_hours ?? 0} giờ.
                        </Typography.Paragraph>
                    </Card>
                </Col>
            </Row>

            <Card title="Top 5 tenant dùng AI nhiều nhất (tháng này)" loading={isLoading}>
                <Table
                    size="small" rowKey="tenant_id" pagination={false}
                    dataSource={data?.ai_usage.top_tenants ?? []}
                    columns={[
                        {
                            title: 'Tenant', dataIndex: 'tenant_name',
                            render: (v: string, r) => <Link to={`/admin/tenants/${r.tenant_id}`}>{v}</Link>,
                        },
                        { title: 'Lượt gọi', dataIndex: 'calls_this_month', width: 120 },
                    ]}
                />
            </Card>
        </div>
    );
}
```

- [ ] **Step 3: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds.

- [ ] **Step 4: Manual browser verification**

With the backend from Task 1 deployed/running locally (dev stack per `CLAUDE.md`), log into
`/admin` and confirm:
1. The page loads without the old "Dùng menu bên trái để quản lý..." placeholder text.
2. All 4 top stat cards show numbers (0 is fine on an empty dev DB, but they must render — not
   spinner-forever or crash).
3. Both charts render (bar chart may be flat/empty on a fresh dev DB — acceptable; it must not
   throw a JS error in the browser console).
4. If any tenant in the dev DB has an active trial (`Subscription.status=trialing`,
   `trial_ends_at` within 7 days), it appears in the "Sắp hết trial" list and clicking its name
   navigates to `/admin/tenants/<id>`.
5. Open browser devtools console — confirm zero errors/warnings from this page.

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/admin/lib/adminDashboard.tsx app/resources/js/admin/pages/AdminDashboardPage.tsx
git commit -m "feat(admin): dựng trang Tổng quan thật (tenant/doanh thu/CSKH/AI usage)"
```

---

## Phase 1 self-review checklist

- Every field in Task 1's documented JSON response shape is populated by
  `AdminDashboardOverviewService` — cross-check against the interface block above if unsure.
- `AiUsageReporter`'s 2 new methods are consumed only through the interface in
  `AdminDashboardOverviewService` (constructor-injected), never `AiUsageReportService` directly —
  keeps Admin decoupled from Billing's concrete implementation per the contract's own rule.
- The frontend never computes MRR/revenue math client-side — all money math happens in
  `AdminDashboardOverviewService` server-side (Global Constraint: integer VND, no floats — floats
  would appear if this logic were duplicated in JS).
