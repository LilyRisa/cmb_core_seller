# Facebook Ad Creation — Plan 4: Publish (job + endpoint) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`).

**Goal:** Turn a saved `AdDraft` into live Facebook entities (Campaign→AdSet→Ad, status PAUSED) via a resume-first idempotent Horizon job, exposed by a gated `POST ad-drafts/{id}/publish` endpoint.

**Architecture:** A pure `AdDraftSpecMapper` translates the draft `payload` into the Plan-1 spec DTOs. A `PublishAdDraft` job resolves the account/connector/token (via `AdsRegistry`, like `SyncAdInsights`), then creates the three levels **resume-first** (skips a level whose external id is already stored) and transitions `status`. The controller gates on permission + the connector's `ads.create` capability and dispatches the job. Publishing of an existing **Page post** works fully here; the "new creative" path consumes an `image_hash` that must already be in the payload (media upload is a separate follow-up).

**Tech Stack:** Laravel 11, Horizon queue `marketing-publish`, `AdsRegistry` + `AdsWriteConnector` + DTOs (Plan 1), PHPUnit + `Http::fake`/`Queue::fake`, Pint, Larastan L5.

**Conventions:** Commands from `app/`. Mirror `Jobs/SyncAdInsights.php` (queue, `ShouldBeUnique`, `withoutGlobalScope(TenantScope::class)`) and its test `tests/Feature/Marketing/SyncAdInsightsTest.php` (sets `config(['integrations.ads' => ['facebook']])` + `forgetInstance(AdsRegistry::class)`).

---

### Task 1: `AdDraftSpecMapper` (payload → spec DTOs)

**Files:**
- Create: `app/app/Modules/Marketing/Services/AdDraftSpecMapper.php`
- Test: `app/tests/Feature/Marketing/AdDraftSpecMapperTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftSpecMapper;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdDraftSpecMapperTest extends TestCase
{
    use RefreshDatabase;

    private function draft(array $payload): AdDraft
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));

        return AdDraft::create([
            'ad_account_id' => 11, 'name' => 'Tết', 'objective' => 'messages',
            'campaign_external_id' => 'C1', 'adset_external_id' => 'AS1',
            'payload' => $payload,
        ]);
    }

    public function test_maps_campaign_with_none_special_category(): void
    {
        $spec = app(AdDraftSpecMapper::class)->campaign($this->draft([]));

        $this->assertSame('messages', $spec->objective);
        $this->assertSame('Tết', $spec->name);
        $this->assertSame(['NONE'], $spec->specialAdCategories);
    }

    public function test_maps_adset_budget_targeting_page(): void
    {
        $draft = $this->draft([
            'budget' => ['daily_major' => 150000],
            'targeting' => ['geo_locations' => ['countries' => ['VN']]],
            'creative' => ['page_id' => '123'],
        ]);

        $spec = app(AdDraftSpecMapper::class)->adSet($draft, 'VND');

        $this->assertSame('C1', $spec->campaignExternalId);
        $this->assertSame(150000, $spec->dailyBudgetMajor);
        $this->assertSame('VND', $spec->currency);
        $this->assertSame(['geo_locations' => ['countries' => ['VN']]], $spec->targeting);
        $this->assertSame('123', $spec->pageId);
    }

    public function test_maps_ad_from_existing_page_post(): void
    {
        $draft = $this->draft(['creative' => ['page_id' => '123', 'page_post_id' => '123_456', 'cta' => 'MESSAGE_PAGE']]);

        $spec = app(AdDraftSpecMapper::class)->ad($draft);

        $this->assertSame('AS1', $spec->adSetExternalId);
        $this->assertSame('123', $spec->pageId);
        $this->assertSame('123_456', $spec->pagePostId);
        $this->assertSame('MESSAGE_PAGE', $spec->cta);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AdDraftSpecMapperTest` → FAIL.

- [ ] **Step 3: Implement** `AdDraftSpecMapper.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;

/**
 * Translates a wizard draft's payload into the connector spec DTOs. Pure (no I/O).
 * Defensive reads — drafts may be partial; Graph rejects truly invalid specs at
 * publish (surfaced as the draft's last_error).
 */
class AdDraftSpecMapper
{
    public function campaign(AdDraft $draft): CampaignSpecDTO
    {
        return new CampaignSpecDTO(
            objective: (string) ($draft->objective ?? 'traffic'),
            name: (string) ($draft->name ?? 'Chiến dịch'),
            specialAdCategories: ['NONE'],   // Graph wants ['NONE'] for "no special category"
        );
    }

    public function adSet(AdDraft $draft, string $currency): AdSetSpecDTO
    {
        $p = $draft->payload ?? [];
        $creative = (array) ($p['creative'] ?? []);

        return new AdSetSpecDTO(
            name: (string) ($draft->name ?? 'Chiến dịch').' — nhóm',
            campaignExternalId: (string) $draft->campaign_external_id,
            objective: (string) ($draft->objective ?? 'traffic'),
            dailyBudgetMajor: (int) (($p['budget']['daily_major'] ?? 0)),
            currency: $currency,
            targeting: (array) ($p['targeting'] ?? []),
            pageId: isset($creative['page_id']) ? (string) $creative['page_id'] : null,
            startTime: isset($p['schedule']['start_time']) ? (string) $p['schedule']['start_time'] : null,
        );
    }

    public function ad(AdDraft $draft): AdSpecDTO
    {
        $c = (array) (($draft->payload ?? [])['creative'] ?? []);

        return new AdSpecDTO(
            name: (string) ($draft->name ?? 'Chiến dịch').' — quảng cáo',
            adSetExternalId: (string) $draft->adset_external_id,
            pageId: (string) ($c['page_id'] ?? ''),
            pagePostId: isset($c['page_post_id']) ? (string) $c['page_post_id'] : null,
            imageHash: isset($c['image_hash']) ? (string) $c['image_hash'] : null,
            videoId: isset($c['video_id']) ? (string) $c['video_id'] : null,
            primaryText: isset($c['primary_text']) ? (string) $c['primary_text'] : null,
            headline: isset($c['headline']) ? (string) $c['headline'] : null,
            linkUrl: isset($c['link_url']) ? (string) $c['link_url'] : null,
            cta: (string) ($c['cta'] ?? 'LEARN_MORE'),
        );
    }
}
```

- [ ] **Step 4: Run** `php artisan test --filter=AdDraftSpecMapperTest` → PASS (3 tests).

- [ ] **Step 5: Quality + commit**
```
# from app/
vendor/bin/pint app/Modules/Marketing/Services/AdDraftSpecMapper.php tests/Feature/Marketing/AdDraftSpecMapperTest.php
vendor/bin/phpstan analyse app/Modules/Marketing/Services/AdDraftSpecMapper.php
```
```
git add app/app/Modules/Marketing/Services/AdDraftSpecMapper.php app/tests/Feature/Marketing/AdDraftSpecMapperTest.php
git commit -m "feat(ads): AdDraftSpecMapper (draft payload -> connector spec DTOs)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: `PublishAdDraft` job (resume-first, idempotent)

**Files:**
- Create: `app/app/Modules/Marketing/Jobs/PublishAdDraft.php`
- Test: `app/tests/Feature/Marketing/PublishAdDraftTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Jobs\PublishAdDraft;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftSpecMapper;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublishAdDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    private function seed(array $overrides = []): AdDraft
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));
        AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1',
            'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK',
        ]);

        return AdDraft::create(array_merge([
            'ad_account_id' => AdAccount::query()->value('id'),
            'name' => 'Tết', 'objective' => 'messages',
            'payload' => [
                'budget' => ['daily_major' => 150000],
                'targeting' => ['geo_locations' => ['countries' => ['VN']]],
                'creative' => ['page_id' => '123', 'page_post_id' => '123_456', 'cta' => 'MESSAGE_PAGE'],
            ],
        ], $overrides));
    }

    private function run(AdDraft $draft): void
    {
        (new PublishAdDraft($draft->id))->handle(app(AdsRegistry::class), app(AdDraftSpecMapper::class));
    }

    public function test_publishes_three_levels_and_marks_published(): void
    {
        Http::fake([
            'graph.facebook.com/*/campaigns' => Http::response(['id' => 'C9'], 200),
            'graph.facebook.com/*/adsets' => Http::response(['id' => 'AS9'], 200),
            'graph.facebook.com/*/ads' => Http::response(['id' => 'AD9'], 200),
        ]);

        $draft = $this->seed();
        $this->run($draft);

        $draft->refresh();
        $this->assertSame('published', $draft->status);
        $this->assertSame('C9', $draft->campaign_external_id);
        $this->assertSame('AS9', $draft->adset_external_id);
        $this->assertSame('AD9', $draft->ad_external_id);
    }

    public function test_resume_skips_already_created_levels(): void
    {
        Http::fake([
            'graph.facebook.com/*/adsets' => Http::response(['id' => 'AS9'], 200),
            'graph.facebook.com/*/ads' => Http::response(['id' => 'AD9'], 200),
        ]);

        $draft = $this->seed(['campaign_external_id' => 'C_EXISTING']);
        $this->run($draft);

        $draft->refresh();
        $this->assertSame('published', $draft->status);
        $this->assertSame('C_EXISTING', $draft->campaign_external_id); // unchanged
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/campaigns')); // not recreated
    }

    public function test_failure_marks_failed_and_records_error(): void
    {
        Http::fake([
            'graph.facebook.com/*/campaigns' => Http::response(['id' => 'C9'], 200),
            'graph.facebook.com/*/adsets' => Http::response(['error' => ['message' => 'bad targeting']], 400),
        ]);

        $draft = $this->seed();
        $this->run($draft);

        $draft->refresh();
        $this->assertSame('failed', $draft->status);
        $this->assertSame('C9', $draft->campaign_external_id);   // partial progress kept (resume later)
        $this->assertNull($draft->adset_external_id);
        $this->assertNotNull($draft->last_error);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=PublishAdDraftTest` → FAIL.

- [ ] **Step 3: Implement** `PublishAdDraft.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftSpecMapper;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Publish one AdDraft to Facebook: create Campaign→AdSet→Ad (PAUSED), resume-first
 * (skip a level whose external id is already stored ⇒ idempotent on retry). On any
 * error the draft is marked `failed` with the message; partial ids are kept so a
 * re-publish resumes from the failed level. No auto-retry (`tries = 1`).
 */
class PublishAdDraft implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 600;

    public function __construct(public int $draftId)
    {
        $this->onQueue('marketing-publish');
    }

    public function uniqueId(): string
    {
        return "publish-draft:{$this->draftId}";
    }

    public function handle(AdsRegistry $registry, AdDraftSpecMapper $mapper): void
    {
        /** @var AdDraft|null $draft */
        $draft = AdDraft::withoutGlobalScope(TenantScope::class)->find($this->draftId);
        if (! $draft) {
            return;
        }
        /** @var AdAccount|null $account */
        $account = AdAccount::withoutGlobalScope(TenantScope::class)->find($draft->ad_account_id);

        $connector = $account && $registry->has($account->provider) ? $registry->for($account->provider) : null;
        if (! $account || ! $connector instanceof AdsWriteConnector || ! $connector->supports('ads.create')) {
            $draft->forceFill(['status' => AdDraft::STATUS_FAILED, 'last_error' => 'Tài khoản/quảng cáo không hỗ trợ tạo.'])->save();

            return;
        }

        $token = (string) $account->access_token;
        $acc = $account->external_account_id;
        $draft->forceFill(['status' => AdDraft::STATUS_PUBLISHING, 'last_error' => null])->save();

        try {
            if (! $draft->campaign_external_id) {
                $draft->campaign_external_id = $connector->createCampaign($token, $acc, $mapper->campaign($draft));
                $draft->save();
            }
            if (! $draft->adset_external_id) {
                $draft->adset_external_id = $connector->createAdSet($token, $acc, $mapper->adSet($draft, (string) $account->currency));
                $draft->save();
            }
            if (! $draft->ad_external_id) {
                $draft->ad_external_id = $connector->createAd($token, $acc, $mapper->ad($draft));
                $draft->save();
            }
            $draft->forceFill(['status' => AdDraft::STATUS_PUBLISHED])->save();
        } catch (\Throwable $e) {
            $draft->forceFill(['status' => AdDraft::STATUS_FAILED, 'last_error' => $e->getMessage()])->save();
        }
    }
}
```

- [ ] **Step 4: Run** `php artisan test --filter=PublishAdDraftTest` → PASS (3 tests).

- [ ] **Step 5: Quality + commit**
```
# from app/
vendor/bin/pint app/Modules/Marketing/Jobs/PublishAdDraft.php tests/Feature/Marketing/PublishAdDraftTest.php
vendor/bin/phpstan analyse app/Modules/Marketing/Jobs/PublishAdDraft.php
```
```
git add app/app/Modules/Marketing/Jobs/PublishAdDraft.php app/tests/Feature/Marketing/PublishAdDraftTest.php
git commit -m "feat(ads): PublishAdDraft job (resume-first idempotent create + status)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Publish endpoint + capability gating

**Files:**
- Modify: `app/app/Modules/Marketing/Http/Controllers/AdDraftController.php` (add `publish`)
- Modify: `app/app/Modules/Marketing/Http/routes.php` (add route)
- Test: `app/tests/Feature/Marketing/AdDraftPublishApiTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Jobs\PublishAdDraft;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdDraftPublishApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
        $this->tenant = Tenant::create(['name' => 'AdShop']);
    }

    private function user(Role $role): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => $role->value]);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function draft(): AdDraft
    {
        app(CurrentTenant::class)->set($this->tenant);
        AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'X']);

        return AdDraft::create(['ad_account_id' => AdAccount::query()->value('id'), 'name' => 'T', 'objective' => 'messages', 'payload' => []]);
    }

    public function test_owner_publish_queues_job_and_sets_publishing(): void
    {
        Queue::fake();
        $draft = $this->draft();

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-drafts/{$draft->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.queued', true)
            ->assertJsonPath('data.status', 'publishing');

        Queue::assertPushed(PublishAdDraft::class, fn ($j) => $j->draftId === (int) $draft->id);
        $this->assertSame('publishing', $draft->fresh()->status);
    }

    public function test_staff_forbidden_to_publish(): void
    {
        Queue::fake();
        $draft = $this->draft();

        $this->actingAs($this->user(Role::StaffOrder))->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-drafts/{$draft->id}/publish")
            ->assertForbidden();
        Queue::assertNothingPushed();
    }

    public function test_publish_422_when_ads_create_not_supported(): void
    {
        Queue::fake();
        config(['integrations.ads' => []]);          // no ads provider registered
        $this->app->forgetInstance(AdsRegistry::class);
        $draft = $this->draft();

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-drafts/{$draft->id}/publish")
            ->assertStatus(422);
        Queue::assertNothingPushed();
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AdDraftPublishApiTest` → FAIL.

- [ ] **Step 3: Implement.** Add imports to `AdDraftController.php`:
```php
use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Modules\Marketing\Jobs\PublishAdDraft;
```
Add the `publish` action (after `destroy`):
```php
    /** POST ad-drafts/{id}/publish — enqueue create-on-Facebook (gated by ads.create + ads_management). */
    public function publish(int $id, AdsRegistry $registry): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $draft = AdDraft::query()->findOrFail($id);
        $account = AdAccount::query()->findOrFail($draft->ad_account_id);

        $connector = $registry->has($account->provider) ? $registry->for($account->provider) : null;
        abort_unless(
            $connector instanceof AdsWriteConnector && $connector->supports('ads.create'),
            422,
            'Tạo quảng cáo chưa được bật cho tài khoản này (cần quyền ads_management + Standard Access).',
        );

        PublishAdDraft::dispatch($draft->id);
        $draft->forceFill(['status' => AdDraft::STATUS_PUBLISHING, 'last_error' => null])->save();

        return response()->json(['data' => ['queued' => true, 'status' => 'publishing']]);
    }
```
Add the route in `routes.php` inside the marketing group (after the draft routes):
```php
        Route::post('ad-drafts/{id}/publish', [AdDraftController::class, 'publish'])->whereNumber('id')->name('marketing.ad-drafts.publish');
```

- [ ] **Step 4: Run** `php artisan test --filter=AdDraftPublishApiTest` → PASS (3 tests). Regression: `php artisan test --filter='AdDraftApiTest|PublishAdDraftTest|AdDraftSpecMapperTest'` green.

- [ ] **Step 5: FINAL gate + commit**
```
# from app/
vendor/bin/pint app/Modules/Marketing/Http app/Modules/Marketing/Jobs app/Modules/Marketing/Services tests/Feature/Marketing/AdDraftPublishApiTest.php
vendor/bin/phpstan analyse app/Modules/Marketing
php artisan test --filter='AdDraftSpecMapperTest|PublishAdDraftTest|AdDraftPublishApiTest|AdDraftApiTest|AdDraftModelTest|AdDraftServiceTest'
```
All green. Then:
```
git add app/app/Modules/Marketing/Http/Controllers/AdDraftController.php app/app/Modules/Marketing/Http/routes.php app/tests/Feature/Marketing/AdDraftPublishApiTest.php
git commit -m "feat(ads): publish endpoint (gated on ads.create capability) + route

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Self-Review (plan author)

**Spec coverage (§3, §5 publish):** resume-first idempotent create ✓ (T2), payload→DTO mapping ✓ (T1), publish endpoint + capability gating ✓ (T3), status transitions draft→publishing→published/failed ✓. `special_ad_categories: ['NONE']` addresses the Plan-1 review note about Graph rejecting `[]` on some versions.

**Placeholder scan:** no TBD. Media upload (`uploadImage`/`uploadVideo`) is a noted follow-up — the existing-Page-post path (the emphasized feature) publishes fully; the new-creative path requires `image_hash` already present in payload.

**Type consistency:** `AdDraftSpecMapper::campaign/adSet/ad` signatures match the job's calls; DTO field names match Plan 1. Job mirrors `SyncAdInsights` (queue name, `ShouldBeUnique`, `withoutGlobalScope`).

**Decisions locked:** `tries = 1` (publish is not auto-retried — resume is a deliberate re-publish). Token `ads_management` scope is NOT pre-checked locally (we can't introspect it); if missing, Graph errors and the job records `failed` with the message. Controller sets `publishing` optimistically for immediate UI feedback; the destroy guard (Plan 3) blocks deletion while publishing.

## Next plan
- Plan 5 — FE wizard (6 steps, desktop-only) + post picker/preview modals + AntD `Tour` + AI slide-over, consuming the Plan 1–4 APIs.
