# SPEC 0036: Thông báo trong ứng dụng (In-app Notifications)

- **Trạng thái:** Implemented
- **Phase:** 6.5 (mở rộng module Notifications — SPEC 0022)
- **Module backend liên quan:** Notifications (chủ), Tenancy, lắng nghe event của Orders / Channels / Marketing
- **Tác giả / Ngày:** lilyrisa · 2026-06-08
- **Liên quan:** SPEC 0022 (Notifications email), SPEC 0024 (Reverb realtime inbox), ADR-0021 (Reverb), `01-architecture/modules.md`

## 1. Vấn đề & mục tiêu

Hiện hệ thống chỉ có notification **email** (verify / welcome / reset). Người bán không được báo trong app về các sự kiện nghiệp vụ quan trọng:

- Liên kết sàn / Facebook đã **hết hiệu lực** → đồng bộ dừng mà user không biết.
- Chiến dịch quảng cáo **sắp đạt mức cần tắt** (gần ngưỡng `pause_above` của AdMonitor).
- Đơn hàng **âm tiền** (`grand_total < 0`).
- Đơn **hủy / hoàn mới**.

Mục tiêu: xây nền tảng **thông báo in-app** (chuông + danh sách, đọc/chưa đọc), realtime qua Reverb (đã có hạ tầng), với một bộ loại thông báo nghiệp vụ ban đầu, **mở rộng dễ** (thêm loại = 1 listener + 1 hằng số).

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Bảng `notifications` (tenant-scoped, 1 dòng / 1 user nhận), model, dispatcher service, chống trùng (dedup).
  - 6 loại thông báo v1 (xem §4) qua listener lắng nghe domain event sẵn có; thêm 1 event mới `AdMonitorThresholdApproaching` ở Marketing.
  - API list / mark-read / mark-all-read (envelope chuẩn, có `unread_count`).
  - Realtime: private channel `tenant.{id}.notifications.{userId}` + event `ShouldBroadcast`; fallback polling khi Reverb tắt.
  - FE: `NotificationBell` (Badge + Popover) thay placeholder ở `AppLayout.tsx`, hook list/realtime, điều hướng theo `action_url`.
- **Ngoài (spec sau):**
  - Hệ thống **popup/announcement do admin tạo** (editor TipTap + upload R2) — spec riêng.
  - Gửi **email** cho thông báo nghiệp vụ (chỉ realtime/in-app ở v1).
  - Người dùng tự **bật/tắt từng loại** (preferences).
  - Push web/mobile.

## 3. Luồng chính

1. Module nguồn (Orders/Channels/Marketing) phát domain event như hiện tại (hầu hết đã có).
2. Listener trong module **Notifications** (queued, `queue=notifications`) nhận event, trích `tenant_id` + dữ liệu, gọi `NotificationDispatcher::dispatch()`.
3. Dispatcher: resolve **tất cả user của tenant** (`$tenant->users()`), **dedup** (bỏ qua nếu đã có notif **chưa đọc** cùng `dedup_key` cho user đó), insert N dòng (1/user), phát `NotificationCreated` (broadcast) cho từng user.
4. FE: chuông hiển thị badge số chưa đọc. Echo subscribe `tenant.{id}.notifications.{userId}` → có event thì invalidate query (cập nhật ngay). Reverb tắt → query có `refetchInterval` tự cập nhật.
5. User mở popover → thấy danh sách; click 1 item → điều hướng `action_url` + đánh dấu đã đọc; nút "Đọc tất cả".

## 4. Hành vi & quy tắc nghiệp vụ

### Loại thông báo v1

| type | nguồn (event) | level | tiêu đề (VN) | dedup_key | action_url |
|---|---|---|---|---|---|
| `channel.reconnect_needed` | `Channels\Events\ChannelAccountNeedsReconnect` | warning | "Liên kết {provider} đã hết hiệu lực" | `channel.reconnect:{accountId}` | `/channels` |
| `order.negative_total` | `Orders\Events\OrderUpserted` khi `grand_total < 0` | warning | "Đơn {code} có tổng tiền âm" | `order.negative:{orderId}` | `/orders/{id}` |
| `order.cancelled` | `Orders\Events\OrderStatusChanged` → `Cancelled` | info | "Đơn {code} đã hủy" | `order.cancelled:{orderId}` | `/orders/{id}` |
| `order.return_new` | `Orders\Events\ReturnStatusChanged` → `Requested` (from=null/khác) | info | "Đơn {code} có yêu cầu hủy/hoàn mới" | `order.return:{returnId}` | `/orders/{id}` |
| `ads.monitor_approaching` | `Marketing\Events\AdMonitorThresholdApproaching` (mới) | warning | "Chiến dịch {name} sắp đạt mức cần tắt" | `ads.approaching:{monitorId}` | `/marketing` |
| `ads.monitor_action` | `Marketing\Events\AdMonitorActionTaken` (mới, phát kèm email hiện có) | info | "Chiến dịch {name} đã được tự động {tạm dừng/tăng NS}" | `ads.action:{actionId}` | `/marketing` |

Mở rộng: thêm loại = thêm hằng số `type` + 1 listener; không sửa core.

### Ngưỡng "sắp đạt mức cần tắt"

Trong `AdMonitorEvaluator::applyRules()`, khi `pause_enabled` + `pause_above != null` + `spend > 0` nhưng **chưa** vượt ngưỡng: tính `cpr` (results>0) hoặc dùng `spend` (results=0); nếu `cpr >= 80% * pause_above` (hoặc `spend >= 80% * pause_above` khi results=0) ⇒ phát `AdMonitorThresholdApproaching`. Hệ số 80% là hằng số `AdMonitor::APPROACHING_RATIO = 0.8`.

### Người nhận
Tất cả thành viên tenant (`$tenant->users()`), không lọc theo quyền ở v1.

### Idempotency / chống spam
- `dedup_key` + chỉ chặn khi tồn tại bản ghi **chưa đọc** cùng key cho user đó (đã đọc rồi thì sự kiện mới được tạo lại — vd reconnect lặp sau khi user đã xử lý).
- `ChannelAccountNeedsReconnect` chạy mỗi 30' → dedup chặn lặp. Order events fire mỗi lần upsert → listener chỉ tạo khi điều kiện đặc thù (âm tiền / chuyển sang Cancelled / return Requested mới), cộng dedup theo entity id.

### Phân quyền
Không gate theo `plan.feature` (UX lõi). Cần đăng nhập + tenant. User chỉ thấy notif của chính mình trong tenant hiện tại (global scope + `where user_id`).

## 5. Dữ liệu

Bảng mới `app_notifications` (module Notifications, `BelongsToTenant`) — **tên `app_notifications`** chứ không phải `notifications` để tránh đụng bảng database notifications mặc định của Laravel (User dùng trait `Notifiable`):

| cột | kiểu | ghi chú |
|---|---|---|
| id | bigint PK | |
| tenant_id | foreignId index | global scope |
| user_id | foreignId index | người nhận |
| type | string(48) | hằng số loại |
| level | string(12) | info/warning/critical |
| title | string(255) | |
| body | text null | |
| action_url | string(512) null | deep-link FE |
| data | json null | id thực thể để render |
| dedup_key | string(160) null | chống trùng |
| read_at | timestamp null | |
| created_at / updated_at | timestamps | |

Index: `(user_id, read_at)`, `(tenant_id, user_id, id)` (sắp xếp & badge), `(tenant_id, user_id, dedup_key, read_at)` (dedup). Migration reversible. Không partition (thấp tải; có thể dọn bằng lệnh prune sau — ngoài phạm vi).

Domain event mới (Marketing): `AdMonitorThresholdApproaching`, `AdMonitorActionTaken`. Broadcast event (Notifications): `NotificationCreated implements ShouldBroadcast` → `tenant.{tenantId}.notifications.{userId}`, `broadcastAs('.notification.created')`.

## 6. API & UI

API (`/api/v1`, `auth:sanctum` + `verified` + `tenant`), module Notifications, throttle nhẹ:

- `GET /notifications?status=unread&limit=30` → `{ data: Notification[], meta: { unread_count } }`
- `POST /notifications/{id}/read` → `{ data: { unread_count } }`
- `POST /notifications/read-all` → `{ data: { unread_count } }`

Controller mỏng: (FormRequest nếu cần) → `NotificationReadService` → `NotificationResource`. Cập nhật `05-api/endpoints.md`.

Realtime channel: thêm vào `routes/channels.php`:
`tenant.{tenantId}.notifications.{userId}` — authz qua `NotificationChannelAuthorizer`: user là thành viên tenant **và** `userId === auth id`.

FE (`features`/`lib`):
- `lib/notifications.ts`: `useNotifications`, `useUnreadNotifications` (badge, `refetchInterval` fallback), `useMarkNotificationRead`, `useMarkAllNotificationsRead`, `useNotificationsRealtime` (mirror `useSupportRealtime`).
- `components/NotificationBell.tsx`: `Badge` + `Popover` danh sách; icon theo type bằng `@ant-design/icons` (không emoji); time-ago; click → `navigate(action_url)` + mark read. Thay placeholder `AppLayout.tsx:154`.

Job: không thêm job định kỳ mới (tận dụng `RunAdMonitors` mỗi 30' đã có; listener chạy trên queue `notifications`).

## 7. Edge case & lỗi
- Reverb tắt (`BROADCAST_CONNECTION=null`) → broadcast no-op; FE polling fallback (đúng như Messaging/Support).
- Listener chạy trong queued job **không có tenant context** → dispatcher set `tenant_id` tường minh và query dedup với `withoutGlobalScope(TenantScope)`.
- Tenant không còn user nào → dispatch no-op.
- Order cập nhật nhiều lần → dedup theo id + điều kiện chuyển trạng thái, tránh trùng.
- AdMonitor "approaching" mỗi 30' → dedup chặn lặp tới khi user đọc.
- `action_url` trỏ tới đơn đã ẩn/khác tenant → FE chỉ điều hướng trong tenant hiện tại; backend không lộ cross-tenant (global scope).

## 8. Bảo mật & dữ liệu cá nhân
- Không lưu PII mới ngoài id thực thể + tên hiển thị có sẵn. Notif tenant-scoped + per-user; channel realtime per-user (không lộ chéo user/tenant).
- `data` chỉ chứa id/tên cần để render; không chứa token/secret.

## 9. Kiểm thử
- Unit: `NotificationDispatcher` (fan-out đúng N user, dedup chặn khi có unread cùng key, cho tạo lại khi đã đọc). Ngưỡng `approaching` trong `AdMonitorEvaluator`.
- Feature: mỗi listener tạo đúng loại notif khi event tương ứng (negative order, cancelled, return Requested, reconnect, approaching). API list/mark-read/mark-all trả `unread_count` đúng, scope đúng user/tenant. `Queue::fake()` cho event→listener.
- FE: render badge theo unread_count; mark read giảm badge; (smoke) hook realtime no-op khi Reverb tắt.

## 10. Tiêu chí hoàn thành
- [ ] Migration `notifications` + model `Notification` (BelongsToTenant) + indexes.
- [ ] `NotificationDispatcher` + hằng số `NotificationType` + dedup.
- [ ] 6 listener + 2 event Marketing mới + ngưỡng approaching; đăng ký ở các ServiceProvider.
- [ ] Channel `notifications.{userId}` + `NotificationChannelAuthorizer` + `NotificationCreated` broadcast.
- [ ] API list/mark-read/mark-all + Resource + routes (module Notifications).
- [ ] FE: `lib/notifications.ts` + `NotificationBell` thay placeholder + realtime hook.
- [ ] Tests BE (unit + feature) xanh cho phần mới; `pint`, `phpstan`, FE `lint/typecheck/build` qua.
- [ ] Cập nhật `05-api/endpoints.md`, `01-architecture/modules.md` (Notifications nghe event nào), bảng channel trong doc realtime.

## 11. Câu hỏi mở
- Có cần lệnh prune notif cũ (>90 ngày) ngay không? (đề xuất: để sau, theo dõi kích thước bảng).
- `ads.monitor_action` có cần tách info "tăng NS" vs "tạm dừng" thành 2 level khác nhau không? (v1: cùng `info`).
