# Lazada IM Chat qua app IM ERP RIÊNG (tách khỏi orders) — Design

> SPEC-0024 nhánh Lazada chat · ADR-0017 (connector registry) · **điều chỉnh ADR-0019**
> Ngày: 2026-06-04 · Trạng thái: approved (Cách A — tách hoàn toàn)

## 1. Bối cảnh & vấn đề

Lazada IM Open API trả `{"type":"ISV","code":"InsufficientPermission","message":"App does not have permission to access this api"}` khi gọi `/im/session/list` bằng token của **app orders** (`config('integrations.lazada')`). Lazada support xác nhận: **phải tạo app mới loại "IM ERP" riêng**, không gắn quyền IM vào app orders được.

Điều này phá vỡ giả định **ADR-0019** (messaging dùng chung app + token với orders) — nhưng **chỉ riêng Lazada**. TikTok/Shopee vẫn dùng chung app orders (đã rà soát: chỉ cần duyệt scope/whitelist).

## 2. Quyết định — Cách A: messaging provider độc lập (mô phỏng Facebook Page)

Lazada IM thành một provider nhắn tin độc lập, có **app riêng + OAuth riêng + `channel_accounts` riêng + token riêng** — đúng pattern Facebook Page (`facebook_page`) đã chạy trong hệ thống.

- Provider mới `lazada_im` (tách khỏi provider `lazada` của orders).
- Connector class `LazadaChatConnector` (code `lazada_chat`) **giữ nguyên** logic poll/gửi; chỉ đổi **nguồn config** sang block IM ERP và **bổ sung OAuth + surfacing lỗi**.
- 1 shop Lazada → 2 dòng `channel_accounts`: `lazada` (orders) + `lazada_im` (chat), gắn nhau qua cùng `external_shop_id` (seller_id).

## 3. ⭐ Phân tích tác động ENV/CONFIG (không gây lỗi sang thành phần khác)

| Thay đổi | Ảnh hưởng | Giảm thiểu |
|---|---|---|
| **Thêm** env `LAZADA_IM_APP_KEY`, `LAZADA_IM_APP_SECRET`, (opt) `LAZADA_IM_REDIRECT_URI`; block config `integrations.messaging_lazada_im` | Thuần bổ sung | Không đụng `integrations.lazada` (orders) — orders connector không đổi |
| `INTEGRATIONS_MESSAGING` | **KHÔNG đổi** — connector code vẫn `lazada_chat`; registry enablement giữ nguyên | Prod env hiện tại tiếp tục chạy |
| `LazadaChatConnector` đọc `config('integrations.lazada.*')` → đổi sang `config('integrations.messaging_lazada_im.*')` | Chỉ trong **1 class** (chat connector) | Orders `LazadaConnector` đọc `integrations.lazada` — **không bị đụng** (class khác). `verifyWebhookSignature` của chat dùng app_secret IM — an toàn vì Lazada IM **không có webhook** (polling-only), route webhook không gọi tới |
| `messagingConnectorCode()`: **bỏ** `'lazada'=>'lazada_chat'`, **thêm** `'lazada_im'=>'lazada_chat'` | Account orders `lazada` không còn poll chat (đúng ý — đường shared-app vốn lỗi). | Mọi consumer đã **null-guard**: `SyncConversationsForShop`/`ReconcileMessagingSync` return sớm khi code=null (không lỗi). Resync endpoint trả 422 cho `lazada` — FE **không** hiển thị nút chat trên gian hàng orders. Prod có 0 hội thoại `lazada` ⇒ không cần migrate dữ liệu |
| Connector **không** thêm constructor (vẫn đọc config inline) | Registry `container->make()` + 11 chỗ `new LazadaChatConnector` trong unit test giữ nguyên chữ ký | Chỉ đổi KEY config đọc; cập nhật test set key mới `messaging_lazada_im.*` |
| FE: thêm provider `lazada_im` (nhãn/icon) + nút "Kết nối Lazada IM Chat" | `MessagingChannelGroup.forProvider('lazada_im')`=marketplace (đúng) | Thêm nhãn hiển thị; không đụng nhóm facebook |

**Kết luận:** mọi thay đổi hoặc thuần bổ sung, hoặc cô lập trong chat connector + bảng mapping (đã null-guard toàn tuyến). Không có đường nào làm orders/Facebook/TikTok/Shopee lỗi.

## 4. Components

**Thêm mới**
- `config/integrations.php` → `messaging_lazada_im`: `app_key`, `app_secret`, `redirect_uri`, `api_base_url`, `auth_base_url`, `authorize_url`, `partner_id`, `authorize_force_auth`, `authorize_country` (mặc định kế thừa giá trị Lazada VN).
- `LazadaImOAuthController` (Messaging module, mô phỏng `FacebookOAuthController`):
  - `start(Request)`: `Gate::authorize('messaging.connect')` → `OAuthState::issue('lazada_im', ...)` → `connector->buildAuthorizationUrl($state)` → trả `authorize_url`.
  - `callback(Request)`: verify state → `connector->exchangeCodeForToken($code)` → lấy `seller_id` từ token raw (`country_user_info[].seller_id`) → `updateOrCreate channel_accounts(provider=lazada_im, external_shop_id=seller_id, access_token, refresh_token, token_expires_at, messaging_enabled=true, status=active)` → tạo `MessagingAccountMeta(SYNC_QUEUED)` → `SyncConversationsForShop::dispatch` → redirect SPA.
- Routes: `POST /api/v1/messaging/lazada-im/connect` (auth, api.php trong module) + `GET /oauth/lazada_im/callback` (web.php).

**Sửa**
- `LazadaChatConnector`: đọc `config('integrations.messaging_lazada_im')`; **implement** `buildAuthorizationUrl`/`exchangeCodeForToken`/`refreshToken` (dùng `LazadaSigner` + `/auth/token/create|refresh` ở `auth_base_url`, mirror `LazadaClient`); **surfacing lỗi**: `fetchConversations`/`fetchMessages` kiểm `!successful()` hoặc `code` != 0/'' → ném `RuntimeException` (giống `send()`), giữ map rate-limit.
- `ChannelAccount::messagingConnectorCode()`: bỏ `lazada`, thêm `lazada_im`.
- FE `messagingConfig.tsx` + trang `/messaging/channels`: thêm `useConnectLazadaIm()` (`POST /messaging/lazada-im/connect`) + nút kết nối; nhãn provider `lazada_im`.

## 5. Data flow

- **Kết nối:** FE → connect → authorize_url (app IM ERP) → seller đồng ý → `/oauth/lazada_im/callback` → token IM → account `lazada_im` + meta `SYNC_QUEUED` → dispatch poll.
- **Nhận:** scheduler `messaging-chat-poll` (5') → account `lazada_im` active+enabled → `lazada_chat` connector + token IM → `/im/session/list`+`/im/message/list` → ingest → hộp thư. (account `lazada` orders → code null → bỏ qua.)
- **Trả lời:** composer → `send_message` bằng token IM.

## 6. Error handling & token refresh

- Surfacing: lỗi Lazada (InsufficientPermission/token/sign/rate-limit) hiện vào `messaging_account_meta.sync_error` thay vì âm thầm 0 tin.
- Refresh: lưu `refresh_token`+expiry; job refresh riêng cho `lazada_im` (tái dùng `refreshToken` connector, credential IM ERP). Token hết hạn ⇒ status `expired` → FE nhắc kết nối lại.

## 7. Testing

- `LazadaImOAuthTest` (mô phỏng `MessagingFacebookOAuthTest`): fake `/auth/token/create` → assert account `lazada_im` + messaging_enabled + meta queued + job dispatched.
- Mapping: `messagingConnectorCode('lazada_im')==='lazada_chat'`, `('lazada')===null`.
- Connector đọc `messaging_lazada_im`; `buildAuthorizationUrl` chứa app_key IM + redirect_uri; `exchangeCodeForToken` parse token.
- Surfacing: response `code!='0'` → connector ném (cập nhật `TikTokLazadaChatConnectorTest` set key mới + thêm case lỗi).
- Poll resolve connector cho provider `lazada_im`.

## 8. Build sequence

1. config block + env.example + ADR-0019 note.
2. Connector: config key + OAuth methods + surfacing (test-first).
3. `messagingConnectorCode` mapping (test).
4. OAuth controller + routes (test-first: `LazadaImOAuthTest`).
5. Cập nhật unit test connector.
6. FE connect entry + provider label.
7. Quality gate: pint, phpstan, phpunit, npm lint/typecheck/build.

## 9. ADR

Điều chỉnh **ADR-0019**: thêm mục "Ngoại lệ Lazada IM" — KHÔNG dùng chung app/token orders; có app IM ERP + OAuth + `channel_accounts(provider=lazada_im)` + token riêng. TikTok/Shopee vẫn theo ADR-0019 gốc.
