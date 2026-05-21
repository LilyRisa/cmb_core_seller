# Thiết kế: Connector `shopee_chat` (Shopee Seller Chat) — SPEC-0024 / ADR-0017, ADR-0019

> Trạng thái: **Đã duyệt thiết kế (2026-05-21)** — sẵn sàng viết plan.
> Mục tiêu chất lượng: **shape-tested** (đúng tài liệu Shopee + unit-test `Http::fake`),
> ngang `tiktok_chat`/`lazada_chat` — CHƯA verify sandbox thật (làm ở task riêng).

## 1. Bối cảnh & vấn đề

Hộp thư hợp nhất (SPEC-0024) đã có pipeline messaging hoàn chỉnh. Pipeline **đã wire
sẵn `shopee_chat`** ở nhiều tầng, chỉ thiếu **class connector + chỗ tách nhánh inbound +
đăng ký**:

- `ProcessMessagingWebhook::channelProviderForMessaging()` đã map `'shopee_chat' => 'shopee'`.
- `ChannelAccount::messagingConnectorCode()` đã map `'shopee' => 'shopee_chat'`.
- Route webhook messaging đã whitelist `shopee_chat` (`routes/webhook.php`).
- `IntegrationsServiceProvider::$messagingConnectors` còn để **comment** dòng `shopee_chat`
  (ghi chú cũ "chờ Channels Shopee infra" — nay hạ tầng Shopee đã có: `ShopeeClient`,
  `ShopeeSigner`, `ShopeeWebhookVerifier`, config block `integrations.shopee`).

OAuth/token dùng **chung với Gian hàng Shopee** (ADR-0019): `channel_account` provider
`shopee` (tạo từ OAuth đơn hàng) mang `access_token` + `external_shop_id = shop_id`.

### Ràng buộc kiến trúc cốt lõi
Shopee chỉ có **1 push URL/app**. Mọi push (đơn hàng code 3/4…, **chat code 10**) đều về
cùng `/webhook/shopee`. KHÔNG thể trỏ push riêng sang `/webhook/messaging/shopee_chat` như
Facebook (nếu trỏ vậy thì push đơn hàng sẽ đi lạc). ⇒ Tin chat phải được **tách nhánh** từ
luồng webhook Shopee hiện có.

## 2. Quyết định thiết kế (đã chốt)

| Hạng mục | Quyết định |
|---|---|
| Mức độ hoàn thành | Shape-tested (đúng docs + unit-test `Http::fake`), không cần verify sandbox ngay |
| Inbound | **Tách nhánh theo `code` tại webhook Shopee** (real-time): code 10 → pipeline messaging; code khác → pipeline đơn hàng |
| Outbound | **Text + ảnh** (ảnh qua signed URL, như Facebook/Lazada) |
| Gửi tin (impl) | Tái dùng `ShopeeClient::shopPost` (đã lo ký + throttle + envelope `error`) qua adapter `MessagingAuthContext → AuthContext` |
| Chỗ demux | Route riêng `/webhook/shopee` → `ShopeeWebhookController` mới; tiktok/lazada giữ `WebhookController` chung (ADR-0017 không bị đụng — Shopee là ngoại lệ vì 1 URL gánh 2 domain) |

## 3. Thành phần

### A. `ShopeeChatConnector` (MỚI)
`app/app/Integrations/Messaging/Shopee/ShopeeChatConnector.php` — implement
`MessagingConnector`, mirror `TikTokChatConnector`/`LazadaChatConnector`.

- `code() = 'shopee_chat'`, `displayName() = 'Shopee Chat'`.
- `capabilities()`: `inbound.webhook=true`, `inbound.polling=false`, `outbound.text=true`,
  `outbound.image=true`, `outbound.video/file/template=false`, `read_receipt=false`, `typing=false`.
- OAuth: `buildAuthorizationUrl`/`exchangeCodeForToken`/`refreshToken` → ném
  `UnsupportedOperation` (dùng chung OAuth đơn hàng Shopee — ADR-0019).
- `registerWebhooks()` → no-op (push cấu hình ở Shopee Console → Push Mechanism).
- `verifyWebhookSignature(Request)` → **tái dùng `ShopeeWebhookVerifier::verify()`** (inject
  qua DI) — 1 nguồn chữ ký duy nhất: `HMAC-SHA256(push_key, push_url . '|' . raw_body)`,
  `push_key` = `push_partner_key` fallback `partner_key` (đọc qua `system_setting`),
  `push_url` = `config('integrations.shopee.push_url')` ?: `url('/webhook/shopee')`.
- `parseWebhook`/`parseWebhookEvents(Request)` → parse push code 10. Body Shopee:
  `{ code:int, shop_id:int, timestamp:int, data:"<json-string>" }`.
  - Code 10 → `MessagingWebhookEventDTO(TYPE_MESSAGE_RECEIVED)` với
    `externalShopId=shop_id`, `externalConversationId=conversation_id`,
    `externalMessageId=message_id`, `buyerExternalId=from_id` (buyer).
  - Code khác → `TYPE_UNKNOWN` (ingest tự bỏ qua).
- `sendText/sendMedia(MessagingAuthContext, convId, …)` → Shopee Seller Chat
  `/api/v2/sellerchat/send_message`, ký `ShopeeSigner::signShop`. Tái dùng
  `ShopeeClient::shopPost` qua adapter dựng `AuthContext` từ `MessagingAuthContext`
  (`accessToken`, `externalShopId`). Body: `{ to_id, message_type:'text'|'image', content }`.
  - `sendText`: `message_type='text'`, `content={ text: <body> }`.
  - `sendMedia` (ảnh): `message_type='image'`, `content={ image_url: <signed URL> }`; kind ≠ image → `UnsupportedOperation`.
- `outboundWindow()` → `freeWindowHours=null, requiresTag=false` (Shopee không có hard-window
  24h như Facebook; ghi chú verify policy spam Shopee khi có sandbox).

> ⚠️ **Cần verify sandbox**: tên field chính xác trong `data` của push code 10
> (`conversation_id`/`message_id`/`from_id`/`message_type`/`content`) và schema body
> `send_message` lấy theo tài liệu/SDK Shopee Seller Chat — phải đối chiếu sandbox thật
> trước production (giống cảnh báo ở `LazadaChatConnector`).

### B. `ShopeeWebhookController` (MỚI) — demux inbound
Trong module Channels (URL `/webhook/shopee` vốn là việc của Channels). Inject cả
`WebhookIngestService` (Channels) lẫn `MessagingWebhookIngestService`.

- Đọc `code` từ body (không cần verify để **định tuyến**; mỗi ingest service tự verify chữ ký).
- `code ∈ config('integrations.shopee.chat_push_codes', [10])` **và** `MessagingRegistry::has('shopee_chat')`
  → `MessagingWebhookIngestService->ingest('shopee_chat', $request)`.
- Ngược lại → `WebhookIngestService->ingest('shopee', $request)` (hành vi cũ, không hồi quy).
- Trả `JsonResponse` theo `['status','body']` của service.

Route: `routes/webhook.php` — tách `shopee` khỏi vòng lặp chung
`['tiktok','shopee','lazada']`, thêm `Route::post('shopee', ShopeeWebhookController::class)`.
tiktok/lazada giữ nguyên `WebhookController` chung.

### C. Wiring
- `IntegrationsServiceProvider`:
  - Bỏ comment `'shopee_chat' => \CMBcoreSeller\Integrations\Messaging\Shopee\ShopeeChatConnector::class`.
  - Bind tường minh (giống `FacebookPageConnector`): inject `ShopeeWebhookVerifier` +
    `ShopeeClient` + `(array) config('integrations.shopee')`.
- `config/integrations.php` (block `shopee`):
  - `endpoints.send_message = '/api/v2/sellerchat/send_message'`.
  - `chat_push_codes = [10]` (để demux đọc; có thể override bằng env sau).

## 4. Luồng dữ liệu

**Nhận tin:** Buyer nhắn → Shopee POST code 10 → `/webhook/shopee` →
`ShopeeWebhookController` (thấy code 10) → `MessagingWebhookIngestService`
(verify chữ ký → lưu `messaging.shopee_chat` `webhook_event` → 200 nhanh) →
`ProcessMessagingWebhook` (có sẵn) resolve `channel_account(provider=shopee, shop_id)` →
`MessageIngestionService` ingest vào hộp thư.

**Gửi tin:** NV trả lời trong inbox → `SendMessage` job → `ShopeeChatConnector::sendText/sendMedia`
(token + shop_id từ `channel_account`) → Shopee `send_message`.

## 5. Lỗi & bảo mật
- Sai/thiếu chữ ký → `MessagingWebhookIngestService` trả **401**, không lưu payload.
- `send_message` lỗi (`error` envelope ≠ rỗng hoặc HTTP lỗi) → `ShopeeApiException`
  (do `ShopeeClient` ném) → `SendMessage` job retry theo backoff sẵn có.
- KHÔNG log secret: `partner_key`, `push_partner_key`, `access_token`.
- `webhook_verify_mode=lenient` (config Shopee) chỉ dùng khi chưa chốt scheme sandbox; production = `strict`.

## 6. Kiểm thử (shape-tested)

- **Unit `ShopeeChatConnectorTest`**:
  - `verifyWebhookSignature`: chữ ký đúng → true; sai/thiếu header → false.
  - `parseWebhookEvents`: payload code-10 mẫu → DTO đúng (shop_id/conv/message/buyer);
    code khác → `TYPE_UNKNOWN`.
  - `sendText`/`sendMedia`: `Http::fake` → assert POST đúng `send_message` path, có `sign`,
    body `message_type`/`content` đúng; envelope lỗi → ném exception.
- **Feature `ShopeeWebhookRoutingTest`** (demux):
  - Push code 10 (chữ ký hợp lệ) → tạo `webhook_event` provider `messaging.shopee_chat`
    + dispatch `ProcessMessagingWebhook`.
  - Push code 3 → vẫn tạo `webhook_event` provider `shopee` (đơn hàng) — không hồi quy.
  - `shopee_chat` chưa bật trong registry → code 10 rơi về luồng Channels (ignore), không vỡ.

## 7. Phạm vi & ngoài phạm vi (YAGNI)
- **Trong:** nhận tin (code 10), gửi text + ảnh, demux, đăng ký, unit/feature test.
- **Ngoài (sau):** gửi item/order/sticker; read-receipt/typing; polling
  `get_conversation_list`/`get_message`; verify sandbox thật; bật mặc định trong
  `INTEGRATIONS_MESSAGING` (chỉ bật sau khi verify).

## 8. Bật/triển khai (sau khi merge)
1. Set env `INTEGRATIONS_MESSAGING=...,shopee_chat`.
2. Shop Shopee phải đã kết nối cho đơn hàng (provider `shopee` trong `INTEGRATIONS_CHANNELS`
   + OAuth xong) — chat dùng chung token.
3. Shopee Console → Push Mechanism: subscribe **code 10 (Webchat)** cho app, callback URL =
   `https://app.cmbcore.com/webhook/shopee` (URL đã dùng cho đơn hàng).
4. Trang Gian hàng → bật "nhắn tin" cho shop Shopee.
5. Redeploy (entrypoint `config:cache` nạp env mới).

## 9. Tài liệu liên quan
- `docs/04-channels/shopee.md`, `shopee_docs/03-push-mechanism-webhook.md` (code 10 = Webchat).
- `docs/01-architecture/adr/0017-messaging-connector-registry.md`, `0019-messaging-reuse-channel-account.md`.
- Spec gốc: `docs/specs/0024-omnichannel-messaging.md`.
- Sẽ bổ sung doc setup: `docs/04-channels/shopee-chat-setup.md` (ở bước implement).
