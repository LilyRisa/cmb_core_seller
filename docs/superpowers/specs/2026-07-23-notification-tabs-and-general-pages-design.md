# Thiết kế: Panel thông báo 3 tab (Đơn hàng / Hệ thống / Chung) + trang thông báo chung do admin soạn

- **Ngày:** 2026-07-23
- **Module:** `Notifications` (chính — mở rộng, không tạo module mới), `Fulfillment`/`Billing`/`Ai` (chỉ bắn thêm event, không đổi service logic), Admin panel (`admin.tsx`)
- **SPEC liên quan:** module `Notifications` hiện có (SPEC 0036) — bảng `app_notifications`, `NotificationDispatcher`, broadcasting qua Reverb; `Admin/Models/Announcement.php` (SPEC 0037, popup) và `Admin/Services/BroadcastService.php` (email) — cả hai giữ nguyên, không sửa, chạy song song

## 1. Bối cảnh & vấn đề

Panel thông báo hiện tại (`NotificationBell.tsx`) là **một danh sách phẳng**, chỉ có notification liên quan đơn hàng (đơn âm tiền, đơn hủy, đơn hoàn mới) và 1 loại lỗi hệ thống (gian hàng cần cấp quyền lại). Người bán không có kênh nào để biết các lỗi vận hành khác (đẩy tồn kho lỗi, tạo vận đơn/lấy tem lỗi, thanh toán/subscription lỗi, AI hết credit) trừ khi tự phát hiện. Đồng thời, hệ thống chưa có cách nào để **chủ hệ thống chủ động gửi nội dung dạng "trang"** (ưu đãi, tin chung) tới từng tenant hoặc tất cả — 2 cơ chế gần giống hiện có (`Announcement` popup, `BroadcastService` email) đều không đáp ứng: Announcement không nhắm theo tenant và chỉ là popup ngắn; Broadcast chỉ gửi email, không có trang xem lại trong app.

## 2. Quyết định đã chốt (từ brainstorming)

- **Không sửa/tái dùng `Announcement`/`BroadcastService` cũ** — xây khả năng mới trong module `Notifications`, định hướng dài hạn (ngoài phạm vi lần này) là thay thế dần Announcement.
- **Trang "Chung" kế thừa toàn bộ khung app** (header + sidebar, dùng `AppLayout` như mọi trang SPA khác) — không phải trang landing rời layout.
- **Mở bằng tab trình duyệt mới thật sự** (`window.open(url, '_blank')`), không điều hướng cùng tab.
- **Editor**: mở rộng `RichTextEditor.tsx` (TipTap) hiện có — thêm block ảnh full-width và nút CTA (button link); không làm page builder kéo-thả.
- **Đối tượng nhận**: admin chọn **nhiều tenant cụ thể (multi-select) hoặc tất cả** — thông báo tới **toàn bộ user của tenant** đó (không phân biệt owner/nhân viên), tái dùng đúng cơ chế fan-out của `NotificationDispatcher`.
- **Vòng đời**: cần đủ 4 tính năng — lưu nháp, lên lịch gửi, hạn hiển thị (tự hết hạn), thống kê lượt xem.
- **Phạm vi tab "Hệ thống"**: 5 nguồn lỗi — (1) gian hàng cần cấp quyền lại [đã có], (2) đẩy tồn kho thất bại [event có sẵn, thiếu listener], (3) tạo vận đơn/lấy tem thất bại [chưa có event], (4) thanh toán thất bại/subscription hết hạn [chưa có event], (5) AI hết credit/provider lỗi [chưa có event]. `ad_monitor.*` (cảnh báo ngân sách/hiệu suất quảng cáo) hiện có cũng xếp vào category `system` (cảnh báo vận hành tự động).
- **Badge chưa đọc**: 1 số tổng trên chuông (giữ hành vi cũ) + số riêng trên từng tab khi mở panel.
- **Không làm trong lần này**: không sửa Announcement/Broadcast cũ; không có page builder tự do; không thêm loại lỗi hệ thống nào ngoài 5 loại đã liệt kê; không thêm notification "đơn hàng mới" (tab Đơn hàng giữ nguyên hành vi cũ).

## 3. Data model

### 3.1 Cột `category` trên `app_notifications` (migration mới)

```
ALTER TABLE app_notifications ADD COLUMN category VARCHAR(16) NOT NULL DEFAULT 'system';
```

Backfill trong cùng migration (data migration, chạy 1 lần):
- `type IN ('order.negative_total', 'order.cancelled', 'return.new')` → `category = 'order'`
- còn lại (bao gồm `channel.reconnect_needed`, `ad_monitor.*`) → `category = 'system'` (đã là default, không cần update)
- Loại `type` mới thêm ở mục 4 → set `category` tường minh ngay khi tạo, không dựa vào default.
- Loại `type = 'general.page'` (mục 5) → `category = 'general'`.

Index thêm `(tenant_id, user_id, category, read_at)` để query theo tab nhanh (thay/mở rộng index hiện có nếu đã có `(tenant_id, user_id, read_at)`).

### 3.2 Bảng mới `general_notification_pages` (module `Notifications`)

```
id, title, slug (unique), body_html (sanitize như Announcement),
cover_image_url (nullable), cta_label (nullable), cta_url (nullable),
audience_type ENUM('all','tenant_ids'), audience_tenant_ids JSON (null nếu all),
status ENUM('draft','scheduled','sent','expired'),
scheduled_at (nullable, UTC), expires_at (nullable, UTC), sent_at (nullable),
created_by (admin user id, FK bảng admin users), timestamps
```

Không có `tenant_id` (bảng thuộc phạm vi admin/toàn hệ thống, giống `Announcement`).

### 3.3 Bảng mới `general_notification_page_views`

```
id, page_id (FK), tenant_id, user_id, viewed_at, timestamps
UNIQUE (page_id, user_id)
```

Ghi 1 dòng khi user mở trang lần đầu (idempotent qua unique constraint — lần xem sau không insert lại, không update `viewed_at`).

## 4. Tab "Hệ thống" — 4 event mới + 1 listener mới cho event có sẵn

Theo đúng pattern hiện có (`ChannelAccountNeedsReconnect` → `NotifyOnChannelReconnect` → `NotificationDispatcher`, dùng `dedup_key` chống lặp):

| # | Event (mới trừ khi ghi chú) | Nơi bắn | Listener mới | `type` | Ghi chú chống spam |
|---|---|---|---|---|---|
| 1 | `ChannelAccountNeedsReconnect` (**có sẵn**) | — | `NotifyOnChannelReconnect` (**có sẵn**) | `channel.reconnect_needed` | Gán `category='system'` trong listener có sẵn — không đổi logic |
| 2 | (dùng lại `StockPushed` có sẵn, `$ok=false`) | — | `NotifyOnStockPushFailed` (mới) | `inventory.stock_push_failed` | `dedup_key` theo `listing_id` — chỉ tạo mới khi bản chưa đọc cũ không còn |
| 3 | `ShipmentIssueDetected` (mới) | `ShipmentService::markLabelUnavailable()` (case terminal) và sau khi job `FetchChannelLabel` retry cạn (case retry exhausted) | `NotifyOnShipmentIssue` (mới) | `fulfillment.shipment_issue` | Chỉ bắn ở 2 điểm đã có log warning, không bắn mỗi lần retry trung gian |
| 4 | `PaymentFailed`, `SubscriptionExpired` (mới, 2 event) | `PaymentService::applyNotification()` khi `!isSucceeded()`; `SubscriptionExpiryService::run()` ở 3 nhánh set `STATUS_EXPIRED` | `NotifyOnPaymentFailed`, `NotifyOnSubscriptionExpired` (mới) | `billing.payment_failed`, `billing.subscription_expired` | `SubscriptionExpiryService` chạy cron hằng ngày → dedup theo `tenant_id` + ngày để không lặp |
| 5 | `AiProviderErrorDetected`, `AiCreditExhausted` (mới, 2 event) | `AiSuggestionService` catch block quanh `$connector->generateReply()`; `AiSuggestionService::assertHasCredit()` (hoặc `AiCreditService::consume()`) | `NotifyOnAiIssue` (mới, xử lý cả 2 event) | `ai.provider_error`, `ai.credit_exhausted` | Lỗi provider: đếm N lỗi liên tiếp (dùng cache) trước khi bắn, tránh 1 request timeout đơn lẻ đã spam |

Tất cả listener mới đăng ký trong `NotificationsServiceProvider.php` cùng chỗ với các listener hiện có. Các module `Fulfillment`/`Billing`/`Ai` chỉ **bắn thêm event** tại các điểm đã xác định — không import gì từ `Notifications`, đúng nguyên tắc giao tiếp qua event.

## 5. Tab "Chung" — luồng admin soạn & gửi

### 5.1 Admin authoring (admin panel)

Trang mới `AdminGeneralNotificationsPage` (`resources/js/admin/pages/notifications/`), route `admin/routes.php`:
- Danh sách trang đã tạo (draft/scheduled/sent/expired), lọc theo trạng thái.
- Form tạo/sửa: tiêu đề, slug (auto-gen từ tiêu đề, sửa được), `RichTextEditor` mở rộng (thêm 2 toolbar item: chèn ảnh full-width, chèn nút CTA với label+URL), chọn đối tượng (Radio "Tất cả tenant" / "Chọn tenant cụ thể" → multi-select tenant giống `AdminBroadcastsPage` đang có), hạn hiển thị (`expires_at`, optional), lên lịch gửi (`scheduled_at`, optional — bỏ trống = gửi ngay khi bấm "Gửi").
- Nút "Lưu nháp" (status=draft, không dispatch), "Gửi ngay" (dispatch ngay trong request nếu không có `scheduled_at`), "Lên lịch" (status=scheduled nếu có `scheduled_at`).
- Trang chi tiết 1 page đã gửi: hiện số lượt xem / tổng số user thuộc audience (join `general_notification_page_views`).

### 5.2 Dispatch

- `GeneralNotificationDispatchService::dispatch(GeneralNotificationPage $page)`:
  1. Resolve danh sách `tenant_id` (tất cả tenant active, hoặc `audience_tenant_ids`).
  2. Với mỗi tenant, gọi `NotificationDispatcher` (qua contract của module `Notifications`) tạo `app_notifications` cho toàn bộ user tenant đó: `category='general'`, `type='general.page'`, `data.page_id`, `data.slug`, `action_url = "/notifications/general/{slug}"`.
  3. Set `status='sent'`, `sent_at=now()`.
- Gửi ngay: gọi trực tiếp trong controller action (queued job nếu audience lớn — dùng `ShouldQueue` job bọc quanh service, theo pattern `bulk-broadcast-from-tenants` đã có).
- Lên lịch: command mới `notifications:dispatch-scheduled-general-pages`, chạy mỗi phút qua scheduler (`app/routes/console.php`), quét `status='scheduled' AND scheduled_at <= now()`.
- Hết hạn: không cần job riêng — kiểm tra `expires_at` **tại thời điểm render trang** (mục 5.3), không cần đổi `status` bằng cron (tránh thêm 1 job chỉ để đổi 1 cờ hiển thị).

### 5.3 Xem trang (FE user app)

- Route mới `/notifications/general/:slug` trong `app.tsx`, dùng `AppLayout` bình thường.
- `GET /api/v1/notifications/general/:slug` (module `Notifications`, tenant-scoped qua middleware hiện có): trả nội dung trang nếu tenant hiện tại nằm trong audience (kiểm tra qua đã có `app_notifications` row cho tenant đó — không public); nếu `expires_at` đã qua → trả lỗi domain riêng, FE hiện "Nội dung đã hết hạn" thay vì trang trắng.
- Lần đầu tenant user mở trang thành công → ghi `general_notification_page_views` (insert idempotent qua unique constraint, bọc trong try/catch bỏ qua lỗi trùng — không dùng `firstOrCreate` để tránh 1 query SELECT thừa mỗi lần xem).

## 6. Frontend — panel thông báo 3 tab

- `NotificationBell.tsx`: thêm `Tabs` (antd) trong `Popover`, 3 tab **Đơn hàng / Hệ thống / Chung**.
- `lib/notifications.ts`: `useNotifications(category?)` — thêm query param `category` vào `GET /api/v1/notifications`; response `meta` trả thêm `unread_count_by_category: { order, system, general }` để hiện số riêng từng tab (badge chuông vẫn dùng tổng `unread_count` như cũ, không đổi).
- Click 1 item:
  - `category='order'` hoặc `'system'`: giữ nguyên hành vi hiện tại (điều hướng `action_url` cùng tab).
  - `category='general'`: `window.open(action_url, '_blank')`, đồng thời vẫn gọi `useMarkNotificationRead` như các category khác (đánh dấu đã đọc trong panel, không phụ thuộc việc mở trang thành công).
- Không đổi cơ chế realtime (Reverb broadcast + polling fallback) — event `NotificationCreated` giữ nguyên payload, chỉ thêm field `category` vào payload đã broadcast.

## 7. Testing

Theo `test-verify-baseline` (không có JS test runner trong repo):
- PHPUnit Feature test: migration backfill `category` đúng theo `type` cũ; mỗi listener mới (5 nguồn lỗi) bắn đúng notification khi event fire + dedup hoạt động (bắn 2 lần liên tiếp chỉ tạo 1 row chưa đọc); endpoint CRUD `general_notification_pages` + validate audience (`all` vs `tenant_ids` rỗng phải lỗi); command `dispatch-scheduled-general-pages` chỉ dispatch đúng page đến hạn; endpoint `GET /notifications/general/:slug` chặn tenant ngoài audience (403) và trả lỗi khi hết hạn; view-tracking insert đúng 1 lần dù gọi nhiều lần.
- FE: chỉ `npm run typecheck && npm run build`, không viết test JS mới.

## 8. Tổng hợp API mới

| Method | Path | Ghi chú |
|---|---|---|
| GET | `/api/v1/notifications` | Sửa: thêm query `category`, response `meta.unread_count_by_category` |
| GET | `/api/v1/notifications/general/{slug}` | Mới — tenant user xem nội dung trang, tự ghi view |
| GET | `/api/v1/admin/general-notification-pages` | Mới — danh sách (admin) |
| POST | `/api/v1/admin/general-notification-pages` | Mới — tạo draft |
| PUT | `/api/v1/admin/general-notification-pages/{id}` | Mới — sửa draft/scheduled |
| POST | `/api/v1/admin/general-notification-pages/{id}/send` | Mới — gửi ngay hoặc lên lịch tuỳ có `scheduled_at` |
| GET | `/api/v1/admin/general-notification-pages/{id}/stats` | Mới — số lượt xem / tổng audience |

Phải cập nhật `docs/05-api/endpoints.md` theo đúng luật CLAUDE.md khi implement.

## 9. Migration cần cho prod

1. `app_notifications`: thêm cột `category` + backfill + index mới.
2. Bảng mới `general_notification_pages`.
3. Bảng mới `general_notification_page_views`.

## 10. Rủi ro / điểm cần chú ý khi viết plan

- Mục 4 (5 nguồn lỗi) là phần việc lớn nhất — chạm vào 4 module khác nhau (`Fulfillment`, `Billing`, `Ai`/`Messaging`) chỉ để thêm event bắn ra; cần review kỹ từng điểm chèn để không đổi hành vi nghiệp vụ hiện có (chỉ thêm `event()` call, không sửa luồng xử lý lỗi/retry sẵn có). Việc `Notifications` nghe event từ `Billing`/`Fulfillment`/`Ai` là hợp lệ theo `docs/01-architecture/modules.md:63` (giao tiếp qua domain event không bị coi là phụ thuộc module) — không cần xác nhận thêm.
- Cần xác nhận khi viết plan: cơ chế phân quyền admin gửi audience `tenant_ids` — dùng chung permission nào với `AdminBroadcastsPage` hiện có (tránh tạo permission mới không cần thiết).
