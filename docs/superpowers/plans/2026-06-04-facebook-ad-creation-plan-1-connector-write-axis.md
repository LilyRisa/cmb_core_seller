# Facebook Ad Creation — Plan 1: Connector Write Axis Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a segregated **write axis** to the Ads integration layer so the app can create Facebook campaigns/ad sets/ads, with the objective matrix and money scaling as data-driven, extensible units.

**Architecture:** A new `AdsWriteConnector` interface (separate from read-only `AdsConnector`) implemented by `FacebookAdsConnector`. Facebook-specific knowledge lives in two small data-driven helpers — `FacebookObjectiveMap` (objective → Graph spec) and `FacebookMoney` (VND major → currency minor units). Spec DTOs are the stable interface between the Marketing module and the connector. No module code, no UI in this plan — pure integration layer, fully unit-testable with `Http::fake`.

**Tech Stack:** Laravel 11, PHP 8.2 (readonly DTOs), `Illuminate\Support\Facades\Http`, PHPUnit + `Http::fake`, Pint, Larastan level 5.

**Conventions:** All commands run from `app/`. Namespace `CMBcoreSeller\Integrations\Ads\*` → `app/app/Integrations/Ads/*`. Match the style of the existing `FacebookAdsConnector.php`.

---

### Task 1: Objective matrix helper (`FacebookObjectiveMap`)

Maps our internal objective codes (`messages`, `engagement`, `traffic`) to the Graph create-spec. Adding an objective later = one array entry. Unknown objective throws `UnsupportedOperation`.

**Files:**
- Create: `app/app/Integrations/Ads/Facebook/FacebookObjectiveMap.php`
- Test: `app/tests/Unit/Marketing/FacebookObjectiveMapTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookObjectiveMap;
use PHPUnit\Framework\TestCase;

class FacebookObjectiveMapTest extends TestCase
{
    public function test_messages_objective_maps_to_messenger_conversation_spec(): void
    {
        $spec = FacebookObjectiveMap::spec('messages');

        $this->assertSame('OUTCOME_ENGAGEMENT', $spec['objective']);
        $this->assertSame('CONVERSATIONS', $spec['optimization_goal']);
        $this->assertSame('IMPRESSIONS', $spec['billing_event']);
        $this->assertSame('MESSENGER', $spec['destination_type']);
        $this->assertTrue($spec['needs_promoted_object']);          // page_id required
        $this->assertContains('MESSAGE_PAGE', $spec['cta_options']);
    }

    public function test_traffic_objective_does_not_need_promoted_object(): void
    {
        $spec = FacebookObjectiveMap::spec('traffic');

        $this->assertSame('OUTCOME_TRAFFIC', $spec['objective']);
        $this->assertSame('LINK_CLICKS', $spec['optimization_goal']);
        $this->assertFalse($spec['needs_promoted_object']);
    }

    public function test_unknown_objective_throws(): void
    {
        $this->expectException(UnsupportedOperation::class);
        FacebookObjectiveMap::spec('sales'); // v2, not in v1 map
    }

    public function test_supported_lists_v1_objectives(): void
    {
        $this->assertSame(['messages', 'engagement', 'traffic'], FacebookObjectiveMap::supported());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FacebookObjectiveMapTest`
Expected: FAIL — class `FacebookObjectiveMap` not found.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;

/**
 * Maps our internal objective codes → Facebook create-spec fields. Data-driven:
 * add a marketing objective by adding ONE entry here (extensibility unit). Keeps
 * the valid (objective × optimization_goal × billing_event × CTA) combos in one
 * place so the wizard can only build combinations Graph accepts.
 */
final class FacebookObjectiveMap
{
    /** @var array<string, array<string,mixed>> */
    private const MAP = [
        'messages' => [
            'objective' => 'OUTCOME_ENGAGEMENT',
            'optimization_goal' => 'CONVERSATIONS',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => 'MESSENGER',
            'needs_promoted_object' => true,            // promoted_object = {page_id}
            'cta_options' => ['MESSAGE_PAGE'],
        ],
        'engagement' => [
            'objective' => 'OUTCOME_AWARENESS',
            'optimization_goal' => 'REACH',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => null,
            'needs_promoted_object' => false,
            'cta_options' => ['LEARN_MORE'],
        ],
        'traffic' => [
            'objective' => 'OUTCOME_TRAFFIC',
            'optimization_goal' => 'LINK_CLICKS',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => null,
            'needs_promoted_object' => false,
            'cta_options' => ['LEARN_MORE', 'SHOP_NOW'],
        ],
    ];

    /** @return array<string,mixed> */
    public static function spec(string $objective): array
    {
        return self::MAP[$objective] ?? throw UnsupportedOperation::for('facebook', "objective({$objective})");
    }

    /** @return list<string> */
    public static function supported(): array
    {
        return array_keys(self::MAP);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FacebookObjectiveMapTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Ads/Facebook/FacebookObjectiveMap.php app/tests/Unit/Marketing/FacebookObjectiveMapTest.php
git commit -m "feat(ads): objective→Graph spec map for ad creation (data-driven)"
```

---

### Task 2: Money scaling helper (`FacebookMoney`)

Converts a VND-integer (core money unit) budget to the ad account currency's **minor units** for write calls. Zero-decimal currencies (VND, JPY, KRW…) pass through; 2-decimal currencies ×100. Single source for money-on-write — prevents the 100× budget bug.

**Files:**
- Create: `app/app/Integrations/Ads/Facebook/FacebookMoney.php`
- Test: `app/tests/Unit/Marketing/FacebookMoneyTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookMoney;
use PHPUnit\Framework\TestCase;

class FacebookMoneyTest extends TestCase
{
    public function test_zero_decimal_currency_passes_through(): void
    {
        $this->assertSame('150000', FacebookMoney::toMinorUnits(150000, 'VND'));
        $this->assertSame('1000', FacebookMoney::toMinorUnits(1000, 'JPY'));
    }

    public function test_two_decimal_currency_scaled_by_100(): void
    {
        $this->assertSame('1000', FacebookMoney::toMinorUnits(10, 'USD'));   // $10.00 → 1000 cents
    }

    public function test_unknown_currency_defaults_to_two_decimal(): void
    {
        $this->assertSame('500', FacebookMoney::toMinorUnits(5, 'XYZ'));     // safe default ×100
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FacebookMoneyTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Write minimal implementation**

```php
<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

/**
 * Budget money mapping for Graph WRITE calls. Core stores money as integer VND;
 * Graph wants the account-currency MINOR unit. Zero-decimal currencies pass
 * through, others ×100. The ONLY place money is rescaled on write — keep it here
 * to avoid the 100× budget bug. Returns a string (Graph budgets are string ints).
 */
final class FacebookMoney
{
    /** ISO-4217 zero-decimal currencies (no minor unit). */
    private const ZERO_DECIMAL = ['VND', 'JPY', 'KRW', 'CLP', 'ISK', 'HUF', 'TWD', 'UGX'];

    public static function toMinorUnits(int $majorAmount, string $currency): string
    {
        $factor = in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? 1 : 100;

        return (string) ($majorAmount * $factor);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FacebookMoneyTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Ads/Facebook/FacebookMoney.php app/tests/Unit/Marketing/FacebookMoneyTest.php
git commit -m "feat(ads): currency minor-unit scaling for budget writes (anti 100x)"
```

---

### Task 3: Spec DTOs (Campaign / AdSet / Ad)

Stable, readonly value objects the Marketing module passes to the connector. Mirror the existing DTO style in `app/app/Integrations/Ads/DTO/`.

**Files:**
- Create: `app/app/Integrations/Ads/DTO/CampaignSpecDTO.php`
- Create: `app/app/Integrations/Ads/DTO/AdSetSpecDTO.php`
- Create: `app/app/Integrations/Ads/DTO/AdSpecDTO.php`
- Test: `app/tests/Unit/Marketing/AdSpecDtoTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use PHPUnit\Framework\TestCase;

class AdSpecDtoTest extends TestCase
{
    public function test_dtos_construct_and_expose_fields(): void
    {
        $c = new CampaignSpecDTO(objective: 'messages', name: 'Camp');
        $this->assertSame('messages', $c->objective);

        $s = new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 150000, currency: 'VND',
            targeting: ['geo_locations' => ['countries' => ['VN']]],
            pageId: '123', startTime: null,
        );
        $this->assertSame(150000, $s->dailyBudgetMajor);
        $this->assertSame('123', $s->pageId);

        $a = new AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS1', pageId: '123',
            pagePostId: '123_456', cta: 'MESSAGE_PAGE',
        );
        $this->assertSame('123_456', $a->pagePostId);
        $this->assertNull($a->imageHash);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdSpecDtoTest`
Expected: FAIL — DTO classes not found.

- [ ] **Step 3: Write minimal implementation**

`CampaignSpecDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class CampaignSpecDTO
{
    /** @param list<string> $specialAdCategories */
    public function __construct(
        public string $objective,                 // internal code: messages|engagement|traffic
        public string $name,
        public string $status = 'PAUSED',
        public array $specialAdCategories = [],   // Graph requires the key (default none)
    ) {}
}
```

`AdSetSpecDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdSetSpecDTO
{
    /**
     * @param array<string,mixed> $targeting Graph targeting spec (geo/age/interests/custom_audiences)
     */
    public function __construct(
        public string $name,
        public string $campaignExternalId,
        public string $objective,            // internal code → FacebookObjectiveMap
        public int $dailyBudgetMajor,        // VND integer (core unit)
        public string $currency,             // ad account currency
        public array $targeting,
        public ?string $pageId = null,       // required when objective needs promoted_object
        public ?string $startTime = null,    // ISO-8601; null = now
        public string $status = 'PAUSED',
    ) {}
}
```

`AdSpecDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdSpecDTO
{
    /**
     * Creative is EITHER an existing page post (pagePostId, keeps social proof)
     * OR a new creative (imageHash/videoId + copy). pageId always required.
     */
    public function __construct(
        public string $name,
        public string $adSetExternalId,
        public string $pageId,
        public ?string $pagePostId = null,   // object_story_id path
        public ?string $imageHash = null,    // object_story_spec path
        public ?string $videoId = null,
        public ?string $primaryText = null,
        public ?string $headline = null,
        public ?string $linkUrl = null,
        public string $cta = 'LEARN_MORE',
        public string $status = 'PAUSED',
    ) {}
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdSpecDtoTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Ads/DTO/CampaignSpecDTO.php app/app/Integrations/Ads/DTO/AdSetSpecDTO.php app/app/Integrations/Ads/DTO/AdSpecDTO.php app/tests/Unit/Marketing/AdSpecDtoTest.php
git commit -m "feat(ads): spec DTOs for campaign/adset/ad creation"
```

---

### Task 4: Segregated `AdsWriteConnector` interface

Separate interface so read-only providers are never forced to implement writes (extensibility + matches the project's segregated-capability pattern). Callers gate on `instanceof AdsWriteConnector` + the `ads.create` capability.

**Files:**
- Create: `app/app/Integrations/Ads/Contracts/AdsWriteConnector.php`
- Test: `app/tests/Unit/Marketing/AdsWriteConnectorContractTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use PHPUnit\Framework\TestCase;

class AdsWriteConnectorContractTest extends TestCase
{
    public function test_facebook_connector_implements_write_contract(): void
    {
        $c = new FacebookAdsConnector(['graph_version' => 'v19.0']);
        $this->assertInstanceOf(AdsWriteConnector::class, $c);
        $this->assertTrue($c->supports('ads.create'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdsWriteConnectorContractTest`
Expected: FAIL — interface not found / not implemented.

- [ ] **Step 3: Write minimal implementation**

`AdsWriteConnector.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\Contracts;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;

/**
 * Segregated WRITE axis for ads providers — implemented only by providers that
 * support ad creation. Read-only providers implement {@see AdsConnector} alone.
 * Callers MUST check `instanceof AdsWriteConnector` + `supports('ads.create')`.
 */
interface AdsWriteConnector
{
    public function createCampaign(string $accessToken, string $externalAccountId, CampaignSpecDTO $spec): string;

    public function createAdSet(string $accessToken, string $externalAccountId, AdSetSpecDTO $spec): string;

    public function createAd(string $accessToken, string $externalAccountId, AdSpecDTO $spec): string;
}
```

Then make `FacebookAdsConnector` declare it. Modify `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php`:

Change the class declaration line:
```php
class FacebookAdsConnector implements AdsConnector, AdsWriteConnector
```
Add the import near the other `use` statements:
```php
use CMBcoreSeller\Integrations\Ads\Contracts\AdsWriteConnector;
use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
```
In `capabilities()`, flip the create-related flags (replace the Phase 3 `false` lines):
```php
            'actions.budget' => false, // Phase 3
            'actions.status' => false, // Phase 3
            'actions.bid' => false,    // Phase 3
            'ads.create' => true,
            'creative.upload' => true,
            'page.posts.read' => true,
            'preview.generate' => true,
            'targeting.search' => true,
```
Add stub methods at the end of the class (real bodies land in Tasks 5–7; for now make Task 4 compile and the contract test pass by returning empty — they will be replaced):
```php
    public function createCampaign(string $accessToken, string $externalAccountId, CampaignSpecDTO $spec): string
    {
        return '';
    }

    public function createAdSet(string $accessToken, string $externalAccountId, AdSetSpecDTO $spec): string
    {
        return '';
    }

    public function createAd(string $accessToken, string $externalAccountId, AdSpecDTO $spec): string
    {
        return '';
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdsWriteConnectorContractTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Ads/Contracts/AdsWriteConnector.php app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/tests/Unit/Marketing/AdsWriteConnectorContractTest.php
git commit -m "feat(ads): segregated AdsWriteConnector contract + capabilities"
```

---

### Task 5: Implement `createCampaign`

**Files:**
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (replace the `createCampaign` stub)
- Test: `app/tests/Feature/Marketing/FacebookAdsCreateTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsCreateTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_create_campaign_posts_mapped_objective_and_returns_id(): void
    {
        Http::fake(['graph.facebook.com/*/campaigns' => Http::response(['id' => 'C_NEW'], 200)]);

        $id = $this->connector()->createCampaign('tok', 'act_1', new CampaignSpecDTO(objective: 'messages', name: 'Camp'));

        $this->assertSame('C_NEW', $id);
        Http::assertSent(function ($request) {
            $d = $request->data();

            return str_contains($request->url(), 'act_1/campaigns')
                && $d['objective'] === 'OUTCOME_ENGAGEMENT'      // mapped from 'messages'
                && $d['status'] === 'PAUSED'
                && array_key_exists('special_ad_categories', $d); // Graph requires the key
        });
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FacebookAdsCreateTest`
Expected: FAIL — returns `''`, assertions fail.

- [ ] **Step 3: Write minimal implementation**

Replace the `createCampaign` stub body in `FacebookAdsConnector.php`:
```php
    public function createCampaign(string $accessToken, string $externalAccountId, CampaignSpecDTO $spec): string
    {
        $objective = FacebookObjectiveMap::spec($spec->objective)['objective'];

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalAccountId.'/campaigns'), [
            'name' => $spec->name,
            'objective' => $objective,
            'status' => $spec->status,
            'special_ad_categories' => json_encode($spec->specialAdCategories),
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads createCampaign failed: '.$res->body());
        }

        return (string) $res->json('id');
    }
```
Add the import for the map (with the other `use` lines):
```php
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookObjectiveMap;
```
> Note: same namespace, so the `use` is optional — reference `FacebookObjectiveMap::spec(...)` directly. Pint will drop a redundant import.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FacebookAdsCreateTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/tests/Feature/Marketing/FacebookAdsCreateTest.php
git commit -m "feat(ads): createCampaign via Graph (objective mapped)"
```

---

### Task 6: Implement `createAdSet` (budget scaling + objective spec + promoted_object)

**Files:**
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (replace `createAdSet` stub)
- Test: `app/tests/Feature/Marketing/FacebookAdsCreateTest.php` (add a method)

- [ ] **Step 1: Write the failing test (add to FacebookAdsCreateTest)**

```php
    public function test_create_adset_scales_budget_and_sets_messaging_spec(): void
    {
        Http::fake(['graph.facebook.com/*/adsets' => Http::response(['id' => 'AS_NEW'], 200)]);

        $id = $this->connector()->createAdSet('tok', 'act_1', new \CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C_NEW', objective: 'messages',
            dailyBudgetMajor: 150000, currency: 'VND',
            targeting: ['geo_locations' => ['countries' => ['VN']]],
            pageId: '123', startTime: null,
        ));

        $this->assertSame('AS_NEW', $id);
        Http::assertSent(function ($request) {
            $d = $request->data();
            $promoted = json_decode($d['promoted_object'] ?? '{}', true);

            return str_contains($request->url(), 'act_1/adsets')
                && $d['campaign_id'] === 'C_NEW'
                && $d['daily_budget'] === '150000'              // VND zero-decimal, no ×100
                && $d['optimization_goal'] === 'CONVERSATIONS'
                && $d['billing_event'] === 'IMPRESSIONS'
                && $d['destination_type'] === 'MESSENGER'
                && ($promoted['page_id'] ?? null) === '123';
        });
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FacebookAdsCreateTest`
Expected: FAIL — `createAdSet` returns `''`.

- [ ] **Step 3: Write minimal implementation**

Replace the `createAdSet` stub body:
```php
    public function createAdSet(string $accessToken, string $externalAccountId, AdSetSpecDTO $spec): string
    {
        $map = FacebookObjectiveMap::spec($spec->objective);

        $params = [
            'name' => $spec->name,
            'campaign_id' => $spec->campaignExternalId,
            'daily_budget' => FacebookMoney::toMinorUnits($spec->dailyBudgetMajor, $spec->currency),
            'billing_event' => $map['billing_event'],
            'optimization_goal' => $map['optimization_goal'],
            'targeting' => json_encode($spec->targeting),
            'status' => $spec->status,
            'start_time' => $spec->startTime ?? now()->toIso8601String(),
            'access_token' => $accessToken,
        ];
        if ($map['destination_type'] !== null) {
            $params['destination_type'] = $map['destination_type'];
        }
        if ($map['needs_promoted_object']) {
            if ($spec->pageId === null) {
                throw new \RuntimeException("Facebook Ads createAdSet: objective '{$spec->objective}' requires pageId.");
            }
            $params['promoted_object'] = json_encode(['page_id' => $spec->pageId]);
        }

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalAccountId.'/adsets'), $params);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads createAdSet failed: '.$res->body());
        }

        return (string) $res->json('id');
    }
```
Add the import (with other `use` lines):
```php
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookMoney;
```
> Same-namespace note as Task 5 — reference `FacebookMoney::toMinorUnits(...)` directly; Pint trims redundant imports.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FacebookAdsCreateTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/tests/Feature/Marketing/FacebookAdsCreateTest.php
git commit -m "feat(ads): createAdSet with budget scaling + objective spec + promoted_object"
```

---

### Task 7: Implement `createAd` (existing page post vs new creative)

**Files:**
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (replace `createAd` stub)
- Test: `app/tests/Feature/Marketing/FacebookAdsCreateTest.php` (add two methods)

- [ ] **Step 1: Write the failing tests (add to FacebookAdsCreateTest)**

```php
    public function test_create_ad_from_existing_page_post_uses_object_story_id(): void
    {
        Http::fake(['graph.facebook.com/*/ads' => Http::response(['id' => 'AD_NEW'], 200)]);

        $id = $this->connector()->createAd('tok', 'act_1', new \CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS_NEW', pageId: '123',
            pagePostId: '123_456', cta: 'MESSAGE_PAGE',
        ));

        $this->assertSame('AD_NEW', $id);
        Http::assertSent(function ($request) {
            $d = $request->data();
            $creative = json_decode($d['creative'] ?? '{}', true);

            return str_contains($request->url(), 'act_1/ads')
                && $d['adset_id'] === 'AS_NEW'
                && ($creative['object_story_id'] ?? null) === '123_456';
        });
    }

    public function test_create_ad_new_creative_uses_object_story_spec(): void
    {
        Http::fake(['graph.facebook.com/*/ads' => Http::response(['id' => 'AD_NEW2'], 200)]);

        $this->connector()->createAd('tok', 'act_1', new \CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS_NEW', pageId: '123',
            imageHash: 'HASH', primaryText: 'Mua ngay', headline: 'Sale', linkUrl: 'https://shop.vn', cta: 'SHOP_NOW',
        ));

        Http::assertSent(function ($request) {
            $creative = json_decode($request->data()['creative'] ?? '{}', true);
            $spec = $creative['object_story_spec'] ?? [];

            return ($spec['page_id'] ?? null) === '123'
                && ($spec['link_data']['image_hash'] ?? null) === 'HASH'
                && ($spec['link_data']['call_to_action']['type'] ?? null) === 'SHOP_NOW';
        });
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FacebookAdsCreateTest`
Expected: FAIL — `createAd` returns `''`.

- [ ] **Step 3: Write minimal implementation**

Replace the `createAd` stub body:
```php
    public function createAd(string $accessToken, string $externalAccountId, AdSpecDTO $spec): string
    {
        // Existing page post keeps social proof (likes/comments/shares); else build a new creative.
        if ($spec->pagePostId !== null) {
            $creative = ['object_story_id' => $spec->pagePostId];
        } else {
            $linkData = array_filter([
                'image_hash' => $spec->imageHash,
                'video_id' => $spec->videoId,
                'message' => $spec->primaryText,
                'name' => $spec->headline,
                'link' => $spec->linkUrl,
                'call_to_action' => ['type' => $spec->cta],
            ], fn ($v) => $v !== null);
            $creative = ['object_story_spec' => ['page_id' => $spec->pageId, 'link_data' => $linkData]];
        }

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalAccountId.'/ads'), [
            'name' => $spec->name,
            'adset_id' => $spec->adSetExternalId,
            'creative' => json_encode($creative),
            'status' => $spec->status,
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads createAd failed: '.$res->body());
        }

        return (string) $res->json('id');
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FacebookAdsCreateTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Run the quality gate + commit**

```bash
vendor/bin/pint app/app/Integrations/Ads app/tests/Unit/Marketing app/tests/Feature/Marketing/FacebookAdsCreateTest.php
vendor/bin/phpstan analyse app/app/Integrations/Ads
php artisan test --filter='FacebookObjectiveMapTest|FacebookMoneyTest|AdSpecDtoTest|AdsWriteConnectorContractTest|FacebookAdsCreateTest'
git add app/app/Integrations/Ads app/tests/Unit/Marketing app/tests/Feature/Marketing/FacebookAdsCreateTest.php
git commit -m "feat(ads): createAd (page post / new creative) + green quality gate"
```
Expected: Pint passes, PHPStan "No errors", all listed tests PASS.

---

## Self-Review (done by plan author)

**Spec coverage (§4 "trục GHI"):** objective matrix (Task 1) ✓, currency scaling (Task 2) ✓, spec DTOs (Task 3) ✓, segregated write interface + capabilities (Task 4) ✓, create campaign/adset/ad incl. `object_story_id` vs `object_story_spec` and promoted_object (Tasks 5–7) ✓. Media upload / page posts / previews / targeting search are explicitly **Plan 2** (read/creative helpers) — out of scope here by design.

**Placeholder scan:** Task 4 intentionally adds temporary `return ''` stubs that Tasks 5–7 replace; each is shown in full and replaced with full code. No "TBD"/"handle errors" placeholders.

**Type consistency:** `FacebookObjectiveMap::spec()` returns keys `objective/optimization_goal/billing_event/destination_type/needs_promoted_object/cta_options` — used identically in Tasks 5–6. DTO field names (`dailyBudgetMajor`, `campaignExternalId`, `pagePostId`, `imageHash`) match across Tasks 3, 6, 7. `FacebookMoney::toMinorUnits(int,string):string` signature consistent.

## Subsequent plans (for context — not part of this plan)
- **Plan 2** — creative/read helpers: `listPages`, `listPagePosts` (engagement+media), `uploadImage/Video`, `searchTargeting`, `estimateAudience`, `generatePreviews` (+ DTOs).
- **Plan 3** — `ad_drafts` model + `AdDraftService` + CRUD/autosave API + `marketing.ads.create` ability.
- **Plan 4** — `PublishAdDraft` job (resume-first, idempotent) + publish endpoint + gating.
- **Plan 5** — FE wizard (6 steps, desktop-only) + post picker/media/audience/placements/preview modals + AntD `Tour` + AI slide-over.
