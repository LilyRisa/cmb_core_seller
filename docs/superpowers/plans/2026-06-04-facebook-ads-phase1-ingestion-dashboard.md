# Facebook Ads — Phase 1 (Ingestion + Dashboard) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Connect a seller's Facebook ad account (OAuth `ads_read`), sync the campaign→ad-set→ad tree and near-real-time insights (poll ~15'), and show a read-only dashboard.

**Architecture:** New Integration axis `Ads` (Connector + Registry, core never knows the provider name — ADR-0017) consumed by a new `Marketing` module that owns storage (ad_accounts, ad_entities, ad_insight_snapshots), sync jobs, and HTTP. Mirrors existing patterns: `LazadaChatConnector` (signed Graph calls + `Http::fake` tests), `FacebookOAuthController` (dedicated OAuth + own token), `SyncConversationsForShop` (idempotent polling job + scheduler).

**Tech Stack:** Laravel 11, PHP 8.2, PHPUnit, React 18 + Vite + Ant Design + TanStack Query. All PHP/Node commands run from `app/`.

**Spec:** `docs/superpowers/specs/2026-06-04-facebook-ads-realtime-ai-design.md` (Phase 1 = §4, §5, §6, §7, §13 steps 1-6).

**Reference reading before starting:**
- `docs/01-architecture/extensibility-rules.md` (Connector/Registry, "core never knows provider name").
- `app/app/Integrations/Messaging/Lazada/LazadaChatConnector.php` (signed Graph + insights shape pattern).
- `app/app/Modules/Messaging/Http/Controllers/FacebookOAuthController.php` + `app/app/Modules/Messaging/Http/Controllers/LazadaImOAuthController.php` (dedicated OAuth + own token; the closest precedent).
- `app/app/Modules/Channels/Database/Migrations/2026_05_12_120001_create_channel_accounts_table.php` (token columns + BelongsToTenant conventions).
- `app/app/Modules/Messaging/Jobs/SyncConversationsForShop.php` + `app/routes/console.php:119` (`messaging-chat-poll`) (poll job + scheduler).
- `app/app/Integrations/IntegrationsServiceProvider.php` (registry build + connector bind pattern).

**Conventions (non-negotiable):** every business table has `tenant_id` + `BelongsToTenant`; money = integer; tokens cast `encrypted`; connectors throw `UnsupportedOperation` for unsupported methods; `config()` not `env()` outside config files; controllers thin (FormRequest → Service → Resource).

---

## File Structure

**Create — Integrations/Ads:**
- `app/app/Integrations/Ads/Contracts/AdsConnector.php` — interface.
- `app/app/Integrations/Ads/DTO/AdAccountDTO.php`, `AdEntityDTO.php`, `AdInsightDTO.php`, `AdInsightThrottleDTO.php`.
- `app/app/Integrations/Ads/Exceptions/UnsupportedOperation.php`.
- `app/app/Integrations/Ads/AdsRegistry.php`.
- `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php`.

**Create — Marketing module:**
- `app/app/Modules/Marketing/Database/Migrations/2026_06_04_100001_create_ad_accounts_table.php`
- `..._100002_create_ad_entities_table.php`, `..._100003_create_ad_insight_snapshots_table.php`
- `app/app/Modules/Marketing/Models/AdAccount.php`, `AdEntity.php`, `AdInsightSnapshot.php`
- `app/app/Modules/Marketing/Jobs/SyncAdAccountEntities.php`, `SyncAdInsights.php`
- `app/app/Modules/Marketing/Services/AdsSyncService.php`
- `app/app/Modules/Marketing/Http/Controllers/AdsOAuthController.php`, `AdAccountController.php`, `AdInsightController.php`
- `app/app/Modules/Marketing/Http/Resources/AdAccountResource.php`, `AdEntityResource.php`, `AdInsightResource.php`
- `app/app/Modules/Marketing/Http/routes.php`
- `app/app/Modules/Marketing/MarketingServiceProvider.php`

**Modify:**
- `app/config/integrations.php` — add `ads_facebook` block + `ads` enabled CSV.
- `app/app/Integrations/IntegrationsServiceProvider.php` — `AdsRegistry` singleton + `FacebookAdsConnector` bind.
- `app/bootstrap/providers.php` — register `MarketingServiceProvider`.
- `app/routes/web.php` — `GET /oauth/facebook_ads/callback`.
- `app/routes/console.php` — `ads-insights-poll` schedule (15').
- `app/.env.example` — `INTEGRATIONS_ADS`, `FACEBOOK_ADS_*`.
- `app/resources/js/pages/MarketingDashboardPage.tsx` + `app/resources/js/lib/marketing.tsx` + router/nav wiring.

---

## Task 1: Migrations + Marketing module skeleton

**Files:**
- Create: the 3 migrations above, `MarketingServiceProvider.php`
- Modify: `app/bootstrap/providers.php`
- Test: `app/tests/Feature/Marketing/MarketingMigrationsTest.php`

- [ ] **Step 1: Write failing test** — `app/tests/Feature/Marketing/MarketingMigrationsTest.php`

```php
<?php
namespace Tests\Feature\Marketing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketingMigrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_tables_exist_with_tenant_id(): void
    {
        foreach (['ad_accounts', 'ad_entities', 'ad_insight_snapshots'] as $t) {
            $this->assertTrue(Schema::hasTable($t), "missing $t");
            $this->assertTrue(Schema::hasColumn($t, 'tenant_id'), "$t missing tenant_id");
        }
        $this->assertTrue(Schema::hasColumn('ad_accounts', 'access_token'));
        $this->assertTrue(Schema::hasColumn('ad_entities', 'parent_id'));
        $this->assertTrue(Schema::hasColumn('ad_insight_snapshots', 'fetched_at'));
    }
}
```

- [ ] **Step 2: Run, expect FAIL** — `php artisan test --filter=MarketingMigrationsTest` → fails (tables missing).

- [ ] **Step 3: Write `ad_accounts` migration** — `app/app/Modules/Marketing/Database/Migrations/2026_06_04_100001_create_ad_accounts_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('provider')->default('facebook');   // ads provider code
            $table->string('external_account_id');             // act_<id>
            $table->string('name')->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('status')->default('active');        // active | expired | revoked | disabled
            $table->text('access_token')->nullable();           // encrypted cast
            $table->text('refresh_token')->nullable();          // encrypted cast (system-user/long-lived)
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('insights_synced_at')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'provider', 'external_account_id']);
            $table->index(['provider', 'external_account_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('ad_accounts'); }
};
```

- [ ] **Step 4: Write `ad_entities` migration** — `..._100002_create_ad_entities_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->string('level');                 // campaign | adset | ad
            $table->string('external_id');           // FB id
            $table->string('parent_external_id')->nullable(); // FB parent id (campaign/adset)
            $table->unsignedBigInteger('parent_id')->nullable(); // local ad_entities.id
            $table->string('name')->nullable();
            $table->string('status')->nullable();    // ACTIVE | PAUSED | ...
            $table->string('effective_status')->nullable();
            $table->unsignedBigInteger('daily_budget')->nullable();    // minor units
            $table->unsignedBigInteger('lifetime_budget')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['ad_account_id', 'level', 'external_id']);
            $table->index(['ad_account_id', 'level']);
        });
    }

    public function down(): void { Schema::dropIfExists('ad_entities'); }
};
```

- [ ] **Step 5: Write `ad_insight_snapshots` migration** — `..._100003_create_ad_insight_snapshots_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_insight_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('ad_account_id')->index();
            $table->foreignId('ad_entity_id')->nullable()->index();
            $table->string('level');                 // account | campaign | adset | ad
            $table->string('external_id');           // entity id this row is for
            $table->date('date_start');
            $table->date('date_stop');
            $table->string('window')->default('today'); // today | last_7d | ...
            $table->boolean('is_finalizing')->default(false); // within 28d re-attribution
            // Core metrics as integers/decimals for querying; full payload in `metrics`.
            $table->unsignedBigInteger('spend')->default(0);     // minor units
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->decimal('ctr', 8, 4)->nullable();
            $table->unsignedBigInteger('cpc')->nullable();       // minor units
            $table->unsignedBigInteger('cpm')->nullable();
            $table->decimal('frequency', 8, 4)->nullable();
            $table->decimal('purchase_roas', 10, 4)->nullable();
            $table->json('metrics')->nullable();                 // raw insight row
            $table->timestamp('fetched_at')->index();
            $table->timestamps();
            // Idempotency: one snapshot per (entity, window, fetched bucket).
            $table->unique(['ad_account_id', 'level', 'external_id', 'window', 'date_start', 'date_stop'], 'ad_insight_unique');
        });
    }

    public function down(): void { Schema::dropIfExists('ad_insight_snapshots'); }
};
```

> NOTE on idempotency: `SyncAdInsights` (Task 11) UPDATES the row for the current `(entity, window, date range)` in place (upsert) and bumps `fetched_at` — so the unique key is the natural key without `fetched_at`. Trend history across time is out of Phase 1 scope (YAGNI); add a separate history table in a later phase if needed. Keep the latest snapshot per window.

- [ ] **Step 6: Write `MarketingServiceProvider`** — `app/app/Modules/Marketing/MarketingServiceProvider.php`

```php
<?php
namespace CMBcoreSeller\Modules\Marketing;

use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
    }
}
```

- [ ] **Step 7: Register provider** — add to `app/bootstrap/providers.php` array: `CMBcoreSeller\Modules\Marketing\MarketingServiceProvider::class,` (match existing formatting/order).

- [ ] **Step 8: Create empty routes file so provider boots** — `app/app/Modules/Marketing/Http/routes.php`

```php
<?php
// Marketing module routes — populated in Task 8/9/13.
```

- [ ] **Step 9: Run test, expect PASS** — `php artisan test --filter=MarketingMigrationsTest` → PASS.

- [ ] **Step 10: Commit**

```bash
git add app/app/Modules/Marketing app/bootstrap/providers.php app/tests/Feature/Marketing/MarketingMigrationsTest.php
git commit -m "feat(marketing): ad_accounts/ad_entities/ad_insight_snapshots + module skeleton"
```

---

## Task 2: Ads Integration contract + DTOs + UnsupportedOperation

**Files:**
- Create: `AdsConnector.php`, 4 DTOs, `Exceptions/UnsupportedOperation.php`
- Test: `app/tests/Unit/Ads/AdsDtoTest.php`

- [ ] **Step 1: Write failing test** — `app/tests/Unit/Ads/AdsDtoTest.php`

```php
<?php
namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\DTO\AdEntityDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use PHPUnit\Framework\TestCase;

class AdsDtoTest extends TestCase
{
    public function test_entity_dto_holds_fields(): void
    {
        $e = new AdEntityDTO(level: 'campaign', externalId: 'C1', parentExternalId: null, name: 'Camp', status: 'ACTIVE', effectiveStatus: 'ACTIVE', dailyBudget: 100000, lifetimeBudget: null, raw: ['id' => 'C1']);
        $this->assertSame('campaign', $e->level);
        $this->assertSame(100000, $e->dailyBudget);
    }

    public function test_insight_dto_holds_metrics(): void
    {
        $i = new AdInsightDTO(level: 'campaign', externalId: 'C1', dateStart: '2026-06-01', dateStop: '2026-06-04', spend: 50000, impressions: 1000, clicks: 30, reach: 800, ctr: 3.0, cpc: 1666, cpm: 50000, frequency: 1.25, purchaseRoas: 2.5, raw: []);
        $this->assertSame(50000, $i->spend);
        $this->assertSame(2.5, $i->purchaseRoas);
    }
}
```

- [ ] **Step 2: Run, expect FAIL** — `php artisan test --filter=AdsDtoTest` → fails (classes missing).

- [ ] **Step 3: Create `Exceptions/UnsupportedOperation.php`** (mirror `app/app/Integrations/Messaging/Exceptions/UnsupportedOperation.php`)

```php
<?php
namespace CMBcoreSeller\Integrations\Ads\Exceptions;

use RuntimeException;

class UnsupportedOperation extends RuntimeException
{
    public static function for(string $provider, string $operation): self
    {
        return new self("Ads connector [{$provider}] does not support operation [{$operation}].");
    }
}
```

- [ ] **Step 4: Create DTOs**

`app/app/Integrations/Ads/DTO/AdAccountDTO.php`:
```php
<?php
namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdAccountDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $externalAccountId,   // act_<id>
        public ?string $name = null,
        public ?string $currency = null,
        public ?string $status = null,
        public array $raw = [],
    ) {}
}
```

`app/app/Integrations/Ads/DTO/AdEntityDTO.php`:
```php
<?php
namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdEntityDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $level,            // campaign | adset | ad
        public string $externalId,
        public ?string $parentExternalId,
        public ?string $name,
        public ?string $status,
        public ?string $effectiveStatus,
        public ?int $dailyBudget,        // minor units
        public ?int $lifetimeBudget,
        public array $raw = [],
    ) {}
}
```

`app/app/Integrations/Ads/DTO/AdInsightDTO.php`:
```php
<?php
namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdInsightDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $level,
        public string $externalId,
        public string $dateStart,
        public string $dateStop,
        public int $spend,           // minor units
        public int $impressions,
        public int $clicks,
        public int $reach,
        public ?float $ctr,
        public ?int $cpc,
        public ?int $cpm,
        public ?float $frequency,
        public ?float $purchaseRoas,
        public array $raw = [],
    ) {}
}
```

`app/app/Integrations/Ads/DTO/AdInsightThrottleDTO.php`:
```php
<?php
namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdInsightThrottleDTO
{
    public function __construct(
        public float $appUtilPct = 0.0,
        public float $accUtilPct = 0.0,
        public string $accessTier = 'development',
    ) {}

    /** True when close to the limit ⇒ caller should pace/async. */
    public function isHot(float $threshold = 80.0): bool
    {
        return $this->appUtilPct >= $threshold || $this->accUtilPct >= $threshold;
    }
}
```

- [ ] **Step 5: Create `Contracts/AdsConnector.php`**

```php
<?php
namespace CMBcoreSeller\Integrations\Ads\Contracts;

use CMBcoreSeller\Integrations\Ads\DTO\AdAccountDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdEntityDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;

interface AdsConnector
{
    public function code(): string;

    public function displayName(): string;

    /** @return array<string,bool> */
    public function capabilities(): array;

    public function supports(string $capability): bool;

    // --- OAuth ---
    public function buildAuthorizationUrl(string $state, array $opts = []): string;

    /** @return array{access_token:string, expires_at:?\Carbon\CarbonImmutable, raw:array<string,mixed>} */
    public function exchangeCodeForToken(string $code): array;

    // --- Read ---
    /** @return list<AdAccountDTO> */
    public function listAdAccounts(string $accessToken): array;

    /**
     * List entities of one level for an account.
     * @return list<AdEntityDTO>
     */
    public function listEntities(string $accessToken, string $externalAccountId, string $level): array;

    /**
     * Fetch insights for one object (account/campaign/adset/ad) at a given date preset.
     * Implementations read the throttle header into `$throttleOut` (by ref) for pacing.
     * @return list<AdInsightDTO>
     */
    public function fetchInsights(string $accessToken, string $externalId, string $level, array $query = [], ?\CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO &$throttleOut = null): array;
}
```

- [ ] **Step 6: Run test, expect PASS** — `php artisan test --filter=AdsDtoTest` → PASS.

- [ ] **Step 7: Commit**

```bash
git add app/app/Integrations/Ads app/tests/Unit/Ads/AdsDtoTest.php
git commit -m "feat(ads): AdsConnector contract + DTOs"
```

---

## Task 3: Config block + AdsRegistry + ServiceProvider wiring

**Files:**
- Modify: `app/config/integrations.php`, `app/app/Integrations/IntegrationsServiceProvider.php`, `app/.env.example`
- Create: `app/app/Integrations/Ads/AdsRegistry.php`
- Test: `app/tests/Feature/Ads/AdsRegistryTest.php`

- [ ] **Step 1: Write failing test** — `app/tests/Feature/Ads/AdsRegistryTest.php`

```php
<?php
namespace Tests\Feature\Ads;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Tests\TestCase;

class AdsRegistryTest extends TestCase
{
    public function test_facebook_registered_when_enabled(): void
    {
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
        $reg = app(AdsRegistry::class);
        $this->assertTrue($reg->has('facebook'));
        $this->assertInstanceOf(FacebookAdsConnector::class, $reg->for('facebook'));
    }

    public function test_absent_when_disabled(): void
    {
        config(['integrations.ads' => []]);
        $this->app->forgetInstance(AdsRegistry::class);
        $this->assertFalse(app(AdsRegistry::class)->has('facebook'));
    }
}
```

- [ ] **Step 2: Run, expect FAIL** — `php artisan test --filter=AdsRegistryTest` (AdsRegistry/connector missing).

- [ ] **Step 3: Create `AdsRegistry.php`** (mirror `app/app/Integrations/Messaging/MessagingRegistry.php`)

```php
<?php
namespace CMBcoreSeller\Integrations\Ads;

use CMBcoreSeller\Integrations\Ads\Contracts\AdsConnector;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class AdsRegistry
{
    /** @var array<string,class-string> */
    private array $connectors = [];

    public function __construct(private Container $container) {}

    public function register(string $provider, string $connectorClass): void
    {
        $this->connectors[$provider] = $connectorClass;
    }

    public function has(string $provider): bool
    {
        return isset($this->connectors[$provider]);
    }

    public function for(string $provider): AdsConnector
    {
        if (! $this->has($provider)) {
            throw new RuntimeException("Ads connector [{$provider}] not registered.");
        }

        return $this->container->make($this->connectors[$provider]);
    }

    /** @return list<string> */
    public function providers(): array
    {
        return array_keys($this->connectors);
    }
}
```

- [ ] **Step 4: Add config block** — append to `app/config/integrations.php` (near the messaging blocks):

```php
    'ads' => array_filter(explode(',', (string) env('INTEGRATIONS_ADS', ''))),

    'ads_facebook' => [
        // Reuse the existing Meta app by default (Meta allows multiple products/scopes
        // per app); override with FACEBOOK_ADS_* if a dedicated app is used.
        'app_id' => env('FACEBOOK_ADS_APP_ID', env('MESSAGING_FACEBOOK_APP_ID')),
        'app_secret' => env('FACEBOOK_ADS_APP_SECRET', env('MESSAGING_FACEBOOK_APP_SECRET')),
        'graph_version' => env('FACEBOOK_ADS_GRAPH_VERSION', 'v19.0'),
        'redirect_uri' => env('FACEBOOK_ADS_REDIRECT_URI'), // defaults to APP_URL + /oauth/facebook_ads/callback
        // ads_read for Phase 1; ads_management added in Phase 3.
        'scopes' => env('FACEBOOK_ADS_SCOPES', 'ads_read,business_management'),
    ],
```

- [ ] **Step 5: Wire registry + bind connector** — in `app/app/Integrations/IntegrationsServiceProvider.php`:
  - Add `use CMBcoreSeller\Integrations\Ads\AdsRegistry;` and `use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;`.
  - Add a `$adsConnectors` property: `protected array $adsConnectors = ['facebook' => FacebookAdsConnector::class];`
  - In `register()`, add (mirror the MessagingRegistry singleton block):

```php
        $this->app->singleton(AdsRegistry::class, function ($app) {
            $registry = new AdsRegistry($app);
            foreach (array_filter(array_map('trim', (array) config('integrations.ads', []))) as $code) {
                if (isset($this->adsConnectors[$code])) {
                    $registry->register($code, $this->adsConnectors[$code]);
                }
            }

            return $registry;
        });

        $this->app->bind(FacebookAdsConnector::class, function () {
            return new FacebookAdsConnector((array) config('integrations.ads_facebook', []));
        });
```

- [ ] **Step 6: env.example** — append to `app/.env.example`:

```dotenv
# --- Facebook Ads (Marketing API) — near-real-time insights + AI optimization (Phase 1) ---
INTEGRATIONS_ADS=                 # set "facebook" to enable
# Reuses Meta app by default; set only if using a dedicated ads app:
# FACEBOOK_ADS_APP_ID=
# FACEBOOK_ADS_APP_SECRET=
# FACEBOOK_ADS_REDIRECT_URI=      # defaults to <APP_URL>/oauth/facebook_ads/callback
# FACEBOOK_ADS_SCOPES=ads_read,business_management
```

- [ ] **Step 7: Stub the connector so the test can resolve it** — create `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` with class + constructor + `code()`/`displayName()`/`capabilities()`/`supports()` only (full methods in Task 4-6). It must `implements AdsConnector` and throw `UnsupportedOperation` for the not-yet-implemented methods so the class is concrete.

```php
<?php
namespace CMBcoreSeller\Integrations\Ads\Facebook;

use CMBcoreSeller\Integrations\Ads\Contracts\AdsConnector;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO;
use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;

class FacebookAdsConnector implements AdsConnector
{
    /** @param array<string,mixed> $config */
    public function __construct(private array $config) {}

    public function code(): string { return 'facebook'; }

    public function displayName(): string { return 'Facebook Ads'; }

    public function capabilities(): array
    {
        return [
            'insights.read' => true,
            'insights.async' => true,
            'entities.list' => true,
            'actions.budget' => false, // Phase 3
            'actions.status' => false, // Phase 3
            'actions.bid' => false,    // Phase 3
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl'); // Task 4
    }

    public function exchangeCodeForToken(string $code): array
    {
        throw UnsupportedOperation::for($this->code(), 'exchangeCodeForToken'); // Task 4
    }

    public function listAdAccounts(string $accessToken): array
    {
        throw UnsupportedOperation::for($this->code(), 'listAdAccounts'); // Task 5
    }

    public function listEntities(string $accessToken, string $externalAccountId, string $level): array
    {
        throw UnsupportedOperation::for($this->code(), 'listEntities'); // Task 5
    }

    public function fetchInsights(string $accessToken, string $externalId, string $level, array $query = [], ?AdInsightThrottleDTO &$throttleOut = null): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchInsights'); // Task 6
    }
}
```

- [ ] **Step 8: Run test, expect PASS** — `php artisan test --filter=AdsRegistryTest` → PASS.

- [ ] **Step 9: Commit**

```bash
git add app/config/integrations.php app/.env.example app/app/Integrations/Ads app/app/Integrations/IntegrationsServiceProvider.php app/tests/Feature/Ads/AdsRegistryTest.php
git commit -m "feat(ads): AdsRegistry + config + FacebookAdsConnector skeleton"
```

---

## Task 4: FacebookAdsConnector — OAuth (authorize URL + token exchange)

**Files:**
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php`
- Test: `app/tests/Unit/Ads/FacebookAdsOAuthTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsOAuthTest extends TestCase
{
    private function conn(): FacebookAdsConnector
    {
        return new FacebookAdsConnector([
            'app_id' => 'APPID', 'app_secret' => 'SEC', 'graph_version' => 'v19.0',
            'redirect_uri' => 'https://app.cmbcore.com/oauth/facebook_ads/callback',
            'scopes' => 'ads_read,business_management',
        ]);
    }

    public function test_authorize_url(): void
    {
        $u = $this->conn()->buildAuthorizationUrl('ST');
        $this->assertStringContainsString('client_id=APPID', $u);
        $this->assertStringContainsString('state=ST', $u);
        $this->assertStringContainsString('ads_read', $u);
        $this->assertStringContainsString('oauth%2Ffacebook_ads%2Fcallback', $u);
    }

    public function test_exchange_code(): void
    {
        Http::fake(['graph.facebook.com/*oauth/access_token*' => Http::response(['access_token' => 'AT', 'expires_in' => 5184000], 200)]);
        $t = $this->conn()->exchangeCodeForToken('CODE');
        $this->assertSame('AT', $t['access_token']);
        $this->assertNotNull($t['expires_at']);
    }
}
```

- [ ] **Step 2: Run, expect FAIL** — `php artisan test --filter=FacebookAdsOAuthTest` (throws UnsupportedOperation).

- [ ] **Step 3: Implement OAuth methods** (mirror `FacebookPageConnector::buildAuthorizationUrl`/`exchangeCodeForToken`). Replace the two stub methods:

```php
    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        return 'https://www.facebook.com/'.$this->graphVersion().'/dialog/oauth?'.http_build_query([
            'client_id' => (string) ($this->config['app_id'] ?? ''),
            'redirect_uri' => $opts['redirect_uri'] ?? $this->redirectUri(),
            'state' => $state,
            'scope' => (string) ($this->config['scopes'] ?? 'ads_read,business_management'),
            'response_type' => 'code',
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $res = Http::get('https://graph.facebook.com/'.$this->graphVersion().'/oauth/access_token', [
            'client_id' => $this->config['app_id'] ?? '',
            'client_secret' => $this->config['app_secret'] ?? '',
            'redirect_uri' => $this->redirectUri(),
            'code' => $code,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads token exchange failed: '.$res->body());
        }

        return [
            'access_token' => (string) $res->json('access_token'),
            'expires_at' => $res->json('expires_in') ? \Carbon\CarbonImmutable::now()->addSeconds((int) $res->json('expires_in')) : null,
            'raw' => (array) $res->json(),
        ];
    }

    private function redirectUri(): string
    {
        $configured = (string) ($this->config['redirect_uri'] ?? '');
        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) config('app.url'), '/').'/oauth/facebook_ads/callback';
    }

    private function graphVersion(): string
    {
        return (string) ($this->config['graph_version'] ?? 'v19.0');
    }
```

Add `use Illuminate\Support\Facades\Http;` at top.

- [ ] **Step 4: Run, expect PASS** — `php artisan test --filter=FacebookAdsOAuthTest`.

- [ ] **Step 5: Commit** — `git commit -am "feat(ads): Facebook Ads OAuth (authorize + token exchange)"`

---

## Task 5: FacebookAdsConnector — listAdAccounts + listEntities

**Files:** Modify connector. Test: `app/tests/Unit/Ads/FacebookAdsReadTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsReadTest extends TestCase
{
    private function conn(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_list_ad_accounts(): void
    {
        Http::fake(['graph.facebook.com/*me/adaccounts*' => Http::response([
            'data' => [['account_id' => '123', 'id' => 'act_123', 'name' => 'Shop', 'currency' => 'VND', 'account_status' => 1]],
        ], 200)]);
        $accts = $this->conn()->listAdAccounts('AT');
        $this->assertCount(1, $accts);
        $this->assertSame('act_123', $accts[0]->externalAccountId);
        $this->assertSame('VND', $accts[0]->currency);
    }

    public function test_list_campaign_entities(): void
    {
        Http::fake(['graph.facebook.com/*act_123/campaigns*' => Http::response([
            'data' => [['id' => 'C1', 'name' => 'Camp', 'status' => 'ACTIVE', 'effective_status' => 'ACTIVE', 'daily_budget' => '100000']],
        ], 200)]);
        $items = $this->conn()->listEntities('AT', 'act_123', 'campaign');
        $this->assertSame('C1', $items[0]->externalId);
        $this->assertSame(100000, $items[0]->dailyBudget);
        $this->assertSame('campaign', $items[0]->level);
    }
}
```

- [ ] **Step 2: Run, expect FAIL.**

- [ ] **Step 3: Implement** — replace `listAdAccounts`/`listEntities`:

```php
    public function listAdAccounts(string $accessToken): array
    {
        $res = Http::timeout(30)->get($this->graphUrl('me/adaccounts'), [
            'fields' => 'account_id,name,currency,account_status',
            'access_token' => $accessToken, 'limit' => 200,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads listAdAccounts failed: '.$res->body());
        }

        return array_values(array_map(fn (array $a) => new AdAccountDTO(
            externalAccountId: (string) ($a['id'] ?? ('act_'.($a['account_id'] ?? ''))),
            name: $a['name'] ?? null,
            currency: $a['currency'] ?? null,
            status: isset($a['account_status']) ? (string) $a['account_status'] : null,
            raw: $a,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function listEntities(string $accessToken, string $externalAccountId, string $level): array
    {
        $edge = match ($level) {
            'campaign' => 'campaigns',
            'adset' => 'adsets',
            'ad' => 'ads',
            default => throw UnsupportedOperation::for($this->code(), "listEntities({$level})"),
        };
        $fields = match ($level) {
            'campaign' => 'id,name,status,effective_status,daily_budget,lifetime_budget',
            'adset' => 'id,name,status,effective_status,daily_budget,lifetime_budget,campaign_id',
            'ad' => 'id,name,status,effective_status,adset_id',
            default => 'id,name,status',
        };
        $res = Http::timeout(30)->get($this->graphUrl($externalAccountId.'/'.$edge), [
            'fields' => $fields, 'access_token' => $accessToken, 'limit' => 500,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException("Facebook Ads listEntities({$level}) failed: ".$res->body());
        }

        return array_values(array_map(fn (array $e) => new AdEntityDTO(
            level: $level,
            externalId: (string) ($e['id'] ?? ''),
            parentExternalId: isset($e['campaign_id']) ? (string) $e['campaign_id'] : (isset($e['adset_id']) ? (string) $e['adset_id'] : null),
            name: $e['name'] ?? null,
            status: $e['status'] ?? null,
            effectiveStatus: $e['effective_status'] ?? null,
            dailyBudget: isset($e['daily_budget']) ? (int) $e['daily_budget'] : null,
            lifetimeBudget: isset($e['lifetime_budget']) ? (int) $e['lifetime_budget'] : null,
            raw: $e,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/'.$this->graphVersion().'/'.ltrim($path, '/');
    }
```

Add `use CMBcoreSeller\Integrations\Ads\DTO\AdAccountDTO;` and `use CMBcoreSeller\Integrations\Ads\DTO\AdEntityDTO;`.

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git commit -am "feat(ads): list ad accounts + entities"`

---

## Task 6: FacebookAdsConnector — fetchInsights + throttle parsing

**Files:** Modify connector. Test: `app/tests/Unit/Ads/FacebookAdsInsightsTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\DTO\AdInsightThrottleDTO;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsInsightsTest extends TestCase
{
    public function test_fetch_insights_maps_metrics_and_throttle(): void
    {
        Http::fake(['graph.facebook.com/*C1/insights*' => Http::response([
            'data' => [[
                'date_start' => '2026-06-01', 'date_stop' => '2026-06-04',
                'spend' => '50000', 'impressions' => '1000', 'clicks' => '30',
                'reach' => '800', 'ctr' => '3.0', 'cpc' => '1666', 'cpm' => '50000',
                'frequency' => '1.25', 'purchase_roas' => [['value' => '2.5']],
            ]],
        ], 200, ['x-fb-ads-insights-throttle' => '{"app_id_util_pct":12.5,"acc_id_util_pct":4.0,"ads_api_access_tier":"standard_access"}']));

        $conn = new FacebookAdsConnector(['graph_version' => 'v19.0']);
        $throttle = null;
        $rows = $conn->fetchInsights('AT', 'C1', 'campaign', ['date_preset' => 'today'], $throttle);

        $this->assertSame(50000, $rows[0]->spend);
        $this->assertSame(2.5, $rows[0]->purchaseRoas);
        $this->assertInstanceOf(AdInsightThrottleDTO::class, $throttle);
        $this->assertSame('standard_access', $throttle->accessTier);
        $this->assertEqualsWithDelta(12.5, $throttle->appUtilPct, 0.01);
    }

    public function test_fetch_insights_throws_on_error(): void
    {
        Http::fake(['graph.facebook.com/*C1/insights*' => Http::response(['error' => ['message' => 'bad', 'code' => 100]], 400)]);
        $this->expectException(\RuntimeException::class);
        (new FacebookAdsConnector(['graph_version' => 'v19.0']))->fetchInsights('AT', 'C1', 'campaign');
    }
}
```

- [ ] **Step 2: Run, expect FAIL.**

- [ ] **Step 3: Implement `fetchInsights`** — replace stub:

```php
    public function fetchInsights(string $accessToken, string $externalId, string $level, array $query = [], ?AdInsightThrottleDTO &$throttleOut = null): array
    {
        $params = [
            'fields' => 'spend,impressions,clicks,reach,ctr,cpc,cpm,frequency,purchase_roas',
            'level' => $level === 'account' ? 'account' : $level,
            'date_preset' => (string) ($query['date_preset'] ?? 'today'),
            'access_token' => $accessToken,
        ];
        if (! empty($query['time_range'])) {
            $params['time_range'] = is_string($query['time_range']) ? $query['time_range'] : json_encode($query['time_range']);
            unset($params['date_preset']);
        }

        $res = Http::timeout(40)->get($this->graphUrl($externalId.'/insights'), $params);

        // Parse throttle header for adaptive pacing (best-effort).
        $hdr = $res->header('x-fb-ads-insights-throttle');
        $t = $hdr ? (array) json_decode($hdr, true) : [];
        $throttleOut = new AdInsightThrottleDTO(
            appUtilPct: (float) ($t['app_id_util_pct'] ?? 0),
            accUtilPct: (float) ($t['acc_id_util_pct'] ?? 0),
            accessTier: (string) ($t['ads_api_access_tier'] ?? 'development'),
        );

        if (! $res->successful()) {
            throw new \RuntimeException("Facebook Ads fetchInsights({$level}) failed: ".$res->body());
        }

        return array_values(array_map(function (array $r) use ($level, $externalId) {
            $roas = null;
            if (isset($r['purchase_roas'][0]['value'])) {
                $roas = (float) $r['purchase_roas'][0]['value'];
            }

            return new AdInsightDTO(
                level: $level,
                externalId: $externalId,
                dateStart: (string) ($r['date_start'] ?? ''),
                dateStop: (string) ($r['date_stop'] ?? ''),
                spend: (int) round(((float) ($r['spend'] ?? 0))),
                impressions: (int) ($r['impressions'] ?? 0),
                clicks: (int) ($r['clicks'] ?? 0),
                reach: (int) ($r['reach'] ?? 0),
                ctr: isset($r['ctr']) ? (float) $r['ctr'] : null,
                cpc: isset($r['cpc']) ? (int) round((float) $r['cpc']) : null,
                cpm: isset($r['cpm']) ? (int) round((float) $r['cpm']) : null,
                frequency: isset($r['frequency']) ? (float) $r['frequency'] : null,
                purchaseRoas: $roas,
                raw: $r,
            );
        }, array_filter((array) $res->json('data', []), 'is_array')));
    }
```

Add `use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;` (AdInsightThrottleDTO already imported from skeleton).

> NOTE: FB returns `spend`/`cpc`/`cpm` as major-unit decimal strings (e.g. "50.00"). For VND (zero-decimal currency) `round()` is correct. For 2-decimal currencies a later phase should multiply by 100 based on `ad_accounts.currency`. Phase 1 targets VND; document this assumption in the connector docblock.

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git commit -am "feat(ads): fetchInsights + throttle header parsing"`

---

## Task 7: Marketing models

**Files:** Create `AdAccount.php`, `AdEntity.php`, `AdInsightSnapshot.php`. Test: `app/tests/Unit/Marketing/AdAccountModelTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
namespace Tests\Unit\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdAccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_encrypted_and_tenant_scoped(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant->id);   // match existing tenant-setting helper
        $a = AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1',
            'name' => 'Shop', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'SECRET',
        ]);
        $this->assertSame('SECRET', $a->fresh()->access_token); // decrypts
        $raw = \DB::table('ad_accounts')->where('id', $a->id)->value('access_token');
        $this->assertNotSame('SECRET', $raw); // stored encrypted
    }
}
```

> Before writing this test, confirm the exact tenant-setting API by reading `app/app/Modules/Tenancy/CurrentTenant.php` and an existing tenant-scoped feature test (e.g. `MessagingApiTest`). Use whatever pattern they use (`CurrentTenant::set()` or middleware/header). Adjust the test accordingly.

- [ ] **Step 2: Run, expect FAIL.**

- [ ] **Step 3: Create models** — mirror `app/app/Modules/Channels/Models/ChannelAccount.php` (BelongsToTenant trait, encrypted casts).

`AdAccount.php`:
```php
<?php
namespace CMBcoreSeller\Modules\Marketing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant; // confirm exact trait path from ChannelAccount
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdAccount extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'tenant_id', 'provider', 'external_account_id', 'name', 'currency', 'status',
        'access_token', 'refresh_token', 'token_expires_at', 'last_synced_at', 'insights_synced_at',
        'meta', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted', 'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime', 'last_synced_at' => 'datetime', 'insights_synced_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function entities() { return $this->hasMany(AdEntity::class); }
}
```

`AdEntity.php` (fillable: tenant_id, ad_account_id, level, external_id, parent_external_id, parent_id, name, status, effective_status, daily_budget, lifetime_budget, meta; cast meta=array). `AdInsightSnapshot.php` (fillable all snapshot columns; casts: date_start/date_stop=date, fetched_at=datetime, is_finalizing=bool, metrics=array). Follow the same structure.

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git commit -am "feat(marketing): AdAccount/AdEntity/AdInsightSnapshot models"`

---

## Task 8: AdsOAuthController + routes (connect + callback)

**Files:** Create `AdsOAuthController.php`; modify `app/app/Modules/Marketing/Http/routes.php`, `app/routes/web.php`. Test: `app/tests/Feature/Marketing/AdsOAuthTest.php`

- [ ] **Step 1: Write failing test** (mirror `app/tests/Feature/Messaging/LazadaImOAuthTest.php`)

```php
<?php
namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdsOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook'], 'integrations.ads_facebook' => ['app_id' => 'A', 'app_secret' => 'S', 'graph_version' => 'v19.0']]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    public function test_callback_connects_ad_accounts(): void
    {
        Queue::fake();
        Http::fake([
            'graph.facebook.com/*oauth/access_token*' => Http::response(['access_token' => 'AT', 'expires_in' => 5184000], 200),
            'graph.facebook.com/*me/adaccounts*' => Http::response(['data' => [['id' => 'act_123', 'name' => 'Shop', 'currency' => 'VND', 'account_status' => 1]]], 200),
        ]);
        $tenant = Tenant::create(['name' => 'T']);
        OAuthState::create(['state' => 'st_ads_1', 'provider' => 'facebook_ads', 'tenant_id' => $tenant->id, 'created_by' => null, 'redirect_after' => '/marketing?connected=facebook_ads', 'expires_at' => now()->addMinutes(10)]);

        $this->get('/oauth/facebook_ads/callback?code=CODE&state=st_ads_1')
            ->assertOk()->assertViewIs('oauth-callback');

        $this->assertDatabaseHas('ad_accounts', ['tenant_id' => $tenant->id, 'provider' => 'facebook', 'external_account_id' => 'act_123']);
        $acct = AdAccount::withoutGlobalScopes()->where('external_account_id', 'act_123')->firstOrFail();
        $this->assertSame('AT', $acct->access_token);
        \CMBcoreSeller\Modules\Marketing\Jobs\SyncAdAccountEntities::class; // referenced in controller
        Queue::assertPushed(\CMBcoreSeller\Modules\Marketing\Jobs\SyncAdAccountEntities::class);
    }
}
```

> This test references `SyncAdAccountEntities` (Task 10). To keep tasks runnable in order, either implement Task 10 before this test's `Queue::assertPushed` line, or split: implement connect/callback first (assert ad_accounts row) and add the `Queue::assertPushed` assertion after Task 10. Recommended: do Task 10 before Task 8's final step, or temporarily dispatch nothing and add dispatch+assertion when Task 10 lands.

- [ ] **Step 2: Run, expect FAIL** (route 404).

- [ ] **Step 3: Implement `AdsOAuthController`** (mirror `LazadaImOAuthController` exactly: `start` issues `OAuthState::issue('facebook_ads', tenantId, userId, '/marketing?connected=facebook_ads')` → `registry->for('facebook')->buildAuthorizationUrl($state->state)`; `callback` verifies state → `exchangeCodeForToken` → `listAdAccounts` → `updateOrCreate` an `AdAccount` per returned account with token + currency + status active → dispatch `SyncAdAccountEntities` per account → `finish($redirect)`). Use `provider='facebook'` on the row but `OAuthState.provider='facebook_ads'`. `finish()` returns `view('oauth-callback', ['redirect' => $redirect])`.

Full controller code: copy `app/app/Modules/Messaging/Http/Controllers/LazadaImOAuthController.php` and adapt: `PROVIDER='facebook_ads'`, registry = `AdsRegistry` resolving `'facebook'`, loop over `listAdAccounts()`, write `ad_accounts`. (Write the complete file — do not leave a reference.)

- [ ] **Step 4: Register routes**
  - `app/app/Modules/Marketing/Http/routes.php`: group `api/v1/marketing` with `auth:sanctum`,`verified`,`tenant` (match messaging group middleware) → `POST ads/connect → [AdsOAuthController, 'start']` (Gate `messaging.connect` or a new `marketing.connect` — confirm RBAC; reuse an existing ads/marketing permission or add one).
  - `app/routes/web.php`: `Route::get('oauth/facebook_ads/callback', [AdsOAuthController::class, 'callback'])->name('marketing.ads.callback');`

- [ ] **Step 5: Run, expect PASS.**
- [ ] **Step 6: Commit** — `git commit -m "feat(marketing): Facebook Ads OAuth connect + callback"`

---

## Task 9: AdAccountController (list / disconnect / refresh-token)

**Files:** Create `AdAccountController.php`, `AdAccountResource.php`; modify routes. Test: `app/tests/Feature/Marketing/AdAccountApiTest.php`

- [ ] **Step 1: Write failing test** — assert `GET /api/v1/marketing/ad-accounts` returns the tenant's accounts (envelope `{data:[...]}`), token NOT exposed; `DELETE /ad-accounts/{id}` soft-deletes; both gated by RBAC + tenant scope. (Write concrete assertions mirroring `MessagingChannelControllerTest`.)
- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement** thin controller (Service optional) + `AdAccountResource` (id, provider, external_account_id, name, currency, status, last_synced_at, insights_synced_at — NEVER token). Add routes `GET ad-accounts`, `DELETE ad-accounts/{id}`, `POST ad-accounts/{id}/refresh` (dispatch SyncAdInsights now).
- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git commit -m "feat(marketing): ad accounts API (list/disconnect/refresh)"`

---

## Task 10: SyncAdAccountEntities job

**Files:** Create `SyncAdAccountEntities.php`, `AdsSyncService.php`. Test: `app/tests/Feature/Marketing/SyncAdAccountEntitiesTest.php`

- [ ] **Step 1: Write failing test** — given an `AdAccount` + `Http::fake` for campaigns/adsets/ads, running the job upserts `ad_entities` rows (campaign + adset with `parent_id` linked + ad), idempotent on re-run (no duplicates; uses the `(ad_account_id, level, external_id)` unique key). Resolve connector via `AdsRegistry` (config `integrations.ads=['facebook']`).
- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement** — `SyncAdAccountEntities implements ShouldQueue, ShouldBeUnique` (`uniqueId = "ads-entities:{accountId}"`, `onQueue('marketing-sync')`). In `handle(AdsRegistry $registry)`: load account (`withoutGlobalScope(TenantScope::class)`), guard active + registry has provider; for each level campaign→adset→ad call `listEntities`, upsert via `AdsSyncService::upsertEntity` (resolves `parent_id` from `parent_external_id`), set `last_synced_at`. Mirror `SyncConversationsForShop` structure (auth context, withoutGlobalScope, idempotent upsert).
- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git commit -m "feat(marketing): SyncAdAccountEntities job"`

---

## Task 11: SyncAdInsights job (near-real-time, adaptive throttle)

**Files:** Create `SyncAdInsights.php`. Test: `app/tests/Feature/Marketing/SyncAdInsightsTest.php`

- [ ] **Step 1: Write failing test** — given account + entities + `Http::fake` insights, the job upserts `ad_insight_snapshots` (one per entity+window), sets metrics + `fetched_at` + `is_finalizing` (true when date range within last 28d), idempotent (re-run updates same row, not duplicate). When throttle header `app_id_util_pct` ≥ threshold, job releases/backs off (assert via a high-util fake → job sets account meta `throttled` and does NOT hammer further; or releases). Keep the throttle assertion simple: when hot, stop after current entity and `release(120)`.
- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement** — `implements ShouldQueue, ShouldBeUnique` (`ads-insights:{accountId}`, `onQueue('marketing-sync')`, `backoff [60,300,900]`). `handle(AdsRegistry $registry)`: for account + each active entity (and account-level), call `fetchInsights(..., $throttle)`; upsert snapshot via `updateOrCreate` on the unique key; if `$throttle->isHot()` → set `meta['insights_throttled']=true` and `release(120); return;`. Set `insights_synced_at=now()` on success. Compute `is_finalizing = date_stop >= today()->subDays(28)`.
- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git commit -m "feat(marketing): SyncAdInsights near-real-time job + throttle pacing"`

---

## Task 12: Scheduler (ads-insights-poll every 15')

**Files:** Modify `app/routes/console.php`. Test: `app/tests/Feature/Marketing/AdsScheduleTest.php`

- [ ] **Step 1: Write failing test** — assert `php artisan schedule:list` contains a task named `ads-insights-poll` (mirror how other schedule tests assert, or assert the closure dispatches `SyncAdInsights` for active accounts when invoked). Simplest: a feature test that creates 1 active `AdAccount`, invokes the scheduled closure (extract to an invokable or call the same query), and asserts `SyncAdInsights` pushed (Queue::fake).
- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement** — add to `app/routes/console.php` (mirror `messaging-chat-poll` block at line ~119):

```php
// Every 15': poll Facebook ad insights for active ad accounts (FB refreshes ~15').
Schedule::call(function () {
    \CMBcoreSeller\Modules\Marketing\Models\AdAccount::withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)
        ->where('status', 'active')->orderBy('id')
        ->each(fn ($a) => \CMBcoreSeller\Modules\Marketing\Jobs\SyncAdInsights::dispatch((int) $a->id));
})->everyFifteenMinutes()->name('ads-insights-poll')->onOneServer()->withoutOverlapping();
```

- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git commit -m "feat(marketing): schedule ads-insights-poll (15m)"`

---

## Task 13: AdInsightController (dashboard data)

**Files:** Create `AdInsightController.php`, `AdEntityResource.php`, `AdInsightResource.php`; modify routes. Test: `app/tests/Feature/Marketing/AdInsightApiTest.php`

- [ ] **Step 1: Write failing test** — `GET /api/v1/marketing/ad-accounts/{id}/insights` returns the entity tree with latest snapshot metrics per entity (envelope), tenant-scoped, RBAC-gated, token never exposed. Seed account + entities + snapshots; assert structure + a metric value + `is_finalizing` flag present.
- [ ] **Step 2: Run, expect FAIL.**
- [ ] **Step 3: Implement** thin controller: load entities of account (tenant-scoped) grouped by level with their latest `AdInsightSnapshot` (by window), return via Resources. Add route `GET ad-accounts/{id}/insights`.
- [ ] **Step 4: Run, expect PASS.**
- [ ] **Step 5: Commit** — `git commit -m "feat(marketing): ad insights dashboard API"`

---

## Task 14: Frontend — Marketing dashboard (read-only)

**Files:** Create `app/resources/js/lib/marketing.tsx`, `app/resources/js/pages/MarketingDashboardPage.tsx`; modify router + nav (find where `MessagingChannelsPage` is routed in `app/resources/js/app.tsx`/router and the nav menu; add a "Quảng cáo" entry).

- [ ] **Step 1: Add API hooks** — `marketing.tsx`: `useAdAccounts()` (`GET /marketing/ad-accounts`), `useConnectFacebookAds()` (`POST /marketing/ads/connect` → `{authorize_url}`, then `openOAuthPopup` like `useConnectFacebook`), `useAdInsights(accountId)` (`GET /marketing/ad-accounts/{id}/insights`, `refetchInterval: 15*60*1000` for auto-poll), `useRefreshAdInsights(id)` (`POST /marketing/ad-accounts/{id}/refresh`). Mirror `app/resources/js/lib/messagingConfig.tsx`.
- [ ] **Step 2: Build `MarketingDashboardPage`** — connect button ("Kết nối Facebook Ads"), account selector (Segmented/Radio, not Select per project pref), metric cards (spend/impressions/clicks/CTR/CPC/ROAS) + entity table (campaign→adset→ad) with `is_finalizing` badge ("đang hoàn tất ≤28 ngày"), Refresh button (`useRefreshAdInsights`). @ant-design/icons only (no emoji). Reuse `openOAuthPopup`, `errorMessage`, `PageHeader` like `MessagingChannelsPage`.
- [ ] **Step 3: Route + nav** — add `/marketing` route to the user SPA router and a sidebar menu item (mirror how `/messaging/channels` is wired).
- [ ] **Step 4: Verify** — `npm run typecheck` (no NEW errors in marketing files), `npm run lint` (clean on new files), `npm run build` (succeeds).
- [ ] **Step 5: Commit** — `git commit -m "feat(marketing): Facebook Ads dashboard (read-only) + connect"`

---

## Task 15: Quality gate + docs

- [ ] **Step 1:** `cd app && vendor/bin/pint` (autofix) then `vendor/bin/pint --test` → passes.
- [ ] **Step 2:** `vendor/bin/phpstan analyse <new files>` → No errors (add `@property` docblocks to new models if phpstan flags dynamic props; do NOT add to baseline). Confirm full-suite phpstan delta is clean on Linux/CI (Windows baseline path mismatch is noise).
- [ ] **Step 3:** `php artisan test tests/Feature/Marketing tests/Unit/Marketing tests/Unit/Ads tests/Feature/Ads` → all pass.
- [ ] **Step 4:** Create `docs/04-channels/facebook-ads-setup.md` (mirror `facebook-messenger-setup.md`): Meta app → add Marketing API product → scopes `ads_read,business_management` → redirect `…/oauth/facebook_ads/callback` → env `INTEGRATIONS_ADS=facebook` → App Review/Standard Tier checklist for prod. Add the new endpoints to `docs/05-api/endpoints.md`. Write the new ADR for the `Ads` integration axis under `docs/01-architecture/adr/`.
- [ ] **Step 5: Commit** — `git commit -m "docs(marketing): Facebook Ads setup + Ads-axis ADR + endpoints"`

---

## Self-Review notes (author)

- **Spec coverage:** §4 P1 (OAuth+ingestion+dashboard) → Tasks 1-14; §6 data model → Tasks 1,7; §7 throttle → Tasks 6,11; §10 OAuth/app reuse → Task 3,8; §12 testing → every task + Task 15; §13 build sequence → task order. P2/P3 explicitly out of this plan (separate plans).
- **Known follow-ups before coding:** (1) confirm exact `BelongsToTenant` trait path + tenant-setting test helper from `ChannelAccount`/an existing tenant test; (2) confirm RBAC permission for marketing (reuse `messaging.connect` or add `marketing.*`); (3) Task 8's `Queue::assertPushed` depends on Task 10 — implement Task 10 first or stage the assertion. These are flagged inline, not placeholders in the code.
- **Currency:** Phase 1 assumes VND (zero-decimal); multi-currency minor-unit handling deferred (noted in Task 6).
