# Danh sách email admin nhận thông báo (CSKH mới, user xác minh email) — Design

> Admin có thể thêm/xoá nhiều email nhận thông báo nền tảng, mỗi email tự chọn nhận loại thông báo nào.
> V1 có 2 loại: (1) khách (chủ shop) nhắn CSKH mở cuộc hội thoại mới, (2) user đăng ký + xác minh email thành
> công. Kiến trúc mở rộng được — thêm loại thông báo mới sau này không sửa bảng/dispatcher/UI hiện có.
> Ngày: 2026-07-15 · Trạng thái: approved. Làm trên `main`.

## 1. Bối cảnh

- **"CSKH"** ở đây là module `Support` đã có sẵn (SPEC-0028) — chủ shop/nhân viên tenant nhắn tin cho đội hỗ trợ
  **của nền tảng CMBcoreSeller** (không phải khách hàng của shop nhắn qua Messaging/FB/Zalo — đó là tính năng
  trả phí per-tenant, không liên quan). `SupportConversationService::postUserMessage()`
  (`app/app/Modules/Support/Services/SupportConversationService.php:29`) đã có logic mở cuộc mới khi cuộc gần
  nhất đã đóng hoặc chưa từng có.
- **"User đăng ký + xác minh email"** = Laravel `Verified` event, đã được lắng nghe bởi
  `Modules\Notifications\Listeners\SendWelcomeEmailOnVerified` (gửi welcome email cho chính user đó). Đây là
  gắn thêm 1 listener mới, không sửa listener cũ.
- Danh sách email nhận thông báo là **cấp nền tảng** (platform-wide), không thuộc tenant nào — vì vậy đặt trong
  `Modules/Admin`, không phải `Modules/Settings` (vốn là settings per-tenant/system scalar qua `system_setting()`).
- Không có cơ chế "system alert" chung nào sẵn có để tái dùng (đã khảo sát: không Slack/webhook, không Sentry
  trong `app/app/Modules`; `NotificationDispatcher` của module `Notifications` là in-app + scoped theo
  `Tenant::users()`, không phù hợp danh sách email admin tự do).

## 2. Data model

Bảng tách biệt, không dùng chung với `system_settings` hay bất kỳ bảng nào khác:

```php
// Migration: create_admin_notification_recipients_table
Schema::create('admin_notification_recipients', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique();
    $table->string('label')->nullable();     // vd "Minh - Founder", chỉ để hiển thị
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Migration: create_admin_notification_subscriptions_table
Schema::create('admin_notification_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('admin_notification_recipient_id')
        ->constrained('admin_notification_recipients')->cascadeOnDelete();
    $table->string('notification_type'); // code từ registry, vd "support.new_conversation"
    $table->timestamp('created_at')->nullable();
    $table->unique(['admin_notification_recipient_id', 'notification_type']);
});
```

Không có `tenant_id` trên 1 trong 2 bảng — đây là dữ liệu nền tảng, không qua `BelongsToTenant`/`TenantScope`.

**Models** (`Modules/Admin/Models/`):
- `AdminNotificationRecipient` — `hasMany(AdminNotificationSubscription::class)`, scope `active()`,
  helper `subscribedTo(string $type): bool`.
- `AdminNotificationSubscription` — `belongsTo(AdminNotificationRecipient::class)`, `$timestamps = false`
  (chỉ có `created_at`, set thủ công khi tạo).

## 3. Registry loại thông báo

File thuần liệt kê code + nhãn — KHÔNG theo Connector/Registry pattern của tầng Integrations (pattern đó dành
cho nhiều nhà cung cấp bên thứ 3 cùng 1 capability; đây chỉ là danh sách hằng số nội bộ, không có "provider"):

```php
// Modules/Admin/Notifications/NotificationTypeCatalog.php
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

Thêm loại thông báo mới sau này: (1) thêm 1 const + 1 dòng nhãn ở đây, (2) viết 1 listener mới gọi
`AdminNotificationDispatcher::notify($type, $context)`, (3) viết 1 nhánh nội dung email trong `AdminAlertMail`.
Không sửa bảng, không sửa dispatcher, không sửa UI liệt kê (UI đọc `NotificationTypeCatalog::all()` qua API).

## 4. Dispatcher

```php
// Modules/Admin/Notifications/Services/AdminNotificationDispatcher.php
class AdminNotificationDispatcher
{
    /** @param array<string,mixed> $context truyền thẳng vào AdminAlertMail */
    public function notify(string $type, array $context): void
    {
        $emails = AdminNotificationRecipient::query()
            ->active()
            ->whereHas('subscriptions', fn ($q) => $q->where('notification_type', $type))
            ->pluck('email');

        foreach ($emails as $email) {
            Mail::to($email)->queue(new AdminAlertMail($type, $context));
        }
    }
}
```

`AdminAlertMail implements ShouldQueue`, `$queue = 'notifications'` (dùng chung queue mail hiện có — không tạo
queue mới). Không quan tâm `$emails` rỗng — vòng lặp rỗng là no-op hợp lệ (chưa cấu hình ai nhận).

## 5. Luồng trigger

### 5.1 CSKH mở cuộc hội thoại mới

Sửa `SupportConversationService::postUserMessage()` — hiện tại không phân biệt được "vừa tạo mới" hay "nối
tiếp cuộc đang mở". Thêm biến `$wasCreated`:

```php
$conv = DB::transaction(function () use ($tenantId, $userId, $body, $validated, &$wasCreated) {
    $conv = SupportConversation::query()->latest('id')->first();
    $wasCreated = ! $conv || $conv->isClosed();
    if ($wasCreated) {
        $conv = SupportConversation::query()->create([...]);
    }
    // ... tạo SupportMessage như cũ
});

if ($wasCreated) {
    SupportNewConversationOpened::dispatch(
        (int) $conv->getKey(), $tenantId, $userId, Str::limit((string) $body, 200),
    );
}
```

`SupportNewConversationOpened` là event **thuần** (không `ShouldBroadcast`) — tách biệt khỏi
`SupportMessageCreated` (vốn phục vụ realtime FE, broadcast MỌI tin nhắn kể cả CSKH trả lời). Trộn 2 mục đích
vào 1 event sẽ buộc payload broadcast (khách FE nhận được) phải mang thêm dữ liệu chỉ admin cần — tách event
tránh rò rỉ ngữ cảnh không cần thiết qua channel realtime.

Listener `Modules\Admin\Notifications\Listeners\NotifyAdminsOnNewSupportConversation` (đăng ký trong
`AdminServiceProvider::boot()`) lấy tên shop qua `Tenant::find($tenantId)`, gọi
`$dispatcher->notify(NotificationTypeCatalog::SUPPORT_NEW_CONVERSATION, [...])`.

### 5.2 User xác minh email

Listener mới `Modules\Admin\Notifications\Listeners\NotifyAdminsOnUserVerified`, lắng nghe
`Illuminate\Auth\Events\Verified` (đăng ký cạnh `SendWelcomeEmailOnVerified` hiện có, trong
`AdminServiceProvider::boot()` — không sửa `NotificationsServiceProvider`). Lấy tên shop qua tenant đầu tiên
của user (`$user->tenants()->first()`), gọi `notify(NotificationTypeCatalog::AUTH_USER_VERIFIED, [...])`.

## 6. Nội dung email

`AdminAlertMail` (`Modules/Admin/Notifications/Mail/AdminAlertMail.php`) — 1 Mailable, chọn subject/view theo
`$type`:

| Type | Subject | Nội dung |
|---|---|---|
| `support.new_conversation` | `[CMBcoreSeller] Khách nhắn CSKH mới — <tên shop>` | Tên shop, người gửi, đoạn tin đầu (≤200 ký tự), link `{ADMIN_URL}/support/{conversationId}` |
| `auth.user_verified` | `[CMBcoreSeller] Người dùng mới đã xác minh email — <tên/email>` | Tên, email, tên shop, thời điểm |
| `test` | `[CMBcoreSeller] Email test thông báo admin` | Xác nhận cấu hình đúng, không context thật, không qua registry (không lưu ở đâu, không cần `notification_type` hợp lệ) |

Dùng Blade view đơn giản (text), không cần thiết kế HTML phức tạp — nhất quán độ đơn giản với
`WelcomeNotification` hiện có.

## 7. API (`Modules/Admin`, gate `web + auth:admin_web`, cùng nhóm middleware với các route admin settings khác)

```
GET    /api/v1/admin/notification-emails            → { data: [{id,email,label,is_active,notification_types:[code,...]}] }
GET    /api/v1/admin/notification-emails/types       → { data: [{code,label}] }  (đọc NotificationTypeCatalog::all())
POST   /api/v1/admin/notification-emails             → { email, label?, notification_types:[code,...] }
PATCH  /api/v1/admin/notification-emails/{id}        → { email?, label?, is_active?, notification_types?:[code,...] }
DELETE /api/v1/admin/notification-emails/{id}
POST   /api/v1/admin/notification-emails/{id}/test   → gửi AdminAlertMail(type='test') tới email của recipient này, { data: { sent: true } }
```

`POST`/`PATCH` nhận `notification_types` là mảng code hợp lệ (validate qua `NotificationTypeCatalog::isValid`);
service ghi đè toàn bộ subscriptions của recipient đó trong 1 transaction (xoá cũ, tạo mới theo mảng — đơn giản
hơn diff, số lượng type nhỏ nên không lo hiệu năng). `email` trùng (unique) ⇒ `422 VALIDATION_FAILED`.

## 8. Frontend Admin

Trang mới `app/resources/js/admin/pages/settings/AdminNotificationEmailsPage.tsx`, thêm entry menu cạnh
"Cài đặt hệ thống" (`SystemSettingsPage`). Route đăng ký trong `AdminApp.tsx`.

- Bảng: Email, Nhãn, Tag các loại đã bật, Trạng thái (Switch active/inactive), nút Sửa / Xoá / Gửi test.
- Form thêm/sửa (Modal): input Email + Nhãn, `Checkbox.Group` render từ `GET .../types` (không dùng `Select`,
  theo quy ước UI hiện có trong repo), Switch Active.
- Nút "Gửi test" gọi `POST .../{id}/test`, hiện `message.success`/`message.error` theo kết quả — không đổi
  state gì khác.
- Hooks TanStack Query trong `app/resources/js/admin/lib/admin.tsx` (cùng file các hook admin khác):
  `useAdminNotificationEmails()`, `useAdminNotificationTypes()`, `useAdminNotificationEmailCreate()`,
  `useAdminNotificationEmailUpdate()`, `useAdminNotificationEmailDelete()`, `useAdminNotificationEmailTest()`.

## 9. Testing

- `AdminNotificationDispatcher`: unit test — subscribe/không subscribe → email nhận đúng danh sách;
  `is_active=false` bị loại dù có subscription.
- `SupportConversationService::postUserMessage()`: feature test — tin đầu tiên (cuộc mới) fire
  `SupportNewConversationOpened`; tin thứ 2 trong cùng cuộc đang mở KHÔNG fire lại (`Event::fake()` + assert
  dispatched-once); cuộc đã đóng + tin mới ⇒ fire lại (mở cuộc mới).
- `NotifyAdminsOnUserVerified`: feature test — fire `Verified` event → recipient có subscribe nhận mail
  (`Mail::fake()` + assert queued), recipient không subscribe không nhận.
- API CRUD: feature test đủ create/update (ghi đè subscriptions)/delete/test-send, validate email trùng ⇒ 422,
  validate `notification_types` chứa code không hợp lệ ⇒ 422, gate `auth:admin_web` (401 chưa đăng nhập admin).

## 10. Ngoài phạm vi (v1)

- Không debounce theo thời gian (đã chọn: gộp theo cuộc hội thoại, không phải theo thời gian).
- Không phân quyền admin nào được sửa danh sách này (mọi admin đăng nhập `admin_web` đều có quyền, khớp
  `SystemSettingsPage` hiện có — không có granular permission per-admin trong hệ thống hiện tại).
- Không giới hạn số lượng email tối đa.
