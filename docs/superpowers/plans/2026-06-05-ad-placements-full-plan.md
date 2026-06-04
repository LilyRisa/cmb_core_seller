# Placements (full) Implementation Plan

> REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Branch: **main** (commit directly per user). `git add` only exact paths.

**Goal:** Per-ad-set placement selection (device / platform / detailed positions) that actually reaches Graph.

---

### Task 1: Connector + mapper merge placements into targeting

**Files:**
- Modify: `app/app/Integrations/Ads/DTO/AdSetSpecDTO.php` (add `?array $placementConfig = null`)
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (createAdSet merges)
- Modify: `app/app/Modules/Marketing/Services/AdDraftSpecMapper.php` (pass placementConfig)
- Test: `app/tests/Feature/Marketing/FacebookAdsCreateTest.php` (+2) and `AdDraftSpecMapperTest.php` (+1)

- [ ] **Step 1 — tests.** Add to `FacebookAdsCreateTest`:
```php
    public function test_create_adset_merges_manual_placements_into_targeting(): void
    {
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*/adsets' => \Illuminate\Support\Facades\Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new \CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 1000, currency: 'VND', targeting: ['geo_locations' => ['countries' => ['VN']]], pageId: '123',
            placementConfig: ['automatic' => false, 'device_platforms' => ['mobile'], 'publisher_platforms' => ['facebook', 'instagram'], 'positions' => ['facebook' => ['feed', 'reels'], 'instagram' => ['story']]],
        ));

        \Illuminate\Support\Facades\Http::assertSent(function ($r) {
            $t = json_decode($r->data()['targeting'], true);

            return $t['geo_locations']['countries'] === ['VN']
                && $t['device_platforms'] === ['mobile']
                && $t['publisher_platforms'] === ['facebook', 'instagram']
                && $t['facebook_positions'] === ['feed', 'reels']
                && $t['instagram_positions'] === ['story'];
        });
    }

    public function test_create_adset_automatic_placements_not_merged(): void
    {
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*/adsets' => \Illuminate\Support\Facades\Http::response(['id' => 'AS'], 200)]);

        $this->connector()->createAdSet('tok', 'act_1', new \CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 1000, currency: 'VND', targeting: [], pageId: '123',
            placementConfig: ['automatic' => true, 'publisher_platforms' => ['facebook']],
        ));

        \Illuminate\Support\Facades\Http::assertSent(function ($r) {
            $t = json_decode($r->data()['targeting'], true);

            return ! array_key_exists('publisher_platforms', $t);
        });
    }
```
Add to `AdDraftSpecMapperTest`:
```php
    public function test_adset_passes_placement_config(): void
    {
        $draft = $this->draft([]);
        $node = ['name' => 'N', 'placement_config' => ['automatic' => false, 'publisher_platforms' => ['facebook']], 'ads' => [['creative' => ['page_id' => '1']]]];
        $spec = app(AdDraftSpecMapper::class)->adSet($draft, $node, 'C9', 'VND');
        $this->assertSame(['automatic' => false, 'publisher_platforms' => ['facebook']], $spec->placementConfig);
    }
```

- [ ] **Step 2 — run** both filters → new tests FAIL.

- [ ] **Step 3 — implement.**

`AdSetSpecDTO.php` — add a constructor param `public ?array $placementConfig = null,` (last, after `status`), with docblock `/** @param array<string,mixed>|null $placementConfig */`.

`FacebookAdsConnector::createAdSet` — replace `'targeting' => json_encode($spec->targeting),` in the `$params` literal with `'targeting' => json_encode($this->mergePlacements($spec->targeting, $spec->placementConfig)),`. Add a private method:
```php
    /**
     * @param  array<string,mixed>  $targeting
     * @param  array<string,mixed>|null  $pc
     * @return array<string,mixed>
     */
    private function mergePlacements(array $targeting, ?array $pc): array
    {
        if ($pc === null || ! empty($pc['automatic'])) {
            return $targeting;
        }
        foreach (['device_platforms', 'publisher_platforms'] as $k) {
            if (! empty($pc[$k]) && is_array($pc[$k])) {
                $targeting[$k] = array_values($pc[$k]);
            }
        }
        foreach (['facebook', 'instagram', 'messenger', 'audience_network'] as $plat) {
            $pos = $pc['positions'][$plat] ?? [];
            if (! empty($pos) && is_array($pos)) {
                $targeting["{$plat}_positions"] = array_values($pos);
            }
        }

        return $targeting;
    }
```

`AdDraftSpecMapper::adSet` — add `placementConfig:` to the returned `AdSetSpecDTO`:
```php
            placementConfig: isset($node['placement_config']) && is_array($node['placement_config']) ? $node['placement_config'] : null,
```

- [ ] **Step 4 — run** `php artisan test --filter='FacebookAdsCreateTest|AdDraftSpecMapperTest|PublishAdDraftTest'` → PASS.

- [ ] **Step 5 — commit:**
```
cd app && vendor/bin/pint app/Integrations/Ads/DTO/AdSetSpecDTO.php app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/Modules/Marketing/Services/AdDraftSpecMapper.php tests/Feature/Marketing/FacebookAdsCreateTest.php tests/Feature/Marketing/AdDraftSpecMapperTest.php && vendor/bin/phpstan analyse app/Integrations/Ads app/Modules/Marketing/Services && cd ..
git add app/app/Integrations/Ads/DTO/AdSetSpecDTO.php app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/app/Modules/Marketing/Services/AdDraftSpecMapper.php app/tests/Feature/Marketing/FacebookAdsCreateTest.php app/tests/Feature/Marketing/AdDraftSpecMapperTest.php
git commit -m "feat(ads): placements -> Graph targeting (device/platform/positions per adset)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: FE — full placement picker

**Files:**
- Modify: `app/resources/js/pages/adWizard/StepPlacements.tsx`

Rework `StepPlacements` to write `adset.placement_config` (via `updateAdSet(key, { placement_config })`):
- `Segmented` Tự động/Thủ công → `placement_config.automatic` (true/false).
- Manual:
  - "Thiết bị": `Checkbox.Group` options [{label:'Điện thoại',value:'mobile'},{label:'Máy tính',value:'desktop'}] → `placement_config.device_platforms`.
  - "Nền tảng": `Checkbox.Group` [{Facebook:'facebook'},{Instagram:'instagram'},{Messenger:'messenger'},{Audience Network:'audience_network'}] → `placement_config.publisher_platforms`.
  - For each SELECTED platform, a `Checkbox.Group` of its positions → `placement_config.positions[platform]`:
    - facebook: [{Bảng tin:'feed'},{Marketplace:'marketplace'},{Video feeds:'video_feeds'},{Tin:'story'},{Reels:'facebook_reels'},{Cột phải:'right_hand_column'},{Tìm kiếm:'search'}]
    - instagram: [{Bảng tin:'stream'},{Tin:'story'},{Reels:'reels'},{Khám phá:'explore'}]
    - messenger: [{Trang chủ:'messenger_home'},{Tin:'story'}]
    - audience_network: [{Cổ điển:'classic'},{Video thưởng:'rewarded_video'}]
- Read current `adset.placement_config` (default `{automatic:true}`). If no adset → `<Empty>`. Use the store's `AdSetNode` — `placement_config` is covered by the node's index signature OR add `placement_config?: Record<string,unknown>` to `AdSetNode` in `app/resources/js/lib/adWizard.tsx` if tsc complains (then also `git add` that file).
- Icons @ant-design/icons. No emoji. Verify tsc + eslint, commit:
```
git add app/resources/js/pages/adWizard/StepPlacements.tsx
git commit -m "feat(ads-fe): full placement picker (device/platform/positions)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Self-Review
Connector merge (T1) ✓, mapper passes config (T1) ✓, FE picker (T2) ✓. `automatic` default keeps current behaviour. Generic `placement_config` in node/DTO; Graph field names only in connector (`mergePlacements`). Position lists curated (common ones the user named: feed/video/marketplace/stories/reels).
