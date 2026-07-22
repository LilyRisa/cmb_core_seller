# Pro Trial Registration Popup + Email Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** When a new tenant registers, and the "Chế độ trải nghiệm Pro" (Pro trial) admin toggle is on, actively offer them the trial via a modal popup (with terms acceptance) and email confirmation on activation — instead of relying on them to discover the existing self-serve button in Cài đặt > Gói.

**Architecture:** A new `pro_trial_offers` table (Billing module) marks which tenants belong to the "new signup" cohort eligible to ever see the popup, written unconditionally by a listener on the existing `TenantCreated` event (same pattern as `StartTrialSubscription`/`ReportSignupToMetaCapi`). The existing `GET billing/pro-trial/eligibility` endpoint gains a `show_popup` field computed live from this cohort flag + the existing eligibility rules. A new `POST billing/pro-trial/decline` endpoint permanently mutes the popup. Activation reuses the existing `POST billing/pro-trial/register` endpoint unchanged; `ProTrialService::register()` now fires a `ProTrialActivated` domain event after commit, which the Notifications module listens to and sends a mail notification to the tenant owner.

**Tech Stack:** Laravel 11 (PHP 8.2), PHPUnit, React 18 + TanStack Query + Ant Design (FE), no JS test runner in this repo (manual/typecheck verification only for FE).

## Global Constraints

- Money/dates: business timestamps display in `app_display_tz()` (Asia/Ho_Chi_Minh); storage stays UTC.
- Modules communicate only through `Contracts/` or domain events — the new Notifications listener imports Billing's `Events\ProTrialActivated` class only (not any `Services/` internals), matching the existing cross-module pattern (`NotifyOnNegativeOrder` importing `Orders\Events\OrderUpserted`).
- FE: icons via `@ant-design/icons` (no emoji); prefer `Radio`/`Button` groups over `Select` for small option sets (not applicable here — no such control needed).
- Terms version is never trusted from the client — always `config('billing.refund_terms_version')` server-side (existing convention, unchanged).
- No new system_setting keys needed — this feature has no its own admin-configurable knobs beyond the existing `billing.pro_trial.*`.
- Every new Eloquent model on a tenant-scoped table uses `CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant` and is queried with `withoutGlobalScope(TenantScope::class)` whenever called with an explicit `$tenantId` outside of a tenant-context request (mirrors `ProTrialGrant`'s existing usage).
- This repo has no JS test runner (see `test-verify-baseline` memory) — FE correctness is verified via `npm run lint && npm run typecheck && npm run build`, not automated tests.

---

## Task 1: `pro_trial_offers` table + `ProTrialOffer` model + `ProTrialService::offer()`/`decline()`

**Files:**
- Create: `app/app/Modules/Billing/Database/Migrations/2026_07_22_120001_create_pro_trial_offers_table.php`
- Create: `app/app/Modules/Billing/Models/ProTrialOffer.php`
- Modify: `app/app/Modules/Billing/Services/ProTrialService.php`
- Test: `app/tests/Feature/Billing/ProTrialOfferTest.php`

**Interfaces:**
- Produces: `ProTrialService::offer(int $tenantId): void` — idempotent, creates a `pro_trial_offers` row with `offered_at = now()` if none exists for that tenant yet.
- Produces: `ProTrialService::decline(int $tenantId): void` — idempotent, sets `declined_at = now()` on that tenant's offer row (no-op, no error, if no row exists).
- Produces: `CMBcoreSeller\Modules\Billing\Models\ProTrialOffer` — fields `tenant_id` (unique), `offered_at`, `declined_at` (nullable).

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Billing/ProTrialOfferTest.php`:

```php
<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Models\ProTrialOffer;
use CMBcoreSeller\Modules\Billing\Services\ProTrialService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialOfferTest extends TestCase
{
    use RefreshDatabase;

    private function offerFor(int $tenantId): ?ProTrialOffer
    {
        return ProTrialOffer::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->first();
    }

    public function test_offer_creates_row_once_and_is_idempotent(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $service = app(ProTrialService::class);

        $service->offer($tenant->getKey());
        $service->offer($tenant->getKey()); // gọi lại (retry queue) không được tạo trùng

        $this->assertSame(
            1,
            ProTrialOffer::query()->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->getKey())->count(),
        );
        $offer = $this->offerFor($tenant->getKey());
        $this->assertNotNull($offer);
        $this->assertNotNull($offer->offered_at);
        $this->assertNull($offer->declined_at);
    }

    public function test_decline_sets_declined_at(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $service = app(ProTrialService::class);
        $service->offer($tenant->getKey());

        $service->decline($tenant->getKey());

        $offer = $this->offerFor($tenant->getKey());
        $this->assertNotNull($offer->declined_at);
    }

    public function test_decline_without_prior_offer_is_noop_safe(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $service = app(ProTrialService::class);

        $service->decline($tenant->getKey()); // chưa từng offer — không được lỗi

        $this->assertSame(
            0,
            ProTrialOffer::query()->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->getKey())->count(),
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run (from `app/`): `vendor/bin/phpunit tests/Feature/Billing/ProTrialOfferTest.php`
Expected: FAIL — `Class "CMBcoreSeller\Modules\Billing\Models\ProTrialOffer" not found` (or method `offer`/`decline` not found once the class exists).

- [ ] **Step 3: Create the migration**

Create `app/app/Modules/Billing/Database/Migrations/2026_07_22_120001_create_pro_trial_offers_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pro_trial_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique(); // 1 row/tenant — cohort "tenant mới"
            $table->timestamp('offered_at');
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_trial_offers');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/app/Modules/Billing/Models/ProTrialOffer.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProTrialOffer extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'offered_at', 'declined_at'];

    protected function casts(): array
    {
        return [
            'offered_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 5: Add `offer()`/`decline()` to `ProTrialService`**

Modify `app/app/Modules/Billing/Services/ProTrialService.php`. First add this import alongside the other `use CMBcoreSeller\Modules\Billing\Models\...` lines at the top of the file:

```php
use CMBcoreSeller\Modules\Billing\Models\ProTrialOffer;
```

Then add two new public methods (place after the existing `eligibility()` method, before `register()`):

```php
    /**
     * Đánh dấu tenant thuộc diện được mời popup trải nghiệm Pro — gọi khi tenant vừa tạo
     * (listener `OfferProTrialPopup` trên `TenantCreated`). Idempotent: retry không tạo trùng.
     */
    public function offer(int $tenantId): void
    {
        ProTrialOffer::query()->withoutGlobalScope(TenantScope::class)
            ->firstOrCreate(['tenant_id' => $tenantId], ['offered_at' => now()]);
    }

    /** Tắt vĩnh viễn popup mời cho tenant này (bấm "Không, cảm ơn"). No-op an toàn nếu chưa từng offer. */
    public function decline(int $tenantId): void
    {
        ProTrialOffer::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->update(['declined_at' => now()]);
    }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Billing/ProTrialOfferTest.php`
Expected: PASS (3 tests).

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Billing/Database/Migrations/2026_07_22_120001_create_pro_trial_offers_table.php app/app/Modules/Billing/Models/ProTrialOffer.php app/app/Modules/Billing/Services/ProTrialService.php app/tests/Feature/Billing/ProTrialOfferTest.php
git commit -m "feat(billing): pro_trial_offers table + ProTrialService offer/decline"
```

---

## Task 2: `eligibility()` gains `show_popup`

**Files:**
- Modify: `app/app/Modules/Billing/Services/ProTrialService.php`
- Test: `app/tests/Feature/Billing/ProTrialEligibilityTest.php`

**Interfaces:**
- Consumes: `ProTrialOffer` model from Task 1 (`withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->first()`).
- Produces: `ProTrialService::eligibility(int $tenantId): array` now always includes key `show_popup: bool` = `eligible && offered && !declined`.

- [ ] **Step 1: Write the failing tests**

Add to `app/tests/Feature/Billing/ProTrialEligibilityTest.php` (append these three test methods inside the class, e.g. after `test_eligible_when_enabled_and_not_used`):

```php
    public function test_show_popup_false_when_never_offered(): void
    {
        // Tenant đủ điều kiện eligible NHƯNG chưa có row pro_trial_offers (tenant "cũ",
        // tạo trước khi tính năng popup này tồn tại) ⇒ không tự hiện popup.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.show_popup', false);
    }

    public function test_show_popup_true_when_offered_and_not_declined(): void
    {
        app(\CMBcoreSeller\Modules\Billing\Services\ProTrialService::class)->offer($this->tenant->getKey());

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.show_popup', true);
    }

    public function test_show_popup_false_when_declined(): void
    {
        $service = app(\CMBcoreSeller\Modules\Billing\Services\ProTrialService::class);
        $service->offer($this->tenant->getKey());
        $service->decline($this->tenant->getKey());

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', true)
            ->assertJsonPath('data.show_popup', false);
    }

    public function test_show_popup_false_when_not_eligible_even_if_offered(): void
    {
        $service = app(\CMBcoreSeller\Modules\Billing\Services\ProTrialService::class);
        $service->offer($this->tenant->getKey());
        app(\CMBcoreSeller\Modules\Settings\Services\SystemSettingService::class)->set('billing.pro_trial.enabled', false);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.eligible', false)
            ->assertJsonPath('data.show_popup', false);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/Billing/ProTrialEligibilityTest.php`
Expected: FAIL — `data.show_popup` key missing from the JSON response (assertJsonPath fails to find it / null mismatch).

- [ ] **Step 3: Implement `show_popup` in `eligibility()`**

In `app/app/Modules/Billing/Services/ProTrialService.php`, replace the existing `eligibility()` method body with:

```php
    /** @return array{eligible:bool,reason:?string,duration_days:int,ends_preview:?string,show_popup:bool} */
    public function eligibility(int $tenantId): array
    {
        $days = ProTrialSettings::durationDays();
        $base = ['eligible' => false, 'reason' => null, 'duration_days' => $days, 'ends_preview' => null, 'show_popup' => false];

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
        // Chỉ tenant đang ở gói trial (miễn phí, kể cả chưa có subscription) mới đủ điều kiện;
        // starter đã trả phí KHÔNG được trải nghiệm Pro.
        $current = $this->subscriptions->currentFor($tenantId);
        $code = $current?->plan?->code;
        if ($code !== null && $code !== Plan::CODE_TRIAL) {
            return [...$base, 'reason' => 'plan_too_high'];
        }

        $offer = ProTrialOffer::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->first();

        return [
            'eligible' => true, 'reason' => null, 'duration_days' => $days,
            'ends_preview' => Carbon::now()->addDays($days)->toIso8601String(),
            'show_popup' => $offer !== null && $offer->declined_at === null,
        ];
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/Billing/ProTrialEligibilityTest.php`
Expected: PASS (all tests, including the pre-existing ones — they don't assert on `show_popup` so are unaffected by the new key).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Billing/Services/ProTrialService.php app/tests/Feature/Billing/ProTrialEligibilityTest.php
git commit -m "feat(billing): eligibility() exposes show_popup (offered && !declined)"
```

---

## Task 3: `ProTrialActivated` event fired from `register()`

**Files:**
- Create: `app/app/Modules/Billing/Events/ProTrialActivated.php`
- Modify: `app/app/Modules/Billing/Services/ProTrialService.php`
- Test: `app/tests/Feature/Billing/ProTrialRegisterTest.php`

**Interfaces:**
- Produces: `CMBcoreSeller\Modules\Billing\Events\ProTrialActivated` — constructor `(int $tenantId, \Illuminate\Support\Carbon $grantedAt, \Illuminate\Support\Carbon $expiresAt)`, all `public readonly`. Fired via `ProTrialActivated::dispatch(...)` (standard `Dispatchable`).
- `ProTrialService::register()` signature/behavior unchanged for callers — only fires this event as a side effect after the existing transaction commits.

- [ ] **Step 1: Write the failing test**

Add to `app/tests/Feature/Billing/ProTrialRegisterTest.php` (append inside the class):

```php
    public function test_register_fires_pro_trial_activated_event(): void
    {
        \Illuminate\Support\Facades\Event::fake([\CMBcoreSeller\Modules\Billing\Events\ProTrialActivated::class]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/register', ['terms_accepted' => true, 'terms_version' => 'refund-v1'])
            ->assertOk();

        \Illuminate\Support\Facades\Event::assertDispatched(
            \CMBcoreSeller\Modules\Billing\Events\ProTrialActivated::class,
            fn ($e) => $e->tenantId === $this->tenant->getKey()
                && $e->expiresAt->diffInDays($e->grantedAt) === 30,
        );
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Billing/ProTrialRegisterTest.php --filter=test_register_fires_pro_trial_activated_event`
Expected: FAIL — `Class "CMBcoreSeller\Modules\Billing\Events\ProTrialActivated" not found`.

- [ ] **Step 3: Create the event**

Create `app/app/Modules/Billing/Events/ProTrialActivated.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

/**
 * Fired sau khi transaction đăng ký trải nghiệm Pro commit thành công (không fire bên
 * trong transaction — tránh gửi email cho một grant có thể bị rollback).
 * Notifications module nghe event này để gửi mail kích hoạt tới chủ shop.
 */
class ProTrialActivated
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly Carbon $grantedAt,
        public readonly Carbon $expiresAt,
    ) {}
}
```

- [ ] **Step 4: Fire the event in `ProTrialService::register()`**

In `app/app/Modules/Billing/Services/ProTrialService.php`, add the import:

```php
use CMBcoreSeller\Modules\Billing\Events\ProTrialActivated;
```

Then change the `register()` method — wrap the existing `DB::transaction(...)` call (keep its entire body exactly as-is) to capture its return value, fire the event after, then return:

```php
    public function register(int $tenantId, string $termsVersion): Subscription
    {
        $subscription = DB::transaction(function () use ($tenantId, $termsVersion) {
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

        ProTrialActivated::dispatch($tenantId, $subscription->current_period_start, $subscription->current_period_end);

        return $subscription;
    }
```

(Only the outer wrapping changed: `DB::transaction(...)` result is now assigned to `$subscription`, the event fires after, then it's returned — the closure body itself is untouched.)

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Billing/ProTrialRegisterTest.php`
Expected: PASS (all tests in the file, including the new one and the 3 pre-existing ones — `Event::fake` in the new test doesn't affect the others since each test method gets a fresh app instance).

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Billing/Events/ProTrialActivated.php app/app/Modules/Billing/Services/ProTrialService.php app/tests/Feature/Billing/ProTrialRegisterTest.php
git commit -m "feat(billing): fire ProTrialActivated event after successful register()"
```

---

## Task 4: `OfferProTrialPopup` listener on `TenantCreated`

**Files:**
- Create: `app/app/Modules/Billing/Listeners/OfferProTrialPopup.php`
- Modify: `app/app/Modules/Billing/BillingServiceProvider.php`
- Test: `app/tests/Feature/Billing/ProTrialOfferTest.php`

**Interfaces:**
- Consumes: `ProTrialService::offer(int $tenantId): void` (Task 1).
- Produces: nothing new consumed by later tasks — this wires the existing `TenantCreated` event (already dispatched by `AuthController::register()`) to `ProTrialService::offer()`.

- [ ] **Step 1: Write the failing test**

Add to `app/tests/Feature/Billing/ProTrialOfferTest.php` (append inside the class):

```php
    public function test_new_tenant_registration_creates_offer_row(): void
    {
        $this->seed(\CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder::class);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'New Shop',
        ])->assertCreated();

        $tenantId = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::where('name', 'New Shop')->value('id');
        $this->assertNotNull($tenantId);

        $offer = $this->offerFor($tenantId);
        $this->assertNotNull($offer, 'pro_trial_offers row phải được tạo tự động khi tenant mới đăng ký.');
        $this->assertNotNull($offer->offered_at);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Billing/ProTrialOfferTest.php --filter=test_new_tenant_registration_creates_offer_row`
Expected: FAIL — `$offer` is null (no listener wired yet).

- [ ] **Step 3: Create the listener**

Create `app/app/Modules/Billing/Listeners/OfferProTrialPopup.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Billing\Listeners;

use CMBcoreSeller\Modules\Billing\Services\ProTrialService;
use CMBcoreSeller\Modules\Tenancy\Events\TenantCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Khi tenant mới tạo ⇒ đánh dấu thuộc diện được mời popup trải nghiệm Pro (không phụ thuộc
 * `ProTrialSettings::enabled()` tại thời điểm này — việc có thực sự hiện popup hay không được
 * quyết định LIVE ở `ProTrialService::eligibility()` mỗi lần tenant tải trang).
 *
 * Idempotent (`ProTrialService::offer()` no-op nếu đã có row) — an toàn nếu event dispatch lại do retry.
 * Queue `billing` — cùng chỗ `StartTrialSubscription`/`ReportSignupToMetaCapi` đang nghe event này.
 */
class OfferProTrialPopup implements ShouldQueue
{
    public string $queue = 'billing';

    public int $tries = 3;

    public function __construct(protected ProTrialService $service) {}

    public function handle(TenantCreated $event): void
    {
        $this->service->offer((int) $event->tenant->getKey());
    }
}
```

- [ ] **Step 4: Wire the listener in `BillingServiceProvider`**

Modify `app/app/Modules/Billing/BillingServiceProvider.php` — add the import:

```php
use CMBcoreSeller\Modules\Billing\Listeners\OfferProTrialPopup;
```

And add one line in `boot()`, right after the existing `Event::listen(TenantCreated::class, StartTrialSubscription::class);`:

```php
        Event::listen(TenantCreated::class, StartTrialSubscription::class);
        Event::listen(TenantCreated::class, OfferProTrialPopup::class);
        Event::listen(InvoicePaid::class, ActivateSubscription::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Billing/ProTrialOfferTest.php`
Expected: PASS (all 4 tests in the file).

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Billing/Listeners/OfferProTrialPopup.php app/app/Modules/Billing/BillingServiceProvider.php app/tests/Feature/Billing/ProTrialOfferTest.php
git commit -m "feat(billing): wire OfferProTrialPopup listener on TenantCreated"
```

---

## Task 5: `POST billing/pro-trial/decline` endpoint

**Files:**
- Modify: `app/app/Modules/Billing/Http/Controllers/BillingController.php`
- Modify: `app/app/Modules/Billing/Http/routes.php`
- Test: `app/tests/Feature/Billing/ProTrialEligibilityTest.php`

**Interfaces:**
- Consumes: `ProTrialService::decline(int $tenantId): void` (Task 1), already bound as `protected ProTrialService $proTrial` in `BillingController`.
- Produces: `POST /api/v1/billing/pro-trial/decline` → `{ "data": { "declined": true } }`, requires `billing.manage` permission, tenant-scoped (same middleware group as the rest of `billing/*`).

- [ ] **Step 1: Write the failing test**

Add to `app/tests/Feature/Billing/ProTrialEligibilityTest.php` (append inside the class):

```php
    public function test_decline_endpoint_flips_show_popup_off(): void
    {
        app(\CMBcoreSeller\Modules\Billing\Services\ProTrialService::class)->offer($this->tenant->getKey());

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/decline')
            ->assertOk()
            ->assertJsonPath('data.declined', true);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/billing/pro-trial/eligibility')
            ->assertOk()
            ->assertJsonPath('data.show_popup', false);
    }

    public function test_decline_endpoint_requires_billing_manage(): void
    {
        $viewer = \CMBcoreSeller\Models\User::factory()->create();
        $this->tenant->users()->attach($viewer->getKey(), ['role' => \CMBcoreSeller\Modules\Tenancy\Enums\Role::Accountant->value]);

        $this->actingAs($viewer)->withHeaders($this->h())
            ->postJson('/api/v1/billing/pro-trial/decline')
            ->assertForbidden();
    }
```

(`Role::Accountant` — verified to exist in `app/app/Modules/Tenancy/Enums/Role.php`; its permission list includes `billing.view` but not `billing.manage`, so this correctly exercises the 403 path.)

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Billing/ProTrialEligibilityTest.php --filter=test_decline_endpoint`
Expected: FAIL — 404 (route doesn't exist yet).

- [ ] **Step 3: Add the controller action**

In `app/app/Modules/Billing/Http/Controllers/BillingController.php`, add a new method right after `proTrialRegister()`:

```php
    /** POST /billing/pro-trial/decline — tắt vĩnh viễn popup mời trải nghiệm Pro cho tenant này. */
    public function proTrialDecline(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('billing.manage'), 403, 'Chỉ chủ shop được từ chối lời mời trải nghiệm.');
        $tenantId = (int) $this->tenant->id();
        $this->proTrial->decline($tenantId);

        return response()->json(['data' => ['declined' => true]]);
    }
```

- [ ] **Step 4: Add the route**

In `app/app/Modules/Billing/Http/routes.php`, add right after the `pro-trial/register` route:

```php
    Route::get('pro-trial/eligibility', [BillingController::class, 'proTrialEligibility'])->name('billing.pro-trial.eligibility');
    Route::post('pro-trial/register', [BillingController::class, 'proTrialRegister'])
        ->middleware('throttle:10,1')->name('billing.pro-trial.register');
    Route::post('pro-trial/decline', [BillingController::class, 'proTrialDecline'])
        ->middleware('throttle:10,1')->name('billing.pro-trial.decline');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Billing/ProTrialEligibilityTest.php`
Expected: PASS (all tests in the file).

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Billing/Http/Controllers/BillingController.php app/app/Modules/Billing/Http/routes.php app/tests/Feature/Billing/ProTrialEligibilityTest.php
git commit -m "feat(billing): POST pro-trial/decline endpoint"
```

---

## Task 6: Activation email — `ProTrialActivatedNotification` + `SendProTrialActivatedEmail` listener

**Files:**
- Create: `app/app/Modules/Notifications/Notifications/ProTrialActivatedNotification.php`
- Create: `app/resources/views/mail/pro-trial-activated.blade.php`
- Create: `app/app/Modules/Notifications/Listeners/SendProTrialActivatedEmail.php`
- Modify: `app/app/Modules/Notifications/NotificationsServiceProvider.php`
- Test: `app/tests/Feature/Notifications/ProTrialActivatedEmailTest.php`

**Interfaces:**
- Consumes: `CMBcoreSeller\Modules\Billing\Events\ProTrialActivated` (Task 3) — cross-module event import, matches existing pattern of `NotifyOnNegativeOrder` consuming `Orders\Events\OrderUpserted`.
- Consumes: `$tenant->users()->wherePivot('role', Role::Owner->value)->first()` — exact pattern already used in `app/app/Modules/Tenancy/Listeners/ReportSignupToMetaCapi.php:31`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/Notifications/ProTrialActivatedEmailTest.php`:

```php
<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Events\ProTrialActivated;
use CMBcoreSeller\Modules\Notifications\Notifications\ProTrialActivatedNotification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProTrialActivatedEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_trial_activated_event_emails_the_tenant_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $grantedAt = Carbon::now();
        $expiresAt = $grantedAt->copy()->addDays(30);
        event(new ProTrialActivated($tenant->getKey(), $grantedAt, $expiresAt));

        Notification::assertSentTo(
            $owner,
            ProTrialActivatedNotification::class,
            fn ($n) => $n->expiresAt->isSameDay($expiresAt),
        );
    }

    public function test_no_owner_no_crash(): void
    {
        Notification::fake();

        $tenant = Tenant::create(['name' => 'Shop No Owner']);
        $grantedAt = Carbon::now();

        event(new ProTrialActivated($tenant->getKey(), $grantedAt, $grantedAt->copy()->addDays(30)));

        Notification::assertNothingSent();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Notifications/ProTrialActivatedEmailTest.php`
Expected: FAIL — `Class "CMBcoreSeller\Modules\Notifications\Notifications\ProTrialActivatedNotification" not found`.

- [ ] **Step 3: Create the notification class**

Create `app/app/Modules/Notifications/Notifications/ProTrialActivatedNotification.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Email xác nhận kích hoạt gói Pro trải nghiệm — gửi khi `ProTrialService::register()` thành công
 * (dù qua popup mời tự động hay nút tự phục vụ ở Cài đặt > Gói, cùng một code path).
 *
 * Queue `notifications`, tries 3, backoff 10/60/300s (đồng bộ các notification khác trong module).
 */
class ProTrialActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Carbon $grantedAt, public Carbon $expiresAt)
    {
        $this->queue = (string) config('notifications.queue', 'notifications');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = (string) system_setting('notifications.brand_name', config('notifications.brand.name', 'CMBcoreSeller'));
        $appUrl = (string) config('notifications.frontend_url', config('app.url'));

        return (new MailMessage)
            ->subject("[{$brand}] Bạn đã được kích hoạt gói Pro trải nghiệm")
            ->view('notifications::pro-trial-activated', [
                'user' => $notifiable,
                'grantedAt' => $this->grantedAt->clone()->timezone(app_display_tz()),
                'expiresAt' => $this->expiresAt->clone()->timezone(app_display_tz()),
                'appUrl' => rtrim($appUrl, '/').'/',
            ]);
    }
}
```

- [ ] **Step 4: Create the mail view**

Create `app/resources/views/mail/pro-trial-activated.blade.php`:

```blade
@extends('notifications::layout')

@php
    $primary = config('notifications.brand.primary_color', '#10B981');
    $accent = config('notifications.brand.accent_color', '#059669');
    $brand = config('notifications.brand.name', 'CMBcoreSeller');
@endphp

@section('title', 'Gói Pro trải nghiệm đã kích hoạt')
@section('preheader', 'Bạn đang dùng thử toàn bộ tính năng gói Pro miễn phí đến ' . $expiresAt->format('d/m/Y') . '.')

@section('content')
    <p style="margin:0 0 12px 0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:{{ $accent }};">
        Trải nghiệm Pro đã kích hoạt
    </p>
    <h1 class="h1-mob" style="margin:0 0 20px 0;font-size:28px;line-height:36px;font-weight:700;color:#0F172A;letter-spacing:-0.02em;">
        Chúc mừng, bạn đang dùng thử gói Pro! 🎉
    </h1>

    <p style="margin:0 0 16px 0;">Xin chào <strong>{{ $user->name }}</strong>,</p>
    <p style="margin:0 0 24px 0;color:#374151;">
        Gói <strong>Pro trải nghiệm</strong> của bạn đã được kích hoạt từ
        <strong>{{ $grantedAt->format('d/m/Y') }}</strong> đến hết
        <strong>{{ $expiresAt->format('d/m/Y') }}</strong> — toàn bộ tính năng Pro (nhắn tin AI,
        quảng cáo, kế toán nâng cao, báo cáo lợi nhuận…) đều mở khoá miễn phí trong thời gian này.
        Khi hết hạn, hệ thống sẽ tự động chuyển về gói trước đó — không mất phí, không cần huỷ.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="cta-mob" style="margin:0 0 28px 0;">
        <tr>
            <td align="center" style="border-radius:10px;background:linear-gradient(135deg,{{ $primary }} 0%, {{ $accent }} 100%);background-color:{{ $primary }};">
                <a href="{{ $appUrl }}"
                   style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;border-radius:10px;line-height:20px;">
                    Mở bảng điều khiển →
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:13px;color:#6B7280;">
        Có thắc mắc? Trả lời email này không tới được — vui lòng liên hệ
        <a href="mailto:{{ config('notifications.brand.support_email') }}" style="color:{{ $accent }};text-decoration:underline;">{{ config('notifications.brand.support_email') }}</a>
        và đội ngũ chúng tôi sẽ phản hồi trong vòng 24 giờ.
    </p>
@endsection
```

- [ ] **Step 5: Create the listener**

Create `app/app/Modules/Notifications/Listeners/SendProTrialActivatedEmail.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Events\ProTrialActivated;
use CMBcoreSeller\Modules\Notifications\Notifications\ProTrialActivatedNotification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Nghe `Billing\Events\ProTrialActivated` ⇒ gửi mail xác nhận kích hoạt tới chủ shop.
 * Kênh giao tiếp hợp lệ giữa module (event, không đụng Services nội bộ Billing).
 *
 * Queue `notifications`, tries 3.
 */
class SendProTrialActivatedEmail implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function handle(ProTrialActivated $event): void
    {
        $tenant = Tenant::query()->find($event->tenantId);
        if ($tenant === null) {
            return;
        }

        /** @var User|null $owner */
        $owner = $tenant->users()->wherePivot('role', Role::Owner->value)->first();
        if ($owner === null || ! $owner->email) {
            return;
        }

        $owner->notify(new ProTrialActivatedNotification($event->grantedAt, $event->expiresAt));
    }
}
```

- [ ] **Step 6: Wire the listener**

Modify `app/app/Modules/Notifications/NotificationsServiceProvider.php` — add imports:

```php
use CMBcoreSeller\Modules\Billing\Events\ProTrialActivated;
use CMBcoreSeller\Modules\Notifications\Listeners\SendProTrialActivatedEmail;
```

And add one line in `boot()`, right after `Event::listen(Verified::class, SendWelcomeEmailOnVerified::class);`:

```php
        Event::listen(Verified::class, SendWelcomeEmailOnVerified::class);
        Event::listen(ProTrialActivated::class, SendProTrialActivatedEmail::class);
```

- [ ] **Step 7: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Notifications/ProTrialActivatedEmailTest.php`
Expected: PASS (both tests).

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Notifications/Notifications/ProTrialActivatedNotification.php app/resources/views/mail/pro-trial-activated.blade.php app/app/Modules/Notifications/Listeners/SendProTrialActivatedEmail.php app/app/Modules/Notifications/NotificationsServiceProvider.php app/tests/Feature/Notifications/ProTrialActivatedEmailTest.php
git commit -m "feat(notifications): email tenant owner when Pro trial activates"
```

---

## Task 7: FE — `lib/billing.tsx` types + `useDeclineProTrial` hook

**Files:**
- Modify: `app/resources/js/lib/billing.tsx`

**Interfaces:**
- Produces: `ProTrialEligibility.show_popup: boolean` (extends existing interface).
- Produces: `useDeclineProTrial(): UseMutationResult` — `mutate()`/`mutateAsync()` with no args, `POST /billing/pro-trial/decline`, invalidates the `['billing', tenantId, 'pro-trial-eligibility']` query on success.

- [ ] **Step 1: Extend the `ProTrialEligibility` interface**

In `app/resources/js/lib/billing.tsx`, change:

```ts
export interface ProTrialEligibility {
    eligible: boolean;
    reason: string | null;
    duration_days: number;
    ends_preview: string | null;
}
```

to:

```ts
export interface ProTrialEligibility {
    eligible: boolean;
    reason: string | null;
    duration_days: number;
    ends_preview: string | null;
    show_popup: boolean;
}
```

- [ ] **Step 2: Add the `useDeclineProTrial` hook**

In `app/resources/js/lib/billing.tsx`, add right after the existing `useRegisterProTrial` function:

```ts
/** Tắt vĩnh viễn popup mời trải nghiệm Pro cho tenant này (nút "Không, cảm ơn"). */
export function useDeclineProTrial() {
    const tenantId = useCurrentTenantId();
    const api = useScopedApi();
    const qc = useQueryClient();

    return useMutation({
        mutationFn: async () => {
            const { data } = await api!.post<{ data: { declined: boolean } }>('/billing/pro-trial/decline');
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['billing', tenantId, 'pro-trial-eligibility'] }),
    });
}
```

- [ ] **Step 3: Verify TypeScript compiles**

Run (from `app/`): `npm run typecheck`
Expected: no new errors. (This alone won't catch consumers yet since `ProTrialOfferModal` doesn't exist until Task 8 — this step just confirms `billing.tsx` itself is syntactically/typewise correct.)

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/lib/billing.tsx
git commit -m "feat(billing-fe): show_popup field + useDeclineProTrial hook"
```

---

## Task 8: FE — `ProTrialOfferModal` component

**Files:**
- Create: `app/resources/js/components/billing/ProTrialOfferModal.tsx`

**Interfaces:**
- Consumes: `useProTrialEligibility()`, `useRegisterProTrial()`, `useDeclineProTrial()`, `REFUND_TERMS_VERSION` from `@/lib/billing` (Task 7 + pre-existing).
- Consumes: `RefundPolicyModal` from `@/components/billing/RefundPolicyModal` (pre-existing, props `{ open, mode, loading?, onCancel, onAccept }`).
- Consumes: `useCan` from `@/lib/tenant`, `formatDate` from `@/lib/format`, `errorMessage` from `@/lib/api` (all pre-existing).
- Produces: `export function ProTrialOfferModal(): JSX.Element | null` — self-contained, no props, renders nothing when not applicable.

- [ ] **Step 1: Create the component**

Create `app/resources/js/components/billing/ProTrialOfferModal.tsx`:

```tsx
import { useState } from 'react';
import { App as AntApp, Button, Modal, Typography } from 'antd';
import { CrownOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { formatDate } from '@/lib/format';
import { REFUND_TERMS_VERSION, useDeclineProTrial, useProTrialEligibility, useRegisterProTrial } from '@/lib/billing';
import { useCan } from '@/lib/tenant';
import RefundPolicyModal from '@/components/billing/RefundPolicyModal';

/**
 * Popup mời tenant MỚI đăng ký trải nghiệm Pro — mount 1 lần ở AppLayout (cùng chỗ
 * `AnnouncementPopup`), hiện ở mọi trang sau đăng nhập khi `eligibility.show_popup === true`.
 *
 * "Không, cảm ơn" → tắt vĩnh viễn (gọi decline). Đóng bằng X/ESC → chỉ đóng phiên này,
 * lần tải trang/đăng nhập sau sẽ hiện lại (không gọi API gì).
 */
export function ProTrialOfferModal() {
    const { message } = AntApp.useApp();
    const canManage = useCan('billing.manage');
    const eligibilityQ = useProTrialEligibility();
    const registerProTrial = useRegisterProTrial();
    const decline = useDeclineProTrial();

    const [dismissed, setDismissed] = useState(false);
    const [termsOpen, setTermsOpen] = useState(false);

    const showOffer = canManage && !dismissed && !!eligibilityQ.data?.show_popup;
    if (!showOffer && !termsOpen) return null;

    const days = eligibilityQ.data?.duration_days ?? 30;
    const endsPreview = eligibilityQ.data?.ends_preview ? formatDate(eligibilityQ.data.ends_preview, false) : '';

    const acceptTrial = async () => {
        try {
            await registerProTrial.mutateAsync(REFUND_TERMS_VERSION);
            setTermsOpen(false);
            message.success('Đã kích hoạt gói Pro trải nghiệm!');
        } catch (e) {
            message.error(errorMessage(e));
        }
    };

    const declineOffer = () => {
        decline.mutate(undefined, {
            onSuccess: () => message.info('Đã ẩn lời mời — bạn vẫn có thể đăng ký ở Cài đặt > Gói.'),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    return (
        <>
            <Modal
                open={showOffer && !termsOpen}
                centered
                maskClosable={false}
                title={<><CrownOutlined /> Bạn được tặng {days} ngày dùng thử Pro!</>}
                footer={[
                    <Button key="decline" onClick={declineOffer} loading={decline.isPending}>Không, cảm ơn</Button>,
                    <Button key="accept" type="primary" icon={<CrownOutlined />} onClick={() => setTermsOpen(true)}>Đồng ý kích hoạt</Button>,
                ]}
                onCancel={() => setDismissed(true)}
            >
                <Typography.Paragraph>
                    Kích hoạt ngay để dùng thử toàn bộ tính năng gói Pro đến hết ngày{' '}
                    <strong>{endsPreview}</strong> — hoàn toàn miễn phí, tự động về gói hiện tại khi hết hạn.
                </Typography.Paragraph>
            </Modal>
            <RefundPolicyModal
                open={termsOpen}
                mode="trial"
                loading={registerProTrial.isPending}
                onCancel={() => setTermsOpen(false)}
                onAccept={acceptTrial}
            />
        </>
    );
}
```

- [ ] **Step 2: Verify TypeScript compiles**

Run (from `app/`): `npm run typecheck`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/components/billing/ProTrialOfferModal.tsx
git commit -m "feat(billing-fe): ProTrialOfferModal component"
```

---

## Task 9: Mount in `AppLayout` + full verification pass

**Files:**
- Modify: `app/resources/js/components/AppLayout.tsx`

- [ ] **Step 1: Import and mount the component**

In `app/resources/js/components/AppLayout.tsx`, add the import alongside the other component imports (near `AnnouncementPopup`):

```tsx
import { AnnouncementPopup } from '@/components/AnnouncementPopup';
import { ProTrialOfferModal } from '@/components/billing/ProTrialOfferModal';
```

Then in the render, add it right after `<AnnouncementPopup />`:

```tsx
            {/* SPEC 0037 — popup thông báo admin (giữa màn hình, 1 lần/tab). */}
            <AnnouncementPopup />
            {/* Popup mời tenant mới đăng ký trải nghiệm Pro — hiện live theo eligibility.show_popup. */}
            <ProTrialOfferModal />
```

- [ ] **Step 2: Frontend verification**

Run (from `app/`):
```bash
npm run lint
npm run typecheck
npm run build
```
Expected: all three pass with no new errors/warnings attributable to these changes.

- [ ] **Step 3: Full backend verification**

Run (from `app/`):
```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test
```
Expected: Pint clean; PHPStan at the existing baseline count (see `docs/01-architecture` / project convention — 0 *new* errors, existing baseline unaffected); `php artisan test` green except the pre-existing unrelated failures documented in the `test-verify-baseline` memory (price-drift assertions in `BillingApiTest`/`BillingTrialTest`/`SePayWebhookTest`, and 7 pre-existing GHN/fulfillment failures on `main`) — no *new* failures.

- [ ] **Step 4: Manual smoke check (optional but recommended before deploy)**

With `composer dev` running (from `app/`): register a brand-new account while `billing.pro_trial.enabled=true` in `/admin/plans` → confirm the popup appears on first authenticated page load with the correct duration/date, "Đồng ý kích hoạt" opens the terms modal and activating flips the subscription to Pro + closes both modals, and "Không, cảm ơn" hides it permanently (reload confirms it doesn't come back). Confirm the activation email lands (check `storage/logs` or the configured mail driver in dev — e.g. Mailpit/log driver).

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/components/AppLayout.tsx
git commit -m "feat(billing-fe): mount ProTrialOfferModal in AppLayout"
```

---

## Deploy note

This feature adds one migration (`2026_07_22_120001_create_pro_trial_offers_table.php`). Per [[prod-ops-ssh-and-deploy]], prod does **not** auto-migrate (`RUN_MIGRATIONS=false`) — after deploying the built image, run `docker exec cmb_seller-app-1 php artisan migrate --force` and verify with `migrate:status`.
