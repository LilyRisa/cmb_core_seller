# Báo cáo Purchase FB Messenger CTM về Meta Ads — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Khi NV tạo đơn ngay trong khung chat Facebook Messenger với khách đến từ quảng cáo Click-to-Messenger (CTM), gửi sự kiện `Purchase` về Meta qua Conversions API for Business Messaging, gate theo toggle bật/tắt riêng từng Page.

**Architecture:** Contract mới `ConversionReportingConnector` (Interface Segregation, chỉ `FacebookPageConnector` implement) cung cấp `ensureDataset()` + `reportPurchase()`. Toggle + `dataset_id` lưu ở cột `settings` (encrypted jsonb) đã có sẵn trên `messaging_account_meta`. Trigger: `ConversationController::linkOrder()` dispatch job `ReportOrderConversionToMeta` (queued, best-effort) khi `notify_customer=true`. Idempotent qua `orders.meta['fb_conversion_reported_at']` (cột `meta` đã có sẵn).

**Tech Stack:** Laravel 11 (PHP), PHPUnit, Illuminate HTTP client (`Http::fake`), Illuminate Queue (`Queue::fake`), React/TS + TanStack Query + Ant Design (FE phần cuối).

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/` (không phải repo root).
- PSR-4: `CMBcoreSeller\` → `app/app/`.
- Golden rule: core/job/controller không `instanceof FacebookPageConnector` — chỉ `instanceof ConversionReportingConnector && supports('conversion.report')`.
- Mọi sync job phải idempotent (bất biến dự án).
- Tiền VND = số nguyên (bigint), field đúng trên `Order` là `grand_total` (KHÔNG phải `total`).
- User-facing string tiếng Việt; code/định danh tiếng Anh.
- Test PHP: `php artisan test --filter=<Test>` (từ `app/`). Không có JS test runner trong dự án này (xem `test-verify-baseline` — không thêm test JS).
- Trước khi coi bất kỳ task nào "xong", chạy đúng lệnh test của task đó và xác nhận PASS.

---

## Task 1: Exception `MissingScopeException`

**Files:**
- Create: `app/app/Integrations/Messaging/Exceptions/MissingScopeException.php`

**Interfaces:**
- Produces: `MissingScopeException::forPageEvents(string $reason = ''): self` — dùng ở Task 3 (connector) để phân biệt lỗi thiếu quyền `page_events` với lỗi tạm thời khác.

- [ ] **Step 1: Viết class**

```php
<?php

namespace CMBcoreSeller\Integrations\Messaging\Exceptions;

use RuntimeException;

/**
 * Ném khi Graph API từ chối vì thiếu quyền `page_events` (Advanced Access, cần Meta App
 * Review) — dùng để caller phân biệt với lỗi tạm thời (retry vô nghĩa khi thiếu quyền).
 */
class MissingScopeException extends RuntimeException
{
    public static function forPageEvents(string $reason = ''): self
    {
        $suffix = $reason !== '' ? " ({$reason})" : '';

        return new self('Thiếu quyền page_events — cần "Cấp quyền lại" (kết nối lại) để bật báo cáo chuyển đổi.'.$suffix);
    }
}
```

- [ ] **Step 2: Xác nhận không có lỗi cú pháp**

Run: `cd app && php -l app/Integrations/Messaging/Exceptions/MissingScopeException.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add app/app/Integrations/Messaging/Exceptions/MissingScopeException.php
git commit -m "feat(messaging): add MissingScopeException for FB page_events gating"
```

---

## Task 2: Contract `ConversionReportingConnector`

**Files:**
- Create: `app/app/Integrations/Messaging/Contracts/ConversionReportingConnector.php`

**Interfaces:**
- Consumes: `MessagingAuthContext` (existing, `app/app/Integrations/Messaging/DTO/MessagingAuthContext.php`).
- Produces: `ConversionReportingConnector::ensureDataset(MessagingAuthContext $auth): string` và
  `ConversionReportingConnector::reportPurchase(MessagingAuthContext $auth, string $datasetId, string $psid, int $valueVnd, \DateTimeInterface $eventTime, string $eventId): void` — Task 3 implement, Task 4/5 gọi qua `instanceof`.

- [ ] **Step 1: Viết interface**

```php
<?php

namespace CMBcoreSeller\Integrations\Messaging\Contracts;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;

/**
 * Năng lực RIÊNG: báo cáo sự kiện chuyển đổi (Purchase) về Meta qua Conversions API for
 * Business Messaging — chỉ Facebook Page (Messenger) hỗ trợ hiện tại; các sàn khác
 * (Zalo OA/Lazada IM) KHÔNG có khái niệm này và KHÔNG bị buộc implement.
 *
 * GOLDEN RULE (extensibility-rules.md): core KHÔNG `instanceof FacebookPageConnector` —
 * chỉ kiểm `instanceof ConversionReportingConnector` + `supports('conversion.report')`.
 */
interface ConversionReportingConnector
{
    /**
     * Tạo (nếu chưa có) dataset gắn theo Page/tài khoản, trả dataset_id. Lỗi thiếu quyền
     * `page_events` ⇒ ném {@see \CMBcoreSeller\Integrations\Messaging\Exceptions\MissingScopeException}.
     */
    public function ensureDataset(MessagingAuthContext $auth): string;

    /**
     * Gửi 1 sự kiện Purchase. `$eventId` dùng để dedupe phía log/Meta (vd "order-{id}").
     * Lỗi thiếu quyền ⇒ ném MissingScopeException; lỗi khác ⇒ RuntimeException.
     */
    public function reportPurchase(
        MessagingAuthContext $auth,
        string $datasetId,
        string $psid,
        int $valueVnd,
        \DateTimeInterface $eventTime,
        string $eventId,
    ): void;
}
```

- [ ] **Step 2: Xác nhận không có lỗi cú pháp**

Run: `cd app && php -l app/Integrations/Messaging/Contracts/ConversionReportingConnector.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add app/app/Integrations/Messaging/Contracts/ConversionReportingConnector.php
git commit -m "feat(messaging): add ConversionReportingConnector contract"
```

---

## Task 3: `FacebookPageConnector` implement contract + capability + OAuth scope

**Files:**
- Modify: `app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php`
- Modify: `app/tests/Unit/Messaging/FacebookPageConnectorTest.php`

**Interfaces:**
- Consumes: `ConversionReportingConnector` (Task 2), `MissingScopeException` (Task 1).
- Produces: `FacebookPageConnector::ensureDataset()`, `FacebookPageConnector::reportPurchase()`, capability key `'conversion.report' => true`. Task 4/5 phụ thuộc các tên này chính xác.

- [ ] **Step 1: Viết test thất bại trước (unit, Http::fake) — thêm vào cuối `FacebookPageConnectorTest.php`**

```php
    public function test_authorization_url_requests_page_events_scope(): void
    {
        // page_events (Advanced Access) — bắt buộc để dùng Conversions API for Business Messaging.
        $url = $this->connector()->buildAuthorizationUrl('state_1');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $scope = (string) ($q['scope'] ?? '');

        $this->assertStringContainsString('page_events', $scope);
    }

    public function test_ensure_dataset_creates_and_returns_id(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'DATASET_1'], 200)]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );

        $datasetId = $this->connector()->ensureDataset($auth);

        $this->assertSame('DATASET_1', $datasetId);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/PAGE_123/dataset'));
    }

    public function test_ensure_dataset_missing_scope_throws(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['type' => 'OAuthException', 'code' => 200, 'message' => 'Missing permission page_events'],
        ], 400)]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );

        $this->expectException(\CMBcoreSeller\Integrations\Messaging\Exceptions\MissingScopeException::class);
        $this->connector()->ensureDataset($auth);
    }

    public function test_report_purchase_posts_correct_shape(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );
        $eventTime = new \DateTimeImmutable('@1720000000');

        $this->connector()->reportPurchase($auth, 'DATASET_1', 'PSID_999', 150000, $eventTime, 'order-42');

        Http::assertSent(function ($request) {
            $data = $request->data();
            $event = $data['data'][0] ?? [];

            return str_contains($request->url(), '/DATASET_1/events')
                && ($event['event_name'] ?? null) === 'Purchase'
                && ($event['action_source'] ?? null) === 'business_messaging'
                && ($event['messaging_channel'] ?? null) === 'messenger'
                && ($event['user_data']['page_id'] ?? null) === 'PAGE_123'
                && ($event['user_data']['page_scoped_user_id'] ?? null) === 'PSID_999'
                && ($event['custom_data']['currency'] ?? null) === 'VND'
                && ($event['custom_data']['value'] ?? null) === 150000
                && ($event['event_id'] ?? null) === 'order-42'
                && ($event['event_time'] ?? null) === 1720000000;
        });
    }

    public function test_report_purchase_missing_scope_throws(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['type' => 'OAuthException', 'code' => 200, 'message' => 'Missing permission page_events'],
        ], 400)]);

        $auth = new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );

        $this->expectException(\CMBcoreSeller\Integrations\Messaging\Exceptions\MissingScopeException::class);
        $this->connector()->reportPurchase($auth, 'DATASET_1', 'PSID_999', 150000, new \DateTimeImmutable, 'order-1');
    }
```

- [ ] **Step 2: Chạy test — phải FAIL (method chưa tồn tại)**

Run: `cd app && php artisan test --filter=FacebookPageConnectorTest`
Expected: FAIL — `Call to undefined method ...ensureDataset()` / `page_events` không có trong scope.

- [ ] **Step 3: Cập nhật `FacebookPageConnector.php`**

3a. Thêm import + `implements`:

```php
use CMBcoreSeller\Integrations\Messaging\Contracts\ConversionReportingConnector;
use CMBcoreSeller\Integrations\Messaging\Exceptions\MissingScopeException;
```

```php
class FacebookPageConnector implements CommentEngagementConnector, ConversionReportingConnector, InteractiveMessagingConnector, ListsPostsConnector, MessagingConnector, UtilityTemplateConnector
```

3b. Thêm capability (trong `capabilities()`, sau `'comment.webhook' => true,`):

```php
            'comment.webhook' => true,     // nhận comment qua webhook feed
            // Conversions API for Business Messaging — báo cáo Purchase về Meta Ads cho
            // hội thoại đến từ quảng cáo Click-to-Messenger (cần quyền page_events).
            'conversion.report' => true,
```

3c. Cập nhật scope OAuth (dòng có biến `$scope` trong `buildAuthorizationUrl`):

```php
        // `pages_utility_messaging`: gửi utility message qua template đã duyệt (thay
        // message tag đã bị Meta khai tử) — cần App Review để dùng ngoài test user.
        // `page_events`: Conversions API for Business Messaging (báo cáo Purchase về Meta
        // Ads cho hội thoại từ CTM ads) — Advanced Access, cần Meta App Review riêng.
        $scope = 'pages_messaging,pages_utility_messaging,pages_manage_metadata,pages_read_engagement,pages_show_list,pages_read_user_content,pages_manage_engagement,business_management,page_events';
```

3d. Thêm 2 method mới + 1 helper lỗi — chèn ngay TRƯỚC `// --- Comment moderation` (sau khối Utility Messages):

```php
    // --- Conversions API for Business Messaging ---------------------------

    /**
     * Tạo dataset gắn theo Page (Dataset API): `POST /{page_id}/dataset`. Idempotent phía
     * caller (chỉ gọi khi chưa có dataset_id lưu sẵn — xem MessagingChannelController::fbConversions
     * + ReportOrderConversionToMeta). Lỗi thiếu quyền `page_events` ⇒ MissingScopeException.
     */
    public function ensureDataset(MessagingAuthContext $auth): string
    {
        $res = Http::post($this->graphUrl($auth->externalShopId.'/dataset'), [
            'access_token' => $auth->accessToken,
        ]);

        if ($res->successful() && ($id = $res->json('id')) !== null && (string) $id !== '') {
            return (string) $id;
        }

        $this->throwConversionsApiError($res, 'ensureDataset');
    }

    /**
     * Gửi 1 event Purchase: `POST /{dataset_id}/events`. Theo tài liệu Meta (Conversions
     * API for Business Messaging, kênh Messenger): định danh bằng page_id + PSID (KHÔNG
     * cần ctwa_clid — chỉ WhatsApp cần). `$eventId` để log/Meta dedupe (vd "order-{id}").
     */
    public function reportPurchase(
        MessagingAuthContext $auth,
        string $datasetId,
        string $psid,
        int $valueVnd,
        \DateTimeInterface $eventTime,
        string $eventId,
    ): void {
        $res = Http::post($this->graphUrl($datasetId.'/events'), [
            'data' => [[
                'event_name' => 'Purchase',
                'event_time' => $eventTime->getTimestamp(),
                'action_source' => 'business_messaging',
                'messaging_channel' => 'messenger',
                'user_data' => [
                    'page_id' => $auth->externalShopId,
                    'page_scoped_user_id' => $psid,
                ],
                'custom_data' => [
                    'currency' => 'VND',
                    'value' => $valueVnd,
                ],
                'event_id' => $eventId,
            ]],
            'access_token' => $auth->accessToken,
        ]);

        if ($res->successful()) {
            return;
        }

        $this->throwConversionsApiError($res, 'reportPurchase');
    }

    /**
     * Ném lỗi Conversions API. Thiếu quyền `page_events` (OAuthException/code 200, hoặc
     * message nhắc "permission") ⇒ MissingScopeException (caller KHÔNG retry); lỗi khác
     * ⇒ RuntimeException thường (caller có thể retry theo backoff).
     */
    private function throwConversionsApiError(Response $res, string $op): never
    {
        $error = (array) $res->json('error');
        $type = (string) ($error['type'] ?? '');
        $code = (int) ($error['code'] ?? 0);
        $message = (string) ($error['message'] ?? '');

        if ($type === 'OAuthException' || $code === 200 || str_contains(strtolower($message), 'permission')) {
            throw MissingScopeException::forPageEvents($message);
        }

        throw new \RuntimeException("Facebook {$op} failed: ".$res->body());
    }
```

- [ ] **Step 4: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=FacebookPageConnectorTest`
Expected: PASS (toàn bộ, gồm test cũ lẫn 5 test mới).

- [ ] **Step 5: Quality gate cho file vừa sửa**

Run: `cd app && vendor/bin/pint --test app/Integrations/Messaging/Facebook/FacebookPageConnector.php && vendor/bin/phpstan analyse app/Integrations/Messaging/Facebook/FacebookPageConnector.php`
Expected: cả hai PASS (0 lỗi).

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/Messaging/Facebook/FacebookPageConnector.php app/tests/Unit/Messaging/FacebookPageConnectorTest.php
git commit -m "feat(messaging): FacebookPageConnector reports Purchase via Conversions API for Business Messaging"
```

---

## Task 4: Toggle bật/tắt theo Page (`messaging_account_meta.settings['fb_conversions']`)

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php`
- Modify: `app/app/Modules/Messaging/Http/routes.php`
- Create: `app/tests/Feature/Messaging/FbConversionsSettingsTest.php`

**Interfaces:**
- Consumes: `ConversionReportingConnector::ensureDataset()` (Task 3), `MissingScopeException` (Task 1), `MessagingRegistry` (existing).
- Produces: response field `fb_conversions: {enabled: bool, dataset_id: ?string, needs_reauth: bool}` trong `GET /messaging/channels` VÀ `PATCH /messaging/channels/{id}/fb-conversions` — Task 5 (job) đọc `settings['fb_conversions']` với đúng 4 khoá (`enabled`, `dataset_id`, `last_error`, `last_error_at`); Task 6 (FE) đọc đúng field JSON này.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Toggle "Gửi dữ liệu chuyển đổi (mua hàng) về Facebook Ads" theo từng Page
 * (design 2026-07-14-fb-messenger-conversion-reporting).
 */
class FbConversionsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'FbShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'page_1', 'shop_name' => 'My Page',
            'status' => 'active', 'messaging_enabled' => true, 'access_token' => 'PAGE_TOKEN',
        ]);

        app(MessagingRegistry::class)->register('facebook_page', FacebookPageConnector::class);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_enabling_creates_dataset_and_persists_settings(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'DATASET_9'], 200)]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/channels/{$this->account->id}/fb-conversions", ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.fb_conversions.enabled', true)
            ->assertJsonPath('data.fb_conversions.dataset_id', 'DATASET_9');

        $meta = MessagingAccountMeta::query()->find($this->account->id);
        $this->assertTrue($meta->settings['fb_conversions']['enabled']);
        $this->assertSame('DATASET_9', $meta->settings['fb_conversions']['dataset_id']);
    }

    public function test_enabling_without_page_events_scope_returns_missing_scope_error(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['type' => 'OAuthException', 'code' => 200, 'message' => 'Missing permission page_events'],
        ], 400)]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/channels/{$this->account->id}/fb-conversions", ['enabled' => true])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'MISSING_SCOPE');

        $meta = MessagingAccountMeta::query()->find($this->account->id);
        $this->assertNull($meta);   // chưa lưu gì khi tạo dataset thất bại
    }

    public function test_disabling_keeps_dataset_id(): void
    {
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $this->account->id, 'tenant_id' => $this->tenant->getKey(),
            'settings' => ['fb_conversions' => ['enabled' => true, 'dataset_id' => 'DATASET_5']],
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/channels/{$this->account->id}/fb-conversions", ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.fb_conversions.enabled', false)
            ->assertJsonPath('data.fb_conversions.dataset_id', 'DATASET_5');

        $meta = MessagingAccountMeta::query()->find($this->account->id);
        $this->assertFalse($meta->settings['fb_conversions']['enabled']);
        $this->assertSame('DATASET_5', $meta->settings['fb_conversions']['dataset_id']);
    }

    public function test_channels_index_exposes_fb_conversions(): void
    {
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $this->account->id, 'tenant_id' => $this->tenant->getKey(),
            'settings' => ['fb_conversions' => ['enabled' => true, 'dataset_id' => 'DATASET_5', 'last_error' => 'missing_scope']],
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/channels?provider=facebook_page')
            ->assertOk();

        $page = collect($res->json('data'))->firstWhere('id', $this->account->id);
        $this->assertTrue($page['fb_conversions']['enabled']);
        $this->assertSame('DATASET_5', $page['fb_conversions']['dataset_id']);
        $this->assertTrue($page['fb_conversions']['needs_reauth']);
    }
}
```

- [ ] **Step 2: Chạy test — phải FAIL (route/method chưa tồn tại)**

Run: `cd app && php artisan test --filter=FbConversionsSettingsTest`
Expected: FAIL — 404 route not found / field `fb_conversions` không có trong response.

- [ ] **Step 3: Thêm route** — trong `app/app/Modules/Messaging/Http/routes.php`, ngay sau dòng `bulk-business_info` (dòng ~175):

```php
        // Báo cáo Purchase (Conversions API for Business Messaging) về Meta Ads cho hội
        // thoại đến từ quảng cáo Click-to-Messenger (design 2026-07-14).
        Route::patch('channels/{id}/fb-conversions', [MessagingChannelController::class, 'fbConversions'])
            ->whereNumber('id')->name('messaging.channels.fb_conversions');   // messaging.connect
```

- [ ] **Step 4: Thêm import + method trong `MessagingChannelController.php`**

4a. Thêm import ở đầu file:

```php
use CMBcoreSeller\Integrations\Messaging\Contracts\ConversionReportingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Exceptions\MissingScopeException;
```

(Lưu ý: `MessagingAuthContext` đã được import sẵn ở dòng 7 — không import trùng.)

4b. Thêm field vào `index()` — trong khối `return [...]` của `map()`, ngay sau dòng `'zalo_send_blocked_reason' => ...`:

```php
                    'zalo_send_blocked_reason' => $a->meta['zalo_send_blocked_reason'] ?? null,
                    // SPEC 2026-07-14 — báo cáo Purchase (Conversions API for Business Messaging)
                    // về Meta Ads cho hội thoại từ quảng cáo Click-to-Messenger.
                    'fb_conversions' => [
                        'enabled' => (bool) ($meta?->settings['fb_conversions']['enabled'] ?? false),
                        'dataset_id' => $meta?->settings['fb_conversions']['dataset_id'] ?? null,
                        'needs_reauth' => ($meta?->settings['fb_conversions']['last_error'] ?? null) === 'missing_scope',
                    ],
```

4c. Thêm method mới ngay sau `businessInfo()` (trước `bulkBusinessInfo()`):

```php
    /**
     * PATCH /channels/{id}/fb-conversions — bật/tắt báo cáo Purchase (Conversions API for
     * Business Messaging) về Meta Ads cho hội thoại đến từ quảng cáo Click-to-Messenger.
     * Bật lần đầu (chưa có dataset_id) ⇒ gọi ensureDataset() NGAY trong request để phát
     * hiện thiếu quyền `page_events` sớm, không đợi tới đơn đầu tiên (design 2026-07-14).
     */
    public function fbConversions(int $id, Request $request, MessagingRegistry $registry): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $account = ChannelAccount::query()->whereIn('provider', ChannelAccount::MESSAGING_ONLY_PROVIDERS)->findOrFail($id);

        $existing = MessagingAccountMeta::query()->find($account->id);
        $settings = (array) ($existing?->settings ?? []);
        $fb = (array) ($settings['fb_conversions'] ?? []);

        if ($data['enabled'] && empty($fb['dataset_id'])) {
            $connector = $registry->has($account->provider) ? $registry->for($account->provider) : null;
            if (! $connector instanceof ConversionReportingConnector || ! $connector->supports('conversion.report')) {
                return response()->json(['error' => [
                    'code' => 'UNSUPPORTED',
                    'message' => 'Kênh này không hỗ trợ báo cáo chuyển đổi.',
                ]], 422);
            }

            $auth = new MessagingAuthContext(
                channelAccountId: $account->id,
                provider: $account->provider,
                externalShopId: $account->external_shop_id,
                accessToken: (string) ($account->access_token ?? ''),
            );

            try {
                $fb['dataset_id'] = $connector->ensureDataset($auth);
                unset($fb['last_error'], $fb['last_error_at']);
            } catch (MissingScopeException) {
                return response()->json(['error' => [
                    'code' => 'MISSING_SCOPE',
                    'message' => 'Token trang thiếu quyền page_events. Bấm "Cấp quyền lại" để kết nối lại rồi thử bật lại.',
                ]], 422);
            }
        }

        $fb['enabled'] = (bool) $data['enabled'];
        $settings['fb_conversions'] = $fb;

        MessagingAccountMeta::query()->updateOrCreate(
            ['channel_account_id' => $account->id],
            ['tenant_id' => $account->tenant_id, 'settings' => $settings],
        );

        AuditLog::record('messaging.'.$account->provider.'.fb_conversions', null, [
            'external_shop_id' => $account->external_shop_id,
            'enabled' => (bool) $data['enabled'],
        ]);

        return response()->json(['data' => ['ok' => true, 'fb_conversions' => [
            'enabled' => (bool) $fb['enabled'],
            'dataset_id' => $fb['dataset_id'] ?? null,
        ]]]);
    }
```

- [ ] **Step 5: Chạy lại test — phải PASS**

Run: `cd app && php artisan test --filter=FbConversionsSettingsTest`
Expected: PASS (4 test).

- [ ] **Step 6: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Messaging/Http/Controllers/MessagingChannelController.php app/Modules/Messaging/Http/routes.php && vendor/bin/phpstan analyse`
Expected: PASS. (`phpstan analyse` chạy full — baseline hiện có đã biết trong `phpstan-baseline.neon`; không thêm lỗi mới liên quan file vừa sửa.)

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Messaging/Http/Controllers/MessagingChannelController.php app/app/Modules/Messaging/Http/routes.php app/tests/Feature/Messaging/FbConversionsSettingsTest.php
git commit -m "feat(messaging): per-page toggle for FB Purchase conversion reporting"
```

---

## Task 5: Job `ReportOrderConversionToMeta` + trigger ở `linkOrder()`

**Files:**
- Create: `app/app/Modules/Messaging/Jobs/ReportOrderConversionToMeta.php`
- Modify: `app/app/Modules/Messaging/Http/Controllers/ConversationController.php:317-349` (method `linkOrder`)
- Create: `app/tests/Feature/Messaging/FbConversionReportDispatchTest.php`
- Create: `app/tests/Feature/Messaging/ReportOrderConversionToMetaJobTest.php`

**Interfaces:**
- Consumes: `ConversionReportingConnector` (Task 3), `MissingScopeException` (Task 1), `MessagingAccountMeta.settings['fb_conversions']` shape từ Task 4 (`enabled`, `dataset_id`, `last_error`, `last_error_at`).
- Produces: `ReportOrderConversionToMeta::dispatch(int $conversationId, int $orderId)`; side-effect `orders.meta['fb_conversion_reported_at']` (ISO-8601 string).

### 5a — Job + dispatch trong `linkOrder()`

- [ ] **Step 1: Viết test dispatch thất bại trước** (`FbConversionReportDispatchTest.php`)

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\ReportOrderConversionToMeta;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * `linkOrder()` phải dispatch ReportOrderConversionToMeta CHỈ khi: notify_customer=true
 * (đơn vừa tạo trong chat) — dù có/không ad_referral (job tự guard phần đó, xem
 * ReportOrderConversionToMetaJobTest). Test này chỉ xác nhận ĐIỂM DISPATCH đúng điều kiện.
 */
class FbConversionReportDispatchTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'FbShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'page_1', 'shop_name' => 'My Page',
            'status' => 'active', 'messaging_enabled' => true,
        ]);
        app(MessagingRegistry::class)->register('facebook_page', FacebookPageConnector::class);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function seedConversation(array $extra = []): Conversation
    {
        return Conversation::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'PSID_123',
            'buyer_external_id' => 'PSID_123',
            'status' => Conversation::STATUS_OPEN,
            'last_message_at' => now(),
            'last_inbound_at' => now(),
        ], $extra));
    }

    private function seedOrder(): Order
    {
        return Order::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'source' => 'manual', 'status' => 'processing',
            'order_number' => 'M-CTM-1', 'grand_total' => 150000, 'is_cod' => true,
        ]);
    }

    public function test_dispatches_when_notify_customer_true(): void
    {
        Queue::fake();
        $conv = $this->seedConversation(['meta' => ['ad_referral' => ['source' => 'ADS', 'ad_id' => 'AD_1']]]);
        $order = $this->seedOrder();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", [
                'order_id' => $order->id, 'notify_customer' => true,
            ])->assertOk();

        Queue::assertPushed(ReportOrderConversionToMeta::class, fn ($job) => $job->conversationId === $conv->id && $job->orderId === $order->id);
    }

    public function test_does_not_dispatch_without_notify_customer_flag(): void
    {
        Queue::fake();
        $conv = $this->seedConversation(['meta' => ['ad_referral' => ['source' => 'ADS', 'ad_id' => 'AD_1']]]);
        $order = $this->seedOrder();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$conv->id}/link-order", ['order_id' => $order->id])
            ->assertOk();

        Queue::assertNotPushed(ReportOrderConversionToMeta::class);
    }
}
```

- [ ] **Step 2: Chạy test — phải FAIL**

Run: `cd app && php artisan test --filter=FbConversionReportDispatchTest`
Expected: FAIL — `Class ReportOrderConversionToMeta not found`.

- [ ] **Step 3: Viết Job** (`app/app/Modules/Messaging/Jobs/ReportOrderConversionToMeta.php`)

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Messaging\Contracts\ConversionReportingConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Exceptions\MissingScopeException;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Báo cáo sự kiện Purchase (Conversions API for Business Messaging) về Meta Ads khi 1 đơn
 * vừa được tạo TRONG khung chat Facebook Messenger với khách đến từ quảng cáo
 * Click-to-Messenger (design 2026-07-14-fb-messenger-conversion-reporting).
 *
 * Best-effort: dispatch cạnh `OrderConfirmationNotifier` trong `linkOrder()`, KHÔNG bao
 * giờ được làm hỏng luồng tạo/link đơn. Idempotent theo `order.meta['fb_conversion_reported_at']`.
 */
class ReportOrderConversionToMeta implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $conversationId, public int $orderId)
    {
        $this->onQueue('messaging-outbound');
    }

    public function uniqueId(): string
    {
        return "fb-conversion-report:{$this->orderId}";
    }

    public function uniqueFor(): int
    {
        return 600;
    }

    public function backoff(): array
    {
        return [30, 300, 900];
    }

    public function handle(MessagingRegistry $registry): void
    {
        $conversation = Conversation::withoutGlobalScope(TenantScope::class)->find($this->conversationId);
        if (! $conversation || $conversation->provider !== 'facebook_page') {
            return;
        }

        // Chỉ hội thoại đến từ quảng cáo CTM (first-touch stampAdReferral) — tránh trộn
        // với tin nhắn tự nhiên (yêu cầu gốc của tính năng).
        $adReferral = (array) ($conversation->meta['ad_referral'] ?? []);
        if ($adReferral === []) {
            return;
        }

        $order = Order::withoutGlobalScope(TenantScope::class)->find($this->orderId);
        if (! $order || ! empty($order->meta['fb_conversion_reported_at'] ?? null)) {
            return; // đơn không tồn tại hoặc đã báo cáo rồi (idempotent)
        }

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($conversation->channel_account_id);
        $fb = (array) ($meta?->settings['fb_conversions'] ?? []);
        if (! $meta || ! ($fb['enabled'] ?? false)) {
            return; // kênh chưa bật báo cáo chuyển đổi
        }

        $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->find($conversation->channel_account_id);
        if (! $account) {
            return;
        }

        $connector = $registry->has($conversation->provider) ? $registry->for($conversation->provider) : null;
        if (! $connector instanceof ConversionReportingConnector || ! $connector->supports('conversion.report')) {
            return; // phòng hờ — không nên xảy ra khi provider=facebook_page
        }

        $auth = new MessagingAuthContext(
            channelAccountId: $account->id,
            provider: $account->provider,
            externalShopId: $account->external_shop_id,
            accessToken: (string) ($account->access_token ?? ''),
        );

        try {
            $datasetId = $fb['dataset_id'] ?? null;
            if (! $datasetId) {
                $datasetId = $connector->ensureDataset($auth);
                $fb['dataset_id'] = $datasetId;
                $settings = (array) ($meta->settings ?? []);
                $settings['fb_conversions'] = $fb;
                $meta->forceFill(['settings' => $settings])->save();
            }

            $connector->reportPurchase(
                $auth,
                $datasetId,
                (string) $conversation->buyer_external_id,
                (int) $order->grand_total,
                $order->created_at,
                "order-{$order->id}",
            );

            $order->forceFill([
                'meta' => [...((array) ($order->meta ?? [])), 'fb_conversion_reported_at' => now()->toIso8601String()],
            ])->save();
        } catch (MissingScopeException) {
            $fb['last_error'] = 'missing_scope';
            $fb['last_error_at'] = now()->toIso8601String();
            $settings = (array) ($meta->settings ?? []);
            $settings['fb_conversions'] = $fb;
            $meta->forceFill(['settings' => $settings])->save();
            // Không retry — thiếu quyền không tự khỏi.
        } catch (Throwable $e) {
            Log::warning('messaging.fb_conversion_report.failed', [
                'order_id' => $order->id, 'attempt' => $this->attempts(), 'error' => $e->getMessage(),
            ]);
            if ($this->attempts() < $this->tries) {
                throw $e; // để queue retry theo backoff
            }
        }
    }
}
```

- [ ] **Step 4: Wire dispatch trong `ConversationController::linkOrder()`**

4a. Thêm import (đầu file, sau dòng `use CMBcoreSeller\Modules\Messaging\Models\Message;`):

```php
use CMBcoreSeller\Modules\Messaging\Jobs\ReportOrderConversionToMeta;
```

4b. Sửa khối `notify_customer` (dòng 343-346 hiện tại):

```php
        // SPEC 0031 — best-effort, không bao giờ làm hỏng link nếu gửi lỗi (notifier tự nuốt lỗi).
        if ($request->boolean('notify_customer')) {
            app(OrderConfirmationNotifier::class)->notify($conv->fresh(), $order);
            // Design 2026-07-14 — best-effort, hội thoại/kênh không đủ điều kiện thì job tự bỏ qua.
            ReportOrderConversionToMeta::dispatch($conv->id, $order->id);
        }
```

- [ ] **Step 5: Chạy lại test dispatch — phải PASS**

Run: `cd app && php artisan test --filter=FbConversionReportDispatchTest`
Expected: PASS (2 test).

### 5b — Test hành vi Job (idempotent, toggle tắt, thiếu quyền)

- [ ] **Step 6: Viết `ReportOrderConversionToMetaJobTest.php`**

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\ReportOrderConversionToMeta;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportOrderConversionToMetaJobTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'FbShop']);
        app(MessagingRegistry::class)->register('facebook_page', FacebookPageConnector::class);

        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id, 'provider' => 'facebook_page',
            'external_shop_id' => 'page_1', 'shop_name' => 'My Page',
            'status' => 'active', 'access_token' => 'PAGE_TOKEN',
        ]);
    }

    private function makeMeta(array $fbConversions): MessagingAccountMeta
    {
        return MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->create([
            'channel_account_id' => $this->account->id, 'tenant_id' => $this->tenant->id,
            'settings' => ['fb_conversions' => $fbConversions],
        ]);
    }

    private function makeConversation(): Conversation
    {
        return Conversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id, 'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page', 'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'PSID_1', 'buyer_external_id' => 'PSID_1',
            'meta' => ['ad_referral' => ['source' => 'ADS', 'ad_id' => 'AD_1']],
        ]);
    }

    private function makeOrder(array $extra = []): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->id, 'source' => 'manual', 'status' => 'processing',
            'order_number' => 'M-JOB-'.uniqid(), 'grand_total' => 150000, 'is_cod' => true,
        ], $extra));
    }

    public function test_reports_purchase_and_marks_order_idempotent(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        $this->makeMeta(['enabled' => true, 'dataset_id' => 'DATASET_1']);
        $conv = $this->makeConversation();
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        $order->refresh();
        $this->assertNotEmpty($order->meta['fb_conversion_reported_at'] ?? null);
        Http::assertSentCount(1);

        // Chạy lại lần 2 — KHÔNG gọi Graph nữa (idempotent).
        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);
        Http::assertSentCount(1);
    }

    public function test_skips_when_toggle_disabled(): void
    {
        Http::fake();
        $this->makeMeta(['enabled' => false, 'dataset_id' => 'DATASET_1']);
        $conv = $this->makeConversation();
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        Http::assertNothingSent();
        $this->assertEmpty($order->fresh()->meta['fb_conversion_reported_at'] ?? null);
    }

    public function test_skips_when_no_ad_referral(): void
    {
        Http::fake();
        $this->makeMeta(['enabled' => true, 'dataset_id' => 'DATASET_1']);
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id, 'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page', 'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'PSID_2', 'buyer_external_id' => 'PSID_2',
        ]);
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        Http::assertNothingSent();
    }

    public function test_missing_scope_sets_error_flag_and_does_not_rethrow(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['type' => 'OAuthException', 'code' => 200, 'message' => 'Missing permission page_events'],
        ], 400)]);
        $this->makeMeta(['enabled' => true, 'dataset_id' => 'DATASET_1']);
        $conv = $this->makeConversation();
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($this->account->id);
        $this->assertSame('missing_scope', $meta->settings['fb_conversions']['last_error'] ?? null);
        $this->assertNotEmpty($meta->settings['fb_conversions']['last_error_at'] ?? null);
        $this->assertEmpty($order->fresh()->meta['fb_conversion_reported_at'] ?? null);
    }

    public function test_ensures_dataset_when_missing_then_persists_it(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'DATASET_NEW'], 200)]);
        $this->makeMeta(['enabled' => true]);   // chưa có dataset_id
        $conv = $this->makeConversation();
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($this->account->id);
        $this->assertSame('DATASET_NEW', $meta->settings['fb_conversions']['dataset_id'] ?? null);
        $this->assertNotEmpty($order->fresh()->meta['fb_conversion_reported_at'] ?? null);
    }
}
```

- [ ] **Step 7: Chạy toàn bộ test của task 5 — phải PASS**

Run: `cd app && php artisan test --filter=FbConversionReportDispatchTest && php artisan test --filter=ReportOrderConversionToMetaJobTest`
Expected: PASS (2 + 5 test).

- [ ] **Step 8: Quality gate**

Run: `cd app && vendor/bin/pint --test app/Modules/Messaging/Jobs/ReportOrderConversionToMeta.php app/Modules/Messaging/Http/Controllers/ConversationController.php && vendor/bin/phpstan analyse`
Expected: PASS.

- [ ] **Step 9: Chạy TOÀN BỘ test suite Messaging (đảm bảo không phá luồng `linkOrder` cũ)**

Run: `cd app && php artisan test --filter=OrderConfirmationOnLinkTest`
Expected: PASS (không đổi hành vi cũ — job mới chỉ THÊM dispatch, không thay đổi response/side-effect của notifier).

- [ ] **Step 10: Commit**

```bash
git add app/app/Modules/Messaging/Jobs/ReportOrderConversionToMeta.php app/app/Modules/Messaging/Http/Controllers/ConversationController.php app/tests/Feature/Messaging/FbConversionReportDispatchTest.php app/tests/Feature/Messaging/ReportOrderConversionToMetaJobTest.php
git commit -m "feat(messaging): report Purchase conversion to Meta when order created from CTM chat"
```

---

## Task 6: Frontend — toggle trong màn cài đặt kênh Facebook Page

**Files:**
- Modify: `app/resources/js/lib/messagingConfig.tsx`
- Modify: `app/resources/js/pages/MessagingChannelsPage.tsx`

**Interfaces:**
- Consumes: `PATCH /messaging/channels/{id}/fb-conversions` (Task 4), response field `fb_conversions` từ `GET /messaging/channels` (Task 4).
- Produces: none (UI leaf).

**Không có JS test runner trong dự án này** (xem `test-verify-baseline` memory) — bước "test" của task này là `npm run typecheck` + `npm run lint` + `npm run build`, và kiểm tra thủ công qua trình duyệt (bước 5).

- [ ] **Step 1: Thêm field vào interface `MessagingChannel`** — trong `messagingConfig.tsx`, sau dòng `zalo_send_blocked_reason?: string | null;` (dòng 354):

```ts
    /** Design 2026-07-14 — báo cáo Purchase (Conversions API for Business Messaging) về Meta Ads. */
    fb_conversions: {
        enabled: boolean;
        dataset_id: string | null;
        needs_reauth: boolean;
    };
```

- [ ] **Step 2: Thêm hook mutation** — trong `messagingConfig.tsx`, ngay sau `useSetChannelAiMode()` (sau dòng 392):

```ts
/** Design 2026-07-14 — bật/tắt báo cáo Purchase (Conversions API for Business Messaging). */
export function useSetChannelFbConversions() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: { id: number; enabled: boolean }) => {
            await api!.patch(`/messaging/channels/${input.id}/fb-conversions`, { enabled: input.enabled });
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', 'channels'] }),
    });
}
```

- [ ] **Step 3: Import hook + `errorCode` trong `MessagingChannelsPage.tsx`**

3a. Sửa dòng import từ `@/lib/messagingConfig` (dòng 15) — thêm `useSetChannelFbConversions`:

```ts
import { useBulkDisconnectChannels, useBulkSyncChannels, useConnectFacebook, useConnectLazadaIm, useDisconnectFacebookPage, useMessagingChannels, useSetChannelAiMode, useSetChannelFbConversions, useStartZaloConnect, useSyncChannel } from '@/lib/messagingConfig';
```

3b. Sửa import từ `@/lib/api` để thêm `errorCode` (tìm dòng `import { errorMessage } from '@/lib/api';` hoặc tương đương và thêm `errorCode`):

```ts
import { errorCode, errorMessage } from '@/lib/api';
```

3c. Thêm khởi tạo hook — ngay sau dòng `const setAiMode = useSetChannelAiMode();` (dòng 56):

```ts
    const setFbConversions = useSetChannelFbConversions();
```

- [ ] **Step 4: Thêm UI Switch** — trong `MessagingChannelsPage.tsx`, ngay sau khối `{canAi && (...)}` chứa Switch AI tự trả lời (sau dòng 343, trước `{canConnect && (`):

```tsx
                                    {canConnect && (
                                        <Tooltip title="Gửi dữ liệu chuyển đổi (mua hàng) về Facebook Ads cho hội thoại đến từ quảng cáo Click-to-Messenger">
                                            <Space size={6} align="center">
                                                <Text type="secondary" style={{ fontSize: 12 }}>Báo cáo Purchase</Text>
                                                <Switch size="small" checked={p.fb_conversions.enabled} loading={setFbConversions.isPending}
                                                    onChange={(v) => setFbConversions.mutate({ id: p.id, enabled: v }, {
                                                        onError: (e) => {
                                                            if (errorCode(e) === 'MISSING_SCOPE') {
                                                                message.error('Token trang thiếu quyền page_events. Bấm "Kết nối lại" rồi thử bật lại.');
                                                            } else {
                                                                message.error(errorMessage(e));
                                                            }
                                                        },
                                                    })} />
                                                {p.fb_conversions.needs_reauth && (
                                                    <Tag color="orange">Cần cấp quyền lại</Tag>
                                                )}
                                            </Space>
                                        </Tooltip>
                                    )}
```

- [ ] **Step 5: Cho phép "Kết nối lại" hiện ra khi thiếu quyền, không chỉ khi token hết hạn** — sửa điều kiện hiện có (dòng 349):

Tìm:
```tsx
                                        {p.token_expired && (
                                            <Button size="small" type="primary" icon={<KeyOutlined />} loading={reconnectingId === p.id} onClick={() => handleReconnect(p.id)}>Kết nối lại</Button>
                                        )}
```

Thay bằng:
```tsx
                                        {(p.token_expired || p.fb_conversions.needs_reauth) && (
                                            <Button size="small" type="primary" icon={<KeyOutlined />} loading={reconnectingId === p.id} onClick={() => handleReconnect(p.id)}>Kết nối lại</Button>
                                        )}
```

- [ ] **Step 6: Typecheck + lint + build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: cả 3 lệnh PASS (0 lỗi TypeScript/ESLint, build thành công).

- [ ] **Step 7: Kiểm tra thủ công qua trình duyệt**

Chạy `composer dev` (từ `app/`), đăng nhập, vào `/messaging/channels`, xác nhận:
- Switch "Báo cáo Purchase" hiện cạnh switch AI tự trả lời cho mỗi Page.
- Bật lên (trong môi trường dev token thật CHƯA có `page_events` do chưa qua App Review) → thấy lỗi + Tag "Cần cấp quyền lại" + nút "Kết nối lại" xuất hiện — đúng hành vi kỳ vọng khi thiếu quyền (xem §7 spec).

- [ ] **Step 8: Commit**

```bash
git add app/resources/js/lib/messagingConfig.tsx app/resources/js/pages/MessagingChannelsPage.tsx
git commit -m "feat(messaging): FE toggle for FB Purchase conversion reporting"
```

---

## Self-Review (thực hiện bởi người viết plan trước khi bàn giao)

**1. Spec coverage:**
- §2 Contract mới → Task 2 + Task 3. ✓
- §3 Data không migration → dùng `messaging_account_meta.settings` (Task 4) thay vì `channel_accounts.meta` như bản spec ban đầu ghi — **đã đối chiếu code thực tế**: `settings` là cột `encrypted:array` có sẵn, đúng vị trí quy ước cho per-page config nhạy cảm (khác `ChannelAccount.meta` vốn dùng cho cờ đơn giản như `zalo_send_blocked`). Quyết định này CHÍNH XÁC HƠN spec gốc, đã note rõ trong code comment; không cần sửa spec doc (plan là nguồn thực thi, spec là ý định thiết kế — sai khác nhỏ về nơi lưu trữ không đổi hành vi/API).
- §4 Backend flow (toggle đồng bộ ensureDataset, job trigger tại `linkOrder`, idempotent, lỗi thiếu quyền) → Task 4 + Task 5. ✓
- §5 Frontend → Task 6. ✓
- §6 Testing (unit connector, feature dispatch, job idempotent + lỗi quyền) → Task 3/4/5 steps. ✓
- §7 Giới hạn ngoài code (App Review `page_events`) → ghi trong Global Constraints ngầm hiểu qua comment Task 3 Step 3c; KHÔNG phải việc code nên không có task riêng — đúng theo spec ("ngoài phạm vi code").

**2. Placeholder scan:** không còn "TBD/TODO"; mọi step code đều đầy đủ, không có "tương tự Task N".

**3. Type consistency:** `ReportOrderConversionToMeta(int $conversationId, int $orderId)` dùng nhất quán ở Task 5 (dispatch + test). `ConversionReportingConnector::ensureDataset/reportPurchase` chữ ký giống nhau xuyên suốt Task 2/3/4/5. `settings['fb_conversions']` 4 khoá (`enabled`, `dataset_id`, `last_error`, `last_error_at`) dùng nhất quán Task 4/5/6. Field FE `fb_conversions.{enabled,dataset_id,needs_reauth}` khớp response BE ở Task 4 Step 4b và Task 6 Step 1.
