# Ad Tree Structure (multi-adset / multi-ad) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`).

**Goal:** Let one `AdDraft` describe a tree (campaign → multiple ad sets → multiple ads) and publish the whole tree (resume-first), with a normalizer that keeps legacy flat drafts working.

**Architecture:** A pure `AdDraftTree::normalize()` collapses both the legacy flat payload and the new `payload.adsets[]` tree into one shape. `AdDraftSpecMapper` becomes node-based. `PublishAdDraft` walks the tree, storing each node's `external_id` back into the payload for idempotent resume. FE store + wizard gain adset/ad lists (separate tasks).

**Tech Stack:** Laravel 11, Horizon, connector DTOs (Plan 1), PHPUnit `Http::fake`, Pint, Larastan L5; React/Zustand/AntD for FE.

**Conventions:** Commands from `app/`. Build on existing `AdDraftSpecMapper`, `Jobs/PublishAdDraft`, `Models/AdDraft`, `lib/adWizard/draftStore.ts`, `pages/AdWizardPage.tsx`. Branch `feature/ads-manager-parity`. Each commit `git add`s only its exact paths.

---

### Task 1: `AdDraftTree::normalize` (legacy-flat ↔ tree → one shape)

**Files:**
- Create: `app/app/Modules/Marketing/Support/AdDraftTree.php`
- Test: `app/tests/Feature/Marketing/AdDraftTreeTest.php`

- [ ] **Step 1: failing test**
```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Support\AdDraftTree;
use PHPUnit\Framework\TestCase;

class AdDraftTreeTest extends TestCase
{
    public function test_tree_payload_passes_through(): void
    {
        $payload = ['adsets' => [
            ['key' => 'a1', 'name' => 'Nhóm 1', 'budget' => ['daily_major' => 100000],
             'targeting' => ['x' => 1], 'ads' => [['key' => 'd1', 'name' => 'QC', 'creative' => ['mode' => 'page_post']]]],
        ]];

        $tree = AdDraftTree::normalize($payload);

        $this->assertCount(1, $tree['adsets']);
        $this->assertSame('a1', $tree['adsets'][0]['key']);
        $this->assertCount(1, $tree['adsets'][0]['ads']);
    }

    public function test_legacy_flat_payload_is_wrapped_into_one_adset_one_ad(): void
    {
        $payload = [
            'budget' => ['daily_major' => 150000],
            'targeting' => ['geo_locations' => ['countries' => ['VN']]],
            'placements' => 'automatic',
            'schedule' => ['start_time' => null],
            'creative' => ['mode' => 'page_post', 'page_id' => '123', 'page_post_id' => '123_456', 'cta' => 'MESSAGE_PAGE'],
        ];

        $tree = AdDraftTree::normalize($payload);

        $this->assertCount(1, $tree['adsets']);
        $as = $tree['adsets'][0];
        $this->assertSame(150000, $as['budget']['daily_major']);
        $this->assertSame('automatic', $as['placements']);
        $this->assertSame(['geo_locations' => ['countries' => ['VN']]], $as['targeting']);
        $this->assertCount(1, $as['ads']);
        $this->assertSame('123_456', $as['ads'][0]['creative']['page_post_id']);
    }

    public function test_empty_payload_yields_empty_adsets(): void
    {
        $this->assertSame([], AdDraftTree::normalize([])['adsets']);
    }
}
```

- [ ] **Step 2: run** `php artisan test --filter=AdDraftTreeTest` → FAIL.

- [ ] **Step 3: implement** `AdDraftTree.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Support;

/**
 * Normalizes an AdDraft payload into ONE tree shape {adsets:[{...,ads:[...]}]}.
 * Accepts both the new tree payload and the legacy flat v1 payload (which is
 * wrapped into a single ad set + single ad), so mapper/publish have one code path.
 */
final class AdDraftTree
{
    /**
     * @param  array<string,mixed>  $payload
     * @return array{adsets: list<array<string,mixed>>}
     */
    public static function normalize(array $payload): array
    {
        if (isset($payload['adsets']) && is_array($payload['adsets'])) {
            return ['adsets' => array_values(array_filter($payload['adsets'], 'is_array'))];
        }

        // Legacy flat payload → one ad set + one ad. Empty payload → no ad sets.
        $hasFlat = isset($payload['creative']) || isset($payload['targeting']) || isset($payload['budget']);
        if (! $hasFlat) {
            return ['adsets' => []];
        }

        return ['adsets' => [[
            'key' => 'adset-1',
            'name' => 'Nhóm 1',
            'budget' => (array) ($payload['budget'] ?? []),
            'targeting' => (array) ($payload['targeting'] ?? []),
            'placements' => $payload['placements'] ?? 'automatic',
            'placement_platforms' => (array) ($payload['placement_platforms'] ?? []),
            'schedule' => (array) ($payload['schedule'] ?? []),
            'external_id' => null,
            'ads' => [[
                'key' => 'ad-1',
                'name' => 'Quảng cáo 1',
                'external_id' => null,
                'creative' => (array) ($payload['creative'] ?? []),
            ]],
        ]]];
    }
}
```

- [ ] **Step 4: run** `php artisan test --filter=AdDraftTreeTest` → PASS (3).
- [ ] **Step 5: commit**
```
cd app && vendor/bin/pint app/Modules/Marketing/Support/AdDraftTree.php tests/Feature/Marketing/AdDraftTreeTest.php && vendor/bin/phpstan analyse app/Modules/Marketing/Support/AdDraftTree.php && cd ..
git add app/app/Modules/Marketing/Support/AdDraftTree.php app/tests/Feature/Marketing/AdDraftTreeTest.php
git commit -m "feat(ads): AdDraftTree normalizer (legacy-flat + tree -> one shape)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: node-based `AdDraftSpecMapper`

**Files:**
- Modify: `app/app/Modules/Marketing/Services/AdDraftSpecMapper.php`
- Modify: `app/tests/Feature/Marketing/AdDraftSpecMapperTest.php`

Change `adSet`/`ad` to take a node + resolved parent external id; add `adsetNodes(AdDraft): array` (delegates to `AdDraftTree::normalize`). `campaign()` unchanged. New signatures:
```php
public function adsetNodes(AdDraft $draft): array  // list<array> from AdDraftTree::normalize($draft->payload)['adsets']
public function adSet(AdDraft $draft, array $node, string $campaignExternalId, string $currency): AdSetSpecDTO
public function ad(AdDraft $draft, array $node, string $adSetExternalId): AdSpecDTO
```
`adSet` reads `$node['budget']['daily_major']`, `$node['targeting']`, `$node['creative']['page_id']`? No — pageId for messaging promoted_object comes from the AD's creative; for the adset, pull page_id from the first ad's creative in the node (`$node['ads'][0]['creative']['page_id']`). `ad` reads `$node['creative']`. Tests updated to the node form (build a draft with `payload.adsets`). Full code provided at dispatch time.

---

### Task 3: tree-aware `PublishAdDraft`

**Files:**
- Modify: `app/app/Modules/Marketing/Jobs/PublishAdDraft.php`
- Modify: `app/tests/Feature/Marketing/PublishAdDraftTest.php`

Walk the normalized tree: create campaign (column), then per adset node (create + write `external_id` into the payload node + save), then per ad node (create + write `external_id` + save). Resume skips nodes with an `external_id`. Failure mid-tree → status `failed`, created nodes keep ids. Tests: 2 adsets × 2 ads end published with all ids; resume skips; failure on 2nd adset keeps 1st adset + its ads. Full code at dispatch.

---

### Task 4: FE store — adset/ad lists

**Files:**
- Modify: `app/resources/js/lib/adWizard/draftStore.ts`
- Modify: `app/resources/js/lib/adWizard.tsx` (extend `AdDraftPayload` with `adsets`)

Add `AdSetNode`/`AdNode` types + store `adsets: AdSetNode[]` + actions `addAdSet/removeAdSet/updateAdSet(key,patch)/addAd(adsetKey)/removeAd/updateAd`. On `load`, normalize legacy flat → one adset/ad. Verify tsc/eslint.

---

### Task 5: FE wizard — multiple ad sets + ads UI

**Files:**
- Modify: `app/resources/js/pages/adWizard/StepAudience.tsx` / `StepPlacements.tsx` (per-adset) + a new `pages/adWizard/AdSetList.tsx`
- Modify: `app/resources/js/pages/adWizard/StepCreative.tsx` (ads list within selected adset)
- Modify: `app/resources/js/pages/AdWizardPage.tsx` (wire)

A "Nhóm quảng cáo" manager (Tabs/Collapse of adsets, "＋ Thêm nhóm", remove) where each adset edits targeting/placements/budget; the creative step manages an ads list within the selected adset ("＋ Thêm quảng cáo"). Autosave sends `payload.adsets`. Verify tsc/eslint. (`key` left in place for sub-feature H clone.)

---

## Self-Review
**Spec coverage:** normalize (T1) ✓, node mapper (T2) ✓, tree publish (T3) ✓, FE store (T4) ✓, FE wizard (T5) ✓. Backward-compat via `AdDraftTree::normalize` (T1) used by T2/T3/T4. Budget stays adset-level (CBO = sub-feature B). `key` preserved for clone (H).
**Placeholders:** T2/T3/T5 give shapes + behaviors; full code provided at dispatch (the controller will hand each implementer complete code). T1/T4 fully coded here.
**Type consistency:** `AdDraftTree::normalize(): {adsets:[{...,external_id,ads:[{...,external_id,creative}]}]}` consumed identically by mapper (T2) + publish (T3) + FE store (T4).
