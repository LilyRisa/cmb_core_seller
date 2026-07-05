# AI Messaging Enhancements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship four AI/messaging features — per-user AI usage counter in admin, AI sending product images on request, per-page shop business info fed to the AI, and a Horizon queue-consumer fix.

**Architecture:** Laravel 11 modular monolith + React (Vite) SPA. Backend changes live in modules `Billing`, `Messaging`, `VisualSearch`, `Admin` and integration `Ai`. Modules talk only through `Contracts/`. The AI credit choke point (`AiCreditService::record()`) gains per-user counting; the messaging AI orchestrator (`AiSuggestionService`) gains an image-request branch and a business-info prompt block, both consuming VisualSearch/config through existing seams.

**Tech Stack:** PHP 8, Laravel 11, PHPUnit, Larastan (level 5), Pint; React 18 + TypeScript + Ant Design + TanStack Query; Horizon/Redis queues.

## Global Constraints

- All PHP/Node commands run from `app/` (not repo root). `cd app` first.
- PSR-4 `CMBcoreSeller\` → `app/app/`.
- Use `config()`, never `env()`, outside config files.
- Every business table carries `tenant_id`; tenant-scoped models use `BelongsToTenant` (global scope). Services running outside tenant context query with `withoutGlobalScope(TenantScope::class)` (mirror `AiCreditService`).
- Money = integer VND; timestamps ISO-8601 UTC; API envelope `{ "data": ..., "meta": ... }` / `{ "error": {...} }`.
- User-facing strings Vietnamese; code/identifiers/routes English.
- Module boundary (PR-blocking): a module may only `use` another module's `Contracts/` or DTOs — never its `Services/`/`Models/`. `Messaging` must reach `VisualSearch` only via `VisualItemSearch` + `VisualSearch\DTO\*`.
- Quality gate (run from `app/`, all must pass): `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run lint && npm run typecheck && npm run build`.
- Prod deploy does NOT auto-migrate (`RUN_MIGRATIONS=false`) — new migrations run manually post-deploy. No JS unit-test runner exists; frontend tasks gate on typecheck + build. ~7 GHN/fulfillment tests fail on `main` already — only touched/new tests must pass.
- Do NOT `{@see AnotherModule\Class}` in docblocks — Pint auto-imports it and breaks the module-dependency check.

---

## Task 1: Fix orphaned `messaging-bg` Horizon queue (Feature 4)

The listener `PushWebOnNewMessage` runs on queue `messaging-bg`, but no Horizon supervisor consumes it, so web-push jobs pile up unconsumed. Add the queue to `supervisor-messaging-bg`.

**Files:**
- Modify: `app/config/horizon.php:307`
- Test: `app/tests/Feature/Infra/HorizonQueueCoverageTest.php` (create)

**Interfaces:**
- Produces: nothing consumed by later tasks (independent).

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Infra/HorizonQueueCoverageTest.php`:

```php
<?php

namespace Tests\Feature\Infra;

use CMBcoreSeller\Modules\Messaging\Listeners\PushWebOnNewMessage;
use Tests\TestCase;

class HorizonQueueCoverageTest extends TestCase
{
    public function test_push_web_listener_queue_is_consumed_by_a_supervisor(): void
    {
        $listenerQueue = (new \ReflectionClass(PushWebOnNewMessage::class))
            ->getDefaultProperties()['queue'] ?? null;
        $this->assertSame('messaging-bg', $listenerQueue, 'guard: listener queue name changed');

        $consumed = collect(config('horizon.defaults'))
            ->flatMap(fn ($sup) => (array) ($sup['queue'] ?? []))
            ->unique()
            ->all();

        $this->assertContains(
            $listenerQueue,
            $consumed,
            "Queue [{$listenerQueue}] is not consumed by any Horizon supervisor — its jobs would pile up.",
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=HorizonQueueCoverageTest`
Expected: FAIL — `messaging-bg` not in the consumed list.

- [ ] **Step 3: Add the queue to the supervisor**

In `app/config/horizon.php`, edit the `supervisor-messaging-bg` `queue` array (line 307) to append `'messaging-bg'`:

```php
            'queue' => ['messaging-sync', 'messaging-media', 'messaging-ai', 'messaging', 'messaging-bg', 'marketing-sync', 'marketing-ai', 'marketing-publish', 'visual-index'],
```

Also add a one-line comment above it near the existing block comment (line 306 area):

```php
            // messaging-bg: PushWebOnNewMessage (web push tin mới) — trước đây KHÔNG supervisor nào tiêu thụ.
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=HorizonQueueCoverageTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/config/horizon.php app/tests/Feature/Infra/HorizonQueueCoverageTest.php
git commit -m "fix(messaging): tiêu thụ queue messaging-bg trong Horizon (web push tin mới không kẹt)"
```

---

## Task 2: `ai_usage_counters` table + model (Feature 1)

Per-user, per-feature, per-month AI-call counter, owned by Billing. `user_id = 0` means system/auto (no request user).

**Files:**
- Create: `app/app/Modules/Billing/Database/Migrations/2026_07_05_100001_create_ai_usage_counters_table.php`
- Create: `app/app/Modules/Billing/Models/AiUsageCounter.php`
- Test: `app/tests/Feature/Billing/AiUsageCounterModelTest.php` (create)

**Interfaces:**
- Produces: `AiUsageCounter` model with columns `tenant_id:int, user_id:int, period_ym:int, feature:string, count:int`. Query outside tenant context via `AiUsageCounter::withoutGlobalScope(TenantScope::class)`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Billing/AiUsageCounterModelTest.php`:

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageCounterModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_row_persists_and_increments(): void
    {
        $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'user_id' => 0, 'period_ym' => 202607, 'feature' => 'messaging', 'count' => 0,
        ]);
        $row->increment('count', 2);

        $this->assertDatabaseHas('ai_usage_counters', [
            'tenant_id' => 1, 'user_id' => 0, 'period_ym' => 202607, 'feature' => 'messaging', 'count' => 2,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AiUsageCounterModelTest`
Expected: FAIL — table/model missing.

- [ ] **Step 3: Create the migration**

Create `app/app/Modules/Billing/Database/Migrations/2026_07_05_100001_create_ai_usage_counters_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_usage_counters — đếm số LƯỢT gọi AI theo (tenant, user, tháng, tính năng).
 *  - user_id = 0 ⇒ hệ thống / auto (không có user request, vd auto-reply hàng đợi).
 *  - period_ym = YYYYMM (vd 202607). feature = messaging|marketing|products|visual|transcription|intent|other.
 *  - Đếm tiến (không backfill); dùng cho màn admin thống kê lượt AI mỗi user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->default(0); // 0 = hệ thống/auto (KHÔNG FK — cho phép giá trị 0)
            $table->unsignedInteger('period_ym');
            $table->string('feature', 24);
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'period_ym', 'feature'], 'ai_usage_counters_unique');
            $table->index(['tenant_id', 'period_ym']);
            $table->index(['user_id', 'period_ym']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_counters');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/app/Modules/Billing/Models/AiUsageCounter.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Bộ đếm lượt gọi AI theo (tenant, user, tháng, tính năng). user_id=0 ⇒ hệ thống/auto.
 *
 * @property int $tenant_id
 * @property int $user_id
 * @property int $period_ym
 * @property string $feature
 * @property int $count
 */
class AiUsageCounter extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'period_ym', 'feature', 'count'];

    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'period_ym' => 'integer',
        'count' => 'integer',
    ];
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=AiUsageCounterModelTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Billing/Database/Migrations app/app/Modules/Billing/Models/AiUsageCounter.php app/tests/Feature/Billing/AiUsageCounterModelTest.php
git commit -m "feat(billing): bảng + model ai_usage_counters (đếm lượt AI theo user/tháng/tính năng)"
```

---

## Task 3: Count usage inside `AiCreditService::record()` (Feature 1)

Extend the metering choke point to increment `ai_usage_counters` on every recorded AI unit, attributing to the acting user (or system).

**Files:**
- Modify: `app/app/Modules/Billing/Contracts/AiCreditMeter.php:31`
- Modify: `app/app/Modules/Billing/Services/AiCreditService.php:122-138`
- Test: `app/tests/Feature/Billing/AiUsageRecordTest.php` (create)

**Interfaces:**
- Consumes: `AiUsageCounter` (Task 2).
- Produces: `AiCreditMeter::record(int $tenantId, int $n = 1, ?string $feature = null, ?int $userId = null): void`. New optional params are backward-compatible; unset `feature` stores `'other'`, unset `userId` resolves `Auth::id()` then `0`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Billing/AiUsageRecordTest.php`:

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_increments_usage_counter_even_without_active_plan(): void
    {
        // aiEnabled=false (no plan) → wallet debit skipped, but the CALL still counts.
        app(AiCreditMeter::class)->record(77, 1, 'marketing', 999);

        $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', 77)->where('user_id', 999)->first();

        $this->assertNotNull($row);
        $this->assertSame('marketing', $row->feature);
        $this->assertSame(1, $row->count);
        $this->assertSame((int) now()->format('Ym'), $row->period_ym);
    }

    public function test_record_defaults_to_system_user_and_other_feature(): void
    {
        app(AiCreditMeter::class)->record(77);

        $this->assertDatabaseHas('ai_usage_counters', [
            'tenant_id' => 77, 'user_id' => 0, 'feature' => 'other', 'count' => 1,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AiUsageRecordTest`
Expected: FAIL — `record()` ignores the extra args / no counter row.

- [ ] **Step 3: Update the contract signature**

In `app/app/Modules/Billing/Contracts/AiCreditMeter.php`, replace the `record` declaration (line 26-31):

```php
    /**
     * Ghi nhận `n` lượt ĐÃ dùng — gọi SAU khi 1 request tới provider AI trả về THÀNH CÔNG.
     * KHÔNG ném (best-effort, clamp ở 0): một reply đã sinh xong không được vỡ vì hết hạn mức.
     * Bỏ qua wallet khi gói không giới hạn / không có AI, NHƯNG luôn đếm vào ai_usage_counters
     * (đầu mối "mỗi response provider = 1 lượt"). `$feature` gắn nhãn tính năng; `$userId` null ⇒
     * resolve Auth::id() rồi 0 (hệ thống/auto).
     */
    public function record(int $tenantId, int $n = 1, ?string $feature = null, ?int $userId = null): void;
```

- [ ] **Step 4: Implement counting in the service**

In `app/app/Modules/Billing/Services/AiCreditService.php`, add imports at the top (after existing `use` lines):

```php
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use Illuminate\Support\Facades\Auth;
```

Replace the whole `record()` method (lines 122-138) with:

```php
    public function record(int $tenantId, int $n = 1, ?string $feature = null, ?int $userId = null): void
    {
        if ($n <= 0) {
            return;
        }

        // Đếm lượt gọi AI (kể cả gói không giới hạn / không có AI — đã có 1 call thực sự xảy ra).
        $this->countUsage($tenantId, $n, $feature, $userId);

        if (! $this->aiEnabled($tenantId) || $this->unlimited($tenantId)) {
            return;
        }
        $w = $this->wallet($tenantId);
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
    }

    /** Best-effort: tăng bộ đếm lượt AI theo (tenant, user, tháng, tính năng). Không ném. */
    private function countUsage(int $tenantId, int $n, ?string $feature, ?int $userId): void
    {
        try {
            $uid = $userId ?? Auth::id() ?? 0;
            $ym = (int) now()->format('Ym');
            $feat = $feature ?? 'other';

            $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                ['tenant_id' => $tenantId, 'user_id' => (int) $uid, 'period_ym' => $ym, 'feature' => $feat],
                ['count' => 0],
            );
            $row->increment('count', $n);
        } catch (\Throwable) {
            // Đếm lỗi không được phép làm vỡ luồng AI.
        }
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=AiUsageRecordTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Billing/Contracts/AiCreditMeter.php app/app/Modules/Billing/Services/AiCreditService.php app/tests/Feature/Billing/AiUsageRecordTest.php
git commit -m "feat(billing): record() đếm lượt AI theo user + tính năng (best-effort)"
```

---

## Task 4: Tag `feature` at every `record()` call site (Feature 1)

Pass a feature label from each AI call site so the counter breaks down by feature.

**Files:**
- Modify: `app/app/Modules/Messaging/Services/AiSuggestionService.php:182` and `:237`
- Modify: `app/app/Modules/Messaging/Services/IntentClassifier.php:54`
- Modify: `app/app/Modules/Marketing/Services/LlmMarketingAnalysisClient.php:50`
- Modify: `app/app/Modules/Products/Services/ProductDescriptionService.php:94`
- Modify: `app/app/Modules/VisualSearch/Services/VisionReRanker.php:92`
- Modify: `app/app/Modules/Messaging/Jobs/TranscribeInboundAudio.php:82`
- Test: `app/tests/Feature/Billing/AiUsageFeatureTagTest.php` (create)

**Interfaces:**
- Consumes: `AiCreditMeter::record(..., ?feature, ?userId)` (Task 3).

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Billing/AiUsageFeatureTagTest.php`:

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageFeatureTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_intent_classifier_records_intent_feature(): void
    {
        // Directly assert the label convention the call sites must use.
        app(AiCreditMeter::class)->record(5, 1, 'intent', 0);
        app(AiCreditMeter::class)->record(5, 1, 'messaging', 0);

        $features = AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', 5)->pluck('feature')->sort()->values()->all();

        $this->assertSame(['intent', 'messaging'], $features);
    }
}
```

(This locks the label vocabulary; the edits below apply those labels at the real call sites.)

- [ ] **Step 2: Run test to verify it passes (baseline vocab)**

Run: `php artisan test --filter=AiUsageFeatureTagTest`
Expected: PASS (Task 3 already supports the args). This test guards the label strings used below.

- [ ] **Step 3: Apply feature labels at call sites**

`AiSuggestionService.php` line 182 — change `$this->credits->record($tenantId, 1);` to:

```php
        $this->credits->record($tenantId, 1, 'messaging');
```

`AiSuggestionService.php` line 237 — change `$this->credits->record($tenantId, 1);` to:

```php
        $this->credits->record($tenantId, 1, 'messaging', $userId);
```

`IntentClassifier.php` line 54 — change `$this->credits->record($tenantId, 1);` to:

```php
            $this->credits->record($tenantId, 1, 'intent');
```

`LlmMarketingAnalysisClient.php` line 50 — change the `record(...)` call to add `'marketing'` as the 3rd arg (keep existing `$tenantId` and unit count):

```php
        $this->credits->record($tenantId, 1, 'marketing');
```

`ProductDescriptionService.php` line 94 — add `'products'`:

```php
        $this->credits->record($tenantId, 1, 'products');
```

`VisionReRanker.php` line 92 — add `'visual'`:

```php
        $this->credits->record($tenantId, 1, 'visual');
```

`TranscribeInboundAudio.php` line 82 — add `'transcription'`:

```php
        $this->credits->record($tenantId, 1, 'transcription');
```

(For each: open the file, confirm the exact existing `record(` call — some pass `$tenantId, 1`, verify the variable name in that scope — and append the label argument. Do NOT change the unit count.)

- [ ] **Step 4: Run the full AI-related suites**

Run: `php artisan test --filter=AiUsage && vendor/bin/phpstan analyse`
Expected: PASS (phpstan confirms the new signature is used correctly everywhere).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules app/tests/Feature/Billing/AiUsageFeatureTagTest.php
git commit -m "feat(billing): gắn nhãn feature tại các call site record() (messaging/intent/marketing/products/visual/transcription)"
```

---

## Task 5: `AiUsageReporter` contract + service (Feature 1)

Read-side aggregation for admin, exposed as a Billing contract so Admin never touches the counter table directly.

**Files:**
- Create: `app/app/Modules/Billing/Contracts/AiUsageReporter.php`
- Create: `app/app/Modules/Billing/Services/AiUsageReportService.php`
- Modify: `app/app/Modules/Billing/BillingServiceProvider.php:34-37` (bind)
- Test: `app/tests/Feature/Billing/AiUsageReporterTest.php` (create)

**Interfaces:**
- Consumes: `AiUsageCounter` (Task 2).
- Produces:
  - `AiUsageReporter::usageForUsers(array $userIds): array` → `array<int, array{this_month:int, all_time:int}>` keyed by user id.
  - `AiUsageReporter::breakdownForUser(int $userId): array` → `array{all_time:int, by_month:list<array{period_ym:int,count:int}>, by_feature:list<array{feature:string,count:int}>}`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Billing/AiUsageReporterTest.php`:

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Contracts\AiUsageReporter;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageReporterTest extends TestCase
{
    use RefreshDatabase;

    private function seed(int $userId, int $ym, string $feature, int $count): void
    {
        AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'user_id' => $userId, 'period_ym' => $ym, 'feature' => $feature, 'count' => $count,
        ]);
    }

    public function test_usage_for_users_splits_month_and_all_time(): void
    {
        $ym = (int) now()->format('Ym');
        $this->seed(10, $ym, 'messaging', 3);
        $this->seed(10, 202601, 'messaging', 5); // old month
        $this->seed(20, $ym, 'intent', 2);

        $out = app(AiUsageReporter::class)->usageForUsers([10, 20, 30]);

        $this->assertSame(['this_month' => 3, 'all_time' => 8], $out[10]);
        $this->assertSame(['this_month' => 2, 'all_time' => 2], $out[20]);
        $this->assertSame(['this_month' => 0, 'all_time' => 0], $out[30]); // no rows
    }

    public function test_breakdown_for_user_groups_by_month_and_feature(): void
    {
        $ym = (int) now()->format('Ym');
        $this->seed(10, $ym, 'messaging', 3);
        $this->seed(10, $ym, 'intent', 1);

        $b = app(AiUsageReporter::class)->breakdownForUser(10);

        $this->assertSame(4, $b['all_time']);
        $this->assertSame([['feature' => 'messaging', 'count' => 3], ['feature' => 'intent', 'count' => 1]], $b['by_feature']);
        $this->assertSame([['period_ym' => $ym, 'count' => 4]], $b['by_month']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AiUsageReporterTest`
Expected: FAIL — contract/service unbound.

- [ ] **Step 3: Create the contract**

Create `app/app/Modules/Billing/Contracts/AiUsageReporter.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Contracts;

/**
 * Đầu mối ĐỌC thống kê lượt gọi AI cho module khác (Admin) — theo luật module:
 * Admin chỉ phụ thuộc Contract này, không chạm bảng ai_usage_counters trực tiếp.
 */
interface AiUsageReporter
{
    /**
     * Tổng lượt AI theo user (tháng hiện tại + tất cả). user_id không có dòng ⇒ 0/0.
     *
     * @param  list<int>  $userIds
     * @return array<int, array{this_month:int, all_time:int}>
     */
    public function usageForUsers(array $userIds): array;

    /**
     * Phân rã lượt AI của 1 user: tổng, theo tháng (mới→cũ), theo tính năng (nhiều→ít).
     *
     * @return array{all_time:int, by_month:list<array{period_ym:int,count:int}>, by_feature:list<array{feature:string,count:int}>}
     */
    public function breakdownForUser(int $userId): array;
}
```

- [ ] **Step 4: Create the service**

Create `app/app/Modules/Billing/Services/AiUsageReportService.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Contracts\AiUsageReporter;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

class AiUsageReportService implements AiUsageReporter
{
    public function usageForUsers(array $userIds): array
    {
        $out = [];
        foreach ($userIds as $id) {
            $out[(int) $id] = ['this_month' => 0, 'all_time' => 0];
        }
        if ($userIds === []) {
            return $out;
        }

        $ym = (int) now()->format('Ym');
        $rows = AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->selectRaw('user_id, SUM(count) as all_time, SUM(CASE WHEN period_ym = ? THEN count ELSE 0 END) as this_month', [$ym])
            ->whereIn('user_id', array_map('intval', $userIds))
            ->groupBy('user_id')
            ->get();

        foreach ($rows as $r) {
            $out[(int) $r->user_id] = ['this_month' => (int) $r->this_month, 'all_time' => (int) $r->all_time];
        }

        return $out;
    }

    public function breakdownForUser(int $userId): array
    {
        $base = AiUsageCounter::withoutGlobalScope(TenantScope::class)->where('user_id', $userId);

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
}
```

- [ ] **Step 5: Bind the contract**

In `app/app/Modules/Billing/BillingServiceProvider.php`, add the import near the other contract import (after line 9):

```php
use CMBcoreSeller\Modules\Billing\Contracts\AiUsageReporter;
use CMBcoreSeller\Modules\Billing\Services\AiUsageReportService;
```

Add inside `register()` after the existing `AiCreditMeter` bind (line 37):

```php
        $this->app->bind(AiUsageReporter::class, AiUsageReportService::class);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=AiUsageReporterTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Billing app/tests/Feature/Billing/AiUsageReporterTest.php
git commit -m "feat(billing): AiUsageReporter contract + service (đọc thống kê lượt AI/user)"
```

---

## Task 6: Admin endpoints — usage in user list + per-user breakdown (Feature 1)

Enrich the tenant-user list with AI usage and add a breakdown endpoint. Admin consumes `AiUsageReporter`.

**Files:**
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminUserController.php` (constructor, `index`, `present`, new `aiUsage`)
- Modify: `app/app/Modules/Admin/Http/routes.php:123` (add route)
- Test: `app/tests/Feature/Admin/AdminUserAiUsageTest.php` (create)

**Interfaces:**
- Consumes: `AiUsageReporter` (Task 5).
- Produces: `GET /api/v1/admin/users` rows gain `ai_usage: {this_month:int, all_time:int}`; `GET /api/v1/admin/users/{id}/ai-usage` returns `{ data: breakdownForUser(...) }`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Admin/AdminUserAiUsageTest.php`. Follow the auth pattern used by the existing admin user tests (search `tests/Feature/Admin` for how an `admin_web` user is authenticated — reuse that helper/factory). Skeleton:

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserAiUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_list_includes_ai_usage_and_breakdown_endpoint(): void
    {
        $this->actingAsSuperAdmin(); // reuse existing helper; if none, replicate the admin_web login used by AdminUserControllerTest

        $u = User::factory()->create();
        $ym = (int) now()->format('Ym');
        AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'user_id' => $u->id, 'period_ym' => $ym, 'feature' => 'messaging', 'count' => 4,
        ]);

        $list = $this->getJson('/api/v1/admin/users')->assertOk()->json('data');
        $row = collect($list)->firstWhere('id', $u->id);
        $this->assertSame(['this_month' => 4, 'all_time' => 4], $row['ai_usage']);

        $this->getJson("/api/v1/admin/users/{$u->id}/ai-usage")
            ->assertOk()
            ->assertJsonPath('data.all_time', 4)
            ->assertJsonPath('data.by_feature.0.feature', 'messaging');
    }
}
```

Before running: grep `app/tests/Feature/Admin` for the real super-admin auth helper name and replace `actingAsSuperAdmin()` accordingly. If `User::factory()` isn't the correct factory, use the one the existing admin tests use.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminUserAiUsageTest`
Expected: FAIL — no `ai_usage` key / route 404.

- [ ] **Step 3: Inject the reporter and enrich the list**

In `AdminUserController.php`:

Add import (after line 8):

```php
use CMBcoreSeller\Modules\Billing\Contracts\AiUsageReporter;
```

Add a constructor:

```php
    public function __construct(private AiUsageReporter $usage) {}
```

In `index()`, after `$tenants = Tenant::query()...keyBy('id');` (line 43), fetch usage and pass it into `present()`:

```php
        $usage = $this->usage->usageForUsers($userIds);

        $rows = collect($page->items())->map(function (User $u) use ($memberships, $tenants, $usage) {
            return $this->present($u, $memberships, $tenants, $usage);
        })->all();
```

Update `present()` signature and payload (line 130-151). Add the `$usage` param (default `[]` so `show()`'s existing call still works) and append `ai_usage`:

```php
    /**
     * @param  array<int, array{this_month:int, all_time:int}>  $usage
     * @return array<string, mixed>
     */
    private function present(User $u, Collection $memberships, Collection $tenants, array $usage = []): array
    {
        // ... unchanged $userTenants block ...

        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'email_verified_at' => optional($u->email_verified_at)->toIso8601String(),
            'suspended_at' => optional($u->suspended_at)->toIso8601String(),
            'tenants' => $userTenants,
            'ai_usage' => $usage[$u->id] ?? ['this_month' => 0, 'all_time' => 0],
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
```

- [ ] **Step 4: Add the breakdown action**

Add a new method to `AdminUserController.php`:

```php
    /** GET /api/v1/admin/users/{id}/ai-usage — phân rã lượt AI theo tháng + tính năng. */
    public function aiUsage(int $id): JsonResponse
    {
        $u = User::query()->findOrFail($id);

        return response()->json(['data' => $this->usage->breakdownForUser($u->id)]);
    }
```

- [ ] **Step 5: Register the route**

In `app/app/Modules/Admin/Http/routes.php`, after the reactivate route (line 123), add:

```php
        Route::get('users/{id}/ai-usage', [AdminUserController::class, 'aiUsage'])
            ->whereNumber('id')->name('admin.users.ai-usage');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=AdminUserAiUsageTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Admin app/tests/Feature/Admin/AdminUserAiUsageTest.php
git commit -m "feat(admin): lượt AI trong danh sách user + endpoint phân rã /users/{id}/ai-usage"
```

---

## Task 7: Admin frontend — AI usage column + breakdown (Feature 1)

Show usage in the tenant-user tab and a breakdown in the drawer.

**Files:**
- Modify: `app/resources/js/admin/lib/tenantUsers.tsx` (types + new hook)
- Modify: `app/resources/js/admin/pages/users/AdminUsersPage.tsx` (column)
- Modify: `app/resources/js/admin/pages/users/TenantUserDrawer.tsx` (breakdown section)

**Interfaces:**
- Consumes: `GET /admin/users` (`ai_usage`), `GET /admin/users/{id}/ai-usage`.

- [ ] **Step 1: Extend types + add hook**

In `tenantUsers.tsx`, add `ai_usage` to `TenantUserRow`:

```tsx
export type TenantUserRow = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    suspended_at: string | null;
    tenants: { id: number; name: string; role: string }[];
    ai_usage: { this_month: number; all_time: number };
    created_at: string | null;
};
```

Add a breakdown type + hook at the end of the file:

```tsx
export type AiUsageBreakdown = {
    all_time: number;
    by_month: { period_ym: number; count: number }[];
    by_feature: { feature: string; count: number }[];
};

export function useTenantUserAiUsage(id: number | null) {
    return useQuery({
        queryKey: ['tenant-user-ai-usage', id],
        queryFn: async () => (await api.get<{ data: AiUsageBreakdown }>(`/admin/users/${id}/ai-usage`)).data.data,
        enabled: id !== null,
    });
}
```

- [ ] **Step 2: Add the column**

In `AdminUsersPage.tsx`, add a column to `tenantCols` (after the "Tenant" column, before "Tạo lúc"):

```tsx
        {
            title: 'Lượt AI (tháng / tổng)',
            dataIndex: 'ai_usage',
            width: 150,
            render: (v: TenantUserRow['ai_usage']) => (
                <Typography.Text>{v.this_month} / {v.all_time}</Typography.Text>
            ),
        },
```

- [ ] **Step 3: Add breakdown to the drawer**

In `TenantUserDrawer.tsx`, import the hook and render the breakdown. First read the file to match its layout, then add near the user detail body:

```tsx
import { useTenantUserAiUsage } from '../../lib/tenantUsers';
// ...
const aiUsage = useTenantUserAiUsage(userId);
// ... inside the drawer body:
<Descriptions title="Lượt gọi AI" column={1} size="small" style={{ marginTop: 16 }}>
    <Descriptions.Item label="Tổng">{aiUsage.data?.all_time ?? 0}</Descriptions.Item>
    {(aiUsage.data?.by_feature ?? []).map((f) => (
        <Descriptions.Item key={f.feature} label={f.feature}>{f.count}</Descriptions.Item>
    ))}
</Descriptions>
```

(Use whatever container the drawer already uses; if it uses `Descriptions` elsewhere, reuse that import. The label is Vietnamese; feature codes stay as-is.)

- [ ] **Step 4: Typecheck + build**

Run: `cd app && npm run typecheck && npm run build`
Expected: PASS (no type errors).

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/admin
git commit -m "feat(admin-ui): cột lượt AI ở danh sách user + phân rã trong drawer"
```

---

## Task 8: `business_info` column + model support (Feature 3)

Per-page shop/owner info on `messaging_account_meta`.

**Files:**
- Create: `app/app/Modules/Messaging/Database/Migrations/2026_07_05_100002_add_business_info_to_messaging_account_meta.php`
- Modify: `app/app/Modules/Messaging/Models/MessagingAccountMeta.php:62-92` (fillable + cast + docblock)
- Test: `app/tests/Feature/Messaging/BusinessInfoPersistenceTest.php` (create)

**Interfaces:**
- Produces: `MessagingAccountMeta.business_info` cast to `array` (nullable). Keys: `shop_name, phone, address, email, warranty_policy, working_hours, website, extra_note`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Messaging/BusinessInfoPersistenceTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessInfoPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_info_is_stored_as_array(): void
    {
        MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->create([
            'channel_account_id' => 1, 'tenant_id' => 1,
            'business_info' => ['shop_name' => 'Shop A', 'phone' => '0900'],
        ]);

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find(1);
        $this->assertSame('Shop A', $meta->business_info['shop_name']);
        $this->assertSame('0900', $meta->business_info['phone']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BusinessInfoPersistenceTest`
Expected: FAIL — column missing / not fillable.

- [ ] **Step 3: Create the migration**

Create `app/app/Modules/Messaging/Database/Migrations/2026_07_05_100002_add_business_info_to_messaging_account_meta.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * business_info (json, nullable) trên messaging_account_meta — thông tin cửa hàng theo PAGE
 * để AI trả lời khi khách hỏi SĐT/địa chỉ/bảo hành... Không mã hoá (thông tin công khai của shop),
 * khác cột `settings` (encrypted) chứa secret.
 * Khoá: shop_name, phone, address, email, warranty_policy, working_hours, website, extra_note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->json('business_info')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('messaging_account_meta', function (Blueprint $table) {
            $table->dropColumn('business_info');
        });
    }
};
```

- [ ] **Step 4: Update the model**

In `MessagingAccountMeta.php`, add `'business_info'` to `$fillable` (after `'settings'` on line 65):

```php
        'outbound_window_meta', 'ai_enabled', 'ai_auto_mode', 'settings', 'business_info',
```

Add the cast inside `casts()` (after the `settings` cast, line 83):

```php
            'business_info' => 'array',
```

Add to the docblock (after line 23):

```php
 * @property ?array $business_info
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=BusinessInfoPersistenceTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Messaging/Database/Migrations app/app/Modules/Messaging/Models/MessagingAccountMeta.php app/tests/Feature/Messaging/BusinessInfoPersistenceTest.php
git commit -m "feat(messaging): cột business_info (thông tin cửa hàng theo page)"
```

---

## Task 9: Business-info API endpoints (Feature 3)

Expose `business_info` in the channels list and add single + bulk save endpoints.

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php` (index payload + `businessInfo` + `bulkBusinessInfo` + validator)
- Modify: `app/app/Modules/Messaging/Http/routes.php:171` (routes)
- Test: `app/tests/Feature/Messaging/ChannelBusinessInfoApiTest.php` (create)

**Interfaces:**
- Produces: channels list rows gain `business_info: object|null`. `PATCH channels/{id}/business-info` body `{ business_info: {...} }`. `PATCH channels/business-info` body `{ ids: int[], business_info: {...} }`. Both gated by `messaging.ai.config`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Messaging/ChannelBusinessInfoApiTest.php`. Reuse the auth/tenant setup from an existing `MessagingChannelController` test (grep `tests/Feature/Messaging` for how a tenant user with `messaging.ai.config` is created and how a `facebook_page` ChannelAccount is seeded). Skeleton:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelBusinessInfoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_patch_business_info_persists_and_lists(): void
    {
        [$user, $tenant] = $this->messagingTenantUser(); // reuse existing helper
        $page = ChannelAccount::factory()->create(['tenant_id' => $tenant->id, 'provider' => 'facebook_page']);

        $this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->patchJson("/api/v1/messaging/channels/{$page->id}/business-info", [
                'business_info' => ['shop_name' => 'Shop A', 'phone' => '0909', 'address' => 'HN'],
            ])->assertOk();

        $this->assertSame('0909', MessagingAccountMeta::query()->find($page->id)->business_info['phone']);

        $row = collect($this->actingAs($user)->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/v1/messaging/channels?provider=facebook_page')->json('data'))
            ->firstWhere('id', $page->id);
        $this->assertSame('Shop A', $row['business_info']['shop_name']);
    }
}
```

Adjust `messagingTenantUser()` / factory calls to the real helpers before running.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ChannelBusinessInfoApiTest`
Expected: FAIL — route 404 / no `business_info` key.

- [ ] **Step 3: Expose `business_info` in the list**

In `MessagingChannelController::index()`, inside the `->map(function (ChannelAccount $a) {...})` return array (after the `'ai_auto_mode'` line, ~line 63), add:

```php
                    // Thông tin cửa hàng theo page (AI dùng để trả lời SĐT/địa chỉ/bảo hành...).
                    'business_info' => $meta?->business_info,
```

- [ ] **Step 4: Add the save endpoints + validator**

Add these methods to `MessagingChannelController.php`:

```php
    /** PATCH /channels/{id}/business-info — lưu thông tin cửa hàng cho 1 page. */
    public function businessInfo(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.ai.config');

        $info = $this->validatedBusinessInfo($request);
        $account = ChannelAccount::query()
            ->whereIn('provider', ChannelAccount::MESSAGING_ONLY_PROVIDERS)->findOrFail($id);

        MessagingAccountMeta::query()->updateOrCreate(
            ['channel_account_id' => $account->id],
            ['tenant_id' => $account->tenant_id, 'business_info' => $info],
        );

        AuditLog::record('messaging.'.$account->provider.'.business_info', null, [
            'external_shop_id' => $account->external_shop_id,
        ]);

        return response()->json(['data' => ['ok' => true, 'business_info' => $info]]);
    }

    /** PATCH /channels/business-info — áp dụng thông tin cửa hàng cho NHIỀU page (body: { ids, business_info }). */
    public function bulkBusinessInfo(Request $request): JsonResponse
    {
        Gate::authorize('messaging.ai.config');

        $ids = $this->validatedIds($request);
        $info = $this->validatedBusinessInfo($request);
        $accounts = ChannelAccount::query()
            ->whereIn('provider', ChannelAccount::MESSAGING_ONLY_PROVIDERS)->whereIn('id', $ids)->get();

        foreach ($accounts as $account) {
            MessagingAccountMeta::query()->updateOrCreate(
                ['channel_account_id' => $account->id],
                ['tenant_id' => $account->tenant_id, 'business_info' => $info],
            );
        }

        if ($accounts->isNotEmpty()) {
            AuditLog::record('messaging.bulk_business_info', null, [
                'external_shop_ids' => $accounts->pluck('external_shop_id')->all(),
                'count' => $accounts->count(),
            ]);
        }

        return response()->json(['data' => ['ok' => true, 'processed' => $accounts->count()]]);
    }

    /**
     * Validate + chuẩn hoá khối business_info (bộ khoá cố định + ghi chú tự do).
     *
     * @return array<string,string>
     */
    private function validatedBusinessInfo(Request $request): array
    {
        $data = $request->validate([
            'business_info' => ['required', 'array'],
            'business_info.shop_name' => ['nullable', 'string', 'max:150'],
            'business_info.phone' => ['nullable', 'string', 'max:60'],
            'business_info.address' => ['nullable', 'string', 'max:400'],
            'business_info.email' => ['nullable', 'string', 'max:150'],
            'business_info.warranty_policy' => ['nullable', 'string', 'max:2000'],
            'business_info.working_hours' => ['nullable', 'string', 'max:200'],
            'business_info.website' => ['nullable', 'string', 'max:200'],
            'business_info.extra_note' => ['nullable', 'string', 'max:2000'],
        ]);

        // Chỉ giữ khoá cho phép + bỏ giá trị rỗng.
        return array_filter($data['business_info'], fn ($v) => is_string($v) && trim($v) !== '');
    }
```

Note: `Request`, `JsonResponse`, `Gate`, `AuditLog`, `ChannelAccount`, `MessagingAccountMeta` are already imported in this controller.

- [ ] **Step 5: Register the routes**

In `app/app/Modules/Messaging/Http/routes.php`, after the `channels/{id}/ai-mode` route (line 171), add:

```php
        // Thông tin cửa hàng theo page (AI trả lời SĐT/địa chỉ/bảo hành...).
        Route::patch('channels/{id}/business-info', [MessagingChannelController::class, 'businessInfo'])
            ->whereNumber('id')->name('messaging.channels.business_info');   // messaging.ai.config
        Route::patch('channels/business-info', [MessagingChannelController::class, 'bulkBusinessInfo'])
            ->name('messaging.channels.bulk-business_info');                 // messaging.ai.config
```

(Place the bulk route so the `whereNumber('id')` single route is registered first; Laravel matches the numeric constraint before the literal `business-info`, so order is safe either way.)

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=ChannelBusinessInfoApiTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Messaging/Http app/tests/Feature/Messaging/ChannelBusinessInfoApiTest.php
git commit -m "feat(messaging): endpoint lưu thông tin cửa hàng theo page (đơn + hàng loạt)"
```

---

## Task 10: Inject business info into the AI prompt (Feature 3)

Append a "# Thông tin cửa hàng" block to `systemPromptExtra` from the conversation's page, in both auto and suggest paths.

**Files:**
- Modify: `app/app/Modules/Messaging/Services/AiSuggestionService.php` (new `withBusinessInfo`, wire into lines 154 and 208)
- Test: `app/tests/Feature/Messaging/BusinessInfoPromptTest.php` (create)

**Interfaces:**
- Consumes: `MessagingAccountMeta.business_info` (Task 8).
- Produces: `AiSuggestionService::withBusinessInfo(string $extra, Conversation $conv): string` (private) — appends a rendered block when the page has business info; returns `$extra` unchanged otherwise.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Messaging/BusinessInfoPromptTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessInfoPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_info_block_is_rendered_for_page(): void
    {
        MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->create([
            'channel_account_id' => 55, 'tenant_id' => 1,
            'business_info' => ['shop_name' => 'Shop A', 'phone' => '0909', 'address' => 'Hà Nội'],
        ]);
        $conv = new Conversation(['tenant_id' => 1, 'channel_account_id' => 55]);

        $svc = app(AiSuggestionService::class);
        $method = (new \ReflectionMethod($svc, 'withBusinessInfo'));
        $method->setAccessible(true);
        $out = $method->invoke($svc, 'BASE', $conv);

        $this->assertStringContainsString('BASE', $out);
        $this->assertStringContainsString('Thông tin cửa hàng', $out);
        $this->assertStringContainsString('Shop A', $out);
        $this->assertStringContainsString('0909', $out);
    }

    public function test_no_business_info_returns_extra_unchanged(): void
    {
        $conv = new Conversation(['tenant_id' => 1, 'channel_account_id' => 999]);
        $svc = app(AiSuggestionService::class);
        $method = (new \ReflectionMethod($svc, 'withBusinessInfo'));
        $method->setAccessible(true);

        $this->assertSame('BASE', $method->invoke($svc, 'BASE', $conv));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BusinessInfoPromptTest`
Expected: FAIL — `withBusinessInfo` missing.

- [ ] **Step 3: Add the method + import**

In `AiSuggestionService.php`, add import (after line 19, the `MessagingSetting` import):

```php
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
```

Add the private method (place near `withAdContext`, after line 644):

```php
    /**
     * Chèn khối "Thông tin cửa hàng" theo PAGE vào system prompt để AI trả lời khi khách hỏi
     * SĐT/địa chỉ/bảo hành/email... Không có info ⇒ trả nguyên `$extra`. Đọc withoutGlobalScope
     * vì có thể chạy trong job (không có tenant context).
     */
    private function withBusinessInfo(string $extra, Conversation $conv): string
    {
        $channelAccountId = $conv->channel_account_id ? (int) $conv->channel_account_id : null;
        if ($channelAccountId === null) {
            return $extra;
        }

        $info = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)
            ->where('channel_account_id', $channelAccountId)
            ->value('business_info');
        if (! is_array($info) || $info === []) {
            return $extra;
        }

        $labels = [
            'shop_name' => 'Tên shop',
            'phone' => 'Số điện thoại',
            'address' => 'Địa chỉ',
            'email' => 'Email',
            'warranty_policy' => 'Chính sách bảo hành',
            'working_hours' => 'Giờ làm việc',
            'website' => 'Website',
            'extra_note' => 'Thông tin thêm',
        ];
        $lines = [];
        foreach ($labels as $key => $label) {
            $val = trim((string) ($info[$key] ?? ''));
            if ($val !== '') {
                $lines[] = "- {$label}: {$val}";
            }
        }
        if ($lines === []) {
            return $extra;
        }

        $block = "# Thông tin cửa hàng (dùng để trả lời khi khách hỏi liên hệ/SĐT/địa chỉ/bảo hành — KHÔNG bịa ngoài các thông tin dưới đây):\n"
            .implode("\n", $lines);

        return $extra !== '' ? $extra."\n\n".$block : $block;
    }
```

- [ ] **Step 4: Wire into both prompt builders**

In `draftAutoReply()` (line 154), wrap the existing `$extra` assignment with `withBusinessInfo`:

```php
        $extra = $this->withBusinessInfo(
            $this->withAdContext($this->withVisualContext($this->baseSystemExtra(), $conv, $tenantId, $providerCode, $provider?->default_model), $conv),
            $conv,
        );
```

In `suggest()` (line 204-209), wrap the `systemPromptExtra` argument likewise:

```php
        $ctx = new AiContext(
            tenantId: $tenantId,
            providerCode: $providerCode,
            model: $provider?->default_model,
            systemPromptExtra: $this->withBusinessInfo(
                $this->withAdContext((string) $this->withVisualContext($this->baseSystemExtra(), $conv, $tenantId, $providerCode, $provider?->default_model), $conv),
                $conv,
            ),
        );
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=BusinessInfoPromptTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Messaging/Services/AiSuggestionService.php app/tests/Feature/Messaging/BusinessInfoPromptTest.php
git commit -m "feat(messaging): nạp thông tin cửa hàng theo page vào prompt AI"
```

---

## Task 11: Business-info frontend (Feature 3)

Per-page form + bulk apply on the channels page.

**Files:**
- Modify: `app/resources/js/lib/messagingConfig.tsx` (`MessagingChannel` type + `BusinessInfo` type + hooks)
- Create: `app/resources/js/components/messaging/BusinessInfoDrawer.tsx`
- Modify: `app/resources/js/pages/MessagingChannelsPage.tsx` (open drawer per page + bulk button)

**Interfaces:**
- Consumes: `PATCH /messaging/channels/{id}/business-info`, `PATCH /messaging/channels/business-info`, list `business_info`.

- [ ] **Step 1: Add types + hooks**

In `messagingConfig.tsx`, add a `BusinessInfo` type and extend `MessagingChannel`:

```tsx
export interface BusinessInfo {
    shop_name?: string;
    phone?: string;
    address?: string;
    email?: string;
    warranty_policy?: string;
    working_hours?: string;
    website?: string;
    extra_note?: string;
}
```

Add `business_info: BusinessInfo | null;` to the `MessagingChannel` interface (after `ai_auto_mode`).

Add two mutation hooks (mirroring `useSetChannelAiMode`, after line 464):

```tsx
export function useSetChannelBusinessInfo() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { id: number; business_info: BusinessInfo }) => {
            await api!.patch(`/messaging/channels/${input.id}/business-info`, { business_info: input.business_info });
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}

export function useBulkSetChannelBusinessInfo() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { ids: number[]; business_info: BusinessInfo }) => {
            await api!.patch('/messaging/channels/business-info', input);
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}
```

- [ ] **Step 2: Create the drawer**

Create `app/resources/js/components/messaging/BusinessInfoDrawer.tsx`:

```tsx
import { useEffect } from 'react';
import { Drawer, Form, Input, Button, Space, message } from 'antd';
import type { BusinessInfo } from '@/lib/messagingConfig';
import { useSetChannelBusinessInfo } from '@/lib/messagingConfig';

const FIELDS: { name: keyof BusinessInfo; label: string; textarea?: boolean }[] = [
    { name: 'shop_name', label: 'Tên shop' },
    { name: 'phone', label: 'Số điện thoại' },
    { name: 'address', label: 'Địa chỉ' },
    { name: 'email', label: 'Email' },
    { name: 'working_hours', label: 'Giờ làm việc' },
    { name: 'website', label: 'Website' },
    { name: 'warranty_policy', label: 'Chính sách bảo hành', textarea: true },
    { name: 'extra_note', label: 'Thông tin thêm', textarea: true },
];

export function BusinessInfoDrawer({
    open,
    channelId,
    initial,
    onClose,
}: {
    open: boolean;
    channelId: number | null;
    initial: BusinessInfo | null;
    onClose: () => void;
}) {
    const [form] = Form.useForm<BusinessInfo>();
    const save = useSetChannelBusinessInfo();

    useEffect(() => {
        if (open) form.setFieldsValue(initial ?? {});
    }, [open, initial, form]);

    const onSave = async () => {
        const values = await form.validateFields();
        if (channelId == null) return;
        await save.mutateAsync({ id: channelId, business_info: values });
        message.success('Đã lưu thông tin cửa hàng');
        onClose();
    };

    return (
        <Drawer
            title="Thông tin cửa hàng"
            open={open}
            onClose={onClose}
            width={420}
            extra={
                <Space>
                    <Button onClick={onClose}>Huỷ</Button>
                    <Button type="primary" loading={save.isPending} onClick={onSave}>Lưu</Button>
                </Space>
            }
        >
            <Form form={form} layout="vertical">
                {FIELDS.map((f) => (
                    <Form.Item key={f.name} name={f.name} label={f.label}>
                        {f.textarea ? <Input.TextArea rows={3} /> : <Input />}
                    </Form.Item>
                ))}
            </Form>
        </Drawer>
    );
}
```

- [ ] **Step 3: Wire into the channels page**

In `MessagingChannelsPage.tsx`, read the file first to match its per-page action layout, then:
- Import `BusinessInfoDrawer` and add state `const [bizPage, setBizPage] = useState<MessagingChannel | null>(null);`.
- Add a per-page action (button/menu item, icon `ShopOutlined` from `@ant-design/icons`, label "Thông tin cửa hàng") that calls `setBizPage(channel)`.
- Render `<BusinessInfoDrawer open={bizPage !== null} channelId={bizPage?.id ?? null} initial={bizPage?.business_info ?? null} onClose={() => setBizPage(null)} />`.

Follow the existing icon-not-emoji and toolbar conventions used on that page.

- [ ] **Step 4: Typecheck + build**

Run: `cd app && npm run typecheck && npm run build`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/resources/js
git commit -m "feat(messaging-ui): form thông tin cửa hàng theo page trên màn kênh nhắn tin"
```

---

## Task 12: Config key + `image_request` intent (Feature 2)

**Files:**
- Modify: `app/config/messaging.php` (add `ai.image_reply.max_images`)
- Modify: `app/app/Modules/Messaging/Services/IntentClassifier.php:29`
- Test: `app/tests/Feature/Messaging/ImageRequestIntentTest.php` (create)

**Interfaces:**
- Produces: `config('messaging.ai.image_reply.max_images')` (default 3); `IntentClassifier::ALL` includes `'image_request'` (non-escalating).

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Messaging/ImageRequestIntentTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Modules\Messaging\Services\IntentClassifier;
use Tests\TestCase;

class ImageRequestIntentTest extends TestCase
{
    public function test_image_request_is_a_candidate_and_not_escalated(): void
    {
        $this->assertContains('image_request', IntentClassifier::ALL);
        $this->assertNotContains('image_request', IntentClassifier::ESCALATE);

        $classifier = app(IntentClassifier::class);
        $this->assertFalse($classifier->shouldEscalate(new IntentDTO(intent: 'image_request', confidence: 0.9)));
    }

    public function test_image_reply_max_images_config_default(): void
    {
        $this->assertSame(3, (int) config('messaging.ai.image_reply.max_images', 3));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ImageRequestIntentTest`
Expected: FAIL — `image_request` not in `ALL`.

- [ ] **Step 3: Add the intent**

In `IntentClassifier.php` line 29, add `'image_request'` to `ALL` (keep `ESCALATE` unchanged):

```php
    public const ALL = ['order_status', 'price', 'image_request', 'complaint', 'refund', 'urgent', 'legal_threat', 'abuse', 'smalltalk', 'other'];
```

- [ ] **Step 4: Add the config key**

Open `app/config/messaging.php`. Inside the returned array, add (or extend an existing `'ai' =>` block if present — read the file first to avoid duplicating the key):

```php
    // AI trả lời kèm ảnh sản phẩm khi khách hỏi hình.
    'ai' => [
        'image_reply' => [
            'max_images' => (int) env('MESSAGING_AI_IMAGE_REPLY_MAX', 3),
        ],
    ],
```

(If `messaging.php` already has an `'ai'` key, merge `image_reply` into it rather than adding a second `'ai' =>` entry.)

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=ImageRequestIntentTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/config/messaging.php app/app/Modules/Messaging/Services/IntentClassifier.php app/tests/Feature/Messaging/ImageRequestIntentTest.php
git commit -m "feat(messaging): intent image_request + config max ảnh trả lời"
```

---

## Task 13: VisualSearch text lookup + image bytes (Feature 2)

Add name-based product lookup and an image-bytes accessor to the VisualSearch contract, so Messaging can resolve a product by name and obtain its images without touching VisualSearch internals.

**Files:**
- Create: `app/app/Modules/VisualSearch/DTO/VisualItemImage.php`
- Modify: `app/app/Modules/VisualSearch/Contracts/VisualItemSearch.php` (2 new methods)
- Modify: `app/app/Modules/VisualSearch/Services/VisualMatcher.php` (implement both)
- Test: `app/tests/Feature/VisualSearch/FindByNameTest.php` (create)

**Interfaces:**
- Consumes: `VisualTrainingItem`, `VisualTrainingImage` (same module — allowed).
- Produces:
  - `VisualItemSearch::findByName(int $tenantId, string $text, ?int $channelAccountId = null): VisualMatchResult` — case-insensitive name/ref_code containment; 1 hit → matched, >1 → ambiguous, 0 → not_found. Confidence 1.0.
  - `VisualItemSearch::imagesForItem(int $tenantId, int $itemId, int $limit = 3): array` → `list<VisualItemImage>` (primary image first). Returns `[]` if none/unreadable.
  - `VisualItemImage { public string $mime; public string $bytes; public function ext(): string; }`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/VisualSearch/FindByNameTest.php`:

```php
<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Modules\VisualSearch\Contracts\VisualItemSearch;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FindByNameTest extends TestCase
{
    use RefreshDatabase;

    private function item(int $tenantId, string $name, string $ref = ''): VisualTrainingItem
    {
        return VisualTrainingItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId, 'name' => $name, 'ref_code' => $ref,
            'status' => 'active', 'applies_all_pages' => true,
        ]);
    }

    public function test_single_name_match_returns_matched(): void
    {
        $this->item(1, 'Áo thun cổ tròn');
        $this->item(1, 'Quần jean');

        $r = app(VisualItemSearch::class)->findByName(1, 'cho em xem áo thun cổ tròn với ạ');

        $this->assertSame(VisualMatchResult::STATUS_MATCHED, $r->status);
        $this->assertSame('Áo thun cổ tròn', $r->item->name);
    }

    public function test_multiple_matches_return_ambiguous(): void
    {
        $this->item(1, 'Áo thun');
        $this->item(1, 'Quần jean');

        $r = app(VisualItemSearch::class)->findByName(1, 'cho xem áo thun và quần jean');
        $this->assertSame(VisualMatchResult::STATUS_AMBIGUOUS, $r->status);
        $this->assertCount(2, $r->candidates);
    }

    public function test_no_match_returns_not_found(): void
    {
        $this->item(1, 'Áo thun');
        $r = app(VisualItemSearch::class)->findByName(1, 'giày thể thao');
        $this->assertSame(VisualMatchResult::STATUS_NOT_FOUND, $r->status);
    }

    public function test_images_for_item_returns_primary_first(): void
    {
        Storage::fake('local');
        $item = $this->item(1, 'Áo thun');
        Storage::disk('local')->put('p/a.jpg', 'AAA');
        Storage::disk('local')->put('p/b.jpg', 'BBB');
        $img1 = VisualTrainingImage::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'item_id' => $item->id, 'storage_disk' => 'local', 'storage_path' => 'p/b.jpg',
            'image_hash' => 'h2', 'mime_type' => 'image/jpeg', 'width' => 1, 'height' => 1, 'size_bytes' => 3, 'sort_order' => 2,
        ]);
        $primary = VisualTrainingImage::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'item_id' => $item->id, 'storage_disk' => 'local', 'storage_path' => 'p/a.jpg',
            'image_hash' => 'h1', 'mime_type' => 'image/jpeg', 'width' => 1, 'height' => 1, 'size_bytes' => 3, 'sort_order' => 1,
        ]);
        $item->forceFill(['primary_image_id' => $primary->id])->save();

        $imgs = app(VisualItemSearch::class)->imagesForItem(1, $item->id, 3);
        $this->assertCount(2, $imgs);
        $this->assertSame('AAA', $imgs[0]->bytes); // primary first
        $this->assertSame('image/jpeg', $imgs[0]->mime);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FindByNameTest`
Expected: FAIL — methods/DTO missing.

- [ ] **Step 3: Create the image DTO**

Create `app/app/Modules/VisualSearch/DTO/VisualItemImage.php`:

```php
<?php

namespace CMBcoreSeller\Modules\VisualSearch\DTO;

/** Ảnh của 1 training item để gửi cho khách (bytes + mime). */
final class VisualItemImage
{
    public function __construct(
        public string $mime,
        public string $bytes,
    ) {}

    /** Đuôi file suy từ mime (mặc định jpg). */
    public function ext(): string
    {
        return match ($this->mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }
}
```

- [ ] **Step 4: Extend the contract**

Replace `app/app/Modules/VisualSearch/Contracts/VisualItemSearch.php` body with:

```php
<?php

namespace CMBcoreSeller\Modules\VisualSearch\Contracts;

use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemImage;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualLookupOptions;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;

/**
 * Cổng tiêu thụ visual search cho module khác (Messaging) + API seller.
 * Triết lý: KHÔNG ném — tắt/lỗi/không khớp ⇒ VisualMatchResult::notFound() / [].
 */
interface VisualItemSearch
{
    public function lookup(int $tenantId, VisualImageInput $image, VisualLookupOptions $opts): VisualMatchResult;

    /**
     * Tra item theo TÊN/mã trong câu khách nhắn (case-insensitive). 1 khớp ⇒ matched,
     * nhiều ⇒ ambiguous, không ⇒ not_found. Lọc per-page nếu có $channelAccountId.
     */
    public function findByName(int $tenantId, string $text, ?int $channelAccountId = null): VisualMatchResult;

    /**
     * Ảnh của 1 item (ảnh primary trước), tối đa $limit. Bytes đọc từ disk của module.
     * Không có / lỗi đọc ⇒ [].
     *
     * @return list<VisualItemImage>
     */
    public function imagesForItem(int $tenantId, int $itemId, int $limit = 3): array;
}
```

- [ ] **Step 5: Implement both in VisualMatcher**

In `VisualMatcher.php`, add imports (after line 13):

```php
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemImage;
```

Add both methods to the class (after `lookup()`, before `recallDecision`):

```php
    public function findByName(int $tenantId, string $text, ?int $channelAccountId = null): VisualMatchResult
    {
        $needle = mb_strtolower(trim($text));
        if ($needle === '') {
            return VisualMatchResult::notFound();
        }

        // Danh mục training item nhỏ ⇒ nạp rồi so khớp chứa-chuỗi trong PHP (portable, không phụ thuộc DB collation).
        $items = VisualTrainingItem::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        // Lọc per-page (SPEC 0035): item applies_all_pages, hoặc gắn page này.
        $pageItemIds = null;
        if ($channelAccountId !== null) {
            $pageItemIds = DB::table('visual_training_item_page')
                ->where('channel_account_id', $channelAccountId)
                ->pluck('item_id')->map(fn ($v) => (int) $v)->all();
        }

        $matches = [];
        foreach ($items as $item) {
            if ($pageItemIds !== null && ! $item->applies_all_pages && ! in_array((int) $item->id, $pageItemIds, true)) {
                continue;
            }
            $name = mb_strtolower(trim((string) $item->name));
            $ref = mb_strtolower(trim((string) $item->ref_code));
            $hit = ($name !== '' && str_contains($needle, $name))
                || ($ref !== '' && str_contains($needle, $ref));
            if ($hit) {
                $matches[$item->id] = $this->toCandidate($item, 1.0);
            }
        }

        $matches = array_values($matches);
        if ($matches === []) {
            return VisualMatchResult::notFound();
        }
        if (count($matches) === 1) {
            return VisualMatchResult::matched($matches[0]);
        }

        return VisualMatchResult::ambiguous(array_slice($matches, 0, 5));
    }

    public function imagesForItem(int $tenantId, int $itemId, int $limit = 3): array
    {
        $item = VisualTrainingItem::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->find($itemId);
        if ($item === null) {
            return [];
        }

        $images = VisualTrainingImage::withoutGlobalScopes()
            ->where('item_id', $item->id)
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [(int) $item->primary_image_id])
            ->orderBy('sort_order')
            ->limit(max(1, $limit))
            ->get();

        $out = [];
        foreach ($images as $img) {
            try {
                $disk = Storage::disk($img->storage_disk);
                if (! $disk->exists($img->storage_path)) {
                    continue;
                }
                $bytes = (string) $disk->get($img->storage_path);
            } catch (\Throwable) {
                continue;
            }
            if ($bytes === '') {
                continue;
            }
            $out[] = new VisualItemImage((string) ($img->mime_type ?: 'image/jpeg'), $bytes);
        }

        return $out;
    }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=FindByNameTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/VisualSearch app/tests/Feature/VisualSearch/FindByNameTest.php
git commit -m "feat(visualsearch): findByName + imagesForItem qua contract (tra SP theo tên + lấy ảnh)"
```

---

## Task 14: Store outbound bytes helper (Feature 2)

Add a method to persist raw image bytes onto the messaging media disk, so a visual-training image (possibly on a different disk) can be sent as an outbound attachment.

**Files:**
- Modify: `app/app/Modules/Messaging/Services/MediaStorage.php`
- Test: `app/tests/Feature/Messaging/MediaStoreOutboundTest.php` (create)

**Interfaces:**
- Produces: `MediaStorage::storeOutboundBytes(int $tenantId, int $conversationId, string $bytes, string $extension): string` — writes to the messaging media disk at `buildPath(...)`, returns the storage path.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Messaging/MediaStoreOutboundTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStoreOutboundTest extends TestCase
{
    public function test_stores_bytes_on_messaging_disk_and_returns_path(): void
    {
        Storage::fake(config('messaging.media_disk', 'local'));
        $path = app(MediaStorage::class)->storeOutboundBytes(7, 9, 'IMGDATA', 'jpg');

        $this->assertStringContainsString('tenants/7/messaging/', $path);
        Storage::disk(config('messaging.media_disk', 'local'))->assertExists($path);
        $this->assertSame('IMGDATA', Storage::disk(config('messaging.media_disk', 'local'))->get($path));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MediaStoreOutboundTest`
Expected: FAIL — method missing.

- [ ] **Step 3: Add the method**

In `MediaStorage.php`, add after `buildPath()` (line 42):

```php
    /**
     * Ghi bytes vào disk media messaging ở path chuẩn (dùng khi nguồn ảnh nằm ở disk khác,
     * vd ảnh training của VisualSearch) rồi trả storage_path để queueMedia + SendMessage sinh signed URL.
     */
    public function storeOutboundBytes(int $tenantId, int $conversationId, string $bytes, string $extension): string
    {
        $path = $this->buildPath($tenantId, $conversationId, $extension);
        $this->disk()->put($path, $bytes);

        return $path;
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=MediaStoreOutboundTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Messaging/Services/MediaStorage.php app/tests/Feature/Messaging/MediaStoreOutboundTest.php
git commit -m "feat(messaging): MediaStorage::storeOutboundBytes (ghi ảnh vào disk media để gửi)"
```

---

## Task 15: AI auto-mode sends product images (Feature 2)

Wire the image-request branch into `AiSuggestionService`: on `image_request`, resolve the product by name and send its images; if unresolved, tell the AI to ask which product.

**Files:**
- Modify: `app/app/Modules/Messaging/Services/AiSuggestionService.php` (`draftAutoReply`, `autoRespond`, new helpers)
- Test: `app/tests/Feature/Messaging/AiImageReplyTest.php` (create)

**Interfaces:**
- Consumes: `VisualItemSearch::findByName` + `imagesForItem` (Task 13), `MediaStorage::storeOutboundBytes` (Task 14), `OutboundMessageService::queueMedia`, `config('messaging.ai.image_reply.max_images')`.
- Produces: `draftAutoReply` may return `['action' => 'send_media', 'intent' => 'image_request', 'images' => list<array{storage_path:string,mime:string}>, 'caption' => string]`; `autoRespond` handles it by calling `queueMedia` per image.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Messaging/AiImageReplyTest.php`. This drives the auto path with a fake `VisualItemSearch` and asserts an outbound image message is queued. Bind fakes and use `Queue::fake()` (SendMessage is dispatched by queueMedia):

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Messaging\Services\IntentClassifier;
use CMBcoreSeller\Modules\VisualSearch\Contracts\VisualItemSearch;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemCandidate;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemImage;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiImageReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_request_matched_sends_image_message(): void
    {
        Storage::fake(config('messaging.media_disk', 'local'));

        // Fake intent → image_request.
        $intent = \Mockery::mock(IntentClassifier::class);
        $intent->shouldReceive('classify')->andReturn(new IntentDTO(intent: 'image_request', confidence: 0.95));
        $intent->shouldReceive('shouldEscalate')->andReturn(false);
        $this->app->instance(IntentClassifier::class, $intent);

        // Fake visual search → matched item with one image.
        $visual = \Mockery::mock(VisualItemSearch::class);
        $visual->shouldReceive('findByName')->andReturn(
            VisualMatchResult::matched(new VisualItemCandidate(itemId: 5, name: 'Áo thun', description: null, attributes: [], confidence: 1.0)),
        );
        $visual->shouldReceive('imagesForItem')->andReturn([new VisualItemImage('image/jpeg', 'IMG')]);
        $this->app->instance(VisualItemSearch::class, $visual);

        // Minimal conversation + one inbound message (so currentTurnText/queueMedia work).
        $conv = $this->seedConversationWithInbound('cho em xin ảnh áo thun'); // helper: create Conversation + inbound Message

        $result = app(AiSuggestionService::class)->autoRespond($conv, 'cho em xin ảnh áo thun');

        $this->assertSame('sent', $result['action']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => 'image',
            'sent_by_ai' => true,
        ]);
    }
}
```

Setup notes (important — `draftAutoReply` runs `resolveProviderCode` then `assertHasCredit` BEFORE the image branch, so both must be satisfied even though the branch returns before `generateReply`):
- Reuse the existing `AiSuggestionService` test harness (grep `tests/Feature/Messaging` for the test that already exercises `autoRespond`/`draftAutoReply`) for provider registration + tenant AI-credit setup.
- Simplest self-contained alternative: bind a fake credit meter so `assertHasCredit` passes — `$fake = \Mockery::mock(\CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter::class); $fake->shouldReceive('canUse')->andReturnTrue(); $fake->shouldReceive('summary')->andReturn(['period_used'=>0,'monthly_allowance'=>1]); $fake->shouldReceive('record'); $this->app->instance(\CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter::class, $fake);` — and register an active AI provider code the way the existing harness does so `resolveProviderCode` returns a value.
- `seedConversationWithInbound()`: create a `Conversation` (`tenant_id`, `channel_account_id`, `provider='facebook_page'`) + one inbound `Message`. The image branch returns before `generateReply`, so no fake AI connector is needed for THIS test.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AiImageReplyTest`
Expected: FAIL — no image branch; no outbound image message.

- [ ] **Step 3: Add the image branch to `draftAutoReply`**

In `AiSuggestionService.php`, add imports (after existing VisualSearch DTO imports, line 26):

```php
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemImage;
```

In `draftAutoReply()`, the current order is: resolve provider → `assertHasCredit` → classify → escalate check. Insert the image-request handling immediately AFTER the escalate check (after line 143) and BEFORE the connector resolution (line 145), so an image reply needs neither a text generation nor a credit unit beyond the intent classify already counted:

```php
        // Khách xin ảnh sản phẩm: nếu xác định được SP theo tên ⇒ gửi ảnh (không cần sinh text).
        // Không rõ SP ⇒ rơi xuống sinh text kèm chỉ dẫn HỎI LẠI khách muốn xem SP nào.
        $askForProduct = false;
        if ($intent->intent === 'image_request') {
            $media = $this->resolveProductImages($conv, $inboundText, $tenantId);
            if ($media !== null) {
                return [
                    'action' => 'send_media',
                    'intent' => $intent->intent,
                    'images' => $media['images'],
                    'caption' => $media['caption'],
                ];
            }
            $askForProduct = true;
        }
```

Then, where `$extra` is built (the `withBusinessInfo(...)` wrap added in Task 10, line 154), append the ask directive when `$askForProduct` is true:

```php
        $extra = $this->withBusinessInfo(
            $this->withAdContext($this->withVisualContext($this->baseSystemExtra(), $conv, $tenantId, $providerCode, $provider?->default_model), $conv),
            $conv,
        );
        if ($askForProduct) {
            $extra .= "\n\nKhách muốn XEM HÌNH sản phẩm nhưng CHƯA rõ là sản phẩm nào. "
                .'Hãy lịch sự HỎI LẠI khách muốn xem hình sản phẩm nào (xin tên hoặc mẫu), KHÔNG bịa và KHÔNG tự gửi hình.';
        }
```

Add the two private helpers (near `withBusinessInfo`):

```php
    /**
     * Xác định SP khách xin ảnh (theo tên trong câu) → tải ảnh (ảnh primary trước) đã sao chép sang
     * disk media messaging + caption. Không rõ SP / không có ảnh ⇒ null (caller hỏi lại).
     *
     * @return array{images:list<array{storage_path:string,mime:string}>, caption:string}|null
     */
    private function resolveProductImages(Conversation $conv, string $inboundText, int $tenantId): ?array
    {
        $match = $this->visualSearch->findByName($tenantId, $inboundText, $conv->channel_account_id ? (int) $conv->channel_account_id : null);
        if ($match->status !== VisualMatchResult::STATUS_MATCHED || $match->item === null) {
            return null; // ambiguous / not_found ⇒ hỏi lại
        }

        $max = max(1, (int) config('messaging.ai.image_reply.max_images', 3));
        $imgs = $this->visualSearch->imagesForItem($tenantId, $match->item->itemId, $max);
        if ($imgs === []) {
            return null; // SP đúng nhưng không có ảnh ⇒ để AI trả lời text
        }

        $stored = [];
        foreach ($imgs as $img) {
            /** @var VisualItemImage $img */
            $path = $this->media->storeOutboundBytes($tenantId, (int) $conv->id, $img->bytes, $img->ext());
            $stored[] = ['storage_path' => $path, 'mime' => $img->mime];
        }

        return [
            'images' => $stored,
            'caption' => 'Dạ, shop gửi anh/chị hình sản phẩm '.$match->item->name.' ạ.',
        ];
    }
```

- [ ] **Step 4: Handle `send_media` in `autoRespond`**

In `autoRespond()` (line 102-117), after the `escalated` check (line 105-107) add:

```php
        if ($draft['action'] === 'send_media') {
            $message = null;
            foreach ($draft['images'] as $i => $img) {
                $message = $this->outbound->queueMedia($conv, [
                    'kind' => 'image',
                    'storage_path' => $img['storage_path'],
                    'mime' => $img['mime'],
                ], [
                    'caption' => $i === 0 ? $draft['caption'] : null,
                    'sent_by_ai' => true,
                ]);
            }

            return ['action' => 'sent', 'intent' => $draft['intent'], 'message' => $message];
        }
```

Update the `draftAutoReply` PHPDoc `@return` (line 127) to include the new shape:

```php
     * @return array{action:'escalated'|'generated'|'send_media', intent:string, body?:string, run_id?:int, images?:list<array{storage_path:string,mime:string}>, caption?:string}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=AiImageReplyTest && vendor/bin/phpstan analyse`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Messaging/Services/AiSuggestionService.php app/tests/Feature/Messaging/AiImageReplyTest.php
git commit -m "feat(messaging): AI auto-mode gửi ảnh SP khi khách xin (khớp tên) hoặc hỏi lại khi chưa rõ"
```

---

## Task 16: Suggest-mode attaches product images to the draft (Feature 2)

When AI runs in suggest-mode (NV approval), attach resolved product images to the draft so the NV can review, and send them on accept.

**Files:**
- Modify: `app/app/Modules/Messaging/Services/AiSuggestionService.php` (`suggest`)
- Modify: `app/app/Modules/Messaging/Http/Controllers/AiSuggestionController.php` (`accept`)
- Test: `app/tests/Feature/Messaging/AiImageSuggestTest.php` (create)

**Interfaces:**
- Consumes: `resolveProductImages` (Task 15), `IntentClassifier`, `OutboundMessageService::queueMedia`.
- Produces: `MessageDraft.suggested_attachments` = `list<array{storage_path:string,mime:string,kind:'image'}>`; `accept()` sends each as a media message after the text.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Messaging/AiImageSuggestTest.php`. Mirror `AiImageReplyTest`'s fakes (intent → image_request, visual → matched + one image), then call `suggest()` and assert the draft carries `suggested_attachments`:

```php
<?php

namespace Tests\Feature\Messaging;

// ... same imports as AiImageReplyTest ...
use CMBcoreSeller\Modules\Messaging\Models\MessageDraft;

class AiImageSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_attaches_product_images_when_image_requested(): void
    {
        Storage::fake(config('messaging.media_disk', 'local'));
        // bind fake IntentClassifier (image_request) + VisualItemSearch (matched + 1 image) exactly as AiImageReplyTest
        // seed a conversation + inbound message "cho xin ảnh áo thun"
        // register a fake AI provider/connector so suggest()'s generateReply succeeds returning a short body
        //   (reuse the existing messaging AI test harness that binds a fake AiAssistantConnector)

        $draft = app(AiSuggestionService::class)->suggest($conv, $user->id);

        $this->assertNotEmpty($draft->suggested_attachments);
        $this->assertSame('image', $draft->suggested_attachments[0]['kind']);
    }
}
```

Note: `suggest()` normally does NOT classify intent. This task adds a lightweight image check inside `suggest()`. Reuse the messaging module's existing fake-AI-connector test setup (grep `tests/Feature/Messaging` for how `AiSuggestionService::suggest` is tested today) to satisfy the `generateReply` call.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AiImageSuggestTest`
Expected: FAIL — `suggested_attachments` empty.

- [ ] **Step 3: Resolve + attach images in `suggest()`**

In `AiSuggestionService::suggest()`, before the `return MessageDraft::create([...])` (line 239), compute suggested attachments. Classify the last inbound text to detect an image request (cheap guardrail reuse), then resolve:

```php
        $suggestedAttachments = [];
        $lastInbound = $this->lastInboundBody($conv) ?? '';
        if ($lastInbound !== '') {
            $intent = $this->intentClassifier->classify($tenantId, $providerCode, $lastInbound);
            if ($intent->intent === 'image_request') {
                $media = $this->resolveProductImages($conv, $lastInbound, $tenantId);
                if ($media !== null) {
                    $suggestedAttachments = array_map(
                        fn ($img) => ['storage_path' => $img['storage_path'], 'mime' => $img['mime'], 'kind' => 'image'],
                        $media['images'],
                    );
                }
            }
        }
```

Change the draft creation `'suggested_attachments' => []` (line 244) to:

```php
            'suggested_attachments' => $suggestedAttachments,
```

- [ ] **Step 4: Send attachments on accept**

In `AiSuggestionController::accept()`, after the text `queueText(...)` (line 96-102) and before `$draft->update([...])`, send any suggested image attachments:

```php
        foreach ((array) ($draft->suggested_attachments ?? []) as $att) {
            if (! is_array($att) || empty($att['storage_path'])) {
                continue;
            }
            $this->outbound->queueMedia($conv, [
                'kind' => (string) ($att['kind'] ?? 'image'),
                'storage_path' => (string) $att['storage_path'],
                'mime' => (string) ($att['mime'] ?? 'image/jpeg'),
            ], [
                'sent_by_ai' => true,
                'message_tag' => $data['message_tag'] ?? null,
            ]);
        }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=AiImageSuggestTest && vendor/bin/phpstan analyse`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Messaging/Services/AiSuggestionService.php app/app/Modules/Messaging/Http/Controllers/AiSuggestionController.php app/tests/Feature/Messaging/AiImageSuggestTest.php
git commit -m "feat(messaging): suggest-mode đính ảnh SP vào draft + accept gửi kèm ảnh"
```

---

## Task 17: Docs + final quality gate

**Files:**
- Modify: `docs/05-api/endpoints.md`
- Modify: relevant per-page scoping / messaging AI doc (whichever documents SPEC 0035 or messaging AI) — add the business-info concept and image-reply behavior.

- [ ] **Step 1: Document the new endpoints**

Add to `docs/05-api/endpoints.md`:
- `GET /api/v1/admin/users` — now returns `ai_usage: {this_month, all_time}` per row.
- `GET /api/v1/admin/users/{id}/ai-usage` — per-user AI usage breakdown (by month, by feature).
- `PATCH /api/v1/messaging/channels/{id}/business-info` and `PATCH /api/v1/messaging/channels/business-info` (bulk) — per-page shop business info; gate `messaging.ai.config`.

- [ ] **Step 2: Note the AI behaviors**

Add a short paragraph (in the messaging AI doc) that: the AI sends product images from VisualSearch training items when a customer requests an image and the product is identified by name (else it asks which product), and that per-page business info is injected into the AI system prompt.

- [ ] **Step 3: Run the full quality gate**

Run (from `app/`):

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test
npm run lint && npm run typecheck && npm run build
```

Expected: PASS, except the ~7 pre-existing GHN/fulfillment failures noted in Global Constraints. If Pint flags formatting, run `vendor/bin/pint` and re-commit. If PHPStan reports new baseline entries, address them rather than baselining.

- [ ] **Step 4: Commit**

```bash
git add docs
git commit -m "docs: endpoints + hành vi AI (gửi ảnh SP, thông tin cửa hàng theo page, lượt AI/user)"
```

---

## Post-implementation (manual — not code steps)

- **Migrations on prod:** after deploy, run `php artisan migrate` manually (prod has `RUN_MIGRATIONS=false`). New tables/columns: `ai_usage_counters`, `messaging_account_meta.business_info`.
- **No backfill:** `ai_usage_counters` counts forward from deploy — historical AI calls are not counted. State this to the user.
- **Verify end-to-end** with the `verify` skill / real app: (1) admin user list shows AI counts after triggering an AI reply; (2) a customer message "cho xin ảnh <tên SP>" makes the bot send the training image on a Facebook page with `ai_auto_mode` on; (3) asking "shop ở đâu / SĐT?" returns the configured business info; (4) Horizon dashboard shows a worker consuming `messaging-bg`.
