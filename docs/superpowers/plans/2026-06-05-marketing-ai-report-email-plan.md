# AI Marketing Report (async + email + creative review) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`).
> **CONCURRENCY:** another agent edits other files in this repo. Touch ONLY the files each task names; `git add` ONLY those exact paths (never `git add -A`/`.`).

**Goal:** Run AI marketing forecast asynchronously, analyze today + 14-day metrics per campaign plus per-ad creative content, and email the full report to every Owner/Admin of the tenant.

**Architecture:** A new `fetchAdCreatives` read on the Ads connector feeds the enriched `AdsForecastService`. The `POST /forecast` endpoint dispatches a `GenerateAdForecast` Horizon job (cooldown-gated) which generates the forecast then sends `MarketingForecastReadyNotification` (mail) to Owner/Admin members. AI output gains a `creative_review` block.

**Tech Stack:** Laravel 11, Horizon, `AdsRegistry`/`AdsConnector` + DTOs, Laravel Notifications (mail, queued), PHPUnit `Http::fake`/`Queue::fake`/`Notification::fake`, Pint, Larastan L5.

**Conventions:** Commands from `app/`. Mirror existing `FacebookAdsConnector` (read methods), `AdsForecastService`, `LlmMarketingAnalysisClient`, `Jobs/SyncAdInsights`, `Modules/Notifications/Notifications/VerifyEmailNotification` + blade at `app/resources/views/mail/` (namespace `notifications::`). `Tenant::users()` is belongsToMany with pivot `role`.

---

### Task 1: `AdCreativeDTO` + `fetchAdCreatives` (connector read)

**Files:**
- Create: `app/app/Integrations/Ads/DTO/AdCreativeDTO.php`
- Modify: `app/app/Integrations/Ads/Contracts/AdsConnector.php` (add method to interface)
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (implement + capability)
- Test: `app/tests/Feature/Marketing/FacebookAdsCreativesTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsCreativesTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_fetch_ad_creatives_maps_text_and_post_id(): void
    {
        Http::fake(['graph.facebook.com/*/ads*' => Http::response(['data' => [[
            'id' => 'AD1', 'name' => 'QC Tết', 'effective_status' => 'ACTIVE',
            'creative' => [
                'effective_object_story_id' => '123_456',
                'object_story_spec' => ['link_data' => [
                    'message' => 'Sale Tết giảm 30%', 'name' => 'Áo khoác hot',
                    'call_to_action' => ['type' => 'MESSAGE_PAGE'],
                ]],
            ],
        ]]], 200)]);

        $list = $this->connector()->fetchAdCreatives('tok', 'act_1');

        $this->assertCount(1, $list);
        $c = $list[0];
        $this->assertSame('AD1', $c->adId);
        $this->assertSame('QC Tết', $c->adName);
        $this->assertSame('Sale Tết giảm 30%', $c->primaryText);
        $this->assertSame('Áo khoác hot', $c->headline);
        $this->assertSame('MESSAGE_PAGE', $c->cta);
        $this->assertSame('123_456', $c->pagePostId);
        $this->assertTrue($this->connector()->supports('creatives.read'));
    }

    public function test_fetch_ad_creatives_throws_on_error(): void
    {
        Http::fake(['graph.facebook.com/*/ads*' => Http::response(['error' => ['message' => 'bad']], 400)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fetchAdCreatives failed');
        $this->connector()->fetchAdCreatives('tok', 'act_1');
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=FacebookAdsCreativesTest` → FAIL.

- [ ] **Step 3: Implement.**

`AdCreativeDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdCreativeDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $adId,
        public ?string $adName,
        public ?string $effectiveStatus,
        public ?string $primaryText,
        public ?string $headline,
        public ?string $cta,
        public ?string $pagePostId,
        public array $raw = [],
    ) {}
}
```

In `AdsConnector.php`, add the import + method signature (after `fetchInsights`):
```php
use CMBcoreSeller\Integrations\Ads\DTO\AdCreativeDTO;
```
```php
    /**
     * Read each ad's creative text (for content/quality analysis).
     *
     * @return list<AdCreativeDTO>
     */
    public function fetchAdCreatives(string $accessToken, string $externalAccountId): array;
```

In `FacebookAdsConnector.php`: add `use CMBcoreSeller\Integrations\Ads\DTO\AdCreativeDTO;`, add `'creatives.read' => true,` to `capabilities()`, and add the method:
```php
    public function fetchAdCreatives(string $accessToken, string $externalAccountId): array
    {
        $res = Http::timeout(30)->get($this->graphUrl($externalAccountId.'/ads'), [
            'fields' => 'id,name,effective_status,creative{body,title,effective_object_story_id,object_story_spec{link_data{message,name,call_to_action{type}}}}',
            'access_token' => $accessToken,
            'limit' => 200,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads fetchAdCreatives failed: '.$res->body());
        }

        return array_values(array_map(function (array $a) {
            $creative = (array) ($a['creative'] ?? []);
            $linkData = (array) ($creative['object_story_spec']['link_data'] ?? []);

            return new AdCreativeDTO(
                adId: (string) ($a['id'] ?? ''),
                adName: isset($a['name']) ? (string) $a['name'] : null,
                effectiveStatus: isset($a['effective_status']) ? (string) $a['effective_status'] : null,
                primaryText: $linkData['message'] ?? ($creative['body'] ?? null) ? (string) ($linkData['message'] ?? $creative['body']) : null,
                headline: $linkData['name'] ?? ($creative['title'] ?? null) ? (string) ($linkData['name'] ?? $creative['title']) : null,
                cta: isset($linkData['call_to_action']['type']) ? (string) $linkData['call_to_action']['type'] : null,
                pagePostId: isset($creative['effective_object_story_id']) ? (string) $creative['effective_object_story_id'] : null,
                raw: $a,
            );
        }, array_filter((array) $res->json('data', []), 'is_array')));
    }
```

- [ ] **Step 4: Run** `php artisan test --filter=FacebookAdsCreativesTest` → PASS (2 tests). Regression: `php artisan test --filter='FacebookAdsConnectorTest|FacebookAdsCreateTest'` green.

- [ ] **Step 5: Commit (exact paths only)**
```
cd app && vendor/bin/pint app/Integrations/Ads/DTO/AdCreativeDTO.php app/Integrations/Ads/Contracts/AdsConnector.php app/Integrations/Ads/Facebook/FacebookAdsConnector.php tests/Feature/Marketing/FacebookAdsCreativesTest.php && vendor/bin/phpstan analyse app/Integrations/Ads && cd ..
git add app/app/Integrations/Ads/DTO/AdCreativeDTO.php app/app/Integrations/Ads/Contracts/AdsConnector.php app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/tests/Feature/Marketing/FacebookAdsCreativesTest.php
git commit -m "feat(ads): fetchAdCreatives read (ad creative text for analysis)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

> phpstan note: the `?:` ternary for primaryText/headline can confuse Larastan. If it flags, simplify to: `primaryText: isset($linkData['message']) ? (string) $linkData['message'] : (isset($creative['body']) ? (string) $creative['body'] : null),` and the same shape for headline. Use that form if needed.

---

### Task 2: `creative_review` in the AI client (stub + schema)

**Files:**
- Modify: `app/app/Modules/Marketing/Services/LlmMarketingAnalysisClient.php`
- Test: `app/tests/Feature/Marketing/MarketingAiSchemaTest.php` (add a test method — file already exists)

- [ ] **Step 1: Add the failing test method** to `MarketingAiSchemaTest`:

```php
    public function test_stub_includes_creative_review_per_creative(): void
    {
        $client = app(\CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient::class);

        $result = $client->analyze([
            'rows' => [],
            'creatives' => [['ad_id' => 'AD1', 'name' => 'QC Tết'], ['post_id' => 'P2', 'name' => 'Bài 2']],
        ], 'instr');

        $review = $result['payload']['creative_review'] ?? null;
        $this->assertIsArray($review);
        $this->assertCount(2, $review);
        $this->assertSame('AD1', $review[0]['ref']);
        $this->assertArrayHasKey('verdict', $review[0]);
        $this->assertArrayHasKey('suggestions', $review[0]);
    }
```

- [ ] **Step 2: Run** `php artisan test --filter=MarketingAiSchemaTest` → the new test FAILs (no creative_review).

- [ ] **Step 3: Implement.** In `LlmMarketingAnalysisClient.php`:

(a) In `prompt()`, extend the JSON schema line to include `creative_review`. Replace the schema sentence with:
```php
            ."\n\nCHỈ trả về JSON đúng schema {forecast:{next_7d:{conversations,orders,spend,projected_cost_per_order}}, strategy:[{action,campaign,rationale,confidence}], creative_review:[{ref,name,verdict,issues:[string],suggestions:[string]}]}. "
            .'creative_review: với MỖI quảng cáo/bài post trong dữ liệu, đánh giá nội dung đã tối ưu chưa (dựa trên text + tương tác + hiệu suất), verdict "tốt" hoặc "cần cải thiện".';
```

(b) In `stub()`, before the `return`, build `creative_review` from `$data['creatives']` and add it to the returned array:
```php
        $creatives = array_values(array_filter((array) ($data['creatives'] ?? []), 'is_array'));
        $review = array_map(fn (array $c) => [
            'ref' => (string) ($c['ad_id'] ?? $c['post_id'] ?? ''),
            'name' => (string) ($c['name'] ?? ''),
            'verdict' => 'cần xem xét',
            'issues' => [],
            'suggestions' => ['Thêm lời kêu gọi hành động rõ ràng và hình ảnh/đoạn mở đầu nổi bật.'],
        ], $creatives);
```
and add `'creative_review' => $review,` to the returned `[...]` array (alongside `forecast`, `strategy`, `generated_by`).

- [ ] **Step 4: Run** `php artisan test --filter=MarketingAiSchemaTest` → PASS. Regression: `php artisan test --filter=AdsForecastServiceTest` green.

- [ ] **Step 5: Commit**
```
cd app && vendor/bin/pint app/Modules/Marketing/Services/LlmMarketingAnalysisClient.php tests/Feature/Marketing/MarketingAiSchemaTest.php && vendor/bin/phpstan analyse app/Modules/Marketing/Services/LlmMarketingAnalysisClient.php && cd ..
git add app/app/Modules/Marketing/Services/LlmMarketingAnalysisClient.php app/tests/Feature/Marketing/MarketingAiSchemaTest.php
git commit -m "feat(marketing): AI creative_review block (stub + prompt schema)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Enrich `AdsForecastService` data (today + 14d + creatives)

**Files:**
- Modify: `app/app/Modules/Marketing/Services/AdsForecastService.php`
- Test: `app/tests/Feature/Marketing/AdsForecastServiceTest.php` (add a method)

- [ ] **Step 1: Add the failing test** to `AdsForecastServiceTest` (it already sets up tenant + Manual AI). Add:

```php
    public function test_generate_gathers_campaign_insights_and_creatives(): void
    {
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(\CMBcoreSeller\Integrations\Ads\AdsRegistry::class);

        $tenant = \CMBcoreSeller\Modules\Tenancy\Models\Tenant::create(['name' => 'T']);
        app(\CMBcoreSeller\Modules\Tenancy\CurrentTenant::class)->set($tenant);
        $account = \CMBcoreSeller\Modules\Marketing\Models\AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND',
            'status' => 'active', 'access_token' => 'TOK',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*/insights*' => \Illuminate\Support\Facades\Http::response(['data' => [
                ['campaign_id' => 'C1', 'spend' => '1000', 'impressions' => '50', 'clicks' => '3'],
            ]], 200),
            'graph.facebook.com/*/ads*' => \Illuminate\Support\Facades\Http::response(['data' => [
                ['id' => 'AD1', 'name' => 'QC', 'effective_status' => 'ACTIVE',
                 'creative' => ['object_story_spec' => ['link_data' => ['message' => 'Mua ngay']]]],
            ]], 200),
        ]);

        $forecast = app(\CMBcoreSeller\Modules\Marketing\Services\AdsForecastService::class)->generate($account, true);

        $this->assertNotNull($forecast->payload['creative_review'] ?? null);
        // creative_review has the AD1 creative from fetchAdCreatives
        $this->assertSame('AD1', $forecast->payload['creative_review'][0]['ref']);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r->url(), 'act_1/ads'));
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r->url(), '/insights'));
    }
```

- [ ] **Step 2: Run** `php artisan test --filter=AdsForecastServiceTest` → new test FAILs.

- [ ] **Step 3: Implement.** In `AdsForecastService.php`:

(a) Add constructor dependency on the registry. Change the constructor to:
```php
    public function __construct(
        private MarketingAnalysisClient $client,
        private AdReconciliationService $reconciliation,
        private \CMBcoreSeller\Integrations\Ads\AdsRegistry $registry,
    ) {}
```

(b) Extend the `INSTRUCTION` constant text to mention today-vs-past + creative review (append to the existing string):
```php
    private const INSTRUCTION = 'Bạn là chuyên gia tối ưu quảng cáo Facebook. Dựa trên đối soát chi tiêu/hội thoại Messenger/leads vs đơn thủ công theo ngày, chỉ số theo chiến dịch (HÔM NAY và 14 ngày qua), và NỘI DUNG creative/bài post, hãy: (1) DỰ BÁO 7 ngày tới, (2) đề xuất CHIẾN LƯỢC tối ưu (tăng/giảm ngân sách, tạm dừng, đổi tệp/nội dung), (3) ĐÁNH GIÁ nội dung từng quảng cáo/bài post đã tối ưu chưa.';
```

(c) In `generate()`, replace the data assembly + analyze call (the two lines building `$rows`/`$result`) with enriched gathering:
```php
        $rows = $this->reconciliation->reconcile($account, 14);
        $result = $this->client->analyze($this->buildData($account, $rows), self::INSTRUCTION);
```

(d) Add a private `buildData` method (best-effort connector reads; failure falls back to reconciliation-only):
```php
    /**
     * @param  list<array<string,mixed>>  $reconciliationRows
     * @return array<string,mixed>
     */
    private function buildData(AdAccount $account, array $reconciliationRows): array
    {
        $data = [
            'currency' => $account->currency,
            'reconciliation' => $reconciliationRows,
            'campaigns_today' => [],
            'campaigns_14d' => [],
            'creatives' => [],
        ];

        if (! $this->registry->has($account->provider)) {
            return $data;
        }

        try {
            $connector = $this->registry->for($account->provider);
            $token = (string) $account->access_token;
            $acc = $account->external_account_id;

            $map = fn (array $rows) => array_map(fn ($r) => [
                'campaign_id' => $r->raw['campaign_id'] ?? null,
                'spend' => $r->spend, 'impressions' => $r->impressions, 'clicks' => $r->clicks,
                'ctr' => $r->ctr, 'cpc' => $r->cpc, 'cpm' => $r->cpm, 'roas' => $r->purchaseRoas,
                'conversations' => $r->messagingConversations, 'leads' => $r->leads,
            ], $rows);

            $data['campaigns_today'] = $map($connector->fetchInsights($token, $acc, 'campaign', ['date_preset' => 'today']));
            $data['campaigns_14d'] = $map($connector->fetchInsights($token, $acc, 'campaign', [
                'time_range' => ['since' => now()->subDays(13)->toDateString(), 'until' => now()->toDateString()],
            ]));

            if ($connector->supports('creatives.read')) {
                $data['creatives'] = array_map(fn ($c) => [
                    'ad_id' => $c->adId, 'name' => $c->adName, 'status' => $c->effectiveStatus,
                    'primary_text' => $c->primaryText, 'headline' => $c->headline, 'cta' => $c->cta, 'post_id' => $c->pagePostId,
                ], $connector->fetchAdCreatives($token, $acc));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('marketing.forecast.enrich_failed', ['account' => $account->getKey(), 'error' => $e->getMessage()]);
        }

        return $data;
    }
```

- [ ] **Step 4: Run** `php artisan test --filter=AdsForecastServiceTest` → PASS. Regression: `php artisan test --filter='AdForecastApiTest|AdReconciliationServiceTest'` green.

- [ ] **Step 5: Commit**
```
cd app && vendor/bin/pint app/Modules/Marketing/Services/AdsForecastService.php tests/Feature/Marketing/AdsForecastServiceTest.php && vendor/bin/phpstan analyse app/Modules/Marketing/Services/AdsForecastService.php && cd ..
git add app/app/Modules/Marketing/Services/AdsForecastService.php app/tests/Feature/Marketing/AdsForecastServiceTest.php
git commit -m "feat(marketing): forecast gathers today+14d campaign insights + ad creatives

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: `MarketingForecastReadyNotification` + mail blade

**Files:**
- Create: `app/app/Modules/Marketing/Notifications/MarketingForecastReadyNotification.php`
- Create: `app/resources/views/mail/marketing-forecast-ready.blade.php`
- Test: `app/tests/Feature/Marketing/MarketingForecastNotificationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdForecast;
use CMBcoreSeller\Modules\Marketing\Notifications\MarketingForecastReadyNotification;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingForecastNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_mail_has_subject_and_view_data(): void
    {
        $tenant = Tenant::create(['name' => 'Shop X']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'name' => 'TK1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'X']);
        $forecast = AdForecast::create([
            'ad_account_id' => $account->id, 'payload' => [
                'forecast' => ['next_7d' => ['orders' => 5, 'spend' => 700000]],
                'strategy' => [['action' => 'maintain_budget', 'rationale' => 'ổn định']],
                'creative_review' => [['ref' => 'AD1', 'name' => 'QC', 'verdict' => 'cần cải thiện', 'issues' => [], 'suggestions' => ['Thêm CTA']]],
            ],
            'provider_code' => 'cmb', 'model' => 'x', 'generated_at' => now(),
        ]);

        $mail = (new MarketingForecastReadyNotification($account, $forecast))->toMail(new \stdClass);

        $this->assertStringContainsString('Báo cáo quảng cáo', $mail->subject);
        $this->assertSame('notifications::marketing-forecast-ready', $mail->view[0]);
        $this->assertSame($account->id, $mail->viewData['account']->id);
        $this->assertArrayHasKey('payload', $mail->viewData);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=MarketingForecastNotificationTest` → FAIL.

- [ ] **Step 3: Implement.**

`MarketingForecastReadyNotification.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Notifications;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdForecast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email báo cáo quảng cáo AI đã sẵn sàng (gửi cho Owner/Admin của tenant).
 * Queue `notifications`. Nội dung: dự báo 7 ngày + chiến lược + đánh giá creative.
 */
class MarketingForecastReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public AdAccount $account, public AdForecast $forecast)
    {
        $this->queue = (string) config('notifications.queue', 'notifications');
    }

    /** @return array<int,int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $brand = (string) system_setting('notifications.brand_name', config('notifications.brand.name', 'CMBcoreSeller'));

        return (new MailMessage)
            ->subject("[{$brand}] Báo cáo quảng cáo đã sẵn sàng")
            ->view('notifications::marketing-forecast-ready', [
                'account' => $this->account,
                'payload' => (array) $this->forecast->payload,
                'generatedAt' => $this->forecast->generated_at,
                'appUrl' => rtrim((string) config('app.url'), '/').'/marketing',
            ]);
    }
}
```

`app/resources/views/mail/marketing-forecast-ready.blade.php`:
```blade
@php
    $f = $payload['forecast']['next_7d'] ?? [];
    $strategy = $payload['strategy'] ?? [];
    $review = $payload['creative_review'] ?? [];
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp
<h2>Báo cáo quảng cáo — {{ $account->name ?? $account->external_account_id }}</h2>
<p>Báo cáo AI cho tài khoản quảng cáo của bạn đã sẵn sàng@if($generatedAt) (lúc {{ $generatedAt->format('H:i d/m/Y') }})@endif.</p>

<h3>Dự báo 7 ngày tới</h3>
<ul>
    <li>Đơn dự kiến: <b>{{ $f['orders'] ?? '—' }}</b></li>
    <li>Chi tiêu dự kiến: <b>{{ isset($f['spend']) ? $fmt($f['spend']).'đ' : '—' }}</b></li>
    <li>Hội thoại dự kiến: <b>{{ $f['conversations'] ?? '—' }}</b></li>
</ul>

<h3>Chiến lược đề xuất</h3>
<ul>
    @forelse($strategy as $s)
        <li><b>{{ $s['action'] ?? '' }}</b> — {{ $s['rationale'] ?? '' }}</li>
    @empty
        <li>—</li>
    @endforelse
</ul>

<h3>Đánh giá nội dung quảng cáo</h3>
@forelse($review as $r)
    <p>
        <b>{{ $r['name'] ?? $r['ref'] ?? 'Quảng cáo' }}</b> — {{ $r['verdict'] ?? '' }}<br>
        @foreach(($r['suggestions'] ?? []) as $sug)• {{ $sug }}<br>@endforeach
    </p>
@empty
    <p>—</p>
@endforelse

<p><a href="{{ $appUrl }}">Xem chi tiết trong ứng dụng</a></p>
```

- [ ] **Step 4: Run** `php artisan test --filter=MarketingForecastNotificationTest` → PASS.

- [ ] **Step 5: Commit**
```
cd app && vendor/bin/pint app/Modules/Marketing/Notifications/MarketingForecastReadyNotification.php tests/Feature/Marketing/MarketingForecastNotificationTest.php && vendor/bin/phpstan analyse app/Modules/Marketing/Notifications && cd ..
git add app/app/Modules/Marketing/Notifications/MarketingForecastReadyNotification.php app/resources/views/mail/marketing-forecast-ready.blade.php app/tests/Feature/Marketing/MarketingForecastNotificationTest.php
git commit -m "feat(marketing): forecast-ready email notification + blade

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 5: `GenerateAdForecast` job (generate + notify Owner/Admin)

**Files:**
- Create: `app/app/Modules/Marketing/Jobs/GenerateAdForecast.php`
- Test: `app/tests/Feature/Marketing/GenerateAdForecastTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Jobs\GenerateAdForecast;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Notifications\MarketingForecastReadyNotification;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GenerateAdForecastTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_and_emails_owner_and_admin_only(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'X']);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $staff = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $tenant->users()->attach($admin->getKey(), ['role' => Role::Admin->value]);
        $tenant->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);

        (new GenerateAdForecast($account->id))->handle();

        $this->assertDatabaseHas('ad_forecasts', ['ad_account_id' => $account->id]);
        Notification::assertSentTo($owner, MarketingForecastReadyNotification::class);
        Notification::assertSentTo($admin, MarketingForecastReadyNotification::class);
        Notification::assertNotSentTo($staff, MarketingForecastReadyNotification::class);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=GenerateAdForecastTest` → FAIL.

- [ ] **Step 3: Implement** `GenerateAdForecast.php`:
```php
<?php

namespace CMBcoreSeller\Modules\Marketing\Jobs;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Notifications\MarketingForecastReadyNotification;
use CMBcoreSeller\Modules\Marketing\Services\AdsForecastService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Generate the AI marketing forecast for one ad account (cooldown already gated by
 * the controller ⇒ force), then email it to every Owner/Admin of the tenant.
 */
class GenerateAdForecast implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 900;

    public function __construct(public int $adAccountId)
    {
        $this->onQueue('marketing-ai');
    }

    public function uniqueId(): string
    {
        return "forecast:{$this->adAccountId}";
    }

    public function handle(): void
    {
        /** @var AdAccount|null $account */
        $account = AdAccount::withoutGlobalScope(TenantScope::class)->find($this->adAccountId);
        if (! $account) {
            return;
        }

        $forecast = app(AdsForecastService::class)->generate($account, true);

        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($account->tenant_id);
        if (! $tenant) {
            return;
        }
        $recipients = $tenant->users()->wherePivotIn('role', [Role::Owner->value, Role::Admin->value])->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new MarketingForecastReadyNotification($account, $forecast));
        }
    }
}
```

- [ ] **Step 4: Run** `php artisan test --filter=GenerateAdForecastTest` → PASS.

- [ ] **Step 5: Commit**
```
cd app && vendor/bin/pint app/Modules/Marketing/Jobs/GenerateAdForecast.php tests/Feature/Marketing/GenerateAdForecastTest.php && vendor/bin/phpstan analyse app/Modules/Marketing/Jobs/GenerateAdForecast.php && cd ..
git add app/app/Modules/Marketing/Jobs/GenerateAdForecast.php app/tests/Feature/Marketing/GenerateAdForecastTest.php
git commit -m "feat(marketing): GenerateAdForecast job (async generate + email Owner/Admin)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 6: Controller dispatches the job (cooldown-gated)

**Files:**
- Modify: `app/app/Modules/Marketing/Http/Controllers/AdForecastController.php`
- Test: `app/tests/Feature/Marketing/AdForecastApiTest.php` (add 2 methods)

- [ ] **Step 1: Add failing tests** to `AdForecastApiTest` (read the file first to reuse its tenant/owner/header helpers; add):

```php
    public function test_generate_dispatches_job_when_no_cache(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $account = $this->makeAccount(); // reuse existing helper that creates an ad account (adapt to the file's helper name)

        $this->actingAs($this->owner())->withHeaders($this->headers())
            ->postJson("/api/v1/marketing/ad-accounts/{$account->id}/forecast")
            ->assertOk()
            ->assertJsonPath('status', 'generating')
            ->assertJsonPath('queued', true);

        \Illuminate\Support\Facades\Queue::assertPushed(\CMBcoreSeller\Modules\Marketing\Jobs\GenerateAdForecast::class, fn ($j) => $j->adAccountId === (int) $account->id);
    }

    public function test_generate_returns_cache_within_cooldown_without_dispatch(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $account = $this->makeAccount();
        \CMBcoreSeller\Modules\Marketing\Models\AdForecast::create([
            'ad_account_id' => $account->id, 'payload' => ['forecast' => []], 'provider_code' => 'x', 'model' => 'x', 'generated_at' => now(),
        ]);

        $this->actingAs($this->owner())->withHeaders($this->headers())
            ->postJson("/api/v1/marketing/ad-accounts/{$account->id}/forecast")
            ->assertOk()
            ->assertJsonPath('queued', false);

        \Illuminate\Support\Facades\Queue::assertNothingPushed();
    }
```
> Adapt `makeAccount()`/`owner()`/`headers()` to the actual helper names already in `AdForecastApiTest`. If the file lacks them, mirror `AdDraftApiTest`'s `account()`/`user(Role)`/`h()` helpers.

- [ ] **Step 2: Run** `php artisan test --filter=AdForecastApiTest` → new tests FAIL.

- [ ] **Step 3: Implement.** In `AdForecastController.php`, replace the `generate` method body. Add imports:
```php
use CMBcoreSeller\Modules\Marketing\Jobs\GenerateAdForecast;
```
```php
    /** POST /api/v1/marketing/ad-accounts/{id}/forecast — async generate (cooldown-gated). */
    public function generate(int $id): JsonResponse
    {
        Gate::authorize('marketing.view');
        $account = AdAccount::query()->findOrFail($id);

        $existing = $this->service->cached($account);
        $cooldown = (int) config('marketing.forecast_cooldown_minutes', 360);
        if ($existing !== null && $existing->generated_at->gt(now()->subMinutes($cooldown))) {
            return response()->json(['data' => $this->format($existing), 'status' => 'cached', 'queued' => false]);
        }

        GenerateAdForecast::dispatch($account->id);

        return response()->json(['data' => $this->format($existing), 'status' => 'generating', 'queued' => true]);
    }
```
(Leave `show`, `format` unchanged.)

- [ ] **Step 4: Run** `php artisan test --filter=AdForecastApiTest` → PASS. Regression: `php artisan test --filter='AdsForecastServiceTest|GenerateAdForecastTest'` green.

- [ ] **Step 5: Commit**
```
cd app && vendor/bin/pint app/Modules/Marketing/Http/Controllers/AdForecastController.php tests/Feature/Marketing/AdForecastApiTest.php && vendor/bin/phpstan analyse app/Modules/Marketing/Http/Controllers/AdForecastController.php && cd ..
git add app/app/Modules/Marketing/Http/Controllers/AdForecastController.php app/tests/Feature/Marketing/AdForecastApiTest.php
git commit -m "feat(marketing): forecast endpoint dispatches async job (cooldown-gated)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 7: Frontend — async generate + poll + creative_review panel

**Files:**
- Modify: `app/resources/js/lib/marketing.tsx` (extend `AdForecast` type + `useGenerateForecast` return)
- Modify: `app/resources/js/pages/MarketingDashboardPage.tsx` (generate→poll message + creative_review render)

- [ ] **Step 1: Implement** (no JS test runner — verify via tsc + eslint).

In `lib/marketing.tsx`:
(a) Extend the `AdForecast` payload interface to include creative review:
```ts
export interface CreativeReview { ref: string; name: string | null; verdict: string; issues: string[]; suggestions: string[] }
```
and add `creative_review?: CreativeReview[]` to the `AdForecast['payload']` shape.
(b) `useGenerateForecast`'s mutation result type → `{ data: AdForecast | null; status?: string; queued?: boolean }` (the POST now returns status/queued). Update the generic and return `.data` (the whole envelope) so the page can read `queued`.

In `pages/MarketingDashboardPage.tsx`, in `handleForecast`:
- On success: if `res.queued` → `message.info('Đang tạo báo cáo — sẽ gửi email cho Quản trị khi xong.')` and rely on the existing `useAdForecast` query refetch (add a short `refetchInterval` while a generation may be in-flight, or just invalidate after ~20s). Keep it simple: `message.info(...)` + `qc.invalidateQueries({ queryKey: ['marketing','forecast'] })`.
- Else (cached): keep the existing `message.success('Đã tạo dự báo.')`.
And in the forecast panel (where strategy renders), add a **"Đánh giá nội dung quảng cáo"** block mapping `forecast.payload.creative_review` → for each: name + a `Tag` (verdict 'tốt' → green, else 'orange') + suggestions list. Icons from `@ant-design/icons`.

- [ ] **Step 2: Verify (from `app/`)**
```
npx tsc --noEmit -p tsconfig.json 2>&1 | grep -E "marketing.tsx|MarketingDashboardPage" || echo "NO new type errors"
npx eslint resources/js/lib/marketing.tsx resources/js/pages/MarketingDashboardPage.tsx
```
Fix any error referencing these two files. (Ignore pre-existing admin-page errors.)

- [ ] **Step 3: Commit**
```
git add app/resources/js/lib/marketing.tsx app/resources/js/pages/MarketingDashboardPage.tsx
git commit -m "feat(ads-fe): async forecast (queued message) + creative review panel

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Self-Review (plan author)

**Spec coverage:** §3 fetchAdCreatives (T1) ✓ · §5 creative_review (T2) ✓ · §4 enriched data today+14d+creatives (T3) ✓ · §6 email notification (T4) + job notify Owner/Admin (T5) ✓ · §7 controller dispatch (T6) ✓ · §8 FE (T7) ✓. **Deferred (spec §4/§12 best-effort page_posts engagement):** NOT implemented in this plan — ad creatives already carry the post text (`primaryText` from object_story_spec / effective_object_story_id); per-post like/comment/share enrichment is a v2 follow-up. (Update spec §12 to mark it deferred.)

**Placeholder scan:** none. T1 phpstan note gives the concrete fallback form. T6 tells the implementer to adapt to the existing test helper names (read the file first).

**Type consistency:** `AdCreativeDTO` fields (`adId/adName/effectiveStatus/primaryText/headline/cta/pagePostId`) consistent T1→T3. `creative_review` item shape `{ref,name,verdict,issues,suggestions}` consistent T2 (stub)→T4 (blade)→T7 (FE). Job `GenerateAdForecast(int $adAccountId)` consistent T5→T6. `AdsForecastService::generate(account, force)` signature unchanged (constructor gains `AdsRegistry`).

## Next / follow-ups
- v2: best-effort page-post engagement (like/comment/share) enrichment via `listPages`+`listPagePosts`.
- v2: real (non-stub) AI provider returns `creative_review` — already covered by the prompt schema.
