# Admin Notification Emails Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Admin có thể quản lý nhiều email nhận thông báo nền tảng, mỗi email tự chọn nhận loại thông báo nào (v1: khách mở cuộc CSKH mới, user đăng ký + xác minh email), kiến trúc mở rộng thêm loại thông báo mới không cần sửa bảng/dispatcher/UI.

**Architecture:** Bảng riêng `admin_notification_recipients` + `admin_notification_subscriptions` (không JSON, không dùng chung bảng khác) trong `Modules/Admin`. Một `AdminNotificationDispatcher::notify($type, $context)` tra subscriptions rồi gửi `AdminAlertNotification` (Laravel `Notification` class, on-demand routing tới email tự do — không cần model `User`) qua queue `notifications`. Hai listener mới lắng nghe domain event của `Support` (mở cuộc mới) và event built-in `Verified` của Laravel, cả hai gọi dispatcher — không `use` Services nội bộ của module khác.

**Tech Stack:** Laravel 11 (Notification/Event/Eloquent), React 18 + Vite + Ant Design + TanStack Query (admin bundle).

## Global Constraints

- Bảng mới KHÔNG có `tenant_id`, KHÔNG dùng `BelongsToTenant`/`TenantScope` — đây là dữ liệu cấp nền tảng (spec §2).
- Lưu subscriptions ở bảng riêng `admin_notification_subscriptions` (không JSON, không dùng chung bảng nào khác — quyết định rõ ràng của người dùng, ghi đè bản nháp JSON ban đầu trong spec).
- Registry loại thông báo là hằng số PHP thuần (`NotificationTypeCatalog`), KHÔNG theo Connector/Registry pattern của tầng Integrations (spec §3).
- Mọi route mới gate `web + auth:admin_web` (khớp mọi route Admin khác), thêm vào nhóm middleware `throttle:60,1` đã có trong `Http/routes.php`.
- Dùng queue `notifications` có sẵn (không tạo queue mới) — `config('notifications.queue', 'notifications')`.
- FE: `Checkbox.Group` cho chọn nhiều loại thông báo (không dùng `Select`, theo quy ước UI hiện có trong repo), icon từ `@ant-design/icons` (không emoji).
- Mọi endpoint mới PHẢI thêm vào `docs/05-api/endpoints.md` (quy tắc CLAUDE.md).
- Không có JS test runner trong repo — verify FE bằng `npm run typecheck` + `npm run build` (từ `app/`), không viết test file `.test.tsx`.

---

### Task 1: Data model — bảng + models + registry loại thông báo

**Files:**
- Create: `app/app/Modules/Admin/Database/Migrations/2026_07_15_120000_create_admin_notification_recipients_table.php`
- Create: `app/app/Modules/Admin/Database/Migrations/2026_07_15_120001_create_admin_notification_subscriptions_table.php`
- Create: `app/app/Modules/Admin/Models/AdminNotificationRecipient.php`
- Create: `app/app/Modules/Admin/Models/AdminNotificationSubscription.php`
- Create: `app/app/Modules/Admin/Notifications/NotificationTypeCatalog.php`
- Test: `app/tests/Unit/Admin/NotificationTypeCatalogTest.php`
- Test: `app/tests/Feature/Admin/AdminNotificationRecipientModelTest.php`

**Interfaces:**
- Produces: `AdminNotificationRecipient` (fillable `email`,`label`,`is_active`; `hasMany` `subscriptions()`; scope `active()`; method `subscribedTo(string $type): bool`).
- Produces: `AdminNotificationSubscription` (fillable `admin_notification_recipient_id`,`notification_type`; `$timestamps = false`, `created_at` set thủ công khi tạo).
- Produces: `NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION = 'support.new_conversation'`, `NotificationTypeCatalog::AUTH_USER_VERIFIED = 'auth.user_verified'`, `NotificationTypeCatalog::all(): array<string,string>`, `NotificationTypeCatalog::isValid(string $type): bool`. Tasks 2-6 đều dùng các hằng số/method này.

- [ ] **Step 1: Viết test cho `NotificationTypeCatalog`**

```php
<?php

namespace Tests\Unit\Admin;

use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use Tests\TestCase;

class NotificationTypeCatalogTest extends TestCase
{
    public function test_all_returns_expected_codes(): void
    {
        $types = NotificationTypeCatalog::all();

        $this->assertArrayHasKey('support.new_conversation', $types);
        $this->assertArrayHasKey('auth.user_verified', $types);
        $this->assertSame(NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION, 'support.new_conversation');
        $this->assertSame(NotificationTypeCatalog::AUTH_USER_VERIFIED, 'auth.user_verified');
    }

    public function test_is_valid_rejects_unknown_code(): void
    {
        $this->assertTrue(NotificationTypeCatalog::isValid('support.new_conversation'));
        $this->assertTrue(NotificationTypeCatalog::isValid('auth.user_verified'));
        $this->assertFalse(NotificationTypeCatalog::isValid('made.up.type'));
    }
}
```

Lưu file `app/tests/Unit/Admin/NotificationTypeCatalogTest.php` (tạo thư mục `Unit/Admin` mới).

- [ ] **Step 2: Chạy test, xác nhận FAIL (class chưa tồn tại)**

Run (từ `app/`): `php artisan test --filter=NotificationTypeCatalogTest`
Expected: FAIL — `Class "CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog" not found`.

- [ ] **Step 3: Viết `NotificationTypeCatalog`**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Notifications;

/**
 * Danh sách loại thông báo admin nhận qua email (SPEC 2026-07-15). Hằng số PHP thuần —
 * KHÔNG theo Connector/Registry pattern của tầng Integrations (không có "provider" ở đây,
 * chỉ là danh sách code nội bộ). Thêm loại mới: thêm 1 const + 1 dòng nhãn ở `all()`.
 */
final class NotificationTypeCatalog
{
    public const SUPPORT_NEW_CONVERSATION = 'support.new_conversation';

    public const AUTH_USER_VERIFIED = 'auth.user_verified';

    /** @return array<string,string> code => nhãn tiếng Việt hiển thị FE */
    public static function all(): array
    {
        return [
            self::SUPPORT_NEW_CONVERSATION => 'Khách nhắn CSKH (mở cuộc hội thoại mới)',
            self::AUTH_USER_VERIFIED => 'Người dùng đăng ký & xác minh email thành công',
        ];
    }

    public static function isValid(string $type): bool
    {
        return array_key_exists($type, self::all());
    }
}
```

- [ ] **Step 4: Chạy lại test, xác nhận PASS**

Run: `php artisan test --filter=NotificationTypeCatalogTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Viết 2 migration**

```php
<?php
// app/app/Modules/Admin/Database/Migrations/2026_07_15_120000_create_admin_notification_recipients_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 2026-07-15 — email nhận thông báo cấp nền tảng. KHÔNG tenant-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_recipients');
    }
};
```

```php
<?php
// app/app/Modules/Admin/Database/Migrations/2026_07_15_120001_create_admin_notification_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 2026-07-15 — loại thông báo mỗi email admin đã bật. Bảng TÁCH RIÊNG, không JSON,
 * không dùng chung bảng nào khác (quyết định người dùng).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notification_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_notification_recipient_id')
                ->constrained('admin_notification_recipients')->cascadeOnDelete();
            $table->string('notification_type');
            $table->timestamp('created_at')->nullable();

            $table->unique(['admin_notification_recipient_id', 'notification_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_subscriptions');
    }
};
```

- [ ] **Step 6: Chạy migrate trên DB test (SQLite) để xác nhận migration hợp lệ**

Run: `php artisan migrate --env=testing` KHÔNG cần chạy thủ công — bước Step 8 (RefreshDatabase trong test) sẽ tự chạy toàn bộ migrations. Chỉ cần đảm bảo không có lỗi cú pháp: `php artisan migrate:status` sau khi chạy test ở Step 8 không báo lỗi là đủ xác nhận.

- [ ] **Step 7: Viết 2 model**

```php
<?php
// app/app/Modules/Admin/Models/AdminNotificationRecipient.php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Email nhận thông báo cấp nền tảng (SPEC 2026-07-15). KHÔNG tenant-scoped.
 *
 * @property int $id
 * @property string $email
 * @property ?string $label
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int,AdminNotificationSubscription> $subscriptions
 */
class AdminNotificationRecipient extends Model
{
    protected $fillable = ['email', 'label', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AdminNotificationSubscription::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** Yêu cầu `subscriptions` đã được load (eager/lazy) trước khi gọi. */
    public function subscribedTo(string $type): bool
    {
        return $this->subscriptions->contains('notification_type', $type);
    }
}
```

```php
<?php
// app/app/Modules/Admin/Models/AdminNotificationSubscription.php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 loại thông báo mà 1 recipient đã bật (SPEC 2026-07-15). Chỉ có `created_at`
 * (không `updated_at`) — hàng này không bao giờ update, chỉ tạo/xoá.
 *
 * @property int $id
 * @property int $admin_notification_recipient_id
 * @property string $notification_type
 */
class AdminNotificationSubscription extends Model
{
    public $timestamps = false;

    protected $fillable = ['admin_notification_recipient_id', 'notification_type', 'created_at'];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(AdminNotificationRecipient::class, 'admin_notification_recipient_id');
    }
}
```

- [ ] **Step 8: Viết feature test cho model (scope, helper, cascade delete)**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationRecipientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_and_subscribed_to(): void
    {
        $active = AdminNotificationRecipient::create(['email' => 'a@x.com', 'is_active' => true]);
        $inactive = AdminNotificationRecipient::create(['email' => 'b@x.com', 'is_active' => false]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $active->id,
            'notification_type' => 'support.new_conversation',
            'created_at' => now(),
        ]);

        $this->assertTrue($active->load('subscriptions')->subscribedTo('support.new_conversation'));
        $this->assertFalse($active->subscribedTo('auth.user_verified'));

        $activeIds = AdminNotificationRecipient::query()->active()->pluck('id')->all();
        $this->assertContains($active->id, $activeIds);
        $this->assertNotContains($inactive->id, $activeIds);
    }

    public function test_deleting_recipient_cascades_subscriptions(): void
    {
        $recipient = AdminNotificationRecipient::create(['email' => 'c@x.com']);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $recipient->id,
            'notification_type' => 'auth.user_verified',
            'created_at' => now(),
        ]);

        $recipient->delete();

        $this->assertSame(
            0,
            AdminNotificationSubscription::query()->where('admin_notification_recipient_id', $recipient->id)->count(),
        );
    }
}
```

- [ ] **Step 9: Chạy toàn bộ test của Task 1, xác nhận PASS**

Run: `php artisan test --filter=NotificationTypeCatalogTest && php artisan test --filter=AdminNotificationRecipientModelTest`
Expected: PASS (2 + 2 = 4 tests, 0 failures).

- [ ] **Step 10: Commit**

```bash
git add app/app/Modules/Admin/Database/Migrations/2026_07_15_120000_create_admin_notification_recipients_table.php \
        app/app/Modules/Admin/Database/Migrations/2026_07_15_120001_create_admin_notification_subscriptions_table.php \
        app/app/Modules/Admin/Models/AdminNotificationRecipient.php \
        app/app/Modules/Admin/Models/AdminNotificationSubscription.php \
        app/app/Modules/Admin/Notifications/NotificationTypeCatalog.php \
        app/tests/Unit/Admin/NotificationTypeCatalogTest.php \
        app/tests/Feature/Admin/AdminNotificationRecipientModelTest.php
git commit -m "feat(admin): data model cho email nhận thông báo (bảng riêng + registry loại thông báo)"
```

---

### Task 2: `AdminAlertNotification` + `AdminNotificationDispatcher`

**Files:**
- Create: `app/app/Modules/Admin/Notifications/AdminAlertNotification.php`
- Create: `app/app/Modules/Admin/Notifications/Services/AdminNotificationDispatcher.php`
- Test: `app/tests/Feature/Admin/AdminNotificationDispatcherTest.php`

**Interfaces:**
- Consumes: `AdminNotificationRecipient`, `AdminNotificationSubscription`, `NotificationTypeCatalog` (Task 1).
- Produces: `AdminAlertNotification(string $type, array $context)` — Laravel `Notification` `ShouldQueue`, `via()=['mail']`, `toMail()` chọn nội dung theo `$type` (`'support.new_conversation'`, `'auth.user_verified'`, `'test'`). `AdminNotificationDispatcher::notify(string $type, array $context): void` — Task 3, 4, 5 đều gọi method này.

- [ ] **Step 1: Viết test cho dispatcher**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use CMBcoreSeller\Modules\Admin\Notifications\Services\AdminNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_sends_only_to_active_subscribed_emails(): void
    {
        Notification::fake();

        $subscribed = AdminNotificationRecipient::create(['email' => 'sub@x.com', 'is_active' => true]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $subscribed->id,
            'notification_type' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION,
            'created_at' => now(),
        ]);

        $notSubscribed = AdminNotificationRecipient::create(['email' => 'nosub@x.com', 'is_active' => true]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $notSubscribed->id,
            'notification_type' => NotificationTypeCatalog::AUTH_USER_VERIFIED,
            'created_at' => now(),
        ]);

        $inactive = AdminNotificationRecipient::create(['email' => 'inactive@x.com', 'is_active' => false]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $inactive->id,
            'notification_type' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION,
            'created_at' => now(),
        ]);

        app(AdminNotificationDispatcher::class)->notify(
            NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION,
            ['tenant_name' => 'Shop Test', 'snippet' => 'Xin chào', 'conversation_id' => 1],
        );

        Notification::assertCount(1);
        Notification::assertSentOnDemand(
            AdminAlertNotification::class,
            fn ($notification, $channels, $notifiable) => in_array('sub@x.com', $notifiable->routes['mail'], true),
        );
    }

    public function test_notify_is_noop_when_nobody_subscribed(): void
    {
        Notification::fake();

        app(AdminNotificationDispatcher::class)->notify(NotificationTypeCatalog::AUTH_USER_VERIFIED, []);

        Notification::assertCount(0);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AdminNotificationDispatcherTest`
Expected: FAIL — class `AdminNotificationDispatcher` không tồn tại.

- [ ] **Step 3: Viết `AdminAlertNotification`**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Email cảnh báo admin (SPEC 2026-07-15) — 1 class dùng chung mọi loại thông báo. Gửi qua
 * on-demand routing tới email tự do (không phải Eloquent User) — xem
 * `AdminNotificationDispatcher::notify()`. Queue `notifications` (dùng chung queue mail
 * hiện có, không tạo queue mới). Dùng `MailMessage` fluent API — không cần đăng ký view
 * Blade riêng cho module Admin.
 */
class AdminAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param array<string,mixed> $context */
    public function __construct(private readonly string $type, private readonly array $context)
    {
        $this->queue = (string) config('notifications.queue', 'notifications');
    }

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->type) {
            'support.new_conversation' => $this->supportMail(),
            'auth.user_verified' => $this->userVerifiedMail(),
            'test' => $this->testMail(),
            default => throw new InvalidArgumentException("Unknown admin alert type: {$this->type}"),
        };
    }

    private function supportMail(): MailMessage
    {
        $tenantName = (string) ($this->context['tenant_name'] ?? '(không rõ shop)');
        $snippet = (string) ($this->context['snippet'] ?? '');
        $conversationId = (int) ($this->context['conversation_id'] ?? 0);
        $appUrl = rtrim((string) config('notifications.frontend_url'), '/');

        return (new MailMessage)
            ->subject("[CMBcoreSeller] Khách nhắn CSKH mới — {$tenantName}")
            ->greeting('Có tin nhắn CSKH mới')
            ->line("Shop: {$tenantName}")
            ->line("Nội dung: {$snippet}")
            ->action('Xem hội thoại', "{$appUrl}/admin/support-requests")
            ->line("Mã hội thoại: #{$conversationId}");
    }

    private function userVerifiedMail(): MailMessage
    {
        $name = (string) ($this->context['name'] ?? '');
        $email = (string) ($this->context['email'] ?? '');
        $tenantName = (string) ($this->context['tenant_name'] ?? '(chưa có shop)');

        return (new MailMessage)
            ->subject("[CMBcoreSeller] Người dùng mới đã xác minh email — {$name}")
            ->greeting('Người dùng mới đã đăng ký & xác minh email')
            ->line("Tên: {$name}")
            ->line("Email: {$email}")
            ->line("Shop: {$tenantName}");
    }

    private function testMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('[CMBcoreSeller] Email test thông báo admin')
            ->line('Đây là email test — cấu hình nhận thông báo admin của bạn đang hoạt động đúng.');
    }
}
```

- [ ] **Step 4: Viết `AdminNotificationDispatcher`**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Notifications\Services;

use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Gửi 1 loại thông báo tới mọi email admin đã bật (SPEC 2026-07-15). $emails rỗng ⇒
 * no-op hợp lệ (chưa cấu hình ai nhận loại này).
 */
class AdminNotificationDispatcher
{
    /** @param array<string,mixed> $context truyền thẳng vào AdminAlertNotification */
    public function notify(string $type, array $context): void
    {
        $emails = AdminNotificationRecipient::query()
            ->active()
            ->whereHas('subscriptions', fn ($q) => $q->where('notification_type', $type))
            ->pluck('email');

        foreach ($emails as $email) {
            Notification::route('mail', $email)->notify(new AdminAlertNotification($type, $context));
        }
    }
}
```

- [ ] **Step 5: Chạy lại test, xác nhận PASS**

Run: `php artisan test --filter=AdminNotificationDispatcherTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Admin/Notifications/AdminAlertNotification.php \
        app/app/Modules/Admin/Notifications/Services/AdminNotificationDispatcher.php \
        app/tests/Feature/Admin/AdminNotificationDispatcherTest.php
git commit -m "feat(admin): AdminAlertNotification + AdminNotificationDispatcher (email on-demand tới danh sách admin)"
```

---

### Task 3: Trigger — khách mở cuộc CSKH mới

**Files:**
- Modify: `app/app/Modules/Support/Services/SupportConversationService.php` (method `postUserMessage()`, thêm `use Illuminate\Support\Str;`)
- Create: `app/app/Modules/Support/Events/SupportNewConversationOpened.php`
- Create: `app/app/Modules/Admin/Notifications/Listeners/NotifyAdminsOnNewSupportConversation.php`
- Modify: `app/app/Modules/Admin/AdminServiceProvider.php` (đăng ký listener trong `boot()`)
- Test: `app/tests/Feature/Support/SupportNewConversationOpenedTest.php`
- Test: `app/tests/Feature/Admin/NotifyAdminsOnNewSupportConversationTest.php`

**Interfaces:**
- Consumes: `AdminNotificationDispatcher::notify()`, `NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION` (Task 1, 2).
- Produces: event `SupportNewConversationOpened(int $conversationId, int $tenantId, ?int $userId, string $snippet)` — thuần (không broadcast), tách biệt khỏi `SupportMessageCreated` (broadcast realtime FE, bắn cho MỌI tin kể cả CSKH trả lời).

- [ ] **Step 1: Viết test HTTP xác nhận event chỉ bắn khi mở cuộc MỚI**

`SupportConversation` dùng `BelongsToTenant`/`TenantScope` — test phải đi qua HTTP thật (header `X-Tenant-Id` + tenant middleware) để scope hoạt động đúng, không gọi thẳng Service (xem `tests/Feature/Support/SupportConversationTest.php` để biết pattern setup: cần seed `BillingPlanSeeder` + tạo `Subscription` active cho tenant trước khi gọi endpoint).

```php
<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Support\Events\SupportNewConversationOpened;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SupportNewConversationOpenedTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Shop Test']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => 'owner']);
    }

    /** @return array<string,string> */
    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_event_fires_only_on_first_message_of_open_conversation(): void
    {
        Event::fake([SupportNewConversationOpened::class]);

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/support/messages', ['body' => 'Tin đầu tiên'])
            ->assertCreated();
        Event::assertDispatched(SupportNewConversationOpened::class, 1);

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/support/messages', ['body' => 'Tin thứ hai cùng cuộc'])
            ->assertCreated();
        Event::assertDispatched(SupportNewConversationOpened::class, 1);
    }

    public function test_event_fires_again_after_conversation_closed(): void
    {
        Event::fake([SupportNewConversationOpened::class]);

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/support/messages', ['body' => 'Tin đầu tiên'])
            ->assertCreated();

        SupportConversation::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->latest('id')->first()
            ->forceFill(['status' => SupportConversation::STATUS_CLOSED])->save();

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/support/messages', ['body' => 'Tin sau khi đóng'])
            ->assertCreated();

        Event::assertDispatched(SupportNewConversationOpened::class, 2);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=SupportNewConversationOpenedTest`
Expected: FAIL — class `SupportNewConversationOpened` không tồn tại.

- [ ] **Step 3: Viết event `SupportNewConversationOpened`**

```php
<?php

namespace CMBcoreSeller\Modules\Support\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát khi 1 tenant MỞ CUỘC HỘI THOẠI CSKH MỚI (không phải mọi tin nhắn) — dùng để báo
 * admin qua email (SPEC 2026-07-15). Tách khỏi `SupportMessageCreated` (broadcast realtime
 * FE, bắn cho MỌI tin kể cả CSKH trả lời) để không trộn 2 mục đích khác nhau — payload
 * broadcast (khách FE nhận) không cần mang thêm dữ liệu chỉ admin cần.
 */
class SupportNewConversationOpened
{
    use Dispatchable;

    public function __construct(
        public int $conversationId,
        public int $tenantId,
        public ?int $userId,
        public string $snippet,
    ) {}
}
```

- [ ] **Step 4: Sửa `SupportConversationService::postUserMessage()`**

Thêm import ở đầu file (cạnh các `use` hiện có):

```php
use CMBcoreSeller\Modules\Support\Events\SupportNewConversationOpened;
use Illuminate\Support\Str;
```

Sửa method (giữ nguyên toàn bộ logic bên trong transaction, chỉ thêm biến `$wasNewConversation` và dispatch event mới sau khi transaction commit):

```php
public function postUserMessage(int $tenantId, ?int $userId, ?string $body, array $files): SupportConversation
{
    // Validate đính kèm TRƯỚC (ném 422 trước khi ghi DB/disk — không tạo cuộc rác).
    $validated = $this->validateFiles($files);
    $wasNewConversation = false;

    $conv = DB::transaction(function () use ($tenantId, $userId, $body, $validated, &$wasNewConversation) {
        $conv = SupportConversation::query()->latest('id')->first();
        $wasNewConversation = ! $conv || $conv->isClosed();
        if ($wasNewConversation) {
            $conv = SupportConversation::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'status' => SupportConversation::STATUS_OPEN,
            ]);
        }

        $msg = SupportMessage::query()->create([
            'tenant_id' => $tenantId,
            'support_conversation_id' => $conv->getKey(),
            'sender' => SupportMessage::SENDER_USER,
            'type' => SupportMessage::TYPE_TEXT,
            'user_id' => $userId,
            'body' => $body,
        ]);
        $this->attachFiles($msg, $validated);

        $conv->forceFill([
            'last_message_at' => now(),
            'last_sender' => SupportConversation::SENDER_USER,
        ])->save();

        return $conv;
    });

    // Realtime (ADR-0021): báo các phiên khác của tenant cập nhật NGAY (no-op khi Reverb tắt).
    SupportMessageCreated::dispatch((int) $conv->getKey(), $tenantId, SupportMessage::SENDER_USER);

    // SPEC 2026-07-15: mở cuộc mới ⇒ báo admin qua email (không bắn lại cho tin nối tiếp).
    if ($wasNewConversation) {
        SupportNewConversationOpened::dispatch(
            (int) $conv->getKey(), $tenantId, $userId, Str::limit((string) $body, 200),
        );
    }

    return $conv;
}
```

- [ ] **Step 5: Viết listener `NotifyAdminsOnNewSupportConversation`**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Notifications\Listeners;

use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use CMBcoreSeller\Modules\Admin\Notifications\Services\AdminNotificationDispatcher;
use CMBcoreSeller\Modules\Support\Events\SupportNewConversationOpened;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Nghe `SupportNewConversationOpened` (module Support) ⇒ báo admin qua email (SPEC
 * 2026-07-15). Giao tiếp qua domain event — không `use` Services nội bộ của Support.
 */
class NotifyAdminsOnNewSupportConversation implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(private readonly AdminNotificationDispatcher $dispatcher) {}

    public function handle(SupportNewConversationOpened $event): void
    {
        $tenantName = Tenant::find($event->tenantId)?->name ?? '(không rõ shop)';

        $this->dispatcher->notify(NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION, [
            'tenant_name' => $tenantName,
            'snippet' => $event->snippet,
            'conversation_id' => $event->conversationId,
        ]);
    }
}
```

- [ ] **Step 6: Đăng ký listener trong `AdminServiceProvider::boot()`**

Sửa `app/app/Modules/Admin/AdminServiceProvider.php`, thêm import + đăng ký:

```php
<?php

namespace CMBcoreSeller\Modules\Admin;

use CMBcoreSeller\Modules\Admin\Notifications\Listeners\NotifyAdminsOnNewSupportConversation;
use CMBcoreSeller\Modules\Support\Events\SupportNewConversationOpened;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Admin hệ thống — SPEC 0020. Phụ thuộc: Tenancy (User, Tenant, AuditLog), Billing
 * (Subscription/Plan), Channels (ChannelConnectionService), Support (event lắng nghe).
 *
 * Tất cả routes ở `Http/routes.php` qua middleware `auth:sanctum` + `super_admin` (KHÔNG `tenant`).
 */
class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }

        // SPEC 2026-07-15 — báo admin qua email khi có sự kiện đáng chú ý.
        Event::listen(SupportNewConversationOpened::class, NotifyAdminsOnNewSupportConversation::class);
    }
}
```

- [ ] **Step 7: Chạy lại test Step 1, xác nhận PASS**

Run: `php artisan test --filter=SupportNewConversationOpenedTest`
Expected: PASS (2 tests).

- [ ] **Step 8: Viết test cho listener (nhận đúng email đã subscribe)**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use CMBcoreSeller\Modules\Support\Events\SupportNewConversationOpened;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifyAdminsOnNewSupportConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribed_admin_receives_alert_on_new_conversation(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'Shop Alert Test']);
        $recipient = AdminNotificationRecipient::create(['email' => 'ops@x.com', 'is_active' => true]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $recipient->id,
            'notification_type' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION,
            'created_at' => now(),
        ]);

        event(new SupportNewConversationOpened(1, $tenant->getKey(), null, 'Cần hỗ trợ gấp'));

        Notification::assertSentOnDemand(
            AdminAlertNotification::class,
            fn ($notification, $channels, $notifiable) => in_array('ops@x.com', $notifiable->routes['mail'], true),
        );
    }

    public function test_unsubscribed_admin_does_not_receive_alert(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'Shop Alert Test 2']);
        AdminNotificationRecipient::create(['email' => 'other@x.com', 'is_active' => true]); // không subscribe

        event(new SupportNewConversationOpened(1, $tenant->getKey(), null, 'Nội dung'));

        Notification::assertCount(0);
    }
}
```

- [ ] **Step 9: Chạy test, xác nhận PASS**

Run: `php artisan test --filter=NotifyAdminsOnNewSupportConversationTest`
Expected: PASS (2 tests).

- [ ] **Step 10: Chạy toàn bộ test Support (không phá vỡ test cũ)**

Run: `php artisan test --filter=Support`
Expected: PASS toàn bộ (bao gồm `SupportConversationTest`, `SupportRealtimeTest` cũ).

- [ ] **Step 11: Commit**

```bash
git add app/app/Modules/Support/Services/SupportConversationService.php \
        app/app/Modules/Support/Events/SupportNewConversationOpened.php \
        app/app/Modules/Admin/Notifications/Listeners/NotifyAdminsOnNewSupportConversation.php \
        app/app/Modules/Admin/AdminServiceProvider.php \
        app/tests/Feature/Support/SupportNewConversationOpenedTest.php \
        app/tests/Feature/Admin/NotifyAdminsOnNewSupportConversationTest.php
git commit -m "feat(admin,support): báo admin qua email khi khách mở cuộc CSKH mới"
```

---

### Task 4: Trigger — user đăng ký + xác minh email

**Files:**
- Create: `app/app/Modules/Admin/Notifications/Listeners/NotifyAdminsOnUserVerified.php`
- Modify: `app/app/Modules/Admin/AdminServiceProvider.php` (đăng ký thêm listener)
- Test: `app/tests/Feature/Admin/NotifyAdminsOnUserVerifiedTest.php`

**Interfaces:**
- Consumes: `AdminNotificationDispatcher::notify()`, `NotificationTypeCatalog::AUTH_USER_VERIFIED` (Task 1, 2). `Illuminate\Auth\Events\Verified` (Laravel built-in, đã được `Modules\Notifications\Listeners\SendWelcomeEmailOnVerified` lắng nghe — KHÔNG sửa listener đó).

- [ ] **Step 1: Viết test**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifyAdminsOnUserVerifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribed_admin_receives_alert_when_user_verified(): void
    {
        Notification::fake();
        $recipient = AdminNotificationRecipient::create(['email' => 'growth@x.com', 'is_active' => true]);
        AdminNotificationSubscription::create([
            'admin_notification_recipient_id' => $recipient->id,
            'notification_type' => NotificationTypeCatalog::AUTH_USER_VERIFIED,
            'created_at' => now(),
        ]);
        $user = User::factory()->unverified()->create(['name' => 'Chị Lan', 'email' => 'lan@shop.vn']);

        event(new Verified($user));

        Notification::assertSentOnDemand(
            AdminAlertNotification::class,
            fn ($notification, $channels, $notifiable) => in_array('growth@x.com', $notifiable->routes['mail'], true),
        );
    }

    public function test_unsubscribed_admin_does_not_receive_alert(): void
    {
        Notification::fake();
        AdminNotificationRecipient::create(['email' => 'other@x.com', 'is_active' => true]); // không subscribe
        $user = User::factory()->unverified()->create();

        event(new Verified($user));

        Notification::assertCount(0);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=NotifyAdminsOnUserVerifiedTest`
Expected: FAIL — class `NotifyAdminsOnUserVerified` không tồn tại (listener chưa đăng ký nên event không có tác dụng).

- [ ] **Step 3: Viết listener**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Notifications\Listeners;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use CMBcoreSeller\Modules\Admin\Notifications\Services\AdminNotificationDispatcher;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Nghe `Verified` (Laravel built-in) ⇒ báo admin qua email khi user xác minh xong (SPEC
 * 2026-07-15). Đăng ký cạnh `SendWelcomeEmailOnVerified` (module Notifications) — không
 * sửa module đó.
 */
class NotifyAdminsOnUserVerified implements ShouldQueue
{
    public string $queue = 'notifications';

    public int $tries = 3;

    public function __construct(private readonly AdminNotificationDispatcher $dispatcher) {}

    public function handle(Verified $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        $tenantName = $user->tenants()->first()?->name ?? '(chưa có shop)';

        $this->dispatcher->notify(NotificationTypeCatalog::AUTH_USER_VERIFIED, [
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'tenant_name' => $tenantName,
        ]);
    }
}
```

- [ ] **Step 4: Đăng ký listener trong `AdminServiceProvider::boot()`**

Thêm import + 1 dòng `Event::listen` (cạnh dòng đã thêm ở Task 3):

```php
use CMBcoreSeller\Modules\Admin\Notifications\Listeners\NotifyAdminsOnUserVerified;
use Illuminate\Auth\Events\Verified;
```

```php
Event::listen(SupportNewConversationOpened::class, NotifyAdminsOnNewSupportConversation::class);
Event::listen(Verified::class, NotifyAdminsOnUserVerified::class);
```

- [ ] **Step 5: Chạy lại test, xác nhận PASS**

Run: `php artisan test --filter=NotifyAdminsOnUserVerifiedTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Chạy test Notifications module (không phá vỡ `SendWelcomeEmailOnVerified` cũ)**

Run: `php artisan test --filter=EmailVerificationTest`
Expected: PASS toàn bộ (bao gồm `test_welcome_notification_fires_on_verified_event` cũ).

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Admin/Notifications/Listeners/NotifyAdminsOnUserVerified.php \
        app/app/Modules/Admin/AdminServiceProvider.php \
        app/tests/Feature/Admin/NotifyAdminsOnUserVerifiedTest.php
git commit -m "feat(admin): báo admin qua email khi user đăng ký + xác minh email thành công"
```

---

### Task 5: API CRUD + gửi test + docs

**Files:**
- Create: `app/app/Modules/Admin/Http/Controllers/AdminNotificationEmailController.php`
- Modify: `app/app/Modules/Admin/Http/routes.php` (thêm route group)
- Modify: `docs/05-api/endpoints.md` (thêm bảng mô tả endpoint mới)
- Test: `app/tests/Feature/Admin/AdminNotificationEmailControllerTest.php`

**Interfaces:**
- Consumes: `AdminNotificationRecipient`, `AdminNotificationSubscription`, `NotificationTypeCatalog`, `AdminAlertNotification` (Task 1, 2).
- Produces: routes `GET/POST /api/v1/admin/notification-emails`, `GET /api/v1/admin/notification-emails/types`, `PATCH/DELETE /api/v1/admin/notification-emails/{id}`, `POST /api/v1/admin/notification-emails/{id}/test`. Response row shape: `{id,email,label,is_active,notification_types:string[]}` — Task 6 (FE) đọc đúng shape này.

- [ ] **Step 1: Viết test controller**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminNotificationEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    public function test_guest_cannot_access(): void
    {
        $this->getJson('/api/v1/admin/notification-emails')->assertUnauthorized();
    }

    public function test_admin_can_create_recipient_with_subscriptions(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->postJson('/api/v1/admin/notification-emails', [
                'email' => 'alerts@x.com',
                'label' => 'Đội vận hành',
                'notification_types' => [NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION],
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'alerts@x.com')
            ->assertJsonPath('data.notification_types.0', NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION);
    }

    public function test_duplicate_email_rejected(): void
    {
        AdminNotificationRecipient::create(['email' => 'dup@x.com']);

        $this->actingAs($this->admin, 'admin_web')
            ->postJson('/api/v1/admin/notification-emails', [
                'email' => 'dup@x.com',
                'notification_types' => [NotificationTypeCatalog::AUTH_USER_VERIFIED],
            ])
            ->assertStatus(422);
    }

    public function test_invalid_notification_type_rejected(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->postJson('/api/v1/admin/notification-emails', [
                'email' => 'bad@x.com',
                'notification_types' => ['made.up'],
            ])
            ->assertStatus(422);
    }

    public function test_update_overrides_subscriptions(): void
    {
        $recipient = AdminNotificationRecipient::create(['email' => 'switch@x.com']);
        $recipient->subscriptions()->create([
            'notification_type' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION, 'created_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin_web')
            ->patchJson("/api/v1/admin/notification-emails/{$recipient->id}", [
                'notification_types' => [NotificationTypeCatalog::AUTH_USER_VERIFIED],
            ])
            ->assertOk()
            ->assertJsonPath('data.notification_types', [NotificationTypeCatalog::AUTH_USER_VERIFIED]);
    }

    public function test_delete_removes_recipient(): void
    {
        $recipient = AdminNotificationRecipient::create(['email' => 'gone@x.com']);

        $this->actingAs($this->admin, 'admin_web')
            ->deleteJson("/api/v1/admin/notification-emails/{$recipient->id}")
            ->assertOk();

        $this->assertNull(AdminNotificationRecipient::find($recipient->id));
    }

    public function test_send_test_email(): void
    {
        Notification::fake();
        $recipient = AdminNotificationRecipient::create(['email' => 'test@x.com']);

        $this->actingAs($this->admin, 'admin_web')
            ->postJson("/api/v1/admin/notification-emails/{$recipient->id}/test")
            ->assertOk()
            ->assertJsonPath('data.sent', true);

        Notification::assertSentOnDemand(
            AdminAlertNotification::class,
            fn ($notification, $channels, $notifiable) => in_array('test@x.com', $notifiable->routes['mail'], true),
        );
    }

    public function test_types_endpoint_lists_catalog(): void
    {
        $this->actingAs($this->admin, 'admin_web')
            ->getJson('/api/v1/admin/notification-emails/types')
            ->assertOk()
            ->assertJsonFragment(['code' => NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION]);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AdminNotificationEmailControllerTest`
Expected: FAIL — 404 (route chưa tồn tại).

- [ ] **Step 3: Viết controller**

```php
<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationRecipient;
use CMBcoreSeller\Modules\Admin\Models\AdminNotificationSubscription;
use CMBcoreSeller\Modules\Admin\Notifications\AdminAlertNotification;
use CMBcoreSeller\Modules\Admin\Notifications\NotificationTypeCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * CRUD email nhận thông báo cấp nền tảng + gửi test (SPEC 2026-07-15). Guard `admin_web`.
 */
class AdminNotificationEmailController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = AdminNotificationRecipient::query()->with('subscriptions')->orderBy('id')->get();

        return response()->json(['data' => $rows->map($this->row(...))->all()]);
    }

    public function types(): JsonResponse
    {
        $types = collect(NotificationTypeCatalog::all())
            ->map(fn ($label, $code) => ['code' => $code, 'label' => $label])
            ->values();

        return response()->json(['data' => $types->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        $recipient = DB::transaction(function () use ($data) {
            $recipient = AdminNotificationRecipient::create([
                'email' => $data['email'],
                'label' => $data['label'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
            $this->syncSubscriptions($recipient, $data['notification_types']);

            return $recipient;
        });

        return response()->json(['data' => $this->row($recipient->load('subscriptions'))], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $recipient = AdminNotificationRecipient::query()->findOrFail((int) $id);
        $data = $this->validateData($request, partial: true, ignoreId: $recipient->id);

        DB::transaction(function () use ($recipient, $data) {
            if (array_key_exists('email', $data)) {
                $recipient->email = $data['email'];
            }
            if (array_key_exists('label', $data)) {
                $recipient->label = $data['label'];
            }
            if (array_key_exists('is_active', $data)) {
                $recipient->is_active = $data['is_active'];
            }
            $recipient->save();

            if (array_key_exists('notification_types', $data)) {
                $this->syncSubscriptions($recipient, $data['notification_types']);
            }
        });

        return response()->json(['data' => $this->row($recipient->fresh('subscriptions'))]);
    }

    public function destroy(string $id): JsonResponse
    {
        AdminNotificationRecipient::query()->findOrFail((int) $id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function test(string $id): JsonResponse
    {
        $recipient = AdminNotificationRecipient::query()->findOrFail((int) $id);

        Notification::route('mail', $recipient->email)->notify(new AdminAlertNotification('test', []));

        return response()->json(['data' => ['sent' => true]]);
    }

    /** @return array<string,mixed> */
    private function validateData(Request $request, bool $partial = false, ?int $ignoreId = null): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'email' => [$req, 'email', 'max:255', Rule::unique('admin_notification_recipients', 'email')->ignore($ignoreId)],
            'label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
            'notification_types' => [$req, 'array'],
            'notification_types.*' => ['string', function ($attribute, $value, $fail) {
                if (! NotificationTypeCatalog::isValid($value)) {
                    $fail("Loại thông báo \"{$value}\" không hợp lệ.");
                }
            }],
        ]);
    }

    /** @param list<string> $types */
    private function syncSubscriptions(AdminNotificationRecipient $recipient, array $types): void
    {
        $recipient->subscriptions()->delete();
        foreach (array_unique($types) as $type) {
            AdminNotificationSubscription::create([
                'admin_notification_recipient_id' => $recipient->id,
                'notification_type' => $type,
                'created_at' => now(),
            ]);
        }
    }

    /** @return array<string,mixed> */
    private function row(AdminNotificationRecipient $recipient): array
    {
        return [
            'id' => $recipient->id,
            'email' => $recipient->email,
            'label' => $recipient->label,
            'is_active' => $recipient->is_active,
            'notification_types' => $recipient->subscriptions->pluck('notification_type')->values()->all(),
        ];
    }
}
```

- [ ] **Step 4: Thêm route group vào `app/app/Modules/Admin/Http/routes.php`**

Thêm import ở đầu file:

```php
use CMBcoreSeller\Modules\Admin\Http\Controllers\AdminNotificationEmailController;
```

Thêm ngay sau khối "Admin Users — quản lý chính các super-admin" (trước dấu `});` đóng nhóm `Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])`):

```php
        // --- Email nhận thông báo admin (SPEC 2026-07-15) ---
        Route::get('notification-emails', [AdminNotificationEmailController::class, 'index'])
            ->name('admin.notification-emails.index');
        Route::get('notification-emails/types', [AdminNotificationEmailController::class, 'types'])
            ->name('admin.notification-emails.types');
        Route::post('notification-emails', [AdminNotificationEmailController::class, 'store'])
            ->name('admin.notification-emails.store');
        Route::patch('notification-emails/{id}', [AdminNotificationEmailController::class, 'update'])
            ->whereNumber('id')->name('admin.notification-emails.update');
        Route::delete('notification-emails/{id}', [AdminNotificationEmailController::class, 'destroy'])
            ->whereNumber('id')->name('admin.notification-emails.destroy');
        Route::post('notification-emails/{id}/test', [AdminNotificationEmailController::class, 'test'])
            ->whereNumber('id')->name('admin.notification-emails.test');
```

- [ ] **Step 5: Chạy lại test, xác nhận PASS**

Run: `php artisan test --filter=AdminNotificationEmailControllerTest`
Expected: PASS (7 tests).

- [ ] **Step 6: Cập nhật `docs/05-api/endpoints.md`**

Thêm 1 subsection mới ngay sau khối "### Admin Tier 1+2 (SPEC 0023)" (khớp format bảng hiện có trong file):

```markdown
### Admin Notification Emails (SPEC 2026-07-15)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/admin/notification-emails` | web + `auth:admin_web` | — | `{ data:[{id,email,label,is_active,notification_types:[code,...]}] }` |
| GET | `/api/v1/admin/notification-emails/types` | web + `auth:admin_web` | — | `{ data:[{code,label}] }` — đọc `NotificationTypeCatalog::all()`. |
| POST | `/api/v1/admin/notification-emails` | web + `auth:admin_web` | `{ email, label?, is_active?, notification_types:[code,...] }` | `201 { data: {...} }` — `email` trùng ⇒ `422`; `notification_types` chứa code không hợp lệ ⇒ `422`. |
| PATCH | `/api/v1/admin/notification-emails/{id}` | web + `auth:admin_web` | như trên (partial) | `{ data: {...} }` — gửi `notification_types` ⇒ **ghi đè toàn bộ** subscriptions cũ. |
| DELETE | `/api/v1/admin/notification-emails/{id}` | web + `auth:admin_web` | — | `{ data:{ deleted:true } }` — cascade xoá subscriptions. |
| POST | `/api/v1/admin/notification-emails/{id}/test` | web + `auth:admin_web` | — | `{ data:{ sent:true } }` — gửi `AdminAlertNotification(type='test')` tới đúng email này, không lưu gì. |
```

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/AdminNotificationEmailController.php \
        app/app/Modules/Admin/Http/routes.php \
        docs/05-api/endpoints.md \
        app/tests/Feature/Admin/AdminNotificationEmailControllerTest.php
git commit -m "feat(admin): API CRUD email nhận thông báo + gửi test + docs"
```

---

### Task 6: Frontend — trang quản lý email nhận thông báo

**Files:**
- Create: `app/resources/js/admin/lib/adminNotificationEmails.tsx`
- Create: `app/resources/js/admin/pages/AdminNotificationEmailsPage.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx` (import + route)
- Modify: `app/resources/js/admin/AdminLayout.tsx` (import icon + sidebar item)

**Interfaces:**
- Consumes: `GET/POST/PATCH/DELETE /api/v1/admin/notification-emails*` (Task 5), response shape `{id,email,label,is_active,notification_types:string[]}` và `{code,label}` cho types.

- [ ] **Step 1: Viết hooks TanStack Query**

```tsx
// app/resources/js/admin/lib/adminNotificationEmails.tsx
// SPEC 2026-07-15 — hooks quản lý email nhận thông báo admin ở /api/v1/admin/notification-emails/*.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { adminClient } from './adminClient';

export interface AdminNotificationEmail {
    id: number;
    email: string;
    label: string | null;
    is_active: boolean;
    notification_types: string[];
}

export interface AdminNotificationType {
    code: string;
    label: string;
}

export interface AdminNotificationEmailInput {
    email: string;
    label?: string | null;
    is_active?: boolean;
    notification_types: string[];
}

export function useAdminNotificationEmails() {
    return useQuery({
        queryKey: ['admin', 'notification-emails'],
        queryFn: async () => (await adminClient.get<{ data: AdminNotificationEmail[] }>('/notification-emails')).data.data,
    });
}

export function useAdminNotificationTypes() {
    return useQuery({
        queryKey: ['admin', 'notification-emails', 'types'],
        queryFn: async () => (await adminClient.get<{ data: AdminNotificationType[] }>('/notification-emails/types')).data.data,
    });
}

export function useCreateAdminNotificationEmail() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (input: AdminNotificationEmailInput) =>
            (await adminClient.post<{ data: AdminNotificationEmail }>('/notification-emails', input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'notification-emails'] }),
    });
}

export function useUpdateAdminNotificationEmail() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, ...input }: { id: number } & Partial<AdminNotificationEmailInput>) =>
            (await adminClient.patch<{ data: AdminNotificationEmail }>(`/notification-emails/${id}`, input)).data.data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'notification-emails'] }),
    });
}

export function useDeleteAdminNotificationEmail() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => (await adminClient.delete(`/notification-emails/${id}`)).data,
        onSuccess: () => void qc.invalidateQueries({ queryKey: ['admin', 'notification-emails'] }),
    });
}

export function useTestAdminNotificationEmail() {
    return useMutation({
        mutationFn: async (id: number) =>
            (await adminClient.post<{ data: { sent: boolean } }>(`/notification-emails/${id}/test`)).data.data,
    });
}
```

- [ ] **Step 2: Viết trang quản lý**

```tsx
// app/resources/js/admin/pages/AdminNotificationEmailsPage.tsx
// SPEC 2026-07-15 — quản lý email nhận thông báo admin (CSKH mới, user xác minh email...).
import { useState } from 'react';
import { App, Button, Card, Checkbox, Form, Input, Space, Switch, Table, Tag } from 'antd';
import { DeleteOutlined, EditOutlined, MailOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import {
    type AdminNotificationEmail,
    useAdminNotificationEmails,
    useAdminNotificationTypes,
    useCreateAdminNotificationEmail,
    useUpdateAdminNotificationEmail,
    useDeleteAdminNotificationEmail,
    useTestAdminNotificationEmail,
} from '../lib/adminNotificationEmails';

interface FormShape {
    email: string;
    label?: string;
    is_active: boolean;
    notification_types: string[];
}

export function AdminNotificationEmailsPage() {
    const { data: rows = [], isLoading } = useAdminNotificationEmails();
    const { data: types = [] } = useAdminNotificationTypes();
    const create = useCreateAdminNotificationEmail();
    const update = useUpdateAdminNotificationEmail();
    const remove = useDeleteAdminNotificationEmail();
    const test = useTestAdminNotificationEmail();
    const { message, modal } = App.useApp();
    const [form] = Form.useForm<FormShape>();
    const [editingId, setEditingId] = useState<number | null>(null);

    const reset = () => { form.resetFields(); setEditingId(null); };

    const startEdit = (r: AdminNotificationEmail) => {
        setEditingId(r.id);
        form.setFieldsValue({
            email: r.email, label: r.label ?? undefined, is_active: r.is_active,
            notification_types: r.notification_types,
        });
    };

    const submit = (v: FormShape) => {
        const input = { email: v.email, label: v.label ?? null, is_active: v.is_active, notification_types: v.notification_types };
        const opts = { onSuccess: () => { message.success('Đã lưu.'); reset(); }, onError: () => message.error('Lưu thất bại.') };
        if (editingId) update.mutate({ id: editingId, ...input }, opts);
        else create.mutate(input, opts);
    };

    const columns: ColumnsType<AdminNotificationEmail> = [
        { title: 'Email', dataIndex: 'email' },
        { title: 'Nhãn', dataIndex: 'label', render: (l: string | null) => l ?? '—' },
        {
            title: 'Nhận thông báo', dataIndex: 'notification_types',
            render: (codes: string[]) => (
                <Space size={4} wrap>
                    {codes.length === 0 && <Tag>Chưa chọn</Tag>}
                    {codes.map((c) => <Tag key={c} color="blue">{types.find((t) => t.code === c)?.label ?? c}</Tag>)}
                </Space>
            ),
        },
        { title: 'Trạng thái', dataIndex: 'is_active', width: 110, render: (a: boolean) => <Tag color={a ? 'green' : 'default'}>{a ? 'Đang bật' : 'Tắt'}</Tag> },
        {
            title: 'Thao tác', width: 170, render: (_, r) => (
                <Space>
                    <Button
                        size="small" icon={<MailOutlined />} loading={test.isPending}
                        onClick={() => test.mutate(r.id, {
                            onSuccess: () => message.success(`Đã gửi email test tới ${r.email}.`),
                            onError: () => message.error('Gửi test thất bại.'),
                        })}
                    />
                    <Button size="small" icon={<EditOutlined />} onClick={() => startEdit(r)} />
                    <Button
                        size="small" danger icon={<DeleteOutlined />}
                        onClick={() => modal.confirm({
                            title: `Xoá email "${r.email}"?`,
                            onOk: () => remove.mutateAsync(r.id).then(() => message.success('Đã xoá.')),
                        })}
                    />
                </Space>
            ),
        },
    ];

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Card title={editingId ? 'Sửa email nhận thông báo' : 'Thêm email nhận thông báo'} size="small" style={{ maxWidth: 560 }}>
                <Form form={form} layout="vertical" initialValues={{ is_active: true, notification_types: [] }} onFinish={submit}>
                    <Form.Item name="email" label="Email" rules={[{ required: true, type: 'email', max: 255 }]}>
                        <Input placeholder="admin@cmbcoreseller.com" />
                    </Form.Item>
                    <Form.Item name="label" label="Nhãn (tuỳ chọn)">
                        <Input placeholder="VD: Đội vận hành" maxLength={120} />
                    </Form.Item>
                    <Form.Item
                        name="notification_types" label="Loại thông báo nhận"
                        rules={[{ required: true, message: 'Chọn ít nhất 1 loại thông báo.' }]}
                    >
                        <Checkbox.Group options={types.map((t) => ({ label: t.label, value: t.code }))} />
                    </Form.Item>
                    <Form.Item name="is_active" label="Bật nhận thông báo" valuePropName="checked">
                        <Switch />
                    </Form.Item>
                    <Space>
                        <Button type="primary" htmlType="submit" icon={<PlusOutlined />} loading={create.isPending || update.isPending}>
                            {editingId ? 'Cập nhật' : 'Thêm'}
                        </Button>
                        {editingId && <Button onClick={reset}>Huỷ</Button>}
                    </Space>
                </Form>
            </Card>

            <Card title="Danh sách email nhận thông báo" size="small">
                <Table rowKey="id" size="small" loading={isLoading} columns={columns} dataSource={rows} pagination={false} />
            </Card>
        </Space>
    );
}
```

- [ ] **Step 3: Đăng ký route trong `AdminApp.tsx`**

Thêm import (cạnh các import page khác):

```tsx
import { AdminNotificationEmailsPage } from './pages/AdminNotificationEmailsPage';
```

Thêm route (cạnh `settings`, trước dòng `<Route path="*" ...>`):

```tsx
<Route path="notification-emails" element={<AdminNotificationEmailsPage />} />
```

- [ ] **Step 4: Thêm sidebar item trong `AdminLayout.tsx`**

Thêm `MailOutlined` vào import `@ant-design/icons` hiện có, thêm 1 dòng vào `SIDEBAR_ITEMS` (ngay sau dòng `/admin/settings`):

```tsx
{ key: '/admin/notification-emails', icon: <MailOutlined />, label: 'Email thông báo' },
```

- [ ] **Step 5: Kiểm tra typecheck + build (không có JS test runner trong repo)**

Run (từ `app/`): `npm run typecheck`
Expected: 0 lỗi TypeScript.

Run: `npm run build`
Expected: build thành công (warning về chunk-size hiện có từ trước là bình thường, không phải lỗi mới).

- [ ] **Step 6: Kiểm thử thủ công nhanh qua trình duyệt (dev server)**

Chạy `composer dev` (từ `app/`, nếu chưa chạy sẵn), đăng nhập `/admin`, vào sidebar "Email thông báo" (`/admin/notification-emails`):
- Thêm 1 email, tick 1-2 loại thông báo, Lưu → thấy xuất hiện trong bảng với đúng Tag loại đã chọn.
- Bấm nút gửi test (icon mail) → thấy `message.success`.
- Sửa lại loại thông báo của email đó, Lưu → Tag trong bảng cập nhật đúng loại mới (không còn loại cũ).
- Xoá email → biến mất khỏi bảng.

- [ ] **Step 7: Commit**

```bash
git add app/resources/js/admin/lib/adminNotificationEmails.tsx \
        app/resources/js/admin/pages/AdminNotificationEmailsPage.tsx \
        app/resources/js/admin/AdminApp.tsx \
        app/resources/js/admin/AdminLayout.tsx
git commit -m "feat(admin): trang quản lý email nhận thông báo (thêm/sửa/xoá/gửi test)"
```
