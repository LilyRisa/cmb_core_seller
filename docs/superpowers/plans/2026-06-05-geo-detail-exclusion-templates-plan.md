# Geo chi tiết + Mẫu loại trừ Implementation Plan

> REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Branch: **main** (commit directly). `git add` only exact paths.
> Commands run from `D:\cmb_core_seller\app`. Namespace `CMBcoreSeller\` → `app/app/`. Local has NO postgres (connection errors in DB tests ≠ your failure). ~244 pre-existing phpstan errors in OTHER modules — scope phpstan to given paths only.

**Goal:** Geo nhắm theo quốc gia/vùng/thành phố + loại trừ địa điểm, và lưu/áp mẫu loại trừ tái sử dụng — đều đi qua targeting pass-through (không đổi DTO/mapper).

**Architecture:** Connector thêm nhánh search `adgeolocation`. FE `StepAudience` dựng `geo_locations` + `excluded_geo_locations` từ metadata `node.geo`. Mẫu loại trừ = bảng tenant-scoped (mirror `ad_drafts`) + CRUD mỏng + hooks FE.

---

### Task 1: Connector — geo search (`adgeolocation`)

**Files:**
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (`searchTargeting` + private `geoLabel`)
- Test: `app/tests/Feature/Marketing/FacebookAdsCreateTest.php` (+2)

- [ ] **Step 1 — tests** (append to `FacebookAdsCreateTest`, mirror existing `connector()` + `Http::fake`/`assertSent` style):
```php
    public function test_search_targeting_geo_uses_adgeolocation_and_location_types(): void
    {
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*/search*' => \Illuminate\Support\Facades\Http::response(['data' => [
            ['key' => 'VN', 'name' => 'Vietnam', 'type' => 'country'],
            ['key' => '3658', 'name' => 'Hanoi', 'type' => 'region', 'country_code' => 'VN', 'country_name' => 'Vietnam'],
            ['key' => '1006824', 'name' => 'Hanoi', 'type' => 'city', 'region' => 'Hanoi', 'country_code' => 'VN'],
        ]], 200)]);

        $out = $this->connector()->searchTargeting('tok', 'Hanoi', 'adgeolocation');

        \Illuminate\Support\Facades\Http::assertSent(function ($r) {
            return str_contains($r->url(), 'type=adgeolocation')
                && str_contains(urldecode($r->url()), 'location_types');
        });
        $this->assertCount(3, $out);
        $this->assertSame('VN', $out[0]->id);
        $this->assertSame('country', $out[0]->type);
        $this->assertSame('3658', $out[1]->id);
        $this->assertSame('region', $out[1]->type);
        $this->assertSame('1006824', $out[2]->id);
        $this->assertSame('city', $out[2]->type);
    }

    public function test_search_targeting_interest_unchanged(): void
    {
        \Illuminate\Support\Facades\Http::fake(['graph.facebook.com/*/search*' => \Illuminate\Support\Facades\Http::response(['data' => [
            ['id' => '123', 'name' => 'Coffee', 'audience_size_lower_bound' => 1000],
        ]], 200)]);

        $out = $this->connector()->searchTargeting('tok', 'coffee');

        $this->assertSame('123', $out[0]->id);
        $this->assertSame('interests', $out[0]->type);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r->url(), 'type=adinterest'));
    }
```

- [ ] **Step 2 — run** `php artisan test --filter='test_search_targeting_geo_uses_adgeolocation_and_location_types|test_search_targeting_interest_unchanged'` → geo test FAILS (id empty / type wrong), interest test PASSES.

- [ ] **Step 3 — implement.** Replace the body of `searchTargeting` so geo is handled. New version:
```php
    public function searchTargeting(string $accessToken, string $query, string $type = 'adinterest'): array
    {
        $isGeo = $type === 'adgeolocation';
        $params = [
            'type' => $type,
            'q' => $query,
            'limit' => 50,
            'access_token' => $accessToken,
        ];
        if ($isGeo) {
            $params['location_types'] = json_encode(['country', 'region', 'city']);
        }

        $res = Http::timeout(30)->get($this->graphUrl('search'), $params);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads searchTargeting failed: '.$res->body());
        }

        if ($isGeo) {
            return array_values(array_map(fn (array $o) => new TargetingOptionDTO(
                id: (string) ($o['key'] ?? ''),
                name: $this->geoLabel($o),
                type: (string) ($o['type'] ?? 'geo'),
                audienceSize: null,
                raw: $o,
            ), array_filter((array) $res->json('data', []), 'is_array')));
        }

        // Label derived from the Graph search type so a 'adbehavior' search isn't
        // mislabelled as 'interests' (the DTO type must reflect what was searched).
        $typeLabel = match ($type) {
            'adinterest' => 'interests',
            'adbehavior' => 'behaviors',
            default => $type,
        };

        return array_values(array_map(fn (array $o) => new TargetingOptionDTO(
            id: (string) ($o['id'] ?? ''),
            name: (string) ($o['name'] ?? ''),
            type: $typeLabel,
            audienceSize: isset($o['audience_size_lower_bound']) ? (int) $o['audience_size_lower_bound'] : null,
            raw: $o,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    /** @param array<string,mixed> $o */
    private function geoLabel(array $o): string
    {
        $name = (string) ($o['name'] ?? '');
        $parts = array_values(array_filter([
            is_string($o['region'] ?? null) ? $o['region'] : null,
            is_string($o['country_name'] ?? null) ? $o['country_name'] : (is_string($o['country_code'] ?? null) ? $o['country_code'] : null),
        ], fn ($p) => is_string($p) && $p !== '' && $p !== $name));

        return $parts === [] ? $name : $name.' · '.implode(', ', $parts);
    }
```

- [ ] **Step 4 — run** `php artisan test --filter='FacebookAdsCreateTest'` → all PASS.

- [ ] **Step 5 — gate + commit:**
```
cd /d/cmb_core_seller/app && vendor/bin/pint app/Integrations/Ads/Facebook/FacebookAdsConnector.php tests/Feature/Marketing/FacebookAdsCreateTest.php && vendor/bin/phpstan analyse app/Integrations/Ads && cd ..
git add app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/tests/Feature/Marketing/FacebookAdsCreateTest.php
git commit -m "feat(ads): geo targeting search (adgeolocation: country/region/city)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Backend — exclusion templates (tenant-scoped CRUD)

**Files (create unless noted):**
- `app/app/Modules/Marketing/Database/Migrations/2026_06_05_100010_create_geo_exclusion_templates_table.php`
- `app/app/Modules/Marketing/Models/GeoExclusionTemplate.php`
- `app/app/Modules/Marketing/Services/GeoExclusionTemplateService.php`
- `app/app/Modules/Marketing/Http/Requests/GeoExclusionTemplateRequest.php`
- `app/app/Modules/Marketing/Http/Resources/GeoExclusionTemplateResource.php`
- `app/app/Modules/Marketing/Http/Controllers/GeoExclusionTemplateController.php`
- Modify: `app/app/Modules/Marketing/Http/routes.php` (add 3 routes in the existing `api/v1/marketing` group)
- Test: `app/tests/Feature/Marketing/GeoExclusionTemplateApiTest.php` (new)

First READ `AdDraft.php` (model), the `ad_drafts` migration, `AdDraftController.php`, `AdDraftRequest.php`, one existing `*Resource.php`, and `routes.php` to match exact conventions (BelongsToTenant import path, casts() style, Gate abilities, route group).

- [ ] **Step 1 — migration:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_exclusion_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('created_by')->nullable();
            $table->string('name');
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_exclusion_templates');
    }
};
```

- [ ] **Step 2 — model** `GeoExclusionTemplate.php` (match `AdDraft` exactly: namespace `CMBcoreSeller\Modules\Marketing\Models`, `use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;`):
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class GeoExclusionTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'created_by', 'name', 'payload'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
```

- [ ] **Step 3 — service** `GeoExclusionTemplateService.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Modules\Marketing\Models\GeoExclusionTemplate;
use Illuminate\Database\Eloquent\Collection;

class GeoExclusionTemplateService
{
    /** @return Collection<int,GeoExclusionTemplate> */
    public function list(): Collection
    {
        return GeoExclusionTemplate::query()->orderByDesc('id')->get();
    }

    /** @param array<int,array<string,mixed>> $payload */
    public function create(?int $userId, string $name, array $payload): GeoExclusionTemplate
    {
        return GeoExclusionTemplate::create([
            'created_by' => $userId,
            'name' => $name,
            'payload' => array_values($payload),
        ]);
    }

    public function delete(GeoExclusionTemplate $template): void
    {
        $template->delete();
    }
}
```

- [ ] **Step 4 — request** `GeoExclusionTemplateRequest.php` (mirror `AdDraftRequest` class/namespace, `authorize(): true`):
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeoExclusionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'payload' => ['present', 'array'],
            'payload.*.key' => ['required', 'string'],
            'payload.*.name' => ['required', 'string'],
            'payload.*.type' => ['required', 'string', 'in:country,region,city'],
        ];
    }
}
```

- [ ] **Step 5 — resource** `GeoExclusionTemplateResource.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \CMBcoreSeller\Modules\Marketing\Models\GeoExclusionTemplate */
class GeoExclusionTemplateResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'payload' => $this->payload ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 6 — controller** `GeoExclusionTemplateController.php` (thin; mirror `AdDraftController` Gate + constructor-injected service + Resource):
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Modules\Marketing\Http\Requests\GeoExclusionTemplateRequest;
use CMBcoreSeller\Modules\Marketing\Http\Resources\GeoExclusionTemplateResource;
use CMBcoreSeller\Modules\Marketing\Models\GeoExclusionTemplate;
use CMBcoreSeller\Modules\Marketing\Services\GeoExclusionTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class GeoExclusionTemplateController extends Controller
{
    public function __construct(private GeoExclusionTemplateService $service) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('marketing.view');

        return GeoExclusionTemplateResource::collection($this->service->list());
    }

    public function store(GeoExclusionTemplateRequest $request): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $template = $this->service->create(
            $request->user()?->id,
            (string) $request->validated('name'),
            (array) $request->validated('payload'),
        );

        return (new GeoExclusionTemplateResource($template))->response()->setStatusCode(201);
    }

    public function destroy(GeoExclusionTemplate $template): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        $this->service->delete($template);

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 7 — routes.** In `app/app/Modules/Marketing/Http/routes.php`, inside the existing `api/v1/marketing` group (next to the `ad-drafts` routes), add (import the controller at top with the other `use` lines):
```php
    Route::get('exclusion-templates', [GeoExclusionTemplateController::class, 'index'])->name('marketing.exclusion-templates.index');
    Route::post('exclusion-templates', [GeoExclusionTemplateController::class, 'store'])->name('marketing.exclusion-templates.store');
    Route::delete('exclusion-templates/{template}', [GeoExclusionTemplateController::class, 'destroy'])->name('marketing.exclusion-templates.destroy');
```
Route-model binding `{template}` resolves `GeoExclusionTemplate` by id (tenant global scope ⇒ cross-tenant 404).

- [ ] **Step 8 — feature test** `GeoExclusionTemplateApiTest.php`. Mirror `AdAuthoringTargetingApiTest` scaffold (RefreshDatabase, Tenant::create, owner() with email_verified_at, h() header, `app(CurrentTenant::class)->set($tenant)` before tenant-scoped creates, `Role::Owner`). Cases:
```php
    public function test_create_list_and_delete_template(): void
    {
        $owner = $this->owner();
        app(\CMBcoreSeller\Modules\Tenancy\Context\CurrentTenant::class)->set($this->tenant); // match the exact CurrentTenant FQCN used in AdAuthoringTargetingApiTest

        $id = $this->actingAs($owner)->withHeaders($this->h())->postJson('/api/v1/marketing/exclusion-templates', [
            'name' => 'VN nội thành',
            'payload' => [['key' => '1006824', 'name' => 'Hanoi', 'type' => 'city']],
        ])->assertCreated()->json('data.id');

        $this->actingAs($owner)->withHeaders($this->h())->getJson('/api/v1/marketing/exclusion-templates')
            ->assertOk()->assertJsonPath('data.0.name', 'VN nội thành')
            ->assertJsonPath('data.0.payload.0.type', 'city');

        $this->actingAs($owner)->withHeaders($this->h())->deleteJson("/api/v1/marketing/exclusion-templates/{$id}")->assertNoContent();
        $this->actingAs($owner)->withHeaders($this->h())->getJson('/api/v1/marketing/exclusion-templates')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_duplicate_name_same_tenant_rejected(): void
    {
        $owner = $this->owner();
        app(\CMBcoreSeller\Modules\Tenancy\Context\CurrentTenant::class)->set($this->tenant);
        $body = ['name' => 'Dup', 'payload' => []];
        $this->actingAs($owner)->withHeaders($this->h())->postJson('/api/v1/marketing/exclusion-templates', $body)->assertCreated();
        $this->actingAs($owner)->withHeaders($this->h())->postJson('/api/v1/marketing/exclusion-templates', $body)->assertStatus(500); // unique violation surfaces as 500 (no app-level uniqueness rule)
    }
```
NOTE: confirm the exact `CurrentTenant` FQCN by reading `AdAuthoringTargetingApiTest` (the explorer referenced `app(CurrentTenant::class)`); use whatever import that test uses. If duplicate-name surfacing differs, adjust the assertion to the actual status (the goal is: a second identical name in the same tenant does not create a 2nd row — you may instead assert DB count). Keep it green.

- [ ] **Step 9 — run** `php artisan test --filter='GeoExclusionTemplateApiTest'` → PASS (requires sqlite migrate; run `php artisan migrate --force` env DB_CONNECTION=sqlite first if needed, but the test's RefreshDatabase handles it).

- [ ] **Step 10 — gate + commit:**
```
cd /d/cmb_core_seller/app && vendor/bin/pint app/Modules/Marketing tests/Feature/Marketing/GeoExclusionTemplateApiTest.php && vendor/bin/phpstan analyse app/Modules/Marketing && cd ..
git add app/app/Modules/Marketing/Database/Migrations/2026_06_05_100010_create_geo_exclusion_templates_table.php app/app/Modules/Marketing/Models/GeoExclusionTemplate.php app/app/Modules/Marketing/Services/GeoExclusionTemplateService.php app/app/Modules/Marketing/Http/Requests/GeoExclusionTemplateRequest.php app/app/Modules/Marketing/Http/Resources/GeoExclusionTemplateResource.php app/app/Modules/Marketing/Http/Controllers/GeoExclusionTemplateController.php app/app/Modules/Marketing/Http/routes.php app/tests/Feature/Marketing/GeoExclusionTemplateApiTest.php
git commit -m "feat(marketing): geo exclusion templates (tenant-scoped CRUD)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: FE — geo include/exclude in StepAudience

**Files:**
- Modify: `app/resources/js/lib/adWizard.tsx` (add `GeoItem` type; extend `AdSetNode` with `geo?`)
- Modify: `app/resources/js/pages/adWizard/StepAudience.tsx`

- [ ] **Step 1 — types** in `adWizard.tsx`. Add exported interface:
```ts
export interface GeoItem { key: string; name: string; type: 'country' | 'region' | 'city'; country_code?: string }
```
Add to `AdSetNode` interface:
```ts
    geo?: { include: GeoItem[]; exclude: GeoItem[] };
```

- [ ] **Step 2 — StepAudience rework.** Add `geo` state and persist both `targeting` (derived) and `geo` (meta) via `updateAdSet`.
  - Seed: read `adset.geo`; if absent, back-compat seed `include` from `targeting.geo_locations.countries` → `GeoItem[]` `{ key:c, name:c, type:'country' }`, `exclude: []`.
  - Replace the "Quốc gia" `<Select>` block with TWO geo pickers ("Khu vực nhắm đến" / "Loại trừ khu vực"), each an AntD `Select mode="multiple" labelInValue filterOption={false} showSearch` whose `onSearch` calls `targetingSearch.mutate({ accountId, q, type: 'adgeolocation' })` and maps results to options `{ label: o.name, value: o.id }` while caching the full `GeoItem` (build from result: `{ key:o.id, name:o.name, type:o.type as GeoItem['type'], country_code: undefined }`) so selection → `GeoItem`. Keep separate result-option state + a lookup map for include and exclude (or one shared search-results map keyed by id). Value shown = `geo.include`/`geo.exclude` mapped to `{label:name, value:key}`.
  - Implement `deriveGeo(geo)`:
```ts
function bucket(items: GeoItem[]) {
    const countries: string[] = [];
    const regions: { key: string }[] = [];
    const cities: { key: string; radius: number; distance_unit: string }[] = [];
    for (const it of items) {
        if (it.type === 'country') countries.push(it.country_code || it.key);
        else if (it.type === 'region') regions.push({ key: it.key });
        else cities.push({ key: it.key, radius: 25, distance_unit: 'kilometer' });
    }
    const geo: Record<string, unknown> = {};
    if (countries.length) geo.countries = countries;
    if (regions.length) geo.regions = regions;
    if (cities.length) geo.cities = cities;
    return geo;
}
```
  - Update `buildTargetingSpec` to accept `geo: { include: GeoItem[]; exclude: GeoItem[] }` instead of `countries: string[]`:
    - `const inc = bucket(geo.include);` → `spec.geo_locations = Object.keys(inc).length ? inc : { countries: ['VN'] };`
    - `const exc = bucket(geo.exclude);` → `if (Object.keys(exc).length) spec.excluded_geo_locations = exc;`
    - keep age/genders/interests logic unchanged.
  - In the persist `useEffect`, build spec from current `geo` state and call `updateAdSet(adsetKey, { targeting: spec, geo })`. Update the effect dep key to a serialized geo signature (e.g. `geo.include.map(i=>i.key).join(',')` + exclude) instead of `countriesKey`.
  - Remove `COUNTRY_OPTIONS` and the old `countries` state. Keep age/gender/interest UI intact.
  - Icons @ant-design/icons only. No emoji.

- [ ] **Step 3 — verify** `npm run typecheck` && `npm run lint` clean (0 errors). 

- [ ] **Step 4 — commit:**
```
git add app/resources/js/lib/adWizard.tsx app/resources/js/pages/adWizard/StepAudience.tsx
git commit -m "feat(ads-fe): geo targeting by country/region/city + location exclusion

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: FE — save & apply exclusion templates

**Files:**
- Create: `app/resources/js/lib/adWizard/exclusionTemplates.ts`
- Modify: `app/resources/js/pages/adWizard/StepAudience.tsx`

- [ ] **Step 1 — hooks lib** `exclusionTemplates.ts` (mirror the TanStack-Query + `useScopedApi` pattern in `adWizard.tsx`; READ how `useAdDrafts`/`useCreateDraft`/`useScopedApi` and `queryClient.invalidateQueries` are used there):
```ts
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useScopedApi } from '@/lib/api'; // confirm the exact import used by adWizard.tsx for the scoped axios
import type { GeoItem } from '@/lib/adWizard';

export interface ExclusionTemplate { id: number; name: string; payload: GeoItem[]; created_at: string | null }

const KEY = ['marketing', 'exclusion-templates'];

export function useExclusionTemplates() {
    const api = useScopedApi();
    return useQuery({
        queryKey: KEY,
        queryFn: async () => (await api!.get<{ data: ExclusionTemplate[] }>('/marketing/exclusion-templates')).data.data,
        enabled: api != null,
    });
}

export function useCreateExclusionTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (body: { name: string; payload: GeoItem[] }) =>
            (await api!.post<{ data: ExclusionTemplate }>('/marketing/exclusion-templates', body)).data.data,
        onSuccess: () => { void qc.invalidateQueries({ queryKey: KEY }); },
    });
}

export function useDeleteExclusionTemplate() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/marketing/exclusion-templates/${id}`); },
        onSuccess: () => { void qc.invalidateQueries({ queryKey: KEY }); },
    });
}
```
IMPORTANT: match the EXACT scoped-api import/usage style of `adWizard.tsx` (it uses `useScopedApi` returning a possibly-null axios — mirror its null-handling `api!`). If `adWizard.tsx` imports from a different path, use that.

- [ ] **Step 2 — UI in StepAudience** (exclusion area): below the "Loại trừ khu vực" picker add a `Space`:
  - `<Select>` "Áp mẫu loại trừ" listing `useExclusionTemplates().data` (option label=name, value=id); on select → merge that template's `payload` into `geo.exclude` deduped by `key`, then persist.
  - Button **"Lưu thành mẫu"** (`SaveOutlined`) → opens an AntD `Modal` with an `Input` for name; on OK calls `useCreateExclusionTemplate().mutate({ name, payload: geo.exclude })` (disable when `geo.exclude` empty). Use `App.useApp()` `message` for success/error (`errorMessage` from `@/lib/api`).
  - Small delete affordance per template optional (a `Popconfirm` + `useDeleteExclusionTemplate`) — include it as a compact list under the apply Select.
  - Icons @ant-design/icons only. No emoji. AntD components.

- [ ] **Step 3 — verify** `npm run typecheck` && `npm run lint` clean; then `npm run build` once (deploy gate) → success.

- [ ] **Step 4 — commit:**
```
git add app/resources/js/lib/adWizard/exclusionTemplates.ts app/resources/js/pages/adWizard/StepAudience.tsx
git commit -m "feat(ads-fe): save & apply geo exclusion templates

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Self-Review
- Geo search (T1) ✓ adgeolocation branch, interest path unchanged, type country/region/city surfaced via DTO `id`/`type`.
- Templates CRUD (T2) ✓ tenant-scoped, mirrors `ad_drafts`, unique per tenant, Gate marketing.view/ads.create.
- FE geo include/exclude (T3) ✓ derives `geo_locations`+`excluded_geo_locations` into opaque targeting; back-compat seed from old countries; `node.geo` meta ignored by mapper.
- FE templates (T4) ✓ save current excludes, apply (dedupe by key), delete.
- No DTO/mapper/connector-createAdSet change (targeting stays pass-through). Generic in core; Graph field names only in connector. Money/VND not involved.
- Types consistent: `GeoItem` shared FE type (adWizard.tsx) used by StepAudience + exclusionTemplates.ts; backend `payload` = `GeoItem[]`.
