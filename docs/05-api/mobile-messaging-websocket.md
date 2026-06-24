# Realtime tin nhắn cho App Mobile (WebSocket / Reverb)

> Hướng dẫn cho **client mobile** (Expo/React Native) nhận tin nhắn realtime qua Laravel
> Reverb bằng **bearer token**. Phần hạ tầng server xem [`docs/07-infra/reverb-realtime-setup.md`](../07-infra/reverb-realtime-setup.md).
> Liên quan: SPEC-0029 (mobile API), ADR-0021 (messaging realtime).

## 1. Tổng quan

Mobile dùng **Sanctum personal access token** (bearer) để:

1. Đăng nhập lấy token: `POST /api/v1/auth/token`.
2. Authorize private channel WebSocket qua: `POST /api/v1/broadcasting/auth` (header `Authorization: Bearer <token>`).
3. Kết nối Reverb (giao thức Pusher) và `listen` channel **`private-tenant.{tenantId}.messaging`**.

Sự kiện chỉ mang **ID** (không kèm nội dung) → khi nhận, gọi REST để lấy dữ liệu mới (giống web).
Nếu Reverb không bật/không kết nối được ⇒ **fallback polling** (gọi REST định kỳ).

```
Mobile app ──wss──► Reverb (/app/{key})            ← stream sự kiện
   │
   │ POST /api/v1/broadcasting/auth (Bearer token)  ← authorize channel
   ▼
   cmb-app (Laravel) ── broadcast(MessageReceived) ─► Reverb
```

## 2. Lấy token (đăng nhập mobile)

```
POST /api/v1/auth/token
Content-Type: application/json
{ "login": "seller@example.com", "password": "...", "device_name": "iPhone 15" }

→ 201 { "data": { "token": "12|abc...xyz", "user": { id, name, tenants:[{id,...}], ... } } }
```

- Lưu `token` an toàn (Keychain/Keystore). Hết hạn mặc định 60 ngày (`sanctum.mobile_token_days`).
- `tenantId` để subscribe lấy từ `user.tenants[].id` (shop đang chọn).

## 3. Authorize channel (bearer)

Client Echo/Pusher sẽ tự gọi endpoint này mỗi khi subscribe một private channel:

```
POST /api/v1/broadcasting/auth
Authorization: Bearer 12|abc...xyz
Content-Type: application/json
{ "socket_id": "<từ pusher>", "channel_name": "private-tenant.42.messaging" }

→ 200 { "auth": "<key>:<signature>" }      // thành công
→ 403  (không có quyền messaging.view ở tenant đó / không phải thành viên)
→ 401  (token sai/thiếu)
```

> Endpoint web cũ `POST /broadcasting/auth` (session cookie) **không** dùng cho mobile —
> mobile dùng `/api/v1/broadcasting/auth` (guard `sanctum`, ép bearer token).

Quyền: channel authorize đòi user là thành viên tenant **và** có `messaging.view` (xem
`routes/channels.php` + `MessagingChannelAuthorizer`).

## 4. Tham số kết nối Reverb

Lấy từ cấu hình server (giá trị công khai `REVERB_*`, KHÔNG phải secret):

| Tham số client | Nguồn (env server) | Ghi chú |
|---|---|---|
| `key` | `REVERB_APP_KEY` | app key công khai |
| `wsHost` | `REVERB_HOST` | domain Reverb (vd `realtime.cmbcore.com`) |
| `wsPort` / `wssPort` | `REVERB_PORT` | 443 ở prod (wss) |
| `scheme` / `forceTLS` | `REVERB_SCHEME` | `https` ⇒ `forceTLS: true` |
| broadcaster | — | `'reverb'` (tương thích Pusher protocol) |

App mobile có thể hardcode theo môi trường hoặc lấy động từ một endpoint cấu hình nếu cần.

## 5. Channel & sự kiện

**Channel (private):** `tenant.{tenantId}.messaging` (Echo tự thêm tiền tố `private-`).

| Sự kiện (`.broadcastAs`) | Payload | Ý nghĩa |
|---|---|---|
| `.message.received` | `{ message_id, conversation_id, requires_human }` | Có tin **đến** mới |
| `.message.sent` | `{ message_id, conversation_id }` | Tin **gửi đi** (đồng bộ đa thiết bị) |
| `.conversation.created` | `{ conversation_id }` | Hội thoại mới |

**Quy tắc xử lý:** payload chỉ có ID → khi nhận, gọi REST để cập nhật:
- danh sách hội thoại: `GET /api/v1/messaging/conversations?...` (kèm header `X-Tenant-Id`).
- nội dung 1 hội thoại: `GET /api/v1/messaging/conversations/{id}/messages` (tuỳ API hiện có).
- badge chưa đọc: `GET /api/v1/messaging/conversations?unread=true`.

> Lưu ý header REST: API user yêu cầu `X-Tenant-Id: {tenantId}` (middleware `tenant`).
> WebSocket KHÔNG cần header này — tenant đã nằm trong tên channel.

## 6. Ví dụ client (laravel-echo + pusher-js, React Native)

```ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js/react-native';

const token = await getStoredToken();          // "12|abc..."
const tenantId = currentTenantId();            // user.tenants[].id

const echo = new Echo({
  broadcaster: 'reverb',
  Pusher,
  key: REVERB_APP_KEY,
  wsHost: REVERB_HOST,
  wsPort: REVERB_PORT,
  wssPort: REVERB_PORT,
  forceTLS: REVERB_SCHEME === 'https',
  enabledTransports: ['ws', 'wss'],
  authEndpoint: `${API_BASE}/api/v1/broadcasting/auth`,
  auth: { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } },
});

echo.private(`tenant.${tenantId}.messaging`)
  .listen('.message.received', (e: { message_id: number; conversation_id: number; requires_human: boolean }) => {
    refetchConversation(e.conversation_id);    // gọi REST lấy nội dung
    refetchUnreadBadge();
  })
  .listen('.message.sent', (e) => refetchConversation(e.conversation_id))
  .listen('.conversation.created', () => refetchConversationList());

// Khi đổi shop: echo.leave(`tenant.${oldTenantId}.messaging`) rồi subscribe tenant mới.
// Khi logout: echo.disconnect().
```

> Tên sự kiện có **dấu chấm đầu** (`.message.received`) vì server đặt `broadcastAs()` không
> theo namespace mặc định.

## 7. Fallback & vận hành

- **Fallback polling:** nếu không kết nối được Reverb (server tắt / mạng chặn ws), mobile nên
  poll REST định kỳ (vd 15–30s) cho danh sách hội thoại + badge — giống web.
- **Bật realtime ở prod:** cần `BROADCAST_CONNECTION=reverb` và **Reverb server đang chạy**
  (`php artisan reverb:start` / container), reverse proxy route `/app` kèm header `Upgrade`
  (xem [`reverb-realtime-setup.md`](../07-infra/reverb-realtime-setup.md)). Dev mặc định
  `BROADCAST_CONNECTION=log` ⇒ realtime tắt, chỉ còn polling.
- **Token hết hạn / 401 khi auth channel:** đăng nhập lại lấy token mới rồi reconnect.
- **403 khi auth channel:** user mất quyền `messaging.view` ở tenant đó hoặc đã rời shop.

## 8. Tham chiếu

- Endpoint: `POST /api/v1/auth/token`, `POST /api/v1/broadcasting/auth` — [`endpoints.md`](endpoints.md).
- Channel authorize: `app/routes/channels.php`, `app/app/Modules/Messaging/Support/MessagingChannelAuthorizer.php`.
- Sự kiện: `app/app/Modules/Messaging/Events/{MessageReceived,MessageSent,ConversationCreated}.php`.
- Test: `app/tests/Feature/Messaging/MobileBroadcastAuthTest.php`.
- Hạ tầng Reverb: [`docs/07-infra/reverb-realtime-setup.md`](../07-infra/reverb-realtime-setup.md).
