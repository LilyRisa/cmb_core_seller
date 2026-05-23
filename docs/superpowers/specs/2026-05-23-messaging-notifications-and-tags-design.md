# Thiết kế: Gắn thẻ inline + Thông báo tin nhắn (in-app + Web Push)

- **Ngày:** 2026-05-23
- **Liên quan:** SPEC-0024 (Hộp thư hợp nhất), Settings module (2026-05-17), Messaging polling.

## Bối cảnh / vấn đề
1. **Gắn thẻ:** Popover "Gắn thẻ" chỉ liệt kê thẻ CÓ SẴN; khi chưa có thẻ nào hiện "Chưa có thẻ nào." và không có cách tạo thẻ ngay → người dùng bí. Tạo thẻ chỉ nằm trong "Quản lý thẻ" (chôn trong popover bộ lọc).
2. **Thông báo:** Chưa có thông báo tin nhắn mới. Inbox dùng polling (15s/10s), KHÔNG có Reverb/websocket, KHÔNG có service worker. `public/noti.wav` đã có sẵn.

## Quyết định

### 1. Gắn thẻ inline (FE)
Thêm form tạo thẻ ngay trong popover `tagAttachContent`: input tên + chọn màu (palette preset) + nút "Tạo & gắn" → `useSaveTag` → gắn thẻ mới vào hội thoại (`useSetConversationTags`). Refetch tags + conversations.

### 2. Thông báo trong app (client-side, dựa polling)
Hook `useMessageNotifications(conversations)`:
- Lập baseline lần đầu (không bắn cho dữ liệu cũ).
- Mỗi lần poll: phát hiện hội thoại có inbound MỚI (so `last_inbound_at` / unread tăng so với lần trước).
- Khi tab **visible** (`document.visibilityState==='visible'`): antd `notification` "Có tin nhắn mới từ \<tên\>" + phát `/noti.wav`.
- Xin quyền Notification qua nút "Bật thông báo".

### 3. Web Push (tab đóng/nền) — gom 30 phút
- **Dep:** `minishlink/web-push`. **Cấu hình VAPID lưu trong DB (system_settings), sửa ở /admin/settings** — KHÔNG dùng env trực tiếp:
  - `push.vapid_public_key` (string, public) · `push.vapid_private_key` (string, **secret**) · `push.vapid_subject` (string, vd `mailto:admin@domain`).
  - Group mới `push` (label "Thông báo") trong `SystemSettingsCatalog` + FE `SystemSettingsPage`.
  - Đọc qua `system_setting('push.vapid_public_key', ...)`.
- **DB:** `messaging_push_subscriptions` (id, tenant_id, user_id, endpoint UNIQUE, p256dh, auth, last_seen_at, last_notified_at, timestamps).
- **Service worker** `public/sw.js`: `push` → `showNotification`; `notificationclick` → focus/mở `/messaging`.
- **FE** `usePushNotifications`: đăng ký SW, xin quyền, `PushManager.subscribe(applicationServerKey=vapid_public_key)`, POST subscription; **heartbeat** cập nhật `last_seen_at` khi tab visible (interval + visibilitychange).
- **API** (nhóm messaging auth): `POST /messaging/push/subscribe`, `POST /messaging/push/heartbeat`, `DELETE /messaging/push/subscribe`. `GET /messaging/push/public-key` (trả VAPID public để FE subscribe).
- **Service** `WebPushSender`: bọc minishlink/web-push, gửi 1 push; 404/410 → xoá subscription.
- **Command** `messaging:push-digest` (scheduler **mỗi 30 phút**): với subscription **không hoạt động** (`last_seen_at` cũ hơn ~5 phút ⇒ tab đóng/away): đếm hội thoại có `last_inbound_at > last_notified_at` trong tenant đó; nếu ≥1 → gửi push "**N người nhắn tin mới**", set `last_notified_at = now`.

## Thành phần & ranh giới
- FE: `tagAttachContent` (sửa) · `useMessageNotifications` (mới) · `usePushNotifications` (mới) · `public/sw.js` · `SystemSettingsPage`/catalog group `push`.
- BE: migration + `PushSubscription` model · `PushSubscriptionController` + routes · `WebPushSender` service · `PushNewMessageDigest` command + scheduler · catalog entries.

## Kiểm thử
- Unit/Feature (test được): catalog có key push; digest đếm đúng + chỉ gửi cho sub không hoạt động + set last_notified_at; expired sub bị xoá (web-push client mock); subscribe/heartbeat endpoints; gắn thẻ inline (logic hook không test được — verify tsc/lint).
- **KHÔNG test được ở đây:** gửi push thật (cần VAPID keys + HTTPS) → tài liệu hoá cho người vận hành.

## Hệ quả / vận hành
- Cần tạo VAPID keys 1 lần (lệnh ghi trong README/hướng dẫn) rồi dán vào /admin/settings (group Thông báo).
- Web Push cần HTTPS (đã có domain prod). Service worker phục vụ từ gốc `/sw.js`.
