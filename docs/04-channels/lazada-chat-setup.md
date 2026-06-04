# Cấu hình Lazada Chat (IM) — SPEC-0024 / ADR-0017, ADR-0019

Hướng dẫn bật tính năng nhắn tin (Instant Messaging) của Lazada trong CMBcoreSeller.

> **Trạng thái: best-effort (SPEC-0024 §11 Q3).** Lazada IM Open API có lịch sử thay
> đổi & giới hạn theo vùng/version. Doc chính thức yêu cầu đăng nhập app
> (JS-rendered) nên các tham số dưới đây tổng hợp từ **doc URL chính thức + SDK
> `lazada-openapi`** — **PHẢI verify lại trên sandbox Lazada** trước khi bật
> production (`INTEGRATIONS_MESSAGING` không gồm `lazada_chat` mặc định).
>
> Nguồn: [Lazada IM Open API](https://open.lazada.com/apps/doc/doc?nodeId=30739&docId=120971) ·
> [`/im/message/send`](https://open.lazada.com/apps/doc/api?path=/im/message/send) ·
> [`/im/message/list`](https://open.lazada.com/apps/doc/api?path=/im/message/list) ·
> [`/im/session/list`](https://open.lazada.com/apps/doc/api?path=/im/session/list) ·
> [Lazada Push Mechanism](https://open.lazada.com/apps/doc/doc?nodeId=29526&docId=120168) ·
> SDK [lazada-openapi](https://www.npmjs.com/package/lazada-openapi).

---

## 0. Kiến trúc (đã code — chỉ cần cấu hình + verify)

| Thành phần | Vị trí | Vai trò |
|---|---|---|
| `LazadaChatConnector` | `app/Integrations/Messaging/Lazada/` | verify webhook, parse, gửi tin (`/im/message/send`), ký `LazadaSigner` |
| Webhook nhận | `POST /webhook/messaging/lazada_chat` | Lazada App Push đẩy tin → verify chữ ký → store → xử lý async |
| OAuth | **App "IM ERP" RIÊNG** — OAuth riêng (`/oauth/lazada_im/callback`), token riêng. KHÔNG dùng chung token orders (ADR-0019 amendment 2026-06-04). |
| Provider account | `channel_accounts.provider = 'lazada_im'` (tách khỏi `lazada` của orders; 1 shop có thể có 2 row) |

`external_conversation_id` = **session_id** (Lazada IM); `external_shop_id` = **seller_id**.
Connector dùng `config('integrations.messaging_lazada_im')` (app IM ERP riêng). Kết nối qua nút
**"Kết nối Lazada IM Chat"** ở `/messaging/channels` (giống kết nối Facebook Page).

> ⚠️ **Lazada gate quyền IM theo app.** Gọi IM bằng token app orders trả
> `{"type":"ISV","code":"InsufficientPermission"}`. PHẢI tạo app loại **IM ERP** riêng trên
> Lazada Open Platform (xác nhận với Lazada support) rồi seller ủy quyền app đó.

---

## 1. Endpoints Lazada IM (Open Platform)

| Path | Mục đích | Tham số chính |
|---|---|---|
| `POST /im/message/send` | Gửi tin | `session_id`, `template_id`, content (xem §2), + system params |
| `GET /im/message/list` | Liệt kê tin trong session (polling backup) | `session_id`, paging |
| `GET /im/session/list` | Liệt kê session (polling backup) | paging |

System params chung (mọi call Lazada): `app_key`, `sign_method=sha256`, `timestamp` (ms),
`access_token`, `sign`. `sign` ký theo `LazadaSigner` (HMAC-SHA256, sort key, prepend path,
UPPERCASE hex).

> Polling (`/im/*/list`) hiện connector chưa bật (`inbound.polling=false`) — webhook là
> đường chính. Bật khi có job `PollConversations` (xem `07-infra/queues-and-scheduler.md`).

---

## 2. Gửi tin: `template_id` + field nội dung

Lazada IM `/im/message/send` dùng `template_id` để chọn loại nội dung; field content
phẳng đi kèm (KHÔNG bọc JSON):

| `template_id` | Loại | Field nội dung |
|---|---|---|
| 1 | Text | `txt` |
| 2 | Image | `img_url`, `width`, `height` |
| 3 | Product | `item_id` |
| 4 | Order | `order_id` |
| 5 | Promotion | `promotion_id` |

Connector hiện gửi **text** (`template_id=1`, `txt`) và **image** (`template_id=2`,
`img_url`). ⚠️ Giá trị int `template_id` cần **xác nhận lại sandbox** (doc gated).

---

## 3. Webhook (Lazada App Push)

1. Lazada Open Platform → app → **Push Mechanism** → đặt **URL Call Back**:
   `https://<APP_DOMAIN>/webhook/messaging/lazada_chat`.
2. Subscribe message-notification (IM) event.
3. Verify chữ ký: connector thử **(A)** header HMAC-SHA256(rawBody, app_secret) hex
   (`X-Lazop-Sign`/`Lazop-Sign`/…), **(B)** body có `sign` (ký các key còn lại sort+concat).
   Sai cả hai ⇒ 401.
4. Payload `data` mang `session_id`, `message_id`, `from_account_id`, `seller_id` → connector
   map sang `MessagingWebhookEventDTO` (TYPE_MESSAGE_RECEIVED).

---

## 4. Biến môi trường

```dotenv
# Bật connector (CSV — thêm vào cùng các provider khác)
INTEGRATIONS_MESSAGING=facebook_page,lazada_chat

# Dùng chung credentials Lazada với orders (Channels) — super-admin nhập ở /admin/system-settings
# marketplace.lazada.app_key / marketplace.lazada.app_secret, hoặc:
LAZADA_APP_KEY=xxxxx
LAZADA_APP_SECRET=xxxxxxxxxxxx
# base_url theo vùng: VN https://api.lazada.vn/rest
```

Access token IM = token Lazada orders đã có (ADR-0019) — không cần OAuth riêng.

---

## 5. Kiểm thử & lưu ý

- Contract test: `TikTokLazadaChatConnectorTest` (verify chữ ký + parse webhook + shape
  send `template_id=1`+`txt` qua `Http::fake`). Live cần app Lazada thật + verify region.
- Trước production: xác nhận (a) Lazada IM còn mở cho seller-app ở vùng bạn, (b) đúng
  int `template_id`, (c) đúng tên event message-notification của App Push.
- Mở rộng / sửa: chỉ trong `LazadaChatConnector` — không đụng controller/pipeline (ADR-0017).
