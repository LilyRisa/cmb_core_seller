# SPEC 0029: Mobile API Access (Permissions + CORS + Expo Push)

- **Trạng thái:** Implemented (branch `feat/mobile-permissions-cors-push`)
- **Phase:** M0 (tiền điều kiện cho mobile milestones)
- **Module backend liên quan:** Tenancy (auth payload + device registry), Messaging (Expo Push)
- **Tác giả / Ngày:** 2026-06-01
- **Liên quan:** Token auth `/auth/token` (SPEC 2026-06-01, đã có), SPEC-0024 (Web Push baseline),
  `app_mobile_cmbcoreseller/docs/...` (mobile design §3), `docs/05-api/endpoints.md`

## 1. Vấn đề & mục tiêu

App mobile Expo React Native là client thuần của API Laravel. Token Bearer auth (`POST
/api/v1/auth/token`) ĐÃ có. Còn 3 khoảng trống mobile phụ thuộc:
(a) payload user thiếu **permissions** per-tenant để app bật/tắt UI theo quyền,
(b) **CORS** cho Expo Web test trên trình duyệt (header `Authorization`, `X-Tenant-Id`),
(c) **Expo Push** để nhận thông báo tin nhắn mới khi app nền — tách hoàn toàn khỏi Web
Push VAPID hiện có (không đụng vào).

## 2. Trong / ngoài phạm vi

**Trong:**
- Thêm `permissions: string[]` vào mỗi tenant trong `ResolvesAuthUserPayload::userPayload`
  → tự chảy vào `/auth/token`, `/auth/me`, login, profile.
- `config/cors.php` — env-driven `CORS_ALLOWED_ORIGINS`, cho phép `Authorization`, `X-Tenant-Id`.
- `POST /api/v1/me/devices` + `DELETE /api/v1/me/devices/{id}` — đăng ký/gỡ thiết bị Expo.
- `ExpoPushSender` service + tích hợp vào `messaging:push-digest`.

**Ngoài (làm sau):**
- Order/status push (cần event `OrderStatusChanged` payload phù hợp).
- Per-device notification preferences; refresh token flow.

## 3. Hành vi & quy tắc nghiệp vụ

- **Permissions trong payload:** lấy từ `Role::permissions()` (1 nguồn — KHÔNG hardcode).
  - Role chứa `'*'` (Owner, Admin) ⇒ trả `['*']` (app hiểu là toàn quyền).
  - Role hạt mịn ⇒ trả danh sách quyền tường minh, BỎ chuỗi phủ định (`!...`) vì app chỉ
    cần các quyền được CẤP để bật UI. Ví dụ `staff_warehouse` ⇒ chứa `fulfillment.scan`.
- **CORS:** `paths=['api/*','sanctum/csrf-cookie']`, `methods=['*']`, headers gồm
  `Authorization`, `X-Tenant-Id`, `Accept`, `Content-Type`. `supports_credentials=true`
  (giữ luồng SPA cookie). Native Bearer client không kích hoạt CORS — chỉ Expo Web cần.
- **Device upsert:** `POST /me/devices` upsert theo `expo_push_token` (unique toàn bảng);
  idempotent. Lần đầu set baseline `last_notified_at=now` tránh push dồn lịch sử.
- **Ownership:** `DELETE /me/devices/{id}` chỉ xoá device của chính user + tenant hiện tại
  ⇒ `404 DEVICE_NOT_FOUND` nếu không sở hữu.
- **Expo digest:** cùng logic `PushNewMessageDigest` — đếm conversation có
  `last_inbound_at > last_notified_at` của thiết bị inactive ⇒ gửi "Bạn có tin nhắn mới";
  cập nhật `last_notified_at`. Expo trả `DeviceNotRegistered` ⇒ xoá row. Web push & Expo
  push gate ĐỘC LẬP (tắt 1 kênh không ảnh hưởng kênh kia).

## 4. Dữ liệu

Bảng mới `mobile_devices`: `id`, `tenant_id` (index), `user_id` (index),
`expo_push_token` (string 255, UNIQUE), `platform` (`ios|android`), `last_seen_at?`,
`last_notified_at?` (index), `timestamps`; composite index `(tenant_id, user_id)`.

**KHÔNG dùng `BelongsToTenant`** — quyết định MIRROR `PushSubscription`: digest command
chạy trong scheduler không có request tenant context và phải quét cross-tenant. `tenant_id`
set tường minh ở controller (từ `CurrentTenant`). Đây là lý do model nằm cùng pattern với
`PushSubscription` thay vì các business table tenant-scoped.

## 5. Module boundary

- `MobileDevice` model + `MobileDeviceController` đặt trong **Tenancy** module: registry
  thuộc về user+tenant (cùng tầng device/token management); Messaging chỉ ĐỌC khi gửi push.
- `ExpoPushSender` + contract đặt trong **Messaging** module (nơi push logic sống). Messaging
  được phép phụ thuộc Tenancy (base module). Contract `ExpoPushSenderContract` bind ở
  `MessagingServiceProvider`.

## 6. Config

- `config/services.php` → `expo.enabled` (`EXPO_PUSH_ENABLED`, default false),
  `expo.access_token` (`EXPO_ACCESS_TOKEN`, optional), `expo.url` (batch endpoint v2).
- `config/cors.php` → `CORS_ALLOWED_ORIGINS` (CSV, dev default `http://localhost:8081`).
- `.env.example` (cả root & `app/`) đã thêm 3 key trên.

## 7. Kiểm thử

- `MobileTokenAuthTest` — bổ sung: payload `/auth/token` & `/auth/me` chứa
  `tenants[].permissions` đúng theo role (warehouse thấy `fulfillment.scan`; owner ⇒ `['*']`).
- `MobileDeviceTest` — register (upsert dedup theo token), delete (ownership 404),
  validate platform, auth required, CORS preflight (`Authorization`/`X-Tenant-Id`).
- `ExpoPushDigestTest` — digest gửi Expo push cho device inactive (mock `Http`, KHÔNG hit
  exp.host); skip device active / không có tin mới; `DeviceNotRegistered` ⇒ xoá row;
  `expo.enabled=false` ⇒ không gửi; contract bound.

## 8. Tiêu chí hoàn thành

- [x] `permissions` xuất hiện trong mỗi tenant của payload auth (token/me/login/profile).
- [x] `config/cors.php` cho phép `Authorization` + `X-Tenant-Id`, origin env-driven.
- [x] `POST /me/devices` upsert; `DELETE /me/devices/{id}` ownership-guarded 404.
- [x] `messaging:push-digest` gửi Expo push kèm Web push (gate độc lập).
- [x] pint + phpstan xanh; PHPUnit Feature tests PASS.
- [x] `docs/05-api/endpoints.md` cập nhật.

## 9. Câu hỏi mở / giả định

- `permissions` cho role hạt mịn bỏ chuỗi phủ định — app chỉ cần quyền được cấp. Nếu sau
  này app cần biểu diễn negation, mở rộng shape (ví dụ trả cả `denied[]`).
- Order push: follow-up sau khi xác nhận shape `OrderStatusChanged`.
