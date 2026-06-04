# Facebook Ad Creation — Plan 5: Authoring HTTP Endpoints Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`).

**Goal:** Expose the Plan-2 connector read/creative helpers over HTTP so the wizard can list Pages, browse Page posts (with like/comment/share + media), search targeting, estimate audience size, and render real ad previews.

**Architecture:** One thin `AdAuthoringController` in the Marketing module. Each action: tenant-scoped `AdAccount::findOrFail`, resolve the connector via `AdsRegistry`, require it `instanceof AdsWriteConnector`, call the helper, map DTOs → arrays. RBAC: `marketing.view` (Marketing is Owner/Admin-only). All under `api/v1/marketing/ad-accounts/{id}/...`.

**Tech Stack:** Laravel 11, `AdsRegistry` + `AdsWriteConnector` (Plan 2), PHPUnit + `Http::fake`, Pint, Larastan L5.

**Conventions:** Commands from `app/`. Mirror `AdInsightController` (resolves services, tenant-scoped `findOrFail`, `response()->json(['data'=>...])`) and `AdDraftApiTest` (auth/tenant/role test setup). Routes in `Http/routes.php` inside the `api/v1/marketing` group.

---

### Task 1: Pages + Page posts endpoints

**Files:**
- Create: `app/app/Modules/Marketing/Http/Controllers/AdAuthoringController.php`
- Modify: `app/app/Modules/Marketing/Http/routes.php`
- Test: `app/tests/Feature/Marketing/AdAuthoringPagesApiTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdAuthoringPagesApiTest extends TestCase
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

    private function owner(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => Role::Owner->value]);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(): AdAccount
    {
        app(CurrentTenant::class)->set($this->tenant);

        return AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'USERTOK']);
    }

    public function test_pages_lists_id_name_without_token(): void
    {
        Http::fake(['graph.facebook.com/*/me/accounts*' => Http::response([
            'data' => [['id' => '123', 'name' => 'Shop', 'access_token' => 'PAGETOK']],
        ], 200)]);
        $acc = $this->account();

        $res = $this->actingAs($this->owner())->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$acc->id}/pages")
            ->assertOk()
            ->assertJsonPath('data.0.id', '123')
            ->assertJsonPath('data.0.name', 'Shop');

        $this->assertStringNotContainsString('PAGETOK', $res->getContent()); // token never exposed
    }

    public function test_page_posts_returns_engagement_and_media(): void
    {
        Http::fake([
            'graph.facebook.com/*/me/accounts*' => Http::response(['data' => [['id' => '123', 'name' => 'Shop', 'access_token' => 'PAGETOK']]], 200),
            'graph.facebook.com/*/123/published_posts*' => Http::response(['data' => [[
                'id' => '123_456', 'message' => 'Sale', 'created_time' => '2026-06-01T00:00:00+0000',
                'full_picture' => 'https://img', 'attachments' => ['data' => [['media_type' => 'photo']]],
                'likes' => ['summary' => ['total_count' => 1200]],
                'comments' => ['summary' => ['total_count' => 89]],
                'shares' => ['count' => 45],
            ]]], 200),
        ]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$acc->id}/pages/123/posts")
            ->assertOk()
            ->assertJsonPath('data.0.id', '123_456')
            ->assertJsonPath('data.0.likes', 1200)
            ->assertJsonPath('data.0.comments', 89)
            ->assertJsonPath('data.0.shares', 45)
            ->assertJsonPath('data.0.media_type', 'photo')
            ->assertJsonPath('data.0.image_url', 'https://img');
    }

    public function test_page_posts_404_for_unknown_page(): void
    {
        Http::fake(['graph.facebook.com/*/me/accounts*' => Http::response(['data' => []], 200)]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$acc->id}/pages/999/posts")
            ->assertStatus(404);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AdAuthoringPagesApiTest` → FAIL.

- [ ] **Step 3: Implement** `AdAuthoringController.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\DTO\PagePostDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PageRefDTO;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Wizard authoring reads: Pages, Page posts (engagement + media), targeting search,
 * audience estimate, ad previews. Tenant-scoped; provider resolved via the registry.
 * Read permission marketing.view (Marketing is Owner/Admin-only). Tokens never leave.
 */
class AdAuthoringController extends Controller
{
    /** GET ad-accounts/{id}/pages */
    public function pages(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        $pages = array_map(fn (PageRefDTO $p) => ['id' => $p->id, 'name' => $p->name],
            $connector->listPages((string) $account->access_token));

        return response()->json(['data' => $pages]);
    }

    /** GET ad-accounts/{id}/pages/{pageId}/posts */
    public function pagePosts(int $id, string $pageId, Request $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        $page = collect($connector->listPages((string) $account->access_token))->firstWhere('id', $pageId);
        abort_unless($page instanceof PageRefDTO, 404, 'Trang không tồn tại hoặc chưa được cấp quyền.');

        $limit = max(1, min(50, (int) $request->integer('limit', 25)));
        $posts = array_map(fn (PagePostDTO $p) => [
            'id' => $p->id,
            'message' => $p->message,
            'created_time' => $p->createdTime,
            'media_type' => $p->mediaType,
            'image_url' => $p->imageUrl,
            'likes' => $p->likes,
            'comments' => $p->comments,
            'shares' => $p->shares,
        ], $connector->listPagePosts($page->accessToken, $pageId, $limit));

        return response()->json(['data' => $posts]);
    }

    /**
     * Resolve tenant-scoped account + its write-capable connector (or 422).
     *
     * @return array{0: AdAccount, 1: AdsWriteConnector}
     */
    private function resolve(int $id): array
    {
        /** @var AdAccount $account */
        $account = AdAccount::query()->findOrFail($id);
        $registry = app(AdsRegistry::class);
        $connector = $registry->has($account->provider) ? $registry->for($account->provider) : null;
        abort_unless($connector instanceof AdsWriteConnector, 422, 'Tính năng tạo quảng cáo chưa được bật.');

        return [$account, $connector];
    }
}
```

Add to `routes.php` inside the marketing group:
```php
        // Wizard authoring reads (pages/posts/targeting/preview).
        Route::get('ad-accounts/{id}/pages', [AdAuthoringController::class, 'pages'])->whereNumber('id')->name('marketing.authoring.pages');
        Route::get('ad-accounts/{id}/pages/{pageId}/posts', [AdAuthoringController::class, 'pagePosts'])->whereNumber('id')->name('marketing.authoring.page-posts');
```
And the import:
```php
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdAuthoringController;
```

- [ ] **Step 4: Run** `php artisan test --filter=AdAuthoringPagesApiTest` → PASS (3 tests).

- [ ] **Step 5: Quality + commit**
```
# from app/
vendor/bin/pint app/Modules/Marketing/Http tests/Feature/Marketing/AdAuthoringPagesApiTest.php
vendor/bin/phpstan analyse app/Modules/Marketing/Http
```
```
git add app/app/Modules/Marketing/Http/Controllers/AdAuthoringController.php app/app/Modules/Marketing/Http/routes.php app/tests/Feature/Marketing/AdAuthoringPagesApiTest.php
git commit -m "feat(ads): authoring endpoints — pages + page posts (engagement + media)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Targeting search + audience estimate endpoints

**Files:**
- Modify: `app/app/Modules/Marketing/Http/Controllers/AdAuthoringController.php` (add 2 actions)
- Modify: `app/app/Modules/Marketing/Http/routes.php` (add 2 routes)
- Test: `app/tests/Feature/Marketing/AdAuthoringTargetingApiTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdAuthoringTargetingApiTest extends TestCase
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

    private function owner(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => Role::Owner->value]);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(): AdAccount
    {
        app(CurrentTenant::class)->set($this->tenant);

        return AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
    }

    public function test_targeting_search_returns_options(): void
    {
        Http::fake(['graph.facebook.com/*/search*' => Http::response(['data' => [['id' => '6003', 'name' => 'Thời trang', 'audience_size_lower_bound' => 5000000]]], 200)]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$acc->id}/targeting-search?q=thời+trang")
            ->assertOk()
            ->assertJsonPath('data.0.id', '6003')
            ->assertJsonPath('data.0.name', 'Thời trang')
            ->assertJsonPath('data.0.audience_size', 5000000);
    }

    public function test_audience_estimate_returns_bounds(): void
    {
        Http::fake(['graph.facebook.com/*/delivery_estimate*' => Http::response(['data' => [['estimate_mau_lower_bound' => 1000000, 'estimate_mau_upper_bound' => 2100000]]], 200)]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$acc->id}/audience-estimate", [
                'targeting' => ['geo_locations' => ['countries' => ['VN']]],
                'optimization_goal' => 'REACH',
            ])
            ->assertOk()
            ->assertJsonPath('data.lower_bound', 1000000)
            ->assertJsonPath('data.upper_bound', 2100000);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AdAuthoringTargetingApiTest` → FAIL.

- [ ] **Step 3: Implement.** Add imports to the controller:
```php
use CMBcoreSeller\Integrations\Ads\DTO\TargetingOptionDTO;
```
Add two actions:
```php
    /** GET ad-accounts/{id}/targeting-search?q=&type= */
    public function targetingSearch(int $id, Request $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        $type = (string) ($request->string('type')->toString() ?: 'adinterest');
        $options = array_map(fn (TargetingOptionDTO $o) => [
            'id' => $o->id, 'name' => $o->name, 'type' => $o->type, 'audience_size' => $o->audienceSize,
        ], $connector->searchTargeting((string) $account->access_token, (string) $request->string('q'), $type));

        return response()->json(['data' => $options]);
    }

    /** POST ad-accounts/{id}/audience-estimate { targeting, optimization_goal } */
    public function audienceEstimate(int $id, Request $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        $size = $connector->estimateAudience(
            (string) $account->access_token,
            $account->external_account_id,
            (array) $request->input('targeting', []),
            (string) ($request->string('optimization_goal')->toString() ?: 'REACH'),
        );

        return response()->json(['data' => ['lower_bound' => $size->lowerBound, 'upper_bound' => $size->upperBound]]);
    }
```
Add routes:
```php
        Route::get('ad-accounts/{id}/targeting-search', [AdAuthoringController::class, 'targetingSearch'])->whereNumber('id')->name('marketing.authoring.targeting-search');
        Route::post('ad-accounts/{id}/audience-estimate', [AdAuthoringController::class, 'audienceEstimate'])->whereNumber('id')->name('marketing.authoring.audience-estimate');
```

- [ ] **Step 4: Run** `php artisan test --filter=AdAuthoringTargetingApiTest` → PASS (2 tests). Regression: `php artisan test --filter=AdAuthoringPagesApiTest` green.

- [ ] **Step 5: Quality + commit**
```
# from app/
vendor/bin/pint app/Modules/Marketing/Http tests/Feature/Marketing/AdAuthoringTargetingApiTest.php
vendor/bin/phpstan analyse app/Modules/Marketing/Http
```
```
git add app/app/Modules/Marketing/Http/Controllers/AdAuthoringController.php app/app/Modules/Marketing/Http/routes.php app/tests/Feature/Marketing/AdAuthoringTargetingApiTest.php
git commit -m "feat(ads): authoring endpoints — targeting search + audience estimate

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Ad previews endpoint

**Files:**
- Modify: `app/app/Modules/Marketing/Http/Controllers/AdAuthoringController.php` (add `previews`)
- Modify: `app/app/Modules/Marketing/Http/routes.php` (add route)
- Test: `app/tests/Feature/Marketing/AdAuthoringPreviewApiTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdAuthoringPreviewApiTest extends TestCase
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

    private function owner(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => Role::Owner->value]);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(): AdAccount
    {
        app(CurrentTenant::class)->set($this->tenant);

        return AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
    }

    public function test_previews_returns_iframe_per_format(): void
    {
        Http::fake(['graph.facebook.com/*/generatepreviews*' => Http::response(['data' => [['body' => '<iframe></iframe>']]], 200)]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$acc->id}/ad-previews", [
                'creative' => ['page_id' => '123', 'link_data' => ['message' => 'Hi']],
                'formats' => ['DESKTOP_FEED_STANDARD'],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.format', 'DESKTOP_FEED_STANDARD')
            ->assertJsonPath('data.0.body', '<iframe></iframe>');
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AdAuthoringPreviewApiTest` → FAIL.

- [ ] **Step 3: Implement.** Add import:
```php
use CMBcoreSeller\Integrations\Ads\DTO\AdPreviewDTO;
```
Add action:
```php
    /** POST ad-accounts/{id}/ad-previews { creative, formats[] } */
    public function previews(int $id, Request $request): JsonResponse
    {
        Gate::authorize('marketing.view');
        [$account, $connector] = $this->resolve($id);

        /** @var list<string> $formats */
        $formats = array_values(array_filter((array) $request->input('formats', []), 'is_string'));
        if ($formats === []) {
            $formats = ['DESKTOP_FEED_STANDARD', 'MOBILE_FEED_STANDARD'];
        }

        $previews = array_map(fn (AdPreviewDTO $p) => ['format' => $p->format, 'body' => $p->body],
            $connector->generatePreviews((string) $account->access_token, $account->external_account_id, (array) $request->input('creative', []), $formats));

        return response()->json(['data' => $previews]);
    }
```
Add route:
```php
        Route::post('ad-accounts/{id}/ad-previews', [AdAuthoringController::class, 'previews'])->whereNumber('id')->name('marketing.authoring.previews');
```

- [ ] **Step 4: Run** `php artisan test --filter=AdAuthoringPreviewApiTest` → PASS. Regression: all authoring tests green.

- [ ] **Step 5: FINAL gate + commit**
```
# from app/
vendor/bin/pint app/Modules/Marketing/Http tests/Feature/Marketing/AdAuthoringPreviewApiTest.php
vendor/bin/phpstan analyse app/Modules/Marketing
php artisan test --filter='AdAuthoringPagesApiTest|AdAuthoringTargetingApiTest|AdAuthoringPreviewApiTest|AdDraftApiTest|AdDraftPublishApiTest'
```
All green. Then:
```
git add app/app/Modules/Marketing/Http/Controllers/AdAuthoringController.php app/app/Modules/Marketing/Http/routes.php app/tests/Feature/Marketing/AdAuthoringPreviewApiTest.php
git commit -m "feat(ads): authoring endpoint — ad previews (Graph iframe per format)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Self-Review (plan author)

**Spec coverage (§5 read endpoints):** pages ✓, page posts (engagement+media) ✓ (T1), targeting-search ✓, audience-estimate ✓ (T2), ad-previews ✓ (T3). All wire Plan-2 connector methods to tenant-scoped, RBAC-gated HTTP. Tokens never serialized (pages/posts expose id/name/metrics only).

**Placeholder scan:** none. The posts endpoint resolves the page access token by matching `listPages` (one extra Graph call) — acceptable for v1; a page-token cache is a later optimization.

**Type consistency:** `resolve()` returns `[AdAccount, AdsWriteConnector]`; every action destructures it. DTO→array field names (`audience_size`, `lower_bound`, `media_type`, `image_url`) are the wire contract the FE (Plan 6) will consume.

## Next plan
- Plan 6 — FE wizard (6 steps, desktop-only): `lib/adWizard.tsx` hooks (drafts CRUD/autosave/publish + these authoring reads) + the wizard page + Page-post picker/preview modals + AntD `Tour` + AI slide-over + a "Quảng cáo của tôi" entry tab.
