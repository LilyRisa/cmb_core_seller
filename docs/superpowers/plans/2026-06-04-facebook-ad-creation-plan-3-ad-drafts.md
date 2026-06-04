# Facebook Ad Creation — Plan 3: Ad Drafts (model + CRUD/autosave API) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`).

**Goal:** Persist the wizard's work-in-progress as `AdDraft` rows with a tenant-scoped CRUD + autosave API, so the wizard (Plan 5) can save/restore drafts without needing publish permission.

**Architecture:** Marketing-module feature: migration + `AdDraft` model (BelongsToTenant), a thin `AdDraftService`, and a thin controller (FormRequest → service → API Resource). Drafts are a loosely-validated bag of wizard state (`payload` JSON); strict validation happens at publish (Plan 4). RBAC: read = `marketing.view`, write = `marketing.ads.create` (Owner/Admin get it via the `*` wildcard; staff roles don't).

**Tech Stack:** Laravel 11, Eloquent, Sanctum cookie auth + `tenant` middleware, PHPUnit `RefreshDatabase`, Pint, Larastan L5.

**Conventions:** Commands from `app/`. Namespace `CMBcoreSeller\Modules\Marketing\*` → `app/app/Modules/Marketing/*`. Mirror existing files: model `Models/AdAccount.php`, migration `Database/Migrations/2026_06_04_100001_create_ad_accounts_table.php`, controller `Http/Controllers/AdAccountController.php`, routes `Http/routes.php`, feature test `tests/Feature/Marketing/AdAccountApiTest.php`. The `MarketingServiceProvider` already `loadMigrationsFrom` the migrations dir and loads `Http/routes.php` — no provider edits needed.

---

### Task 1: `ad_drafts` migration + `AdDraft` model

**Files:**
- Create: `app/app/Modules/Marketing/Database/Migrations/2026_06_04_100007_create_ad_drafts_table.php`
- Create: `app/app/Modules/Marketing/Models/AdDraft.php`
- Test: `app/tests/Feature/Marketing/AdDraftModelTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdDraftModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_draft_with_tenant_autoset_and_payload_cast(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);

        $draft = AdDraft::create([
            'ad_account_id' => 11,
            'name' => 'Bản nháp Tết',
            'objective' => 'messages',
            'payload' => ['budget' => 150000, 'targeting' => ['age_min' => 18]],
        ]);

        $this->assertSame((int) $tenant->getKey(), $draft->tenant_id);          // BelongsToTenant auto-set
        $this->assertSame('draft', $draft->status);                              // DB default
        $this->assertIsArray($draft->fresh()->payload);                          // json cast
        $this->assertSame(150000, $draft->fresh()->payload['budget']);
    }

    public function test_tenant_scope_hides_other_tenants_drafts(): void
    {
        $t1 = Tenant::create(['name' => 'T1']);
        app(CurrentTenant::class)->set($t1);
        AdDraft::create(['ad_account_id' => 1, 'name' => 'mine', 'payload' => []]);

        $t2 = Tenant::create(['name' => 'T2']);
        app(CurrentTenant::class)->set($t2);

        $this->assertSame(0, AdDraft::query()->count());                         // global scope
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AdDraftModelTest` → FAIL (table/model missing).

- [ ] **Step 3: Implement.**

Migration `2026_06_04_100007_create_ad_drafts_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ad_drafts — wizard work-in-progress for ONE ad (1 draft = 1 campaign/adset/ad in v1).
 * Loosely validated (payload JSON holds the step state); strict validation + the
 * Facebook external ids are filled at publish time (PublishAdDraft, Plan 4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->foreignId('created_by')->nullable();
            $table->string('name')->nullable();
            $table->string('status')->default('draft');   // draft | publishing | published | failed
            $table->string('objective')->nullable();       // internal code: messages|engagement|traffic
            $table->json('payload')->nullable();           // wizard step state
            $table->string('idempotency_key')->nullable(); // set at publish, for idempotent retries
            $table->string('campaign_external_id')->nullable();
            $table->string('adset_external_id')->nullable();
            $table->string('ad_external_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'ad_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_drafts');
    }
};
```

Model `AdDraft.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Wizard draft for one Facebook ad. `payload` holds the per-step state; external
 * ids + status transition at publish time (PublishAdDraft, Plan 4).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $ad_account_id
 * @property ?int $created_by
 * @property ?string $name
 * @property string $status
 * @property ?string $objective
 * @property ?array<string,mixed> $payload
 * @property ?string $idempotency_key
 * @property ?string $campaign_external_id
 * @property ?string $adset_external_id
 * @property ?string $ad_external_id
 * @property ?string $last_error
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class AdDraft extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHING = 'publishing';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'ad_account_id', 'created_by', 'name', 'status', 'objective', 'payload',
        'idempotency_key', 'campaign_external_id', 'adset_external_id', 'ad_external_id', 'last_error',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
```

- [ ] **Step 4: Run** `php artisan test --filter=AdDraftModelTest` → PASS (2 tests).

- [ ] **Step 5: Quality + commit**
```
# from app/
vendor/bin/pint app/Modules/Marketing/Models/AdDraft.php app/Modules/Marketing/Database/Migrations/2026_06_04_100007_create_ad_drafts_table.php tests/Feature/Marketing/AdDraftModelTest.php
vendor/bin/phpstan analyse app/Modules/Marketing/Models/AdDraft.php
```
```
git add app/app/Modules/Marketing/Models/AdDraft.php app/app/Modules/Marketing/Database/Migrations/2026_06_04_100007_create_ad_drafts_table.php app/tests/Feature/Marketing/AdDraftModelTest.php
git commit -m "feat(ads): ad_drafts table + AdDraft model (tenant-scoped, payload json)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: `AdDraftService` (create / update)

**Files:**
- Create: `app/app/Modules/Marketing/Services/AdDraftService.php`
- Test: `app/tests/Feature/Marketing/AdDraftServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdDraftServiceTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): AdDraftService
    {
        return app(AdDraftService::class);
    }

    public function test_create_persists_defaults(): void
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));

        $draft = $this->svc()->create(11, 7, ['name' => 'N', 'objective' => 'messages', 'payload' => ['a' => 1]]);

        $this->assertSame(11, $draft->ad_account_id);
        $this->assertSame(7, $draft->created_by);
        $this->assertSame('draft', $draft->status);
        $this->assertSame(['a' => 1], $draft->payload);
    }

    public function test_update_only_touches_provided_fields(): void
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));
        $draft = $this->svc()->create(11, 7, ['name' => 'Old', 'objective' => 'messages', 'payload' => ['a' => 1]]);

        $updated = $this->svc()->update($draft, ['payload' => ['a' => 2, 'b' => 3]]);

        $this->assertSame('Old', $updated->name);            // unchanged (not provided)
        $this->assertSame('messages', $updated->objective);  // unchanged
        $this->assertSame(['a' => 2, 'b' => 3], $updated->payload); // replaced
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AdDraftServiceTest` → FAIL.

- [ ] **Step 3: Implement** `AdDraftService.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

use CMBcoreSeller\Modules\Marketing\Models\AdDraft;

/**
 * CRUD for wizard drafts. tenant_id is auto-set by BelongsToTenant. Update only
 * overwrites the fields present in the request (autosave sends partial payloads).
 */
class AdDraftService
{
    /** @param array<string,mixed> $data */
    public function create(int $adAccountId, ?int $userId, array $data): AdDraft
    {
        return AdDraft::create([
            'ad_account_id' => $adAccountId,
            'created_by' => $userId,
            'name' => $data['name'] ?? null,
            'objective' => $data['objective'] ?? null,
            'payload' => $data['payload'] ?? [],
            'status' => AdDraft::STATUS_DRAFT,
        ]);
    }

    /** @param array<string,mixed> $data */
    public function update(AdDraft $draft, array $data): AdDraft
    {
        foreach (['name', 'objective', 'payload'] as $field) {
            if (array_key_exists($field, $data)) {
                $draft->{$field} = $data[$field];
            }
        }
        $draft->save();

        return $draft;
    }
}
```

- [ ] **Step 4: Run** `php artisan test --filter=AdDraftServiceTest` → PASS (2 tests).

- [ ] **Step 5: Quality + commit**
```
# from app/
vendor/bin/pint app/Modules/Marketing/Services/AdDraftService.php tests/Feature/Marketing/AdDraftServiceTest.php
vendor/bin/phpstan analyse app/Modules/Marketing/Services/AdDraftService.php
```
```
git add app/app/Modules/Marketing/Services/AdDraftService.php app/tests/Feature/Marketing/AdDraftServiceTest.php
git commit -m "feat(ads): AdDraftService (create + partial-update for autosave)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: CRUD/autosave API (request + controller + resource + routes)

**Files:**
- Create: `app/app/Modules/Marketing/Http/Requests/AdDraftRequest.php`
- Create: `app/app/Modules/Marketing/Http/Resources/AdDraftResource.php`
- Create: `app/app/Modules/Marketing/Http/Controllers/AdDraftController.php`
- Modify: `app/app/Modules/Marketing/Http/routes.php`
- Test: `app/tests/Feature/Marketing/AdDraftApiTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdDraftApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
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

    private function account(): AdAccount
    {
        app(CurrentTenant::class)->set($this->tenant);

        return AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1',
            'currency' => 'VND', 'status' => 'active', 'access_token' => 'X',
        ]);
    }

    public function test_owner_can_create_then_autosave_then_show(): void
    {
        $acc = $this->account();

        $id = $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson('/api/v1/marketing/ad-drafts', [
                'ad_account_id' => $acc->id, 'name' => 'Tết', 'objective' => 'messages',
                'payload' => ['budget' => 150000],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.name', 'Tết')
            ->json('data.id');

        // autosave a partial update
        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->patchJson("/api/v1/marketing/ad-drafts/{$id}", ['payload' => ['budget' => 200000]])
            ->assertOk()
            ->assertJsonPath('data.payload.budget', 200000);

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-drafts/{$id}")
            ->assertOk()
            ->assertJsonPath('data.payload.budget', 200000);
    }

    public function test_index_lists_only_tenant_drafts(): void
    {
        $acc = $this->account();
        AdDraft::create(['ad_account_id' => $acc->id, 'name' => 'mine', 'payload' => []]);

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->getJson('/api/v1/marketing/ad-drafts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_create_rejects_account_from_other_tenant(): void
    {
        // account belongs to a DIFFERENT tenant
        $other = Tenant::create(['name' => 'Other']);
        app(CurrentTenant::class)->set($other);
        $foreign = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_x', 'status' => 'active', 'access_token' => 'X']);

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson('/api/v1/marketing/ad-drafts', ['ad_account_id' => $foreign->id, 'payload' => []])
            ->assertStatus(404); // tenant-scoped lookup fails
    }

    public function test_staff_order_forbidden_to_create(): void
    {
        $acc = $this->account();

        $this->actingAs($this->user(Role::StaffOrder))->withHeaders($this->h())
            ->postJson('/api/v1/marketing/ad-drafts', ['ad_account_id' => $acc->id, 'payload' => []])
            ->assertForbidden();
    }

    public function test_owner_can_delete(): void
    {
        $acc = $this->account();
        $draft = AdDraft::create(['ad_account_id' => $acc->id, 'name' => 'x', 'payload' => []]);

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->deleteJson("/api/v1/marketing/ad-drafts/{$draft->id}")
            ->assertOk();
        $this->assertDatabaseMissing('ad_drafts', ['id' => $draft->id]);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AdDraftApiTest` → FAIL (routes/controller missing).

- [ ] **Step 3: Implement.**

`AdDraftRequest.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates ad-draft create/update. Drafts are work-in-progress so fields are
 * lenient (most nullable); strict completeness is checked at publish (Plan 4).
 */
class AdDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission enforced in controller via Gate
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'ad_account_id' => [$creating ? 'required' : 'prohibited', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:32'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
```

`AdDraftResource.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Resources;

use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AdDraft */
class AdDraftResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ad_account_id' => $this->ad_account_id,
            'name' => $this->name,
            'status' => $this->status,
            'objective' => $this->objective,
            'payload' => $this->payload ?? [],
            'campaign_external_id' => $this->campaign_external_id,
            'adset_external_id' => $this->adset_external_id,
            'ad_external_id' => $this->ad_external_id,
            'last_error' => $this->last_error,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

`AdDraftController.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Marketing\Http\Requests\AdDraftRequest;
use CMBcoreSeller\Modules\Marketing\Http\Resources\AdDraftResource;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Wizard draft CRUD + autosave. Read = marketing.view; write = marketing.ads.create
 * (Owner/Admin). All lookups are tenant-scoped via the model global scope.
 */
class AdDraftController extends Controller
{
    public function __construct(private AdDraftService $service) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('marketing.view');

        return AdDraftResource::collection(AdDraft::query()->latest('id')->get());
    }

    public function store(AdDraftRequest $request): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        // Tenant-scoped ownership check: findOrFail respects the global scope ⇒ 404 for foreign accounts.
        $account = AdAccount::query()->findOrFail((int) $request->integer('ad_account_id'));

        $draft = $this->service->create($account->id, $request->user()?->id, $request->validated());

        return (new AdDraftResource($draft))->response()->setStatusCode(201);
    }

    public function show(int $id): AdDraftResource
    {
        Gate::authorize('marketing.view');

        return new AdDraftResource(AdDraft::query()->findOrFail($id));
    }

    public function update(int $id, AdDraftRequest $request): AdDraftResource
    {
        Gate::authorize('marketing.ads.create');
        $draft = AdDraft::query()->findOrFail($id);

        return new AdDraftResource($this->service->update($draft, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('marketing.ads.create');
        AdDraft::query()->findOrFail($id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
```

Modify `app/app/Modules/Marketing/Http/routes.php` — add the import at the top with the other controller imports:
```php
use CMBcoreSeller\Modules\Marketing\Http\Controllers\AdDraftController;
```
and add these routes INSIDE the existing `Route::middleware([...])->prefix('api/v1/marketing')->group(function () {` block (e.g. right after the forecast routes):
```php
        // Wizard drafts (CRUD + autosave). Write gated by marketing.ads.create.
        Route::get('ad-drafts', [AdDraftController::class, 'index'])->name('marketing.ad-drafts.index');
        Route::post('ad-drafts', [AdDraftController::class, 'store'])->name('marketing.ad-drafts.store');
        Route::get('ad-drafts/{id}', [AdDraftController::class, 'show'])->whereNumber('id')->name('marketing.ad-drafts.show');
        Route::patch('ad-drafts/{id}', [AdDraftController::class, 'update'])->whereNumber('id')->name('marketing.ad-drafts.update');
        Route::delete('ad-drafts/{id}', [AdDraftController::class, 'destroy'])->whereNumber('id')->name('marketing.ad-drafts.destroy');
```

- [ ] **Step 4: Run** `php artisan test --filter=AdDraftApiTest` → PASS (5 tests). Regression: `php artisan test --filter='AdAccountApiTest|AdInsightApiTest'` green.

- [ ] **Step 5: FINAL gate + commit**
```
# from app/
vendor/bin/pint app/Modules/Marketing/Http tests/Feature/Marketing/AdDraftApiTest.php
vendor/bin/phpstan analyse app/Modules/Marketing/Http
php artisan test --filter='AdDraftModelTest|AdDraftServiceTest|AdDraftApiTest|AdAccountApiTest'
```
All green. Then:
```
git add app/app/Modules/Marketing/Http/Requests/AdDraftRequest.php app/app/Modules/Marketing/Http/Resources/AdDraftResource.php app/app/Modules/Marketing/Http/Controllers/AdDraftController.php app/app/Modules/Marketing/Http/routes.php app/tests/Feature/Marketing/AdDraftApiTest.php
git commit -m "feat(ads): ad-drafts CRUD + autosave API (RBAC + tenant scoped)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Self-Review (plan author)

**Spec coverage (§5 model/API):** `ad_drafts` migration + `AdDraft` model ✓ (T1), `AdDraftService` create+partial-update ✓ (T2), CRUD + autosave (PATCH) API with RBAC + tenant scope ✓ (T3). Ability `marketing.ads.create` needs NO catalog entry — Owner/Admin get it via the `*` wildcard in `Role::permissions()`; staff roles are denied (test `test_staff_order_forbidden_to_create`).

**Placeholder scan:** no TBD. Publish-related columns (`idempotency_key`, `*_external_id`, `last_error`) exist on the table now but are written by Plan 4 — they are surfaced read-only in the Resource.

**Type consistency:** `AdDraftService::create(int $adAccountId, ?int $userId, array $data)` and `update(AdDraft, array)` match the controller calls. `payload` is `array` cast everywhere. Status constants on the model.

**Decisions locked:** drafts are leniently validated (work-in-progress); `ad_account_id` ownership enforced via tenant-scoped `findOrFail` (404 for foreign accounts, asserted). Objective is a free string here; valid-objective enforcement is at publish (Plan 4) via `FacebookObjectiveMap`.

## Next plans
- Plan 4 — `PublishAdDraft` job (resume-first idempotent) + `POST ad-drafts/{id}/publish` + gating + uploadImage/Video.
- Plan 5 — FE wizard + post picker/preview + AntD Tour + AI slide-over.
