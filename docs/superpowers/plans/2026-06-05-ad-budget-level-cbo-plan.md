# Budget Level (CBO vs ad-set) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Checkbox steps. Branch `feature/ads-manager-parity`. `git add` only exact paths.

**Goal:** Let a draft put its budget at the campaign level (CBO) or the ad-set level.

**Architecture:** `CampaignSpecDTO` gains optional budget; `createCampaign` sends `daily_budget`+`bid_strategy` only when CBO; `createAdSet` sends `daily_budget` only when > 0. The mapper reads `payload.campaign.budget_mode`. FE toggle in StepBudget.

**Tech Stack:** Laravel 11, connector DTOs, PHPUnit `Http::fake`, Pint, Larastan L5; React/Zustand/AntD.

---

### Task 1: Connector CBO support

**Files:**
- Modify: `app/app/Integrations/Ads/DTO/CampaignSpecDTO.php`
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (createCampaign + createAdSet)
- Modify: `app/tests/Feature/Marketing/FacebookAdsCreateTest.php` (add 3 tests)

- [ ] **Step 1 — add tests** to `FacebookAdsCreateTest`:
```php
    public function test_create_campaign_with_cbo_sends_budget_and_bid_strategy(): void
    {
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*/campaigns' => \Illuminate\Support\Facades\Http::response(['id' => 'C_CBO'], 200)]);

        $this->connector()->createCampaign('tok', 'act_1', new \CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO(
            objective: 'messages', name: 'Camp', dailyBudgetMajor: 300000, currency: 'VND',
        ));

        \Illuminate\Support\Facades\Http::assertSent(function ($r) {
            $d = $r->data();

            return ($d['daily_budget'] ?? null) === '300000' && ($d['bid_strategy'] ?? null) === 'LOWEST_COST_WITHOUT_CAP';
        });
    }

    public function test_create_campaign_without_budget_omits_it(): void
    {
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*/campaigns' => \Illuminate\Support\Facades\Http::response(['id' => 'C'], 200)]);

        $this->connector()->createCampaign('tok', 'act_1', new \CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO(objective: 'messages', name: 'Camp'));

        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => ! array_key_exists('daily_budget', $r->data()));
    }

    public function test_create_adset_omits_daily_budget_when_zero_cbo(): void
    {
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*/adsets' => \Illuminate\Support\Facades\Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new \CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 0, currency: 'VND', targeting: [], pageId: '123',
        ));

        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => ! array_key_exists('daily_budget', $r->data()));
    }
```

- [ ] **Step 2 — run** `php artisan test --filter=FacebookAdsCreateTest` → 3 new FAIL.

- [ ] **Step 3 — implement.**

`CampaignSpecDTO.php` — add fields:
```php
    /** @param list<string> $specialAdCategories */
    public function __construct(
        public string $objective,
        public string $name,
        public string $status = 'PAUSED',
        public array $specialAdCategories = [],
        public ?int $dailyBudgetMajor = null,   // set ⇒ CBO (campaign-level budget)
        public ?string $currency = null,
        public string $bidStrategy = 'LOWEST_COST_WITHOUT_CAP',
    ) {}
```

`FacebookAdsConnector::createCampaign` — build params array + conditional budget:
```php
    public function createCampaign(string $accessToken, string $externalAccountId, CampaignSpecDTO $spec): string
    {
        $objective = FacebookObjectiveMap::spec($spec->objective)['objective'];

        $params = [
            'name' => $spec->name,
            'objective' => $objective,
            'status' => $spec->status,
            'special_ad_categories' => json_encode($spec->specialAdCategories),
            'access_token' => $accessToken,
        ];
        if ($spec->dailyBudgetMajor !== null && $spec->dailyBudgetMajor > 0) {
            $params['daily_budget'] = FacebookMoney::toMinorUnits($spec->dailyBudgetMajor, (string) ($spec->currency ?? 'VND'));
            $params['bid_strategy'] = $spec->bidStrategy;
        }

        $res = Http::timeout(30)->asForm()->post($this->graphUrl($externalAccountId.'/campaigns'), $params);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads createCampaign failed: '.$res->body());
        }

        return (string) $res->json('id');
    }
```

`FacebookAdsConnector::createAdSet` — make `daily_budget` conditional: REMOVE the `'daily_budget' => FacebookMoney::toMinorUnits(...)` line from the `$params` array literal, and AFTER building `$params` (before the destination_type block) add:
```php
        if ($spec->dailyBudgetMajor > 0) {
            $params['daily_budget'] = FacebookMoney::toMinorUnits($spec->dailyBudgetMajor, $spec->currency);
        }
```

- [ ] **Step 4 — run** `php artisan test --filter=FacebookAdsCreateTest` → PASS (all; the existing adset test uses 150000 > 0 so still includes daily_budget).

- [ ] **Step 5 — commit:**
```
cd app && vendor/bin/pint app/Integrations/Ads/DTO/CampaignSpecDTO.php app/Integrations/Ads/Facebook/FacebookAdsConnector.php tests/Feature/Marketing/FacebookAdsCreateTest.php && vendor/bin/phpstan analyse app/Integrations/Ads && cd ..
git add app/app/Integrations/Ads/DTO/CampaignSpecDTO.php app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/tests/Feature/Marketing/FacebookAdsCreateTest.php
git commit -m "feat(ads): CBO support — campaign-level budget + conditional adset budget

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Mapper CBO-aware + publish passes currency

**Files:**
- Modify: `app/app/Modules/Marketing/Services/AdDraftSpecMapper.php` (`campaign` takes currency; `adSet` CBO)
- Modify: `app/app/Modules/Marketing/Jobs/PublishAdDraft.php` (call `campaign($draft, $currency)`)
- Modify: `app/tests/Feature/Marketing/AdDraftSpecMapperTest.php`

- [ ] **Step 1 — update tests.** In `AdDraftSpecMapperTest`: change the campaign test to pass a currency and add a CBO test:
```php
    public function test_maps_campaign_with_none_special_category(): void
    {
        $spec = app(AdDraftSpecMapper::class)->campaign($this->draft([]), 'VND');
        $this->assertSame('messages', $spec->objective);
        $this->assertSame('Tết', $spec->name);
        $this->assertSame(['NONE'], $spec->specialAdCategories);
        $this->assertNull($spec->dailyBudgetMajor); // default adset mode ⇒ no campaign budget
    }

    public function test_maps_campaign_cbo_budget(): void
    {
        $draft = $this->draft(['campaign' => ['budget_mode' => 'campaign', 'daily_budget_major' => 500000]]);
        $spec = app(AdDraftSpecMapper::class)->campaign($draft, 'VND');
        $this->assertSame(500000, $spec->dailyBudgetMajor);
        $this->assertSame('VND', $spec->currency);
    }

    public function test_adset_budget_zero_when_cbo(): void
    {
        $draft = $this->draft(['campaign' => ['budget_mode' => 'campaign']]);
        $node = ['name' => 'N', 'budget' => ['daily_major' => 999999], 'ads' => [['creative' => ['page_id' => '1']]]];
        $spec = app(AdDraftSpecMapper::class)->adSet($draft, $node, 'C9', 'VND');
        $this->assertSame(0, $spec->dailyBudgetMajor); // CBO ⇒ adset has no budget
    }
```

- [ ] **Step 2 — run** `php artisan test --filter=AdDraftSpecMapperTest` → FAIL.

- [ ] **Step 3 — implement.** In `AdDraftSpecMapper.php`:
Change `campaign` signature + body:
```php
    public function campaign(AdDraft $draft, string $currency): CampaignSpecDTO
    {
        $campaign = (array) (((array) ($draft->payload ?? []))['campaign'] ?? []);
        $cbo = ($campaign['budget_mode'] ?? 'adset') === 'campaign';

        return new CampaignSpecDTO(
            objective: (string) ($draft->objective ?? 'traffic'),
            name: (string) ($draft->name ?? 'Chiến dịch'),
            specialAdCategories: ['NONE'],
            dailyBudgetMajor: $cbo ? (int) ($campaign['daily_budget_major'] ?? 0) : null,
            currency: $cbo ? $currency : null,
        );
    }
```
In `adSet`, compute CBO and override budget:
```php
        $campaign = (array) (((array) ($draft->payload ?? []))['campaign'] ?? []);
        $cbo = ($campaign['budget_mode'] ?? 'adset') === 'campaign';
        $budget = (array) ($node['budget'] ?? []);
        // ... build AdSetSpecDTO with:
        dailyBudgetMajor: $cbo ? 0 : (int) ($budget['daily_major'] ?? 0),
```
In `PublishAdDraft.php`, change the campaign create line from `$mapper->campaign($draft)` to `$mapper->campaign($draft, (string) $account->currency)`.

- [ ] **Step 4 — run** `php artisan test --filter='AdDraftSpecMapperTest|PublishAdDraftTest'` → PASS.

- [ ] **Step 5 — commit:**
```
cd app && vendor/bin/pint app/Modules/Marketing/Services/AdDraftSpecMapper.php app/Modules/Marketing/Jobs/PublishAdDraft.php tests/Feature/Marketing/AdDraftSpecMapperTest.php && vendor/bin/phpstan analyse app/Modules/Marketing && cd ..
git add app/app/Modules/Marketing/Services/AdDraftSpecMapper.php app/app/Modules/Marketing/Jobs/PublishAdDraft.php app/tests/Feature/Marketing/AdDraftSpecMapperTest.php
git commit -m "feat(ads): mapper CBO-aware (campaign budget / adset budget 0 under CBO)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: FE — budget-level toggle

**Files:**
- Modify: `app/resources/js/lib/adWizard.tsx` (payload `campaign?: { budget_mode?: 'campaign'|'adset'; daily_budget_major?: number }`)
- Modify: `app/resources/js/lib/adWizard/draftStore.ts` (actions `setBudgetMode`, `setCampaignBudget`)
- Modify: `app/resources/js/pages/adWizard/StepBudget.tsx` (toggle)
- Modify: `app/resources/js/pages/AdWizardPage.tsx` (`canProceed(1)`)
- Modify: `app/resources/js/pages/adWizard/StepReview.tsx` (CBO summary + canPublish)

Add `campaign?: { budget_mode?: 'campaign' | 'adset'; daily_budget_major?: number }` to `AdDraftPayload`. Store actions:
```ts
setBudgetMode: (mode) => set((s) => ({ payload: { ...s.payload, campaign: { ...s.payload.campaign, budget_mode: mode } }, dirty: true })),
setCampaignBudget: (major) => set((s) => ({ payload: { ...s.payload, campaign: { ...s.payload.campaign, daily_budget_major: major } }, dirty: true })),
```
`StepBudget`: a `Segmented` "Cấp ngân sách" [{label:'Chiến dịch (tối ưu tự động)',value:'campaign'},{label:'Nhóm quảng cáo',value:'adset'}] bound to `payload.campaign?.budget_mode ?? 'adset'` via `setBudgetMode`. If `campaign` → one `InputNumber` (VND/day) bound to `payload.campaign?.daily_budget_major` via `setCampaignBudget` + note "Facebook tự chia ngân sách cho các nhóm hiệu quả nhất."; the existing per-adset budget UI shows only when mode is `adset`. `AdWizardPage canProceed(1)`: campaign mode → `(payload.campaign?.daily_budget_major ?? 0) > 0`; adset mode → selected adset budget > 0. `StepReview`: when CBO, show "Ngân sách chiến dịch (CBO)" = `formatBudget(payload.campaign?.daily_budget_major)` and `canPublish` requires that; else require every adset budget > 0 (current). Verify tsc/eslint, commit.

---

## Self-Review
**Spec coverage:** connector CBO (T1), mapper/publish CBO (T2), FE toggle (T3). Default `'adset'` keeps C/legacy working. `bid_strategy` default LOWEST_COST_WITHOUT_CAP. **Type consistency:** `CampaignSpecDTO.dailyBudgetMajor/currency/bidStrategy` used T1↔T2; `payload.campaign.budget_mode/daily_budget_major` used T2 (BE) ↔ T3 (FE). `campaign(draft,currency)` signature change updated in publish + tests.
