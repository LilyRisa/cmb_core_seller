# Plan C — Tab "Chung": admin soạn + gửi trang thông báo theo tenant Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Admin soạn 1 "trang" nội dung (ưu đãi/tin chung — tiêu đề, ảnh bìa, thân bài TipTap, nút CTA), gửi tới 1/nhiều tenant cụ thể hoặc tất cả; tenant nhận thông báo ở tab "Chung" (dựng ở Plan A), bấm vào mở tab trình duyệt mới xem trang đầy đủ trong khung app hiện có.

**Architecture:** Theo đúng tiền lệ đã có trong repo (`Announcement`/`Broadcast` — admin tạo nội dung + tenant xem đều nằm **trong module `Admin`**, KHÔNG tạo module mới). Điểm khác Announcement: trang "Chung" phải đi qua **cùng hạ tầng `app_notifications`** (để hiện trong tab "Chung" của panel thông báo, có unread badge, dedup, realtime) — nên `Admin` module gọi vào `Notifications` module qua **Contract mới** `NotificationDispatcherContract` (extract từ `NotificationDispatcher` hiện có), đúng pattern `AiCreditMeter` (Billing expose contract cho Messaging/Marketing dùng). **Chiều phụ thuộc Admin → Notifications đã tồn tại sẵn** (`BroadcastService` đã `use Notifications\Notifications\BroadcastNotification`) — dùng thêm 1 contract nữa theo đúng chiều này, KHÔNG tạo phụ thuộc vòng. Đây là **Plan C trong loạt 3 plan độc lập** (spec: `docs/superpowers/specs/2026-07-23-notification-tabs-and-general-pages-design.md`), không phụ thuộc Plan B, có thể làm song song; **phụ thuộc Plan A đã merge** (cần cột `category`, `NotificationType::categoryFor()`, panel 3 tab đã xử lý sẵn nhánh `category==='general'` mở tab mới).

**Tech Stack:** Laravel 11 (PHP 8.3+, PHPUnit, Queue job cho fan-out ngoài request HTTP), React 18 + TypeScript + Ant Design + TipTap (tái dùng `RichTextEditor`/`TenantPicker` đã có, không sửa).

## Global Constraints

- Chạy mọi lệnh PHP/Node từ `app/`.
- PSR-4 `CMBcoreSeller\` map vào `app/app/`.
- Response envelope API: `{ "data": ..., "meta": ... }` / lỗi `{ "error": { "code","message" } }`.
- Toàn bộ CRUD/tenant-view của tính năng này nằm **trong module `Admin`** (theo tiền lệ `Announcement`), CHỈ contract `NotificationDispatcherContract` + hằng số `NotificationType::GENERAL_PAGE` là ở module `Notifications`.
- `body_html` LUÔN sanitize qua `HtmlSanitizer::clean()` trước khi lưu (như Announcement) — không bao giờ lưu HTML thô từ request.
- Route mới phải thêm vào `docs/05-api/endpoints.md` (Task 9).
- Không viết JS test mới (repo không có JS test runner) — FE verify bằng `npm run typecheck && npm run build` + kiểm tra thủ công trên trình duyệt.
- UI dùng font icon `@ant-design/icons`, không emoji.

---

## Task 1: Data model — `general_notification_pages` + `general_notification_page_views`

**Files:**
- Create: `app/app/Modules/Admin/Database/Migrations/2026_07_23_110000_create_general_notification_pages_table.php`
- Create: `app/app/Modules/Admin/Database/Migrations/2026_07_23_110001_create_general_notification_page_views_table.php`
- Create: `app/app/Modules/Admin/Models/GeneralNotificationPage.php`
- Create: `app/app/Modules/Admin/Models/GeneralNotificationPageView.php`
- Test: `app/tests/Feature/Admin/GeneralNotificationPageModelTest.php`

**Interfaces:**
- Produces: model `GeneralNotificationPage` (`STATUS_DRAFT|STATUS_SCHEDULED|STATUS_SENT`, `AUDIENCE_ALL|AUDIENCE_TENANT_IDS`, method `isExpired(): bool`), model `GeneralNotificationPageView` (unique `(page_id,user_id)`).

- [ ] **Step 1: Viết test trước**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPageView;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralNotificationPageModelTest extends TestCase
{
    use RefreshDatabase;

    private function makePage(array $attrs = []): GeneralNotificationPage
    {
        return GeneralNotificationPage::create(array_merge([
            'title' => 'Ưu đãi tháng 8', 'slug' => 'uu-dai-thang-8', 'body_html' => '<p>Nội dung</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL,
            'status' => GeneralNotificationPage::STATUS_DRAFT,
            'created_by_user_id' => 1,
        ], $attrs));
    }

    public function test_slug_is_unique(): void
    {
        $this->makePage();
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->makePage();
    }

    public function test_is_expired_false_when_no_expiry_set(): void
    {
        $page = $this->makePage();
        $this->assertFalse($page->isExpired());
    }

    public function test_is_expired_true_when_expires_at_in_past(): void
    {
        $page = $this->makePage(['expires_at' => now()->subDay()]);
        $this->assertTrue($page->isExpired());
    }

    public function test_is_expired_false_when_expires_at_in_future(): void
    {
        $page = $this->makePage(['expires_at' => now()->addDay()]);
        $this->assertFalse($page->isExpired());
    }

    public function test_audience_tenant_ids_casts_to_array(): void
    {
        $page = $this->makePage(['audience_type' => GeneralNotificationPage::AUDIENCE_TENANT_IDS, 'audience_tenant_ids' => [1, 2, 3]]);
        $this->assertSame([1, 2, 3], $page->fresh()->audience_tenant_ids);
    }

    public function test_page_view_unique_per_page_and_user(): void
    {
        $page = $this->makePage();
        GeneralNotificationPageView::create(['page_id' => $page->getKey(), 'tenant_id' => 5, 'user_id' => 9, 'viewed_at' => now()]);

        $this->expectException(UniqueConstraintViolationException::class);
        GeneralNotificationPageView::create(['page_id' => $page->getKey(), 'tenant_id' => 5, 'user_id' => 9, 'viewed_at' => now()]);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/GeneralNotificationPageModelTest.php`
Expected: FAIL — bảng/model chưa tồn tại.

- [ ] **Step 3: Tạo migration `general_notification_pages`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan C (2026-07-23) — "trang thông báo chung" (ưu đãi/tin chung) do admin soạn, gửi tới
 * tenant cụ thể hoặc tất cả. KHÔNG tenant-scoped (thuộc phạm vi admin global, giống
 * `announcements`). `body_html` đã sanitize allowlist trước khi lưu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_notification_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 160)->unique();
            $table->longText('body_html');
            $table->string('cover_image_url', 512)->nullable();
            $table->string('cta_label', 60)->nullable();
            $table->string('cta_url', 512)->nullable();
            $table->string('audience_type', 16); // all|tenant_ids
            $table->json('audience_tenant_ids')->nullable();
            $table->string('status', 16)->default('draft'); // draft|scheduled|sent
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by_user_id'); // admin_user id
            $table->timestamps();

            $table->index(['status', 'scheduled_at']); // quét lịch gửi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_notification_pages');
    }
};
```

- [ ] **Step 4: Tạo migration `general_notification_page_views`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plan C (2026-07-23) — lượt xem trang "Chung" theo user (1 dòng/user, idempotent qua unique). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_notification_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('general_notification_pages')->cascadeOnDelete();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('user_id')->index();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->unique(['page_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_notification_page_views');
    }
};
```

- [ ] **Step 5: Tạo model `GeneralNotificationPage`**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Plan C (2026-07-23) — trang thông báo chung admin soạn + gửi theo tenant/tất cả. KHÔNG
 * tenant-scoped (giống {@see Announcement}). Xem cũng ghi {@see GeneralNotificationPageView}.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $body_html
 * @property string|null $cover_image_url
 * @property string|null $cta_label
 * @property string|null $cta_url
 * @property string $audience_type
 * @property array<int,int>|null $audience_tenant_ids
 * @property string $status
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $sent_at
 * @property int $created_by_user_id
 */
class GeneralNotificationPage extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENT = 'sent';

    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_TENANT_IDS = 'tenant_ids';

    protected $fillable = [
        'title', 'slug', 'body_html', 'cover_image_url', 'cta_label', 'cta_url',
        'audience_type', 'audience_tenant_ids', 'status', 'scheduled_at', 'expires_at', 'sent_at', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'audience_tenant_ids' => 'array',
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /** Hết hạn hiển thị? Tính LIVE tại thời điểm gọi — không lưu trạng thái riêng. */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function views(): HasMany
    {
        return $this->hasMany(GeneralNotificationPageView::class, 'page_id');
    }
}
```

- [ ] **Step 6: Tạo model `GeneralNotificationPageView`**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Plan C (2026-07-23) — 1 lượt xem trang "Chung" của 1 user (unique per page+user, idempotent).
 *
 * @property int $id
 * @property int $page_id
 * @property int $tenant_id
 * @property int $user_id
 * @property Carbon $viewed_at
 */
class GeneralNotificationPageView extends Model
{
    protected $fillable = ['page_id', 'tenant_id', 'user_id', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }
}
```

- [ ] **Step 7: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/GeneralNotificationPageModelTest.php`
Expected: PASS (6 tests)

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Admin/Database/Migrations/2026_07_23_110000_create_general_notification_pages_table.php app/app/Modules/Admin/Database/Migrations/2026_07_23_110001_create_general_notification_page_views_table.php app/app/Modules/Admin/Models/GeneralNotificationPage.php app/app/Modules/Admin/Models/GeneralNotificationPageView.php app/tests/Feature/Admin/GeneralNotificationPageModelTest.php
git commit -m "feat(admin): add general_notification_pages data model"
```

---

## Task 2: `NotificationDispatcherContract` + `NotificationType::GENERAL_PAGE`

**Files:**
- Create: `app/app/Modules/Notifications/Contracts/NotificationDispatcherContract.php`
- Modify: `app/app/Modules/Notifications/Services/NotificationDispatcher.php`
- Modify: `app/app/Modules/Notifications/NotificationsServiceProvider.php`
- Modify: `app/app/Modules/Notifications/Support/NotificationType.php`
- Test: `app/tests/Feature/Notifications/NotificationDispatcherContractTest.php`

**Interfaces:**
- Produces: interface `NotificationDispatcherContract::dispatch(int $tenantId, array $payload, ?array $userIds = null): int`, bound tới `NotificationDispatcher` trong container. `NotificationType::GENERAL_PAGE = 'general.page'` (category `general`).

- [ ] **Step 1: Viết test trước**

```php
<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Modules\Notifications\Contracts\NotificationDispatcherContract;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDispatcherContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_resolves_to_notification_dispatcher(): void
    {
        $this->assertInstanceOf(NotificationDispatcher::class, app(NotificationDispatcherContract::class));
    }

    public function test_general_page_type_maps_to_general_category(): void
    {
        $this->assertSame('general', NotificationType::categoryFor(NotificationType::GENERAL_PAGE));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationDispatcherContractTest.php`
Expected: FAIL — interface và constant chưa tồn tại.

- [ ] **Step 3: Tạo contract**

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Contracts;

/**
 * Đầu mối tạo/fan-out thông báo in-app cho các module khác (Plan C, 2026-07-23) — theo luật
 * module: chỉ phụ thuộc Contract, không chạm Services/ nội bộ. Cài đặt:
 * {@see \CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher}.
 */
interface NotificationDispatcherContract
{
    /**
     * @param  array{type:string,level?:string,title:string,body?:?string,action_url?:?string,data?:array<string,mixed>,dedup_key?:?string}  $payload
     * @param  list<int>|null  $userIds  null ⇒ tất cả thành viên tenant
     * @return int số bản ghi đã tạo
     */
    public function dispatch(int $tenantId, array $payload, ?array $userIds = null): int;
}
```

- [ ] **Step 4: `NotificationDispatcher implements NotificationDispatcherContract`**

Trong `NotificationDispatcher.php`, thêm import:

```php
use CMBcoreSeller\Modules\Notifications\Contracts\NotificationDispatcherContract;
```

Sửa khai báo class:

```php
class NotificationDispatcher implements NotificationDispatcherContract
```

- [ ] **Step 5: Bind contract trong provider**

Trong `NotificationsServiceProvider.php`, thêm import:

```php
use CMBcoreSeller\Modules\Notifications\Contracts\NotificationDispatcherContract;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
```

Sửa `register()`:

```php
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/notifications.php', 'notifications');
        $this->app->bind(NotificationDispatcherContract::class, NotificationDispatcher::class);
    }
```

- [ ] **Step 6: Thêm `NotificationType::GENERAL_PAGE`**

Trong `NotificationType.php`, thêm hằng số (sau hằng số cuối cùng đã có từ Plan A/B):

```php
    /** Trang "Chung" (ưu đãi/tin chung) admin gửi (Plan C, 2026-07-23). */
    public const GENERAL_PAGE = 'general.page';
```

Thêm vào `CATEGORY_MAP`:

```php
        self::GENERAL_PAGE => self::CATEGORY_GENERAL,
```

- [ ] **Step 7: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationDispatcherContractTest.php tests/Feature/Notifications/NotificationDispatcherTest.php`
Expected: PASS toàn bộ (regression `NotificationDispatcherTest` không bị phá — class vẫn hoạt động y hệt, chỉ thêm `implements`).

- [ ] **Step 8: Commit**

```bash
git add app/app/Modules/Notifications/Contracts/NotificationDispatcherContract.php app/app/Modules/Notifications/Services/NotificationDispatcher.php app/app/Modules/Notifications/NotificationsServiceProvider.php app/app/Modules/Notifications/Support/NotificationType.php app/tests/Feature/Notifications/NotificationDispatcherContractTest.php
git commit -m "feat(notifications): expose NotificationDispatcherContract + general.page type"
```

---

## Task 3: `GeneralNotificationPageService` + `DispatchGeneralNotificationPageJob`

**Files:**
- Create: `app/app/Modules/Admin/Services/GeneralNotificationPageService.php`
- Create: `app/app/Modules/Admin/Jobs/DispatchGeneralNotificationPageJob.php`
- Test: `app/tests/Feature/Admin/GeneralNotificationPageServiceTest.php`
- Test: `app/tests/Feature/Admin/DispatchGeneralNotificationPageJobTest.php`

**Interfaces:**
- Consumes: `NotificationDispatcherContract` (Task 2), `GeneralNotificationPage`/`GeneralNotificationPageView` (Task 1).
- Produces: `GeneralNotificationPageService::resolveTenantIds(GeneralNotificationPage): list<int>`, `::dispatch(GeneralNotificationPage): int` (trả số tenant đã gửi, set `status=sent`+`sent_at`), `::generateUniqueSlug(string): string`. `DispatchGeneralNotificationPageJob(int $pageId)` — job bọc `dispatch()`, guard chống gửi trùng.

- [ ] **Step 1: Viết test cho `GeneralNotificationPageService`**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralNotificationPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): GeneralNotificationPageService
    {
        return app(GeneralNotificationPageService::class);
    }

    public function test_resolve_tenant_ids_all_excludes_suspended(): void
    {
        $active = Tenant::create(['name' => 'Active', 'status' => 'active']);
        Tenant::create(['name' => 'Suspended', 'status' => 'suspended']);
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'slug-1', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        $ids = $this->svc()->resolveTenantIds($page);

        $this->assertSame([(int) $active->getKey()], $ids);
    }

    public function test_resolve_tenant_ids_tenant_ids_filters_to_selected_and_excludes_suspended(): void
    {
        $t1 = Tenant::create(['name' => 'A', 'status' => 'active']);
        $t2 = Tenant::create(['name' => 'B', 'status' => 'suspended']);
        $t3 = Tenant::create(['name' => 'C', 'status' => 'active']); // không được chọn
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'slug-2', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_TENANT_IDS,
            'audience_tenant_ids' => [$t1->getKey(), $t2->getKey()],
            'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        $ids = $this->svc()->resolveTenantIds($page);

        $this->assertSame([(int) $t1->getKey()], $ids);
        $this->assertNotContains((int) $t3->getKey(), $ids);
    }

    public function test_dispatch_fans_out_to_all_users_of_each_tenant_and_marks_sent(): void
    {
        $tenant = Tenant::create(['name' => 'Shop', 'status' => 'active']);
        $u1 = \CMBcoreSeller\Models\User::factory()->create();
        $u2 = \CMBcoreSeller\Models\User::factory()->create();
        $tenant->users()->attach($u1->getKey(), ['role' => \CMBcoreSeller\Modules\Tenancy\Enums\Role::Owner->value]);
        $tenant->users()->attach($u2->getKey(), ['role' => \CMBcoreSeller\Modules\Tenancy\Enums\Role::Owner->value]);
        $page = GeneralNotificationPage::create([
            'title' => 'Ưu đãi', 'slug' => 'slug-3', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        $sentTo = $this->svc()->dispatch($page);

        $this->assertSame(1, $sentTo);
        $this->assertSame(GeneralNotificationPage::STATUS_SENT, $page->fresh()->status);
        $this->assertNotNull($page->fresh()->sent_at);
        $rows = Notification::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->getKey())->get();
        $this->assertCount(2, $rows);
        $this->assertSame('general', $rows->first()->category);
        $this->assertSame('/notifications/general/slug-3', $rows->first()->action_url);
    }

    public function test_generate_unique_slug_appends_suffix_on_collision(): void
    {
        GeneralNotificationPage::create([
            'title' => 'Ưu đãi tháng 8', 'slug' => 'uu-dai-thang-8', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        $this->assertSame('uu-dai-thang-8-2', $this->svc()->generateUniqueSlug('Ưu đãi tháng 8'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/GeneralNotificationPageServiceTest.php`
Expected: FAIL — class chưa tồn tại.

- [ ] **Step 3: Viết `GeneralNotificationPageService`**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Services;

use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Notifications\Contracts\NotificationDispatcherContract;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Plan C (2026-07-23) — soạn + gửi "trang thông báo chung" (ưu đãi/tin chung) tới tenant. Gửi
 * qua {@see NotificationDispatcherContract} (module Notifications) — mỗi tenant trong audience
 * nhận 1 loạt `app_notifications` fan-out cho TOÀN BỘ user của tenant đó (category=general).
 */
class GeneralNotificationPageService
{
    public function __construct(private NotificationDispatcherContract $dispatcher) {}

    /** @return list<int> */
    public function resolveTenantIds(GeneralNotificationPage $page): array
    {
        if ($page->audience_type === GeneralNotificationPage::AUDIENCE_ALL) {
            return Tenant::query()->where('status', '!=', 'suspended')
                ->pluck('id')->map(fn ($v) => (int) $v)->all();
        }

        $ids = collect($page->audience_tenant_ids ?? [])->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        return Tenant::query()->whereIn('id', $ids)->where('status', '!=', 'suspended')
            ->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /** Fan-out tới toàn bộ user của mỗi tenant trong audience; đánh dấu page đã gửi. Trả số tenant đã gửi. */
    public function dispatch(GeneralNotificationPage $page): int
    {
        $tenantIds = $this->resolveTenantIds($page);

        foreach ($tenantIds as $tenantId) {
            $this->dispatcher->dispatch($tenantId, [
                'type' => NotificationType::GENERAL_PAGE,
                'level' => 'info',
                'title' => $page->title,
                'action_url' => '/notifications/general/'.$page->slug,
                'data' => ['page_id' => (int) $page->getKey(), 'slug' => $page->slug],
                'dedup_key' => 'general.page:'.$page->getKey(),
            ]);
        }

        $page->forceFill(['status' => GeneralNotificationPage::STATUS_SENT, 'sent_at' => now()])->save();

        return count($tenantIds);
    }

    /** Slug duy nhất từ tiêu đề — thêm hậu tố `-2`, `-3`... nếu trùng. */
    public function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'thong-bao';
        $slug = $base;
        $suffix = 2;
        while (GeneralNotificationPage::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
```

- [ ] **Step 4: Chạy test service để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/GeneralNotificationPageServiceTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Viết test cho `DispatchGeneralNotificationPageJob`**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Jobs\DispatchGeneralNotificationPageJob;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchGeneralNotificationPageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_dispatches_page_and_marks_sent(): void
    {
        $tenant = Tenant::create(['name' => 'JobShop', 'status' => 'active']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $page = GeneralNotificationPage::create([
            'title' => 'Tin gấp', 'slug' => 'tin-gap', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ]);

        (new DispatchGeneralNotificationPageJob((int) $page->getKey()))->handle(app(\CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService::class));

        $this->assertSame(GeneralNotificationPage::STATUS_SENT, $page->fresh()->status);
        $this->assertSame(1, Notification::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->getKey())->count());
    }

    public function test_job_is_noop_when_already_sent(): void
    {
        $tenant = Tenant::create(['name' => 'JobShop2', 'status' => 'active']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $page = GeneralNotificationPage::create([
            'title' => 'Tin gấp 2', 'slug' => 'tin-gap-2', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => GeneralNotificationPage::STATUS_SENT,
            'sent_at' => now()->subHour(), 'created_by_user_id' => 1,
        ]);

        (new DispatchGeneralNotificationPageJob((int) $page->getKey()))->handle(app(\CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService::class));

        $this->assertSame(0, Notification::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->getKey())->count());
    }

    public function test_job_is_noop_when_page_not_found(): void
    {
        // Không throw — page đã bị xoá giữa lúc job chờ hàng đợi là tình huống hợp lệ.
        (new DispatchGeneralNotificationPageJob(999999))->handle(app(\CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService::class));
        $this->assertTrue(true);
    }
}
```

- [ ] **Step 6: Chạy test job để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/DispatchGeneralNotificationPageJobTest.php`
Expected: FAIL — class `DispatchGeneralNotificationPageJob` chưa tồn tại.

- [ ] **Step 7: Viết `DispatchGeneralNotificationPageJob`**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Jobs;

use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Plan C (2026-07-23) — chạy fan-out ngoài request HTTP (audience có thể hàng nghìn tenant).
 * Dùng cho cả "Gửi ngay" (controller dispatch job) lẫn lịch gửi (scheduled command dispatch job
 * cho từng page đến hạn). Guard `status !== sent` tránh gửi trùng nếu job chạy lại (retry/race).
 */
class DispatchGeneralNotificationPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public string $queue = 'notifications';

    public function __construct(public readonly int $pageId) {}

    public function handle(GeneralNotificationPageService $service): void
    {
        $page = GeneralNotificationPage::query()->find($this->pageId);
        if ($page === null || $page->status === GeneralNotificationPage::STATUS_SENT) {
            return;
        }
        $service->dispatch($page);
    }
}
```

- [ ] **Step 8: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/GeneralNotificationPageServiceTest.php tests/Feature/Admin/DispatchGeneralNotificationPageJobTest.php`
Expected: PASS toàn bộ

- [ ] **Step 9: Commit**

```bash
git add app/app/Modules/Admin/Services/GeneralNotificationPageService.php app/app/Modules/Admin/Jobs/DispatchGeneralNotificationPageJob.php app/tests/Feature/Admin/GeneralNotificationPageServiceTest.php app/tests/Feature/Admin/DispatchGeneralNotificationPageJobTest.php
git commit -m "feat(admin): general notification page dispatch service + job"
```

---

## Task 4: Admin CRUD controller + routes

**Files:**
- Create: `app/app/Modules/Admin/Http/Controllers/AdminGeneralNotificationPageController.php`
- Modify: `app/app/Modules/Admin/Http/routes.php`
- Test: `app/tests/Feature/Admin/AdminGeneralNotificationPageControllerTest.php`

**Interfaces:**
- Produces: `GET/POST /api/v1/admin/general-notification-pages`, `PATCH/DELETE .../{id}`, `POST .../media` — guard `web + auth:admin_web` (đúng nhóm middleware các route admin khác).

- [ ] **Step 1: Viết test trước**

Cần biết thông tin đăng nhập admin test — tham khảo cách các test Admin khác tạo admin user + login. Chạy trước:

Run: `cd app && grep -n "actingAs\|admin_web\|AdminUser::factory" tests/Feature/Admin/AdminTenantAiCreditAdjustTest.php | head -10`

Dùng đúng pattern tìm được (login admin/actingAs guard `admin_web`) để viết test dưới đây — thay thế phần `TODO_ADMIN_AUTH` bằng đoạn setup admin thật của repo trước khi chạy.

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\AdminUser;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGeneralNotificationPageControllerTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    private function actingAsAdmin(): self
    {
        $this->actingAs($this->admin, 'admin_web');

        return $this;
    }

    public function test_store_creates_draft_page_with_sanitized_html(): void
    {
        $res = $this->actingAsAdmin()->postJson('/api/v1/admin/general-notification-pages', [
            'title' => 'Ưu đãi tháng 8',
            'body_html' => '<p>Nội dung</p><script>alert(1)</script>',
            'audience_type' => 'all',
        ]);

        $res->assertCreated()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.slug', 'uu-dai-thang-8');
        $this->assertStringNotContainsString('<script>', $res->json('data.body_html'));
    }

    public function test_store_requires_tenant_ids_when_audience_is_tenant_ids(): void
    {
        $this->actingAsAdmin()->postJson('/api/v1/admin/general-notification-pages', [
            'title' => 'T', 'body_html' => '<p>x</p>', 'audience_type' => 'tenant_ids',
        ])->assertStatus(422);
    }

    public function test_store_with_scheduled_at_sets_status_scheduled(): void
    {
        $res = $this->actingAsAdmin()->postJson('/api/v1/admin/general-notification-pages', [
            'title' => 'Lên lịch', 'body_html' => '<p>x</p>', 'audience_type' => 'all',
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ]);

        $res->assertCreated()->assertJsonPath('data.status', 'scheduled');
    }

    public function test_update_rejects_when_already_sent(): void
    {
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'sent-page', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => GeneralNotificationPage::STATUS_SENT,
            'sent_at' => now(), 'created_by_user_id' => $this->admin->getKey(),
        ]);

        $this->actingAsAdmin()->patchJson("/api/v1/admin/general-notification-pages/{$page->getKey()}", ['title' => 'X'])
            ->assertStatus(422)->assertJsonPath('error.code', 'PAGE_ALREADY_SENT');
    }

    public function test_destroy_deletes_page(): void
    {
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'to-delete', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => 'draft', 'created_by_user_id' => $this->admin->getKey(),
        ]);

        $this->actingAsAdmin()->deleteJson("/api/v1/admin/general-notification-pages/{$page->getKey()}")
            ->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertDatabaseMissing('general_notification_pages', ['id' => $page->getKey()]);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/AdminGeneralNotificationPageControllerTest.php`
Expected: FAIL — controller/route chưa tồn tại (nếu setup admin auth trong test sai pattern thật của repo, sửa lại theo đúng cách `AdminAuthController`/các test Admin khác đang login trước khi tiếp tục).

- [ ] **Step 3: Viết controller**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use CMBcoreSeller\Support\HtmlSanitizer;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Plan C (2026-07-23) — CRUD "trang thông báo chung" (ưu đãi/tin chung) admin soạn + gửi theo
 * tenant hoặc tất cả. `body_html` (TipTap) sanitize allowlist trước khi lưu — cùng cơ chế
 * Announcement (SPEC 0037). Xem `send()` (Task 5) cho hành động gửi thật.
 */
class AdminGeneralNotificationPageController extends Controller
{
    public function __construct(private GeneralNotificationPageService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));
        $rows = GeneralNotificationPage::query()->latest('id')->paginate($perPage);

        return response()->json([
            'data' => collect($rows->items())->map(fn (GeneralNotificationPage $p) => $this->resource($p))->all(),
            'meta' => ['pagination' => ['total' => $rows->total()]],
        ]);
    }

    public function store(Request $request, HtmlSanitizer $sanitizer): JsonResponse
    {
        $data = $this->validated($request);
        $data['body_html'] = $sanitizer->clean($data['body_html']);
        $data['slug'] = $this->service->generateUniqueSlug($data['title']);
        $data['status'] = ! empty($data['scheduled_at']) ? GeneralNotificationPage::STATUS_SCHEDULED : GeneralNotificationPage::STATUS_DRAFT;
        $data['created_by_user_id'] = (int) $request->user()?->getKey();

        $page = GeneralNotificationPage::create($data);

        return response()->json(['data' => $this->resource($page)], 201);
    }

    public function update(Request $request, HtmlSanitizer $sanitizer, string $id): JsonResponse
    {
        $page = GeneralNotificationPage::query()->findOrFail((int) $id);
        if ($page->status === GeneralNotificationPage::STATUS_SENT) {
            return response()->json(['error' => ['code' => 'PAGE_ALREADY_SENT', 'message' => 'Trang đã gửi, không thể sửa.']], 422);
        }
        $data = $this->validated($request, partial: true);
        if (isset($data['body_html'])) {
            $data['body_html'] = $sanitizer->clean($data['body_html']);
        }
        if (array_key_exists('scheduled_at', $data)) {
            $data['status'] = $data['scheduled_at'] ? GeneralNotificationPage::STATUS_SCHEDULED : GeneralNotificationPage::STATUS_DRAFT;
        }
        $page->update($data);

        return response()->json(['data' => $this->resource($page->fresh())]);
    }

    public function destroy(string $id): JsonResponse
    {
        GeneralNotificationPage::query()->findOrFail((int) $id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** Upload ảnh bìa → R2 (thư mục general-notification-pages, non-tenant). */
    public function media(Request $request, MediaUploader $uploader): JsonResponse
    {
        $mimes = implode(',', (array) config('media.images.mimes', ['jpg', 'jpeg', 'png', 'webp']));
        $request->validate([
            'file' => ['required', 'file', 'mimes:'.$mimes, 'max:'.(int) config('media.images.max_kb', 5120)],
        ]);
        $stored = $uploader->storePublic($request->file('file'), 'general-notification-pages');

        return response()->json(['data' => ['url' => $stored['url']]]);
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$req, 'string', 'max:255'],
            'body_html' => [$req, 'string', 'max:200000'],
            'cover_image_url' => ['nullable', 'string', 'max:512'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:512'],
            'audience_type' => [$req, Rule::in([GeneralNotificationPage::AUDIENCE_ALL, GeneralNotificationPage::AUDIENCE_TENANT_IDS])],
            'audience_tenant_ids' => [
                Rule::requiredIf(fn () => $request->input('audience_type') === GeneralNotificationPage::AUDIENCE_TENANT_IDS),
                'nullable', 'array',
            ],
            'audience_tenant_ids.*' => ['integer'],
            'scheduled_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);
    }

    /** @return array<string,mixed> */
    private function resource(GeneralNotificationPage $p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'body_html' => $p->body_html,
            'cover_image_url' => $p->cover_image_url,
            'cta_label' => $p->cta_label,
            'cta_url' => $p->cta_url,
            'audience_type' => $p->audience_type,
            'audience_tenant_ids' => $p->audience_tenant_ids,
            'status' => $p->status,
            'scheduled_at' => $p->scheduled_at?->toIso8601String(),
            'expires_at' => $p->expires_at?->toIso8601String(),
            'sent_at' => $p->sent_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 4: Thêm routes**

Trong `app/app/Modules/Admin/Http/routes.php`, thêm import:

```php
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminGeneralNotificationPageController;
```

Thêm khối route ngay sau khối `// --- Announcement popups (SPEC 0037) ---` (trong cùng `Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])->prefix('api/v1/admin')` group):

```php
        // --- General notification pages (Plan C, 2026-07-23) ---
        Route::get('general-notification-pages', [AdminGeneralNotificationPageController::class, 'index'])->name('admin.general-notification-pages.index');
        Route::post('general-notification-pages', [AdminGeneralNotificationPageController::class, 'store'])->name('admin.general-notification-pages.store');
        Route::post('general-notification-pages/media', [AdminGeneralNotificationPageController::class, 'media'])->name('admin.general-notification-pages.media');
        Route::patch('general-notification-pages/{id}', [AdminGeneralNotificationPageController::class, 'update'])->whereNumber('id')->name('admin.general-notification-pages.update');
        Route::delete('general-notification-pages/{id}', [AdminGeneralNotificationPageController::class, 'destroy'])->whereNumber('id')->name('admin.general-notification-pages.destroy');
```

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/AdminGeneralNotificationPageControllerTest.php`
Expected: PASS toàn bộ 5 test

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/AdminGeneralNotificationPageController.php app/app/Modules/Admin/Http/routes.php app/tests/Feature/Admin/AdminGeneralNotificationPageControllerTest.php
git commit -m "feat(admin): CRUD API for general notification pages"
```

---

## Task 5: Gửi ngay + lên lịch tự động + thống kê lượt xem

**Files:**
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminGeneralNotificationPageController.php` (thêm `send`, `stats`)
- Create: `app/app/Modules/Admin/Console/Commands/DispatchScheduledGeneralNotificationPages.php`
- Modify: `app/app/Modules/Admin/AdminServiceProvider.php`
- Modify: `app/routes/console.php`
- Modify: `app/app/Modules/Admin/Http/routes.php`
- Test: `app/tests/Feature/Admin/AdminGeneralNotificationPageControllerTest.php` (thêm)
- Test: `app/tests/Feature/Admin/DispatchScheduledGeneralNotificationPagesTest.php`

**Interfaces:**
- Produces: `POST /api/v1/admin/general-notification-pages/{id}/send` (đưa job vào hàng đợi), `GET .../{id}/stats`, lệnh Artisan `notifications:dispatch-scheduled-general-pages` chạy mỗi phút.

- [ ] **Step 1: Thêm test `send`/`stats` vào `AdminGeneralNotificationPageControllerTest.php`**

Thêm import ở đầu file:

```php
use CMBcoreSeller\Modules\Admin\Jobs\DispatchGeneralNotificationPageJob;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPageView;
use Illuminate\Support\Facades\Queue;
```

Thêm test method mới (cuối class, trước `}`):

```php
    public function test_send_dispatches_job_for_draft_page(): void
    {
        Queue::fake();
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'to-send', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => 'draft', 'created_by_user_id' => $this->admin->getKey(),
        ]);

        $this->actingAsAdmin()->postJson("/api/v1/admin/general-notification-pages/{$page->getKey()}/send")
            ->assertOk()->assertJsonPath('data.dispatched', true);

        Queue::assertPushed(DispatchGeneralNotificationPageJob::class, fn ($j) => $j->pageId === (int) $page->getKey());
    }

    public function test_send_rejects_already_sent_page(): void
    {
        Queue::fake();
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'already-sent', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => GeneralNotificationPage::STATUS_SENT, 'sent_at' => now(),
            'created_by_user_id' => $this->admin->getKey(),
        ]);

        $this->actingAsAdmin()->postJson("/api/v1/admin/general-notification-pages/{$page->getKey()}/send")
            ->assertStatus(422)->assertJsonPath('error.code', 'PAGE_ALREADY_SENT');
        Queue::assertNotPushed(DispatchGeneralNotificationPageJob::class);
    }

    public function test_stats_returns_view_count(): void
    {
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'stats-page', 'body_html' => '<p>x</p>',
            'audience_type' => 'all', 'status' => GeneralNotificationPage::STATUS_SENT, 'sent_at' => now(),
            'created_by_user_id' => $this->admin->getKey(),
        ]);
        GeneralNotificationPageView::create(['page_id' => $page->getKey(), 'tenant_id' => 1, 'user_id' => 1, 'viewed_at' => now()]);
        GeneralNotificationPageView::create(['page_id' => $page->getKey(), 'tenant_id' => 2, 'user_id' => 2, 'viewed_at' => now()]);

        $this->actingAsAdmin()->getJson("/api/v1/admin/general-notification-pages/{$page->getKey()}/stats")
            ->assertOk()->assertJsonPath('data.view_count', 2);
    }
```

- [ ] **Step 2: Viết test cho lệnh scheduled dispatch**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Jobs\DispatchGeneralNotificationPageJob;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchScheduledGeneralNotificationPagesTest extends TestCase
{
    use RefreshDatabase;

    private function makePage(string $slug, string $status, ?\Illuminate\Support\Carbon $scheduledAt): GeneralNotificationPage
    {
        return GeneralNotificationPage::create([
            'title' => 'T', 'slug' => $slug, 'body_html' => '<p>x</p>', 'audience_type' => 'all',
            'status' => $status, 'scheduled_at' => $scheduledAt, 'created_by_user_id' => 1,
        ]);
    }

    public function test_dispatches_only_due_scheduled_pages(): void
    {
        Queue::fake();
        $due = $this->makePage('due', GeneralNotificationPage::STATUS_SCHEDULED, now()->subMinute());
        $future = $this->makePage('future', GeneralNotificationPage::STATUS_SCHEDULED, now()->addHour());
        $draft = $this->makePage('draft', GeneralNotificationPage::STATUS_DRAFT, null);
        $sent = $this->makePage('sent', GeneralNotificationPage::STATUS_SENT, now()->subDay());

        $this->artisan('notifications:dispatch-scheduled-general-pages')->assertExitCode(0);

        Queue::assertPushed(DispatchGeneralNotificationPageJob::class, 1);
        Queue::assertPushed(DispatchGeneralNotificationPageJob::class, fn ($j) => $j->pageId === (int) $due->getKey());
    }
}
```

- [ ] **Step 3: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/AdminGeneralNotificationPageControllerTest.php tests/Feature/Admin/DispatchScheduledGeneralNotificationPagesTest.php`
Expected: FAIL — action `send`/`stats`/command chưa tồn tại.

- [ ] **Step 4: Thêm `send()` + `stats()` vào controller**

Thêm import ở đầu `AdminGeneralNotificationPageController.php`:

```php
use CMBcoreSeller\Modules\Admin\Jobs\DispatchGeneralNotificationPageJob;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPageView;
```

Thêm 2 method vào class (sau `destroy()`, trước `media()`):

```php
    public function send(string $id): JsonResponse
    {
        $page = GeneralNotificationPage::query()->findOrFail((int) $id);
        if ($page->status === GeneralNotificationPage::STATUS_SENT) {
            return response()->json(['error' => ['code' => 'PAGE_ALREADY_SENT', 'message' => 'Trang đã gửi rồi.']], 422);
        }
        DispatchGeneralNotificationPageJob::dispatch((int) $page->getKey());

        return response()->json(['data' => ['dispatched' => true]]);
    }

    public function stats(string $id): JsonResponse
    {
        $page = GeneralNotificationPage::query()->findOrFail((int) $id);
        $viewCount = GeneralNotificationPageView::query()->where('page_id', $page->getKey())->count();
        $audienceTenantCount = count($this->service->resolveTenantIds($page));

        return response()->json(['data' => [
            'view_count' => $viewCount,
            'audience_tenant_count' => $audienceTenantCount,
        ]]);
    }
```

- [ ] **Step 5: Viết command `DispatchScheduledGeneralNotificationPages`**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Console\Commands;

use CMBcoreSeller\Modules\Admin\Jobs\DispatchGeneralNotificationPageJob;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use Illuminate\Console\Command;

/**
 * Plan C (2026-07-23) — quét trang "Chung" đã lên lịch (scheduled_at đã tới) và đưa vào hàng
 * đợi gửi. Chạy mỗi phút (app/routes/console.php). Idempotent — job tự guard `status !== sent`.
 */
class DispatchScheduledGeneralNotificationPages extends Command
{
    protected $signature = 'notifications:dispatch-scheduled-general-pages';

    protected $description = 'Gửi các trang thông báo chung đã lên lịch mà thời điểm gửi đã tới (Plan C, 2026-07-23)';

    public function handle(): int
    {
        $due = GeneralNotificationPage::query()
            ->where('status', GeneralNotificationPage::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($due as $page) {
            DispatchGeneralNotificationPageJob::dispatch((int) $page->getKey());
        }

        $this->info("Đã đưa {$due->count()} trang vào hàng đợi gửi.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 6: Đăng ký command trong `AdminServiceProvider`**

Thêm import:

```php
use CMBcoreSeller\Modules\Admin\Console\Commands\DispatchScheduledGeneralNotificationPages;
```

Thêm cuối `boot()`:

```php
        if ($this->app->runningInConsole()) {
            $this->commands([DispatchScheduledGeneralNotificationPages::class]);
        }
```

- [ ] **Step 7: Đăng ký scheduler trong `app/routes/console.php`**

Thêm dòng (cạnh các `Schedule::command('messaging:...')`):

```php
Schedule::command('notifications:dispatch-scheduled-general-pages')->everyMinute()->onOneServer()->withoutOverlapping();
```

- [ ] **Step 8: Thêm routes `send`/`stats`**

Trong `app/app/Modules/Admin/Http/routes.php`, thêm 2 dòng ngay sau route `destroy` của general-notification-pages (Task 4):

```php
        Route::post('general-notification-pages/{id}/send', [AdminGeneralNotificationPageController::class, 'send'])->whereNumber('id')->name('admin.general-notification-pages.send');
        Route::get('general-notification-pages/{id}/stats', [AdminGeneralNotificationPageController::class, 'stats'])->whereNumber('id')->name('admin.general-notification-pages.stats');
```

- [ ] **Step 9: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/AdminGeneralNotificationPageControllerTest.php tests/Feature/Admin/DispatchScheduledGeneralNotificationPagesTest.php`
Expected: PASS toàn bộ

- [ ] **Step 10: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/AdminGeneralNotificationPageController.php app/app/Modules/Admin/Console/Commands/DispatchScheduledGeneralNotificationPages.php app/app/Modules/Admin/AdminServiceProvider.php app/routes/console.php app/app/Modules/Admin/Http/routes.php app/tests/Feature/Admin/AdminGeneralNotificationPageControllerTest.php app/tests/Feature/Admin/DispatchScheduledGeneralNotificationPagesTest.php
git commit -m "feat(admin): send now + scheduled dispatch + view stats for general notification pages"
```

---

## Task 6: Tenant-facing view + ghi lượt xem

**Files:**
- Create: `app/app/Modules/Admin/Http/Controllers/GeneralNotificationPageViewController.php`
- Modify: `app/app/Modules/Admin/Http/routes.php`
- Test: `app/tests/Feature/Admin/GeneralNotificationPageViewControllerTest.php`

**Interfaces:**
- Produces: `GET /api/v1/notifications/general/{slug}` — `sanctum + verified + tenant`. Trả 403 nếu tenant hiện tại chưa từng nhận trang này (không có `app_notifications` row tương ứng); 410 nếu hết hạn; ghi `GeneralNotificationPageView` lần đầu xem (idempotent).

- [ ] **Step 1: Viết test trước**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPageView;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralNotificationPageViewControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'ViewShop', 'status' => 'active']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function makeAndSendPage(array $attrs = []): GeneralNotificationPage
    {
        $page = GeneralNotificationPage::create(array_merge([
            'title' => 'Ưu đãi', 'slug' => 'view-page', 'body_html' => '<p>Nội dung</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_ALL, 'status' => 'draft', 'created_by_user_id' => 1,
        ], $attrs));
        app(GeneralNotificationPageService::class)->dispatch($page);

        return $page->fresh();
    }

    public function test_tenant_that_received_page_can_view_and_records_view(): void
    {
        $page = $this->makeAndSendPage();

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/notifications/general/view-page');

        $res->assertOk()->assertJsonPath('data.title', 'Ưu đãi');
        $this->assertSame(1, GeneralNotificationPageView::query()
            ->where('page_id', $page->getKey())->where('user_id', $this->owner->getKey())->count());
    }

    public function test_viewing_twice_only_records_one_view(): void
    {
        $this->makeAndSendPage();

        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/notifications/general/view-page')->assertOk();
        $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/notifications/general/view-page')->assertOk();

        $this->assertSame(1, GeneralNotificationPageView::query()->where('user_id', $this->owner->getKey())->count());
    }

    public function test_tenant_not_in_audience_gets_forbidden(): void
    {
        // Gửi cho tenant KHÁC, không phải $this->tenant.
        $other = Tenant::create(['name' => 'OtherShop', 'status' => 'active']);
        $page = GeneralNotificationPage::create([
            'title' => 'T', 'slug' => 'other-page', 'body_html' => '<p>x</p>',
            'audience_type' => GeneralNotificationPage::AUDIENCE_TENANT_IDS,
            'audience_tenant_ids' => [$other->getKey()], 'status' => 'draft', 'created_by_user_id' => 1,
        ]);
        app(GeneralNotificationPageService::class)->dispatch($page);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/notifications/general/other-page')->assertForbidden();
    }

    public function test_expired_page_returns_410(): void
    {
        $this->makeAndSendPage(['slug' => 'expired-page', 'expires_at' => now()->subDay()]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/notifications/general/expired-page')->assertStatus(410);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/notifications/general/khong-ton-tai')->assertNotFound();
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/GeneralNotificationPageViewControllerTest.php`
Expected: FAIL — controller/route chưa tồn tại.

- [ ] **Step 3: Viết controller**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPageView;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Plan C (2026-07-23) — tenant user xem 1 trang "Chung" đã nhận qua panel thông báo. Chỉ cho
 * xem nếu tenant hiện tại THẬT SỰ nằm trong audience đã gửi (kiểm tra qua đã tồn tại
 * `app_notifications` row `type=general.page` cho tenant này) — không public theo slug.
 */
class GeneralNotificationPageViewController extends Controller
{
    public function show(Request $request, string $slug): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-Id');
        $userId = (int) $request->user()?->getKey();

        $page = GeneralNotificationPage::query()->where('slug', $slug)->first();
        if ($page === null) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy nội dung.']], 404);
        }

        $received = Notification::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('type', 'general.page')
            ->whereJsonContains('data->page_id', (int) $page->getKey())
            ->exists();
        if (! $received) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Bạn không có quyền xem nội dung này.']], 403);
        }

        if ($page->isExpired()) {
            return response()->json(['error' => ['code' => 'PAGE_EXPIRED', 'message' => 'Nội dung đã hết hạn.']], 410);
        }

        $this->recordView($page, $tenantId, $userId);

        return response()->json(['data' => [
            'title' => $page->title,
            'body_html' => $page->body_html,
            'cover_image_url' => $page->cover_image_url,
            'cta_label' => $page->cta_label,
            'cta_url' => $page->cta_url,
            'sent_at' => $page->sent_at?->toIso8601String(),
        ]]);
    }

    private function recordView(GeneralNotificationPage $page, int $tenantId, int $userId): void
    {
        try {
            GeneralNotificationPageView::create([
                'page_id' => $page->getKey(), 'tenant_id' => $tenantId, 'user_id' => $userId, 'viewed_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Đã xem trước đó (race hoặc F5 lại) — bỏ qua, không phải lỗi.
        }
    }
}
```

- [ ] **Step 4: Thêm route**

Trong `app/app/Modules/Admin/Http/routes.php`, thêm import:

```php
use CMBcoreSeller\Modules\Admin\Http\Controllers\GeneralNotificationPageViewController;
```

Thêm route group MỚI (route này cần middleware `tenant`, khác nhóm `announcements/active` hiện có không có `tenant`) — thêm cuối file, sau khối `User-facing: popup announcement`:

```php
/*
 |--------------------------------------------------------------------------
 | User-facing: xem trang "Chung" đã nhận (Plan C, 2026-07-23) — /api/v1/notifications/general/{slug}
 |--------------------------------------------------------------------------
 | Cần tenant context để kiểm tra tenant hiện tại có nằm trong audience đã gửi không.
 */
Route::middleware(['api', 'auth:sanctum', 'verified', 'tenant'])
    ->prefix('api/v1')->group(function () {
        Route::get('notifications/general/{slug}', [GeneralNotificationPageViewController::class, 'show'])
            ->middleware('throttle:60,1')->name('notifications.general.show');
    });
```

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Admin/GeneralNotificationPageViewControllerTest.php`
Expected: PASS (5 tests)

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/GeneralNotificationPageViewController.php app/app/Modules/Admin/Http/routes.php app/tests/Feature/Admin/GeneralNotificationPageViewControllerTest.php
git commit -m "feat(admin): tenant-facing view endpoint for general notification pages"
```

---

## Task 7: FE Admin — trang soạn/gửi

**Files:**
- Create: `app/resources/js/admin/lib/generalNotificationPages.tsx`
- Create: `app/resources/js/admin/pages/AdminGeneralNotificationPagesPage.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx`
- Modify: `app/resources/js/admin/AdminLayout.tsx`

**Interfaces:**
- Consumes: API Task 4/5 (`/admin/general-notification-pages*`).
- Produces: trang admin `/admin/general-notification-pages` — danh sách, form tạo/sửa (tái dùng `RichTextEditor`, `TenantPicker` — KHÔNG sửa 2 component này), nút "Gửi ngay".

- [ ] **Step 1: Viết `admin/lib/generalNotificationPages.tsx`**

```tsx
// Plan C (2026-07-23) — hooks quản lý "trang thông báo chung" ở /api/v1/admin/general-notification-pages/*.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient } from './adminClient';

export interface AdminGeneralNotificationPage {
    id: number;
    title: string;
    slug: string;
    body_html: string;
    cover_image_url: string | null;
    cta_label: string | null;
    cta_url: string | null;
    audience_type: 'all' | 'tenant_ids';
    audience_tenant_ids: number[] | null;
    status: 'draft' | 'scheduled' | 'sent';
    scheduled_at: string | null;
    expires_at: string | null;
    sent_at: string | null;
    created_at: string | null;
}

export interface GeneralNotificationPageInput {
    title: string;
    body_html: string;
    cover_image_url?: string | null;
    cta_label?: string | null;
    cta_url?: string | null;
    audience_type: 'all' | 'tenant_ids';
    audience_tenant_ids?: number[];
    scheduled_at?: string | null;
    expires_at?: string | null;
}

interface ListResponse {
    data: AdminGeneralNotificationPage[];
    meta: { pagination: { total: number } };
}

export function useAdminGeneralNotificationPages() {
    return useQuery({
        queryKey: ['admin', 'general-notification-pages'],
        queryFn: async () => (await adminClient.get<ListResponse>('/general-notification-pages')).data,
    });
}

export function useCreateGeneralNotificationPage() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: GeneralNotificationPageInput) =>
            (await adminClient.post<{ data: AdminGeneralNotificationPage }>('/general-notification-pages', input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'general-notification-pages'] }),
    });
}

export function useUpdateGeneralNotificationPage() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...input }: { id: number } & Partial<GeneralNotificationPageInput>) =>
            (await adminClient.patch<{ data: AdminGeneralNotificationPage }>(`/general-notification-pages/${id}`, input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'general-notification-pages'] }),
    });
}

export function useDeleteGeneralNotificationPage() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.delete(`/general-notification-pages/${id}`)).data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'general-notification-pages'] }),
    });
}

export function useSendGeneralNotificationPage() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await adminClient.post<{ data: { dispatched: boolean } }>(`/general-notification-pages/${id}/send`)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'general-notification-pages'] }),
    });
}

/** Upload ảnh bìa trong form → R2; trả URL công khai. */
export async function uploadGeneralNotificationPageMedia(file: File): Promise<string> {
    const fd = new FormData();
    fd.append('file', file);
    const { data } = await adminClient.post<{ data: { url: string } }>('/general-notification-pages/media', fd);
    return data.data.url;
}
```

- [ ] **Step 2: Viết `AdminGeneralNotificationPagesPage.tsx`**

```tsx
import { useState } from 'react';
import { App, Button, Card, DatePicker, Drawer, Form, Input, Popconfirm, Radio, Space, Table, Tag, Typography, Upload } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { DeleteOutlined, EditOutlined, PlusOutlined, SendOutlined, UploadOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { formatDate } from '@/lib/format';
import { RichTextEditor } from '@admin/components/RichTextEditor';
import { TenantPicker } from '@admin/components/TenantPicker';
import {
    useAdminGeneralNotificationPages,
    useCreateGeneralNotificationPage,
    useUpdateGeneralNotificationPage,
    useDeleteGeneralNotificationPage,
    useSendGeneralNotificationPage,
    uploadGeneralNotificationPageMedia,
    type AdminGeneralNotificationPage,
} from '@admin/lib/generalNotificationPages';

interface FormShape {
    title: string;
    audience_type: 'all' | 'tenant_ids';
    tenant_ids?: number[];
    cta_label?: string;
    cta_url?: string;
    scheduled_at?: dayjs.Dayjs;
    expires_at?: dayjs.Dayjs;
}

const STATUS_TAG: Record<AdminGeneralNotificationPage['status'], { color: string; label: string }> = {
    draft: { color: 'default', label: 'Nháp' },
    scheduled: { color: 'blue', label: 'Đã lên lịch' },
    sent: { color: 'green', label: 'Đã gửi' },
};

/**
 * Plan C (2026-07-23) — admin soạn + gửi "trang thông báo chung" (ưu đãi/tin chung) tới tenant.
 * Tái dùng RichTextEditor (TipTap, không sửa) cho thân bài; ảnh bìa + nút CTA là field riêng
 * (KHÔNG nhúng trong body_html) — đơn giản hoá, tenant page tự render layout cố định.
 */
export function AdminGeneralNotificationPagesPage() {
    const { message } = App.useApp();
    const { data, isFetching } = useAdminGeneralNotificationPages();
    const create = useCreateGeneralNotificationPage();
    const update = useUpdateGeneralNotificationPage();
    const remove = useDeleteGeneralNotificationPage();
    const send = useSendGeneralNotificationPage();
    const [form] = Form.useForm<FormShape>();
    const [open, setOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [bodyHtml, setBodyHtml] = useState('');
    const [coverImageUrl, setCoverImageUrl] = useState<string | null>(null);
    const [editorKey, setEditorKey] = useState('new');
    const [uploading, setUploading] = useState(false);

    const openCreate = () => {
        form.resetFields();
        setBodyHtml('');
        setCoverImageUrl(null);
        setEditingId(null);
        setEditorKey('new-' + Date.now());
        setOpen(true);
    };

    const openEdit = (p: AdminGeneralNotificationPage) => {
        setEditingId(p.id);
        setBodyHtml(p.body_html);
        setCoverImageUrl(p.cover_image_url);
        setEditorKey('edit-' + p.id);
        form.setFieldsValue({
            title: p.title,
            audience_type: p.audience_type,
            tenant_ids: p.audience_tenant_ids ?? undefined,
            cta_label: p.cta_label ?? undefined,
            cta_url: p.cta_url ?? undefined,
            scheduled_at: p.scheduled_at ? dayjs(p.scheduled_at) : undefined,
            expires_at: p.expires_at ? dayjs(p.expires_at) : undefined,
        });
        setOpen(true);
    };

    const submit = (v: FormShape) => {
        const input = {
            title: v.title,
            body_html: bodyHtml,
            cover_image_url: coverImageUrl,
            cta_label: v.cta_label || null,
            cta_url: v.cta_url || null,
            audience_type: v.audience_type,
            audience_tenant_ids: v.audience_type === 'tenant_ids' ? (v.tenant_ids ?? []) : undefined,
            scheduled_at: v.scheduled_at ? v.scheduled_at.toISOString() : null,
            expires_at: v.expires_at ? v.expires_at.toISOString() : null,
        };
        const opts = {
            onSuccess: () => { message.success(editingId ? 'Đã cập nhật.' : 'Đã lưu nháp.'); setOpen(false); },
            onError: (e: unknown) => message.error(errorMessage(e, 'Lưu lỗi.')),
        };
        if (editingId) update.mutate({ id: editingId, ...input }, opts);
        else create.mutate(input, opts);
    };

    const uploadCover = (file: File) => {
        setUploading(true);
        uploadGeneralNotificationPageMedia(file)
            .then(setCoverImageUrl)
            .catch(() => message.error('Tải ảnh lên thất bại.'))
            .finally(() => setUploading(false));
        return false;
    };

    const columns: ColumnsType<AdminGeneralNotificationPage> = [
        { title: 'ID', dataIndex: 'id', width: 60 },
        { title: 'Tiêu đề', dataIndex: 'title' },
        {
            title: 'Đối tượng', dataIndex: 'audience_type', width: 160,
            render: (v: AdminGeneralNotificationPage['audience_type'], r) => v === 'all' ? <Tag>Tất cả tenant</Tag> : <Tag>{r.audience_tenant_ids?.length ?? 0} tenant cụ thể</Tag>,
        },
        {
            title: 'Trạng thái', dataIndex: 'status', width: 120,
            render: (v: AdminGeneralNotificationPage['status']) => <Tag color={STATUS_TAG[v].color}>{STATUS_TAG[v].label}</Tag>,
        },
        { title: 'Gửi lúc', dataIndex: 'sent_at', width: 160, render: (v: string | null) => formatDate(v) },
        {
            title: '', key: 'actions', width: 160,
            render: (_, r) => r.status === 'sent' ? null : (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(r)} />
                    <Popconfirm
                        title="Gửi ngay trang này?"
                        onConfirm={() => send.mutate(r.id, {
                            onSuccess: () => message.success('Đã đưa vào hàng đợi gửi.'),
                            onError: (e) => message.error(errorMessage(e, 'Gửi lỗi.')),
                        })}
                    >
                        <Button size="small" type="primary" icon={<SendOutlined />} />
                    </Popconfirm>
                    <Popconfirm title="Xoá trang này?" onConfirm={() => remove.mutate(r.id, { onSuccess: () => message.success('Đã xoá.') })}>
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <>
            <PageHeader
                title="Thông báo chung"
                subtitle='Soạn trang ưu đãi/tin chung, gửi tới 1 hoặc nhiều tenant cụ thể hoặc tất cả — hiện ở tab "Chung" trong chuông thông báo.'
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Tạo trang mới</Button>}
            />

            <Card title="Danh sách trang">
                <Table rowKey="id" size="small" columns={columns} dataSource={data?.data ?? []} loading={isFetching} pagination={{ pageSize: 20 }} />
            </Card>

            <Drawer open={open} title={editingId ? `Sửa trang #${editingId}` : 'Tạo trang mới'} width={720} onClose={() => setOpen(false)} destroyOnHidden>
                <Form form={form} layout="vertical" initialValues={{ audience_type: 'all' }} onFinish={submit}>
                    <Form.Item name="title" label="Tiêu đề" rules={[{ required: true, max: 255 }]}>
                        <Input placeholder="VD: Ưu đãi tháng 8 cho chủ shop" />
                    </Form.Item>

                    <Form.Item label="Ảnh bìa (tuỳ chọn)">
                        <Upload beforeUpload={uploadCover} showUploadList={false} accept="image/*">
                            <Button icon={<UploadOutlined />} loading={uploading}>Tải ảnh bìa</Button>
                        </Upload>
                        {coverImageUrl && <img src={coverImageUrl} alt="" style={{ maxWidth: '100%', marginTop: 8, borderRadius: 8 }} />}
                    </Form.Item>

                    <Form.Item label="Nội dung" required>
                        <RichTextEditor key={editorKey} value={bodyHtml} onChange={setBodyHtml} />
                    </Form.Item>

                    <Space size="large" wrap align="start">
                        <Form.Item name="cta_label" label="Nhãn nút CTA (tuỳ chọn)">
                            <Input style={{ width: 220 }} placeholder="VD: Xem chi tiết" />
                        </Form.Item>
                        <Form.Item name="cta_url" label="Link CTA (tuỳ chọn)">
                            <Input style={{ width: 320 }} placeholder="https://..." />
                        </Form.Item>
                    </Space>

                    <Form.Item name="audience_type" label="Đối tượng nhận">
                        <Radio.Group>
                            <Radio.Button value="all">Tất cả tenant</Radio.Button>
                            <Radio.Button value="tenant_ids">Tenant cụ thể</Radio.Button>
                        </Radio.Group>
                    </Form.Item>

                    <Form.Item shouldUpdate={(p, c) => p.audience_type !== c.audience_type} noStyle>
                        {({ getFieldValue }) => getFieldValue('audience_type') === 'tenant_ids' && (
                            <Form.Item name="tenant_ids" label="Tenant cụ thể" rules={[{ required: true }]}>
                                <TenantPicker mode="multiple" placeholder="Tìm theo mã / tên / email…" />
                            </Form.Item>
                        )}
                    </Form.Item>

                    <Space size="large" wrap align="start">
                        <Form.Item name="scheduled_at" label="Lên lịch gửi (tuỳ chọn — để trống thì bấm Gửi ngay ở danh sách)">
                            <DatePicker showTime format="DD/MM/YYYY HH:mm" style={{ width: 260 }} />
                        </Form.Item>
                        <Form.Item name="expires_at" label="Hạn hiển thị (tuỳ chọn)">
                            <DatePicker showTime format="DD/MM/YYYY HH:mm" style={{ width: 260 }} />
                        </Form.Item>
                    </Space>

                    <Space>
                        <Button type="primary" htmlType="submit" loading={create.isPending || update.isPending}>
                            {editingId ? 'Cập nhật' : 'Lưu nháp'}
                        </Button>
                        <Button onClick={() => setOpen(false)}>Huỷ</Button>
                    </Space>
                    <Typography.Paragraph type="secondary" style={{ marginTop: 12 }}>
                        Lưu ở đây chỉ tạo NHÁP (hoặc lên lịch nếu có chọn thời điểm) — bấm nút gửi ở danh sách để gửi ngay.
                    </Typography.Paragraph>
                </Form>
            </Drawer>
        </>
    );
}
```

- [ ] **Step 3: Đăng ký route trong `AdminApp.tsx`**

Thêm import:

```tsx
import { AdminGeneralNotificationPagesPage } from './pages/AdminGeneralNotificationPagesPage';
```

Thêm route (cạnh route `announcements`):

```tsx
                    <Route path="general-notification-pages" element={<AdminGeneralNotificationPagesPage />} />
```

- [ ] **Step 4: Đăng ký nav item trong `AdminLayout.tsx`**

Thêm import icon:

```tsx
    FileTextOutlined,
```

Thêm item vào nhóm `'TRUYỀN THÔNG'` (sau `announcements`):

```tsx
            { key: '/admin/general-notification-pages', icon: <FileTextOutlined />, label: 'Thông báo chung' },
```

- [ ] **Step 5: Kiểm tra kiểu + build**

Run: `cd app && npm run typecheck && npm run build`
Expected: PASS

- [ ] **Step 6: Kiểm tra thủ công trên trình duyệt**

Run: `cd app && composer dev`, đăng nhập admin (`/admin`), vào "Thông báo chung": tạo 1 trang nháp (điền tiêu đề, nội dung, chọn "Tất cả tenant"), lưu → xuất hiện trong danh sách trạng thái "Nháp" → bấm nút gửi → trạng thái chuyển "Đã gửi".

- [ ] **Step 7: Commit**

```bash
git add app/resources/js/admin/lib/generalNotificationPages.tsx app/resources/js/admin/pages/AdminGeneralNotificationPagesPage.tsx app/resources/js/admin/AdminApp.tsx app/resources/js/admin/AdminLayout.tsx
git commit -m "feat(admin-fe): general notification pages authoring UI"
```

---

## Task 8: FE tenant — trang xem nội dung "Chung"

**Files:**
- Create: `app/resources/js/lib/generalNotificationPage.ts`
- Create: `app/resources/js/pages/GeneralNotificationPage.tsx`
- Modify: `app/resources/js/routes/appRoutes.tsx`

**Interfaces:**
- Consumes: API Task 6 (`GET /notifications/general/:slug`).
- Produces: route `/notifications/general/:slug` trong SPA user — mở qua `window.open` từ `NotificationBell.tsx` (đã xử lý ở Plan A Task 9).

- [ ] **Step 1: Viết `lib/generalNotificationPage.ts`**

```ts
import { useQuery } from '@tanstack/react-query';
import { tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';

export interface GeneralNotificationPageContent {
    title: string;
    body_html: string;
    cover_image_url: string | null;
    cta_label: string | null;
    cta_url: string | null;
    sent_at: string | null;
}

/** Plan C (2026-07-23) — nội dung trang "Chung" theo slug (chỉ tenant nằm trong audience đã gửi mới xem được). */
export function useGeneralNotificationPage(slug: string | undefined) {
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['notifications', 'general', tenantId, slug],
        queryFn: async () =>
            (await tenantApi(tenantId!).get<{ data: GeneralNotificationPageContent }>(`/notifications/general/${slug}`)).data.data,
        enabled: tenantId != null && !!slug,
        retry: false,
    });
}
```

- [ ] **Step 2: Viết `pages/GeneralNotificationPage.tsx`**

```tsx
import { useParams } from 'react-router-dom';
import { Alert, Button, Card, Spin, Typography } from 'antd';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useGeneralNotificationPage } from '@/lib/generalNotificationPage';

/**
 * Plan C (2026-07-23) — trang nội dung "Chung" (ưu đãi/tin chung) admin gửi, mở qua tab mới từ
 * chuông thông báo. `body_html` đã được server sanitize (HtmlSanitizer) trước khi lưu — an toàn
 * để render trực tiếp.
 */
export function GeneralNotificationPage() {
    const { slug } = useParams<{ slug: string }>();
    const { data, isLoading, error } = useGeneralNotificationPage(slug);

    if (isLoading) return <div style={{ textAlign: 'center', padding: 48 }}><Spin size="large" /></div>;
    if (error) return <Alert type="error" showIcon message={errorMessage(error, 'Không thể tải nội dung.')} style={{ margin: 24 }} />;
    if (!data) return null;

    return (
        <>
            <PageHeader title={data.title} />
            <Card>
                {data.cover_image_url && (
                    <img src={data.cover_image_url} alt="" style={{ maxWidth: '100%', borderRadius: 8, marginBottom: 16 }} />
                )}
                <Typography.Paragraph>
                    <div dangerouslySetInnerHTML={{ __html: data.body_html }} />
                </Typography.Paragraph>
                {data.cta_url && (
                    <Button type="primary" size="large" href={data.cta_url} target="_blank" rel="noopener noreferrer">
                        {data.cta_label || 'Xem chi tiết'}
                    </Button>
                )}
            </Card>
        </>
    );
}
```

- [ ] **Step 3: Đăng ký route trong `appRoutes.tsx`**

Thêm import:

```tsx
import { GeneralNotificationPage } from '@/pages/GeneralNotificationPage';
```

Thêm route (trong `appRouteElements()`, cạnh các route khác):

```tsx
            <Route path="notifications/general/:slug" element={<GeneralNotificationPage />} />
```

- [ ] **Step 4: Kiểm tra kiểu + build**

Run: `cd app && npm run typecheck && npm run build`
Expected: PASS

- [ ] **Step 5: Kiểm tra thủ công đầu-cuối trên trình duyệt**

1. Ở admin: tạo + gửi ngay 1 trang "Chung" cho "Tất cả tenant".
2. Ở app user (tenant bất kỳ, đăng nhập `owner@demo.local`/`password`): mở chuông thông báo → tab "Chung" → thấy thông báo mới, badge đúng số.
3. Bấm vào thông báo → tab trình duyệt MỚI mở ra `/notifications/general/{slug}`, hiện đúng tiêu đề/nội dung/ảnh bìa/nút CTA (nếu có).
4. Quay lại admin → mở stats trang vừa gửi (gọi trực tiếp `GET .../stats` hoặc qua UI nếu có) → `view_count` = 1.

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/lib/generalNotificationPage.ts app/resources/js/pages/GeneralNotificationPage.tsx app/resources/js/routes/appRoutes.tsx
git commit -m "feat(notifications-fe): tenant-facing general notification page view"
```

---

## Task 9: Docs + quality gate cuối

**Files:**
- Modify: `docs/05-api/endpoints.md`

- [ ] **Step 1: Cập nhật `docs/05-api/endpoints.md`**

Sửa dòng 206 (mục "Thông báo in-app") — thêm `general.page` vào danh sách type:

```
Loại (`type`) v1: `channel.reconnect_needed`, `order.negative_total`, `order.cancelled`, `order.return_new`, `ads.monitor_approaching`, `ads.monitor_action`, `inventory.stock_push_failed`, `fulfillment.shipment_issue`, `billing.payment_failed`, `billing.subscription_expired`, `ai.provider_error`, `ai.credit_exhausted`, `general.page` — sinh tự động từ domain event (xem SPEC 0036 §4) hoặc do admin gửi (`general.page`). `level ∈ {info,warning,critical}`. `category ∈ {order,system,general}` quyết định tab hiển thị ở panel FE (Plan A, 2026-07-23).
```

Thêm mục mới sau dòng 439 (`GET /api/v1/announcements/active`), trước `#### Cấu hình trải nghiệm Pro`:

```markdown
#### Thông báo chung (General notification pages — Plan C, 2026-07-23)

Admin soạn "trang" nội dung (ưu đãi/tin chung — tiêu đề, ảnh bìa, thân bài TipTap sanitize, nút CTA), gửi tới tenant cụ thể hoặc tất cả. Fan-out qua `app_notifications` (category=`general`) — hiện ở tab "Chung" panel thông báo user, bấm mở tab trình duyệt mới. Gửi ngay/lên lịch đều qua queue job `DispatchGeneralNotificationPageJob` (audience có thể hàng nghìn tenant).

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/admin/general-notification-pages` | web + `auth:admin_web` | Danh sách (phân trang). |
| POST | `/api/v1/admin/general-notification-pages` | web + `auth:admin_web` | `{ title, body_html, cover_image_url?, cta_label?, cta_url?, audience_type: all\|tenant_ids, audience_tenant_ids?, scheduled_at?, expires_at? }` — sanitize `body_html`; có `scheduled_at` ⇒ `status=scheduled`, ngược lại `draft`. |
| PATCH | `/api/v1/admin/general-notification-pages/{id}` | web + `auth:admin_web` | Sửa (partial). `422 PAGE_ALREADY_SENT` nếu đã gửi. |
| DELETE | `/api/v1/admin/general-notification-pages/{id}` | web + `auth:admin_web` | Xoá. |
| POST | `/api/v1/admin/general-notification-pages/media` | web + `auth:admin_web` | multipart `file` (ảnh) → R2 `general-notification-pages/` → `{ data:{ url } }`. |
| POST | `/api/v1/admin/general-notification-pages/{id}/send` | web + `auth:admin_web` | Đưa job fan-out vào hàng đợi ngay. `422 PAGE_ALREADY_SENT` nếu đã gửi. |
| GET | `/api/v1/admin/general-notification-pages/{id}/stats` | web + `auth:admin_web` | `{ data:{ view_count, audience_tenant_count } }`. |
| GET | `/api/v1/notifications/general/{slug}` | `sanctum + verified + tenant` | Tenant xem nội dung đã nhận. `403` nếu tenant hiện tại không nằm trong audience đã gửi (không có `app_notifications` row tương ứng); `410` nếu `expires_at` đã qua; ghi lượt xem idempotent (`general_notification_page_views`, unique per user). |

Lịch gửi tự động: `notifications:dispatch-scheduled-general-pages` (mỗi phút, `app/routes/console.php`) quét `status=scheduled AND scheduled_at<=now()`.
```

- [ ] **Step 2: Commit docs**

```bash
git add docs/05-api/endpoints.md
git commit -m "docs(api): document general notification pages endpoints"
```

- [ ] **Step 3: Chạy toàn bộ quality gate**

```bash
cd app
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test
npm run lint && npm run typecheck && npm run build
```

Tất cả phải xanh. Ghi vào memory/deploy note: **CẦN DEPLOY + chạy `php artisan migrate`** trên prod (2 bảng mới, không cần backfill thủ công — khác Plan A).
