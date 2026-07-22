# Plan A — Phân loại `category` + panel thông báo 3 tab (Đơn hàng/Hệ thống/Chung) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm cột `category` (order/system/general) vào `app_notifications`, để module `Notifications` tự gán category theo `type`, và đổi panel thông báo (`NotificationBell.tsx`) từ 1 danh sách phẳng thành 3 tab lọc theo category — không đổi hành vi các loại thông báo hiện có (đơn hàng/gian hàng cần cấp quyền lại/ads monitor).

**Architecture:** `category` được suy ra tự động từ `type` qua 1 bảng ánh xạ tĩnh (`NotificationType::categoryFor()`), gọi bởi `NotificationDispatcher::dispatch()` — không cần sửa payload của 6 listener hiện có. Thêm 1 listener mới (`NotifyOnStockPushFailed`) để tab "Hệ thống" có nguồn dữ liệu thứ 2 ngoài `channel.reconnect_needed`. Đây là **Plan A trong loạt 3 plan độc lập** (spec: `docs/superpowers/specs/2026-07-23-notification-tabs-and-general-pages-design.md`) — Plan B thêm 4 nguồn lỗi hệ thống còn lại (vận đơn/tem, thanh toán/subscription, AI), Plan C xây tab "Chung" (admin soạn trang + gửi). Plan A tự chạy được, tự test được, deploy độc lập không phụ thuộc B/C.

**Tech Stack:** Laravel 11 (PHP 8.3+, PHPUnit, migration+Artisan command backfill), React 18 + TypeScript + Ant Design (Tabs) + TanStack Query.

## Global Constraints

- Chạy mọi lệnh PHP/Node từ `app/`, không phải repo root.
- PSR-4 `CMBcoreSeller\` map vào `app/app/`.
- Response envelope API: `{ "data": ..., "meta": ... }`.
- Module `Notifications` chỉ được nghe event của module khác qua `Event::listen` trong `NotificationsServiceProvider` — không `use` Services nội bộ của module khác (`docs/01-architecture/modules.md`).
- UI dùng font icon `@ant-design/icons`, không emoji (`ui-use-font-icons-not-emoji`).
- Không viết JS test mới (repo không có JS test runner) — chỉ `npm run typecheck && npm run build` để verify FE.
- Migration mới phải chạy `php artisan test` xanh trước khi coi là xong; **không tự động migrate prod** — đây là việc của bước deploy riêng, ngoài phạm vi plan này.
- Sau khi thêm route API mới, phải cập nhật `docs/05-api/endpoints.md` (Plan A không thêm route mới nên không áp dụng, nhưng Plan B/C sẽ cần).

---

## Task 1: Migration — thêm cột `category` vào `app_notifications`

**Files:**
- Create: `app/app/Modules/Notifications/Database/Migrations/2026_07_23_100000_add_category_to_app_notifications_table.php`
- Modify: `app/app/Modules/Notifications/Models/Notification.php`
- Test: `app/tests/Feature/Notifications/NotificationCategoryColumnTest.php`

**Interfaces:**
- Produces: cột `app_notifications.category` (string, mặc định `'system'`), 2 index mới `(tenant_id, user_id, category, id)` và `(tenant_id, user_id, category, read_at)`. `Notification::$fillable` có thêm `'category'`.

- [ ] **Step 1: Viết test trước (kỳ vọng cột tồn tại + default đúng)**

```php
<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationCategoryColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_column_exists(): void
    {
        $this->assertTrue(Schema::hasColumn('app_notifications', 'category'));
    }

    public function test_category_defaults_to_system_when_not_set(): void
    {
        $tenant = Tenant::create(['name' => 'CatShop']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $n = Notification::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->getKey(),
            'type' => 'channel.reconnect_needed',
            'title' => 'X',
        ]);

        $this->assertSame('system', $n->fresh()->category);
    }

    public function test_category_is_mass_assignable(): void
    {
        $tenant = Tenant::create(['name' => 'CatShop2']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $n = Notification::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->getKey(),
            'type' => 'order.cancelled',
            'title' => 'X',
            'category' => 'order',
        ]);

        $this->assertSame('order', $n->fresh()->category);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationCategoryColumnTest.php`
Expected: FAIL — `Schema::hasColumn` false hoặc "category" không có trong fillable (MassAssignmentException hoặc cột null).

- [ ] **Step 3: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan A (2026-07-23) — thêm `category` (order|system|general) để panel thông báo lọc
 * theo tab. Mặc định 'system' (an toàn cho loại chưa phân loại); giá trị THẬT được
 * NotificationDispatcher tự gán qua NotificationType::categoryFor() khi tạo mới — cột
 * default chỉ là fallback cho backfill (xem notifications:backfill-category, Task 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->string('category', 16)->default('system')->after('type');
        });

        Schema::table('app_notifications', function (Blueprint $table) {
            $table->index(['tenant_id', 'user_id', 'category', 'id']);
            $table->index(['tenant_id', 'user_id', 'category', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'user_id', 'category', 'id']);
            $table->dropIndex(['tenant_id', 'user_id', 'category', 'read_at']);
            $table->dropColumn('category');
        });
    }
};
```

- [ ] **Step 4: Thêm `category` vào `$fillable` của model**

Trong `app/app/Modules/Notifications/Models/Notification.php`, sửa dòng `protected $fillable`:

```php
    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'category', 'level', 'title', 'body', 'action_url', 'data', 'dedup_key', 'read_at',
    ];
```

Và thêm dòng PHPDoc `@property string $category` ngay dưới `@property string $type` (dòng 19 hiện tại).

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationCategoryColumnTest.php`
Expected: PASS (3 tests)

- [ ] **Step 6: Chạy toàn bộ test suite Notifications để chắc không phá gì cũ**

Run: `cd app && php artisan test --filter=Notification`
Expected: PASS (bao gồm `NotificationDispatcherTest`, `NotificationApiTest`, `NotificationListenersTest` hiện có)

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Notifications/Database/Migrations/2026_07_23_100000_add_category_to_app_notifications_table.php app/app/Modules/Notifications/Models/Notification.php app/tests/Feature/Notifications/NotificationCategoryColumnTest.php
git commit -m "feat(notifications): add category column to app_notifications"
```

---

## Task 2: Command backfill `category` cho dữ liệu cũ

**Files:**
- Create: `app/app/Modules/Notifications/Console/Commands/BackfillNotificationCategory.php`
- Modify: `app/app/Modules/Notifications/NotificationsServiceProvider.php`
- Test: `app/tests/Feature/Notifications/BackfillNotificationCategoryTest.php`

**Interfaces:**
- Consumes: cột `category` từ Task 1.
- Produces: lệnh Artisan `notifications:backfill-category` — cập nhật `category='order'` cho các row có `type` thuộc nhóm đơn hàng, giữ nguyên (`'system'`) cho các loại còn lại.

- [ ] **Step 1: Viết test trước**

```php
<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillNotificationCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeRow(int $tenantId, int $userId, string $type, string $category = 'system'): Notification
    {
        return Notification::create([
            'tenant_id' => $tenantId, 'user_id' => $userId, 'type' => $type,
            'category' => $category, 'title' => 'X',
        ]);
    }

    public function test_backfills_order_types_to_order_category(): void
    {
        $tenant = Tenant::create(['name' => 'BackfillShop']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $tid = (int) $tenant->getKey();
        $uid = (int) $user->getKey();

        $negative = $this->makeRow($tid, $uid, 'order.negative_total');
        $cancelled = $this->makeRow($tid, $uid, 'order.cancelled');
        $returnNew = $this->makeRow($tid, $uid, 'order.return_new');
        $channel = $this->makeRow($tid, $uid, 'channel.reconnect_needed');

        $this->artisan('notifications:backfill-category')->assertExitCode(0);

        $this->assertSame('order', $negative->fresh()->category);
        $this->assertSame('order', $cancelled->fresh()->category);
        $this->assertSame('order', $returnNew->fresh()->category);
        $this->assertSame('system', $channel->fresh()->category);
    }

    public function test_idempotent_second_run_changes_nothing(): void
    {
        $tenant = Tenant::create(['name' => 'BackfillShop2']);
        $user = User::factory()->create();
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);
        $row = $this->makeRow((int) $tenant->getKey(), (int) $user->getKey(), 'order.cancelled');

        $this->artisan('notifications:backfill-category')->assertExitCode(0);
        $this->assertSame('order', $row->fresh()->category);

        $this->artisan('notifications:backfill-category')->assertExitCode(0);
        $this->assertSame('order', $row->fresh()->category);
    }

    public function test_scans_across_all_tenants_without_scope_leak(): void
    {
        $t1 = Tenant::create(['name' => 'T1']);
        $u1 = User::factory()->create();
        $t1->users()->attach($u1->getKey(), ['role' => Role::Owner->value]);
        $t2 = Tenant::create(['name' => 'T2']);
        $u2 = User::factory()->create();
        $t2->users()->attach($u2->getKey(), ['role' => Role::Owner->value]);

        $row1 = $this->makeRow((int) $t1->getKey(), (int) $u1->getKey(), 'order.cancelled');
        $row2 = $this->makeRow((int) $t2->getKey(), (int) $u2->getKey(), 'order.negative_total');

        $this->artisan('notifications:backfill-category')->assertExitCode(0);

        $this->assertSame('order', Notification::withoutGlobalScope(TenantScope::class)->find($row1->id)->category);
        $this->assertSame('order', Notification::withoutGlobalScope(TenantScope::class)->find($row2->id)->category);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/BackfillNotificationCategoryTest.php`
Expected: FAIL — lệnh `notifications:backfill-category` chưa tồn tại.

- [ ] **Step 3: Viết command**

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Console\Commands;

use CMBcoreSeller\Modules\Notifications\Models\Notification;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Plan A (2026-07-23) — chạy 1 LẦN sau migration thêm cột `category` (Task 1), để gán
 * category='order' cho row cũ có type thuộc nhóm đơn hàng (cột mới mặc định 'system').
 * Idempotent — chạy lại không đổi gì. Quét toàn bộ tenant (withoutGlobalScope vì chạy
 * ngoài request context, không có tenant hiện tại).
 */
class BackfillNotificationCategory extends Command
{
    protected $signature = 'notifications:backfill-category';

    protected $description = 'Backfill category=order cho app_notifications cũ có type thuộc nhóm đơn hàng (Plan A, 2026-07-23)';

    private const ORDER_TYPES = ['order.negative_total', 'order.cancelled', 'order.return_new'];

    public function handle(): int
    {
        $updated = 0;
        Notification::withoutGlobalScope(TenantScope::class)
            ->whereIn('type', self::ORDER_TYPES)
            ->where('category', '!=', 'order')
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows) use (&$updated) {
                foreach ($rows as $row) {
                    $row->forceFill(['category' => 'order'])->save();
                    $updated++;
                }
            });

        $this->info("Đã backfill category='order' cho {$updated} thông báo.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Đăng ký command trong provider**

Trong `app/app/Modules/Notifications/NotificationsServiceProvider.php`, thêm import và đăng ký trong `boot()`:

```php
use CMBcoreSeller\Modules\Notifications\Console\Commands\BackfillNotificationCategory;
```

Thêm cuối method `boot()` (sau dòng `Event::listen(AdMonitorActionTaken::class, ...)`):

```php
        if ($this->app->runningInConsole()) {
            $this->commands([BackfillNotificationCategory::class]);
        }
```

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/BackfillNotificationCategoryTest.php`
Expected: PASS (3 tests)

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Notifications/Console/Commands/BackfillNotificationCategory.php app/app/Modules/Notifications/NotificationsServiceProvider.php app/tests/Feature/Notifications/BackfillNotificationCategoryTest.php
git commit -m "feat(notifications): add notifications:backfill-category command"
```

---

## Task 3: `NotificationType::categoryFor()` — ánh xạ type → category

**Files:**
- Modify: `app/app/Modules/Notifications/Support/NotificationType.php`
- Test: `app/tests/Unit/Notifications/NotificationTypeCategoryTest.php`

**Interfaces:**
- Produces: `NotificationType::CATEGORY_ORDER = 'order'`, `NotificationType::CATEGORY_SYSTEM = 'system'`, `NotificationType::CATEGORY_GENERAL = 'general'`, `NotificationType::categoryFor(string $type): string` (static). `NotificationType::INVENTORY_STOCK_PUSH_FAILED = 'inventory.stock_push_failed'` (dùng ở Task 5).

- [ ] **Step 1: Viết test trước**

Tạo thư mục `app/tests/Unit/Notifications/` nếu chưa có.

```php
<?php

namespace Tests\Unit\Notifications;

use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Tests\TestCase;

class NotificationTypeCategoryTest extends TestCase
{
    public function test_order_types_map_to_order_category(): void
    {
        $this->assertSame('order', NotificationType::categoryFor(NotificationType::ORDER_NEGATIVE_TOTAL));
        $this->assertSame('order', NotificationType::categoryFor(NotificationType::ORDER_CANCELLED));
        $this->assertSame('order', NotificationType::categoryFor(NotificationType::ORDER_RETURN_NEW));
    }

    public function test_system_types_map_to_system_category(): void
    {
        $this->assertSame('system', NotificationType::categoryFor(NotificationType::CHANNEL_RECONNECT_NEEDED));
        $this->assertSame('system', NotificationType::categoryFor(NotificationType::ADS_MONITOR_APPROACHING));
        $this->assertSame('system', NotificationType::categoryFor(NotificationType::ADS_MONITOR_ACTION));
        $this->assertSame('system', NotificationType::categoryFor(NotificationType::INVENTORY_STOCK_PUSH_FAILED));
    }

    public function test_unknown_type_falls_back_to_system(): void
    {
        $this->assertSame('system', NotificationType::categoryFor('some.unmapped.type'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Unit/Notifications/NotificationTypeCategoryTest.php`
Expected: FAIL — `categoryFor` và `INVENTORY_STOCK_PUSH_FAILED` chưa tồn tại.

- [ ] **Step 3: Sửa `NotificationType.php`**

Thay toàn bộ nội dung file `app/app/Modules/Notifications/Support/NotificationType.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Support;

/**
 * Hằng số loại thông báo in-app (SPEC 0036 §4). Thêm loại mới = thêm hằng số ở đây
 * + 1 dòng trong CATEGORY_MAP + 1 listener trong `Notifications\Listeners` lắng nghe
 * domain event tương ứng — KHÔNG sửa core. FE map type → icon trong
 * `components/NotificationBell.tsx`.
 *
 * `category` (order|system|general) quyết định notification rơi vào tab nào ở panel FE
 * (Plan A, 2026-07-23) — được `NotificationDispatcher` tự gán qua `categoryFor()`, các
 * listener KHÔNG cần truyền `category` trong payload.
 */
final class NotificationType
{
    public const CATEGORY_ORDER = 'order';

    public const CATEGORY_SYSTEM = 'system';

    public const CATEGORY_GENERAL = 'general';

    /** Liên kết sàn/Facebook hết hiệu lực, cần kết nối lại. */
    public const CHANNEL_RECONNECT_NEEDED = 'channel.reconnect_needed';

    /** Đơn có tổng tiền âm. */
    public const ORDER_NEGATIVE_TOTAL = 'order.negative_total';

    /** Đơn chuyển sang trạng thái đã hủy. */
    public const ORDER_CANCELLED = 'order.cancelled';

    /** Có yêu cầu hủy/hoàn mới (after-sales Requested). */
    public const ORDER_RETURN_NEW = 'order.return_new';

    /** Chiến dịch quảng cáo sắp đạt ngưỡng cần tắt. */
    public const ADS_MONITOR_APPROACHING = 'ads.monitor_approaching';

    /** AdMonitor đã tự động tạm dừng / tăng ngân sách chiến dịch. */
    public const ADS_MONITOR_ACTION = 'ads.monitor_action';

    /** Đẩy tồn kho lên sàn thất bại (Plan A, 2026-07-23). */
    public const INVENTORY_STOCK_PUSH_FAILED = 'inventory.stock_push_failed';

    /** @var array<string,string> type => category */
    private const CATEGORY_MAP = [
        self::ORDER_NEGATIVE_TOTAL => self::CATEGORY_ORDER,
        self::ORDER_CANCELLED => self::CATEGORY_ORDER,
        self::ORDER_RETURN_NEW => self::CATEGORY_ORDER,
        self::CHANNEL_RECONNECT_NEEDED => self::CATEGORY_SYSTEM,
        self::ADS_MONITOR_APPROACHING => self::CATEGORY_SYSTEM,
        self::ADS_MONITOR_ACTION => self::CATEGORY_SYSTEM,
        self::INVENTORY_STOCK_PUSH_FAILED => self::CATEGORY_SYSTEM,
    ];

    /** Type không có trong map ⇒ mặc định 'system' (an toàn hơn 'order'/'general'). */
    public static function categoryFor(string $type): string
    {
        return self::CATEGORY_MAP[$type] ?? self::CATEGORY_SYSTEM;
    }

    private function __construct() {}
}
```

- [ ] **Step 4: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Unit/Notifications/NotificationTypeCategoryTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Notifications/Support/NotificationType.php app/tests/Unit/Notifications/NotificationTypeCategoryTest.php
git commit -m "feat(notifications): add NotificationType::categoryFor() mapping"
```

---

## Task 4: `NotificationDispatcher` tự gán `category` khi tạo notification

**Files:**
- Modify: `app/app/Modules/Notifications/Services/NotificationDispatcher.php`
- Test: `app/tests/Feature/Notifications/NotificationDispatcherTest.php` (thêm test vào file có sẵn)

**Interfaces:**
- Consumes: `NotificationType::categoryFor()` (Task 3).
- Produces: mọi `Notification` tạo qua `NotificationDispatcher::dispatch()` có `category` đúng theo `type` — không cần payload truyền `category`.

- [ ] **Step 1: Thêm test vào `NotificationDispatcherTest.php`**

Thêm method mới vào cuối class (trước dấu `}` đóng class, dòng 101):

```php
    public function test_dispatch_auto_assigns_category_from_type(): void
    {
        $tenant = $this->makeTenantWithUsers(1);

        app(NotificationDispatcher::class)->dispatch((int) $tenant->getKey(), [
            'type' => 'order.cancelled',
            'title' => 'Đơn đã hủy',
        ]);

        $n = Notification::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->first();
        $this->assertSame('order', $n->category);
    }

    public function test_dispatch_ignores_category_in_payload_and_derives_from_type(): void
    {
        $tenant = $this->makeTenantWithUsers(1);

        // Dù payload cố tình truyền category sai, dispatcher vẫn tự suy ra đúng từ type
        // (category không phải input tin cậy từ listener — tránh 6 listener hiện có phải
        // sửa để truyền đúng field này).
        app(NotificationDispatcher::class)->dispatch((int) $tenant->getKey(), [
            'type' => 'channel.reconnect_needed',
            'title' => 'X',
            'category' => 'order',
        ]);

        $n = Notification::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->first();
        $this->assertSame('system', $n->category);
    }
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationDispatcherTest.php --filter=category`
Expected: FAIL — `category` vẫn là default `'system'` cho test đầu (đúng ra đúng vì fallback, nên test 1 có thể PASS giả — kiểm tra kỹ: `order.cancelled` phải ra `'order'`, nhưng dispatcher CHƯA gọi `categoryFor()` nên category vẫn `'system'` mặc định của cột ⇒ test 1 FAIL đúng như kỳ vọng).

- [ ] **Step 3: Sửa `NotificationDispatcher::dispatch()`**

Trong `app/app/Modules/Notifications/Services/NotificationDispatcher.php`, thêm import:

```php
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
```

Sửa khối tạo `Notification::create([...])` (dòng 40-50) — thêm dòng `'category'`:

```php
            $notification = Notification::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'type' => $payload['type'],
                'category' => NotificationType::categoryFor($payload['type']),
                'level' => $payload['level'] ?? Notification::LEVEL_INFO,
                'title' => $payload['title'],
                'body' => $payload['body'] ?? null,
                'action_url' => $payload['action_url'] ?? null,
                'data' => $payload['data'] ?? null,
                'dedup_key' => $dedup,
            ]);
```

Cập nhật docblock tham số `$payload` (dòng 19) để không gợi ý `category` là input hợp lệ — giữ nguyên vì `category` chưa từng có trong docblock, không cần sửa.

- [ ] **Step 4: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationDispatcherTest.php`
Expected: PASS (tất cả test trong file, kể cả 2 test mới)

- [ ] **Step 5: Chạy lại toàn bộ test Notifications**

Run: `cd app && php artisan test --filter=Notification`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Notifications/Services/NotificationDispatcher.php app/tests/Feature/Notifications/NotificationDispatcherTest.php
git commit -m "feat(notifications): dispatcher auto-assigns category via NotificationType::categoryFor()"
```

---

## Task 5: API — lọc theo `category` + `unread_count_by_category`

**Files:**
- Modify: `app/app/Modules/Notifications/Http/Controllers/NotificationController.php`
- Modify: `app/app/Modules/Notifications/Http/Resources/NotificationResource.php`
- Test: `app/tests/Feature/Notifications/NotificationApiTest.php` (thêm test vào file có sẵn)

**Interfaces:**
- Produces: `GET /api/v1/notifications?category=order|system|general` lọc danh sách; `meta.unread_count_by_category = {order, system, general}` luôn trả đủ 3 khóa (0 nếu không có); `NotificationResource` có thêm field `category`.

- [ ] **Step 1: Thêm test vào `NotificationApiTest.php`**

Sửa `makeNotif()` (dòng 41-51) để nhận `category` tuỳ chọn:

```php
    private function makeNotif(int $userId, bool $read = false, string $type = 'order.cancelled', string $category = 'order'): Notification
    {
        return Notification::create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $userId,
            'type' => $type,
            'category' => $category,
            'level' => 'info',
            'title' => 'Đơn đã hủy',
            'read_at' => $read ? now() : null,
        ]);
    }
```

Thêm 2 test method mới vào cuối class (trước `}` đóng class):

```php
    public function test_index_filters_by_category(): void
    {
        $this->makeNotif($this->owner->getKey(), category: 'order');
        $this->makeNotif($this->owner->getKey(), type: 'channel.reconnect_needed', category: 'system');

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/notifications?category=system');

        $res->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'system');
    }

    public function test_index_returns_unread_count_by_category(): void
    {
        $this->makeNotif($this->owner->getKey(), category: 'order');
        $this->makeNotif($this->owner->getKey(), category: 'order', read: true);
        $this->makeNotif($this->owner->getKey(), type: 'channel.reconnect_needed', category: 'system');

        $res = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/notifications');

        $res->assertOk()
            ->assertJsonPath('meta.unread_count_by_category.order', 1)
            ->assertJsonPath('meta.unread_count_by_category.system', 1)
            ->assertJsonPath('meta.unread_count_by_category.general', 0)
            ->assertJsonPath('meta.unread_count', 2);
    }
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationApiTest.php --filter=category`
Expected: FAIL — `?category=` chưa được lọc, `meta.unread_count_by_category` không tồn tại.

- [ ] **Step 3: Sửa `NotificationController.php`**

Thay toàn bộ nội dung file:

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Notifications\Http\Resources\NotificationResource;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chuông thông báo in-app của user trong tenant hiện tại (SPEC 0036, Plan A mở rộng
 * category 2026-07-23). TenantScope + filter `user_id` đảm bảo chỉ thấy thông báo của
 * CHÍNH MÌNH trong tenant hiện tại. Controller mỏng — không cần Service riêng.
 */
class NotificationController extends Controller
{
    private const CATEGORIES = ['order', 'system', 'general'];

    /**
     * Danh sách (mới nhất trước); `?status=unread` lọc chưa đọc, `?category=` lọc theo
     * tab panel FE. `meta.unread_count` = tổng (không lọc); `meta.unread_count_by_category`
     * luôn trả đủ 3 khóa để FE hiện badge riêng từng tab.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()?->getKey();
        $limit = max(1, min(100, (int) $request->query('limit', 30)));

        $query = Notification::query()->where('user_id', $userId);
        if ($request->query('status') === 'unread') {
            $query->whereNull('read_at');
        }
        $category = $request->query('category');
        if (is_string($category) && in_array($category, self::CATEGORIES, true)) {
            $query->where('category', $category);
        }
        $items = $query->latest('id')->limit($limit)->get();

        return response()->json([
            'data' => NotificationResource::collection($items)->resolve(),
            'meta' => [
                'unread_count' => $this->unreadCount($userId),
                'unread_count_by_category' => $this->unreadCountByCategory($userId),
            ],
        ]);
    }

    /** Đánh dấu đã đọc 1 thông báo; trả `unread_count` còn lại. */
    public function read(Request $request, string $id): JsonResponse
    {
        $userId = (int) $request->user()?->getKey();
        $notification = Notification::query()->where('user_id', $userId)->findOrFail((int) $id);
        if ($notification->read_at === null) {
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json(['data' => ['unread_count' => $this->unreadCount($userId)]]);
    }

    /** Đánh dấu đã đọc tất cả; trả `unread_count` (=0). */
    public function readAll(Request $request): JsonResponse
    {
        $userId = (int) $request->user()?->getKey();
        Notification::query()->where('user_id', $userId)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['data' => ['unread_count' => 0]]);
    }

    private function unreadCount(int $userId): int
    {
        return Notification::query()->where('user_id', $userId)->whereNull('read_at')->count();
    }

    /** @return array{order:int,system:int,general:int} */
    private function unreadCountByCategory(int $userId): array
    {
        $counts = Notification::query()
            ->where('user_id', $userId)->whereNull('read_at')
            ->selectRaw('category, count(*) as c')->groupBy('category')
            ->pluck('c', 'category');

        return [
            'order' => (int) ($counts['order'] ?? 0),
            'system' => (int) ($counts['system'] ?? 0),
            'general' => (int) ($counts['general'] ?? 0),
        ];
    }
}
```

- [ ] **Step 4: Sửa `NotificationResource.php`**

Thêm dòng `'category' => $this->category,` ngay sau dòng `'type' => $this->type,`:

```php
            'id' => $this->id,
            'type' => $this->type,
            'category' => $this->category,
            'level' => $this->level,
```

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationApiTest.php`
Expected: PASS (tất cả, kể cả 2 test mới)

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Notifications/Http/Controllers/NotificationController.php app/app/Modules/Notifications/Http/Resources/NotificationResource.php app/tests/Feature/Notifications/NotificationApiTest.php
git commit -m "feat(notifications): filter by category + unread_count_by_category in API"
```

---

## Task 6: Listener mới — `NotifyOnStockPushFailed`

**Files:**
- Create: `app/app/Modules/Notifications/Listeners/NotifyOnStockPushFailed.php`
- Modify: `app/app/Modules/Notifications/NotificationsServiceProvider.php`
- Test: `app/tests/Feature/Notifications/NotificationListenersTest.php` (thêm test vào file có sẵn)

**Interfaces:**
- Consumes: event `CMBcoreSeller\Modules\Inventory\Events\StockPushed` (đã tồn tại, field `listing: ChannelListing`, `desired: int`, `ok: bool`).
- Produces: notification `type=inventory.stock_push_failed`, `category=system`, `action_url=/inventory`, `dedup_key='inventory.stock_push_failed:{listing_id}'`.

- [ ] **Step 1: Thêm test vào `NotificationListenersTest.php`**

Thêm import ở đầu file (sau dòng `use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnNegativeOrder;`):

```php
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnStockPushFailed;
use CMBcoreSeller\Modules\Inventory\Events\StockPushed;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
```

Thêm test method mới vào cuối class (trước `}` đóng class):

```php
    public function test_stock_push_failed_creates_system_notification(): void
    {
        $listing = (new ChannelListing)->forceFill([
            'id' => 55, 'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => 1,
            'seller_sku' => 'SKU-X', 'title' => 'Áo thun nam', 'sync_error' => 'Sàn từ chối: hết hạn mức API',
        ]);

        (new NotifyOnStockPushFailed(app(NotificationDispatcher::class)))
            ->handle(new StockPushed($listing, 10, false));

        $n = $this->notifications()->first();
        $this->assertSame(NotificationType::INVENTORY_STOCK_PUSH_FAILED, $n->type);
        $this->assertSame('system', $n->category);
        $this->assertSame('warning', $n->level);
        $this->assertStringContainsString('Áo thun nam', $n->title);
        $this->assertSame('inventory.stock_push_failed:55', $n->dedup_key);
    }

    public function test_stock_push_success_creates_nothing(): void
    {
        $listing = (new ChannelListing)->forceFill([
            'id' => 56, 'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => 1,
            'seller_sku' => 'SKU-Y', 'title' => 'Quần jean',
        ]);

        (new NotifyOnStockPushFailed(app(NotificationDispatcher::class)))
            ->handle(new StockPushed($listing, 10, true));

        $this->assertCount(0, $this->notifications());
    }
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationListenersTest.php --filter=stock_push`
Expected: FAIL — class `NotifyOnStockPushFailed` chưa tồn tại.

- [ ] **Step 3: Viết listener**

```php
<?php

namespace CMBcoreSeller\Modules\Notifications\Listeners;

use CMBcoreSeller\Modules\Inventory\Events\StockPushed;
use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Plan A (2026-07-23) — đẩy tồn kho lên sàn thất bại ⇒ thông báo in-app tab "Hệ thống".
 * Dedup theo channel_listing id ⇒ không spam mỗi lần job PushStockToListing retry, tới
 * khi user đọc/đẩy lại thành công (đọc rồi thì event fail tiếp sẽ tạo lại — hành vi dedup
 * chuẩn của NotificationDispatcher).
 */
class NotifyOnStockPushFailed implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private NotificationDispatcher $dispatcher) {}

    public function handle(StockPushed $event): void
    {
        if ($event->ok) {
            return;
        }

        $listing = $event->listing;
        $name = $listing->title ?: $listing->seller_sku;

        $this->dispatcher->dispatch((int) $listing->tenant_id, [
            'type' => NotificationType::INVENTORY_STOCK_PUSH_FAILED,
            'level' => 'warning',
            'title' => "Đẩy tồn kho \"{$name}\" lên sàn thất bại",
            'body' => $listing->sync_error ?: 'Vui lòng kiểm tra lại kết nối gian hàng và thử đẩy tồn lại.',
            'action_url' => '/inventory',
            'data' => [
                'channel_listing_id' => (int) $listing->getKey(),
                'seller_sku' => $listing->seller_sku,
                'desired_qty' => $event->desired,
            ],
            'dedup_key' => 'inventory.stock_push_failed:'.$listing->getKey(),
        ]);
    }
}
```

- [ ] **Step 4: Đăng ký listener trong provider**

Trong `app/app/Modules/Notifications/NotificationsServiceProvider.php`, thêm import:

```php
use CMBcoreSeller\Modules\Inventory\Events\StockPushed;
use CMBcoreSeller\Modules\Notifications\Listeners\NotifyOnStockPushFailed;
```

Thêm dòng đăng ký ngay sau `Event::listen(AdMonitorActionTaken::class, NotifyOnAdMonitorAction::class);`:

```php
        Event::listen(StockPushed::class, NotifyOnStockPushFailed::class);
```

- [ ] **Step 5: Chạy test để xác nhận PASS**

Run: `cd app && vendor/bin/phpunit tests/Feature/Notifications/NotificationListenersTest.php`
Expected: PASS (tất cả, kể cả 2 test mới)

- [ ] **Step 6: Chạy toàn bộ test Notifications + Inventory (không phá listener cũ `RecordStockPushLog`)**

Run: `cd app && php artisan test --filter=Notification && php artisan test --filter=StockPush`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Notifications/Listeners/NotifyOnStockPushFailed.php app/app/Modules/Notifications/NotificationsServiceProvider.php app/tests/Feature/Notifications/NotificationListenersTest.php
git commit -m "feat(notifications): notify on stock push failure (system tab)"
```

---

## Task 7: FE — `lib/notifications.ts` hỗ trợ `category`

**Files:**
- Modify: `app/resources/js/lib/notifications.ts`

**Interfaces:**
- Consumes: API Task 5 (`GET /notifications?category=`, `meta.unread_count_by_category`).
- Produces: `useNotifications(category?: NotificationCategory)`, type `NotificationCategory = 'order' | 'system' | 'general'`, `AppNotification.category`, `NotificationsResponse.meta.unread_count_by_category`.

- [ ] **Step 1: Sửa `notifications.ts`**

Thay các đoạn sau trong `app/resources/js/lib/notifications.ts`:

Thêm type mới ngay dưới `export type NotificationLevel = 'info' | 'warning' | 'critical';` (dòng 13):

```ts
export type NotificationCategory = 'order' | 'system' | 'general';
```

Sửa interface `AppNotification` (dòng 15-26) — thêm field `category`:

```ts
export interface AppNotification {
    id: number;
    type: string;
    category: NotificationCategory;
    level: NotificationLevel;
    title: string;
    body: string | null;
    action_url: string | null;
    data: Record<string, unknown> | null;
    is_read: boolean;
    read_at: string | null;
    created_at: string | null;
}
```

Sửa interface `NotificationsResponse` (dòng 28-31):

```ts
interface NotificationsResponse {
    data: AppNotification[];
    meta: { unread_count: number; unread_count_by_category: Record<NotificationCategory, number> };
}
```

Sửa hàm `useNotifications` (dòng 38-49) để nhận tham số `category` tuỳ chọn và đưa vào queryKey + params:

```ts
/** Danh sách + số chưa đọc cho chuông. `category` lọc theo tab panel FE. Poll fallback khi Reverb tắt. */
export function useNotifications(category?: NotificationCategory) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['notifications', 'list', tenantId, category ?? 'all'],
        queryFn: async () =>
            (await api!.get<NotificationsResponse>('/notifications', { params: { limit: 30, category } })).data,
        enabled: api != null,
        // Reverb đẩy realtime ⇒ poll thưa; tắt Reverb ⇒ poll dày hơn để chuông không "đứng".
        refetchInterval: (query) => (query.state.status === 'error' ? false : (realtimeEnabled() ? 60_000 : 30_000)),
    });
}
```

Sửa `useMarkNotificationRead` và `useMarkAllNotificationsRead` — đổi `invalidateQueries` từ khoá cứng `['notifications', 'list', tenantId]` sang khoá tiền tố (invalidate mọi category cùng lúc vì đọc 1 mục có thể đổi badge của tab khác — không xảy ra thật nhưng an toàn và đơn giản hơn theo dõi riêng từng tab):

```ts
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['notifications', 'list', tenantId] }),
```

(giữ nguyên 2 dòng này — TanStack Query mặc định invalidate theo tiền tố khớp, `['notifications','list',tenantId]` vẫn khớp cả `['notifications','list',tenantId,'order']` lẫn `['notifications','list',tenantId,'all']` vì đây là partial match theo mảng — không cần sửa gì thêm ở 2 hook này).

Sửa `useNotificationsRealtime` — đổi `invalidateQueries` (dòng 92) tương tự để invalidate mọi tab khi có notification mới qua realtime:

```ts
        echo.private(channelName).listen('.notification.created', () => {
            void qc.invalidateQueries({ queryKey: ['notifications', 'list', tenantId] });
        });
```

(không đổi — đã đúng theo lý do partial-match ở trên, chỉ xác nhận không cần sửa).

- [ ] **Step 2: Kiểm tra kiểu (không có JS test runner, dùng typecheck)**

Run: `cd app && npm run typecheck`
Expected: PASS — không lỗi type ở `notifications.ts` hay nơi import nó.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/notifications.ts
git commit -m "feat(notifications-fe): add category param + unread_count_by_category types"
```

---

## Task 8: Docs — `docs/05-api/endpoints.md`

**Files:**
- Modify: `docs/05-api/endpoints.md`

**Interfaces:** không có — chỉ cập nhật tài liệu theo đúng luật CLAUDE.md ("route mới/đổi response phải cập nhật endpoints.md").

- [ ] **Step 1: Cập nhật mục "Thông báo in-app"**

Trong `docs/05-api/endpoints.md`, sửa dòng bảng `GET /api/v1/notifications` (dòng 202 hiện tại):

```
| GET | `/api/v1/notifications?status=unread&category=order&limit=30` | sanctum + tenant | `{ data:[{ id, type, category, level, title, body, action_url, data, is_read, read_at, created_at }], meta:{ unread_count, unread_count_by_category:{order,system,general} } }`. `status=unread` lọc chưa đọc; `category` lọc theo tab panel FE (order\|system\|general), bỏ trống = tất cả. |
```

Sửa dòng ghi chú `type` v1 (dòng 206 hiện tại) — thêm `inventory.stock_push_failed`:

```
Loại (`type`) v1: `channel.reconnect_needed`, `order.negative_total`, `order.cancelled`, `order.return_new`, `ads.monitor_approaching`, `ads.monitor_action`, `inventory.stock_push_failed` — sinh tự động từ domain event (xem SPEC 0036 §4). `level ∈ {info,warning,critical}`. `category ∈ {order,system,general}` quyết định tab hiển thị ở panel FE (Plan A, 2026-07-23) — suy tự động từ `type` qua `NotificationType::categoryFor()`.
```

(Plan B sẽ nối thêm 4 type mới, Plan C thêm `general.page` — mỗi plan tự cập nhật dòng này khi thực thi; nếu nội dung dòng đã khác do plan khác chạy trước, sửa theo đúng state hiện tại của file thay vì chép y nguyên đoạn trên.)

- [ ] **Step 2: Commit**

```bash
git add docs/05-api/endpoints.md
git commit -m "docs(api): document category filter + unread_count_by_category on notifications endpoint"
```

---

## Task 9: FE — `NotificationBell.tsx` panel 3 tab

**Files:**
- Modify: `app/resources/js/components/NotificationBell.tsx`

**Interfaces:**
- Consumes: `useNotifications(category)` (Task 7).
- Produces: panel với 3 tab **Đơn hàng / Hệ thống / Chung**, mỗi tab hiện số chưa đọc riêng; click mục ở tab Chung mở tab trình duyệt mới (hành vi đầy đủ của tab Chung sẽ hoàn thiện ở Plan C — task này chỉ đặt sẵn nhánh `category === 'general'`).

- [ ] **Step 1: Thay toàn bộ nội dung `NotificationBell.tsx`**

```tsx
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Badge, Button, Empty, List, Popover, Spin, Tabs, Tooltip, Typography } from 'antd';
import {
    BellOutlined,
    CheckOutlined,
    ExclamationCircleTwoTone,
    InfoCircleTwoTone,
    WarningTwoTone,
} from '@ant-design/icons';
import {
    useMarkAllNotificationsRead,
    useMarkNotificationRead,
    useNotifications,
    type AppNotification,
    type NotificationCategory,
    type NotificationLevel,
} from '@/lib/notifications';

/** Icon theo mức độ (font icon @ant-design/icons — không dùng emoji). */
function levelIcon(level: NotificationLevel) {
    if (level === 'critical') return <ExclamationCircleTwoTone twoToneColor="#ff4d4f" />;
    if (level === 'warning') return <WarningTwoTone twoToneColor="#faad14" />;
    return <InfoCircleTwoTone twoToneColor="#1677ff" />;
}

/** Thời gian tương đối ngắn gọn tiếng Việt. */
function timeAgo(iso: string | null): string {
    if (!iso) return '';
    const diff = Date.now() - new Date(iso).getTime();
    const m = Math.floor(diff / 60_000);
    if (m < 1) return 'vừa xong';
    if (m < 60) return `${m} phút trước`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h} giờ trước`;
    const d = Math.floor(h / 24);
    return `${d} ngày trước`;
}

const TABS: { key: NotificationCategory; label: string }[] = [
    { key: 'order', label: 'Đơn hàng' },
    { key: 'system', label: 'Hệ thống' },
    { key: 'general', label: 'Chung' },
];

function TabLabel({ label, unread }: { label: string; unread: number }) {
    return (
        <span>
            {label}
            {unread > 0 ? <Badge count={unread} size="small" overflowCount={99} style={{ marginLeft: 6 }} /> : null}
        </span>
    );
}

/**
 * Chuông thông báo in-app (SPEC 0036, Plan A mở rộng 3 tab 2026-07-23) — Badge số chưa đọc
 * tổng + Popover danh sách 3 tab (Đơn hàng/Hệ thống/Chung). Click 1 mục → đánh dấu đã đọc +
 * điều hướng `action_url` (tab Chung mở tab trình duyệt mới, còn lại điều hướng cùng tab).
 * Realtime do `useNotificationsRealtime` (mount ở AppLayout) lo; component này chỉ đọc
 * cache + thao tác đọc.
 */
export function NotificationBell() {
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const [activeTab, setActiveTab] = useState<NotificationCategory>('order');
    const { data, isLoading } = useNotifications(activeTab);
    const markRead = useMarkNotificationRead();
    const markAll = useMarkAllNotificationsRead();

    const items = data?.data ?? [];
    const unread = data?.meta.unread_count ?? 0;
    const unreadByCategory = data?.meta.unread_count_by_category;

    const onClickItem = (n: AppNotification) => {
        if (!n.is_read) markRead.mutate(n.id);
        if (n.category === 'general') {
            if (n.action_url) window.open(n.action_url, '_blank');
            return;
        }
        setOpen(false);
        if (n.action_url) navigate(n.action_url);
    };

    const content = (
        <div style={{ width: 380, maxWidth: '90vw' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '4px 4px 0' }}>
                <Typography.Text strong>Thông báo</Typography.Text>
                <Button
                    type="link" size="small" icon={<CheckOutlined />}
                    disabled={unread === 0 || markAll.isPending}
                    onClick={() => markAll.mutate()}
                >
                    Đọc tất cả
                </Button>
            </div>
            <Tabs
                size="small"
                activeKey={activeTab}
                onChange={(key) => setActiveTab(key as NotificationCategory)}
                items={TABS.map((t) => ({
                    key: t.key,
                    label: <TabLabel label={t.label} unread={unreadByCategory?.[t.key] ?? 0} />,
                }))}
            />
            {isLoading ? (
                <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
            ) : items.length === 0 ? (
                <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="Chưa có thông báo" style={{ padding: 16 }} />
            ) : (
                <List
                    size="small"
                    style={{ maxHeight: 420, overflowY: 'auto' }}
                    dataSource={items}
                    renderItem={(n) => (
                        <List.Item
                            onClick={() => onClickItem(n)}
                            style={{ cursor: 'pointer', alignItems: 'flex-start', background: n.is_read ? undefined : '#f0f7ff', padding: '8px 10px', borderRadius: 6 }}
                        >
                            <List.Item.Meta
                                avatar={levelIcon(n.level)}
                                title={<Typography.Text strong={!n.is_read} style={{ fontSize: 13 }}>{n.title}</Typography.Text>}
                                description={
                                    <span style={{ fontSize: 12, color: '#64748b' }}>
                                        {n.body ? <div>{n.body}</div> : null}
                                        <span>{timeAgo(n.created_at)}</span>
                                    </span>
                                }
                            />
                        </List.Item>
                    )}
                />
            )}
        </div>
    );

    return (
        <Popover content={content} trigger="click" open={open} onOpenChange={setOpen} placement="bottomRight">
            <Tooltip title="Thông báo">
                <Badge count={unread} size="small" overflowCount={99}>
                    <Button type="text" icon={<BellOutlined />} />
                </Badge>
            </Tooltip>
        </Popover>
    );
}
```

- [ ] **Step 2: Kiểm tra kiểu + build**

Run: `cd app && npm run typecheck && npm run build`
Expected: PASS — không lỗi type/build.

- [ ] **Step 3: Kiểm tra thủ công trên trình duyệt (golden path)**

Run: `cd app && composer dev` (hoặc `docker compose up -d` nếu dùng stack đầy đủ), đăng nhập `owner@demo.local` / `password`, mở chuông thông báo:
- Xác nhận 3 tab hiện đúng: Đơn hàng, Hệ thống, Chung.
- Tạo 1 đơn tổng tiền âm hoặc dùng seed data có sẵn (`order.negative_total`) → xuất hiện ở tab Đơn hàng, không xuất hiện ở tab Hệ thống.
- Nếu có gian hàng cần cấp quyền lại (hoặc tạo thủ công 1 row `channel.reconnect_needed` qua tinker) → xuất hiện ở tab Hệ thống.
- Badge số trên từng tab đúng số chưa đọc; badge chuông ngoài Popover = tổng 3 tab.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/components/NotificationBell.tsx
git commit -m "feat(notifications-fe): 3-tab panel (Đơn hàng/Hệ thống/Chung)"
```

---

## Hoàn tất Plan A

Sau Task 9: chạy toàn bộ quality gate trước khi coi Plan A xong:

```bash
cd app
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test
npm run lint && npm run typecheck && npm run build
```

Tất cả phải xanh. Ghi vào memory/deploy note: **CẦN DEPLOY + chạy `php artisan migrate` rồi `php artisan notifications:backfill-category` một lần trên prod** (theo đúng quy ước "deploy KHÔNG tự migrate" của repo — xem `prod-ops-ssh-and-deploy` memory).

Plan B (4 nguồn lỗi hệ thống còn lại) và Plan C (tab "Chung" đầy đủ) là các plan riêng, viết sau khi Plan A merge xong.
