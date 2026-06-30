# SPEC 0039: Tích hợp Zalo OA (Official Account) vào Messaging — connector, flow & broadcast

- **Trạng thái:** Draft (brainstorm chốt với người dùng 2026-06-30). Phase 1 (Nền tảng) sẵn sàng viết plan triển khai.
- **Phase:** 7.x (Messaging) — thêm kênh chat mới
- **Module backend liên quan:** Integrations/Messaging (connector mới `zalo_oa`) + Messaging (reuse engine inbox/flow/auto-reply). Channels (reuse `ChannelAccount`). KHÔNG đụng core marketplace.
- **Tác giả / Ngày:** Claude · 2026-06-30
- **Liên quan:** SPEC 0024 (Omnichannel Messaging), SPEC 0027 (FB comment + private message), SPEC 0032/0033 (FB utility messages + composer cửa-sổ), SPEC 0035 (per-page scoping), ADR-0004/0017 (Connector + Registry), ADR-0019 (Messaging reuse `ChannelAccount`), ADR-0020 (storage/partition media), ADR-0021 (Reverb realtime), ADR-0022 (AI ⊕ auto-reply precedence). Tham khảo protocol: dự án mã nguồn mở **ChatbotXIO/ChatbotX** (`integrations/zalo`).
- **Memory:** [[fulfillment-status-semantics-and-shopee-label]] (capability map), [[shopee-token-refresh-rotation]] (token xoay vòng, lock per-account), [[messaging-marketplace-webhook-demux]], [[per-page-messaging-scoping-spec0035]], [[messaging-autoreply-dev-gotchas]] (queued listener phải có supervisor), [[timezone-architecture-utc-store-hcm-display]] (scheduler theo HCM), [[ui-use-font-icons-not-emoji]], [[ui-avoid-select-prefer-radio]].

---

## 1. Vấn đề & mục tiêu

Hệ Messaging hiện hỗ trợ Facebook Page + chat marketplace (TikTok/Shopee/Lazada) qua **Connector + Registry** (ADR-0017): core không biết tên nhà cung cấp; thêm 1 kênh = 1 connector + 1 dòng registry + config + webhook route. Zalo OA là kênh chăm sóc khách phổ biến nhất ở VN nhưng **chưa được hỗ trợ**.

**Mục tiêu tổng:** đưa Zalo OA thành một messaging provider đầy đủ — kết nối OA, hộp thoại 1-1 trong inbox, auto-reply + kịch bản (flow) tự động, gửi tin trong & ngoài cửa sổ tương tác (ZNS), và gửi hàng loạt (broadcast) — **không sửa core**, chỉ thêm connector + mở rộng có kiểm soát.

Tham khảo **ChatbotX** (TypeScript/Next.js, ManyChat-alternative) **chỉ để lấy chi tiết protocol Zalo và mô hình flow**; không tái sử dụng code (stack khác). Các hằng/endpoint/thuật toán chữ ký bên dưới trích từ `integrations/zalo/src` của ChatbotX và phải đối chiếu tài liệu Zalo Open Platform khi có credentials.

## 2. Quyết định thiết kế (đã chốt với người dùng 2026-06-30)

1. **Phạm vi cuối = đầy đủ**: connector + flow builder mở rộng + ZNS + broadcast.
2. **Tách 4 phase**, mỗi phase 1 spec/plan riêng. Ràng buộc phụ thuộc: **mọi phase cần Phase 1**; Broadcast (ngoài cửa sổ) cần ZNS.
3. **Thứ tự triển khai (người dùng chốt):** **Phase 1 (Nền tảng)** → **Phase 2 (Flow builder mở rộng)** → **Phase 3 (ZNS template)** → **Phase 4 (Broadcast)**.
4. **Loại tin v1 (Phase 1) = chỉ CS** (`message/cs`, free-form trong cửa sổ tương tác). ZNS để Phase 3.
5. **Frontend = tách menu theo nền tảng** (đã chốt 2026-06-30, thay quyết định "gộp đa-provider" trước đó): "Tin nhắn" là **mục riêng**, **tách khỏi nhóm "Bán hàng"**; bên trong là **submenu theo nền tảng** (Facebook, Zalo OA, … sau này TikTok/Shopee/Lazada chat), **mỗi nền tảng có đủ trang con riêng** (Hộp thư, Kết nối, Mẫu tin, Tự động trả lời, Kịch bản, AI training). Trang/route bên dưới vẫn dùng connector provider-agnostic; FE lọc theo `provider`.
6. **Chưa có credentials → code-first**: implement theo protocol, để placeholder config, test bằng `Http::fake`/`Queue::fake`; bật end-to-end khi có Zalo OA app.
7. **Reuse flow engine nguyên trạng ở Phase 1** (không thêm node mới); flow chạy với `provider='zalo_oa'` nhờ capability gating.

## 3. Phân rã phase (toàn cảnh)

| Phase | Tên | Nội dung chính | Phụ thuộc | Spec |
|---|---|---|---|---|
| **1** | Nền tảng Zalo OA | Connector + OAuth connect + webhook + inbox 1-1 + gửi CS + cron refresh token + FE đa-provider | — | **SPEC này (§5–§13)** |
| **2** | Flow builder mở rộng | Node mới: `delay` (hẹn giờ, cần cursor + scanner), `set_tag`, `user_input` (hỏi-đáp lưu biến), `set_field`, `split_traffic` (A/B) | Phase 1 | Spec riêng |
| **3** | ZNS template | `UtilityTemplateConnector` cho Zalo, quản lý & gửi template ZNS ngoài cửa sổ, map quota/permission error | Phase 1 | Spec riêng |
| **4** | Broadcast | Gửi hàng loạt theo tag/segment follower, queue + quota-aware; ngoài cửa sổ qua ZNS | Phase 1 + 3 | Spec riêng |

Phase 2–4 chỉ phác ở §14; sẽ brainstorm/spec chi tiết khi tới.

---

# PHASE 1 — NỀN TẢNG ZALO OA

## 4. Trong / ngoài phạm vi (Phase 1)

**Trong:**
- Connector `ZaloOaConnector` (`MessagingConnector` + `InteractiveMessagingConnector`) + `ZaloSignatureVerifier` + `ZaloClient`.
- OAuth connect OA (`ZaloOaOAuthController`), tạo `ChannelAccount` provider `zalo_oa`, lưu token vào account.
- Cron refresh token (xoay vòng), lock per-account.
- Webhook `/webhook/messaging/zalo_oa`: parse inbound (text/image/file/audio/sticker/location), postback, read-receipt; map vào pipeline `MessagingWebhookIngestService` → `ProcessMessagingWebhook`.
- Gửi CS: `sendText` / `sendMedia` (upload 2 bước) / `sendInteractive` (nút ≤5); `outboundWindow` cửa sổ tương tác.
- Wiring: registry + `config/integrations.php` (`messaging_zalo_oa` + CSV) + route + `channelProviderForMessaging` map + provider hợp lệ cho `ChannelAccount`.
- FE: tổng quát hoá trang Kênh + inbox thành đa-provider; nút "Kết nối Zalo OA".
- Reuse flow/auto-reply với `provider='zalo_oa'`.
- Test (fake) cho toàn bộ trên.

**Ngoài (Phase 1):**
- KHÔNG: ZNS/template ngoài cửa sổ, node flow mới, broadcast, đồng bộ tag follower 2 chiều, comment feed (Zalo OA không có), `outbound.video` (để `false` cho an toàn, mở sau nếu cần).
- KHÔNG sửa core marketplace, KHÔNG đổi `MessagingConnector` interface (chỉ implement).

## 5. Định danh & mô hình dữ liệu

- **Provider mới `zalo_oa`** trong cả 2 trục: messaging code (`zalo_oa`) và channel provider (`zalo_oa`) — quan hệ **1:1** như `facebook_page` (KHÔNG dùng chung token đơn hàng marketplace).
- **`ChannelAccount` provider `zalo_oa`** (reuse, ADR-0019): `external_shop_id = oa_id`, `name = oa_name`, `credentials`(jsonb) = `{ access_token, refresh_token, expires_at, oa_id }`. Per-OA token nằm ở account, KHÔNG ở config.
- **`MessagingAccountMeta`** (reuse): tạo row cho OA (avatar, ai_auto_mode, sync status).
- **`Conversation`**: `provider='zalo_oa'`, `channel_account_id` → OA account, `buyer_external_id = user_id` (UID theo OA), `thread_type='message'` (không có comment).
- **`Message`**: dùng `MessageKind` hiện có (text/image/file/audio/interactive). Sticker/location lưu là text/attachment + `meta` (KHÔNG thêm kind mới — giữ tối giản).
- **Không bảng mới ở Phase 1.** (Bảng cursor cho node `delay` thuộc Phase 2; template ZNS thuộc Phase 3.)

## 6. Connector & capability map

`app/app/Integrations/Messaging/Zalo/ZaloOaConnector.php`:
```php
class ZaloOaConnector implements MessagingConnector, InteractiveMessagingConnector
```
Capability (`capabilities()`):
```
inbound.webhook        => true
inbound.postback       => true
outbound.text          => true
outbound.image         => true
outbound.file          => true
outbound.interactive   => true     // nút ≤5 (oa.open.url / oa.query.hide)
outbound.video         => false    // Phase 1 tắt cho an toàn
outbound.template      => false
outbound.utility_template => false // bật ở Phase 3 (ZNS)
read_receipt           => true     // user_seen_message
typing                 => false
```
KHÔNG implement `ListsPostsConnector` / `CommentEngagementConnector` / `UtilityTemplateConnector` ở Phase 1 → các node/UI tương ứng tự fail-soft (cơ chế segregated capability sẵn có, không cần sửa core).

**Lỗi Zalo:** envelope `{ error:int, message }`, `error !== 0` là lỗi dù HTTP 200. Map sang `ChannelError*`:
- token: `-124` (invalid), `-1001` (expired) → đánh dấu account cần reconnect / trigger refresh.
- vĩnh viễn (không retry outbound): `-216` user blocked OA, ngoài cửa sổ tương tác (CS) → fail kèm code máy đọc.
- quota/permission: `-115/-117/-120/-139/-140/-145…` → map category, KHÔNG retry mù.

## 7. Kết nối OA (OAuth) + redirect broker

`ZaloOaOAuthController` (mirror `FacebookOAuthController`):
- **Authorize:** `GET oauth.zaloapp.com/v4/oa/permission?app_id=<app_id>&redirect_uri=<redirect>&state=<base64(json)>`. **Không có `scope`** (Zalo cấp quyền ở mức OA/app trên màn hình consent). `state` mang `tenant_id` + nonce ký để chống CSRF & gắn tenant khi callback.
- **Callback → đổi token:** `POST oauth.zaloapp.com/v4/oa/access_token`, `application/x-www-form-urlencoded`, **secret ở header `secret_key`**, body `code, app_id, redirect_uri, grant_type=authorization_code` → `{ access_token, refresh_token, expires_in }`.
- **Lấy OA profile:** `GET openapi.zalo.me/v2.0/oa/getoa` → `{ oa_id, name, avatar }`. `oa_id` = định danh account.
- Tạo/cập nhật `ChannelAccount` (withTrashed firstOrNew + restore — [[softdelete-updateorcreate-unique-violation]]) + `MessagingAccountMeta`.
- **Gotcha redirect_uri:** phải đăng ký sẵn trên Zalo và **khớp tuyệt đối** giữa authorize & đổi token → 1 hằng `redirect_uri` trong `config('integrations.messaging_zalo_oa.redirect_uri')`.

## 8. Cron refresh token (xoay vòng)

- Access ~25h, refresh ~3 tháng, **refresh trả token mới + refresh_token mới (xoay vòng)** → phải lưu lại refresh_token mới mỗi lần.
- Command `php artisan messaging:zalo:refresh-tokens` + đăng ký scheduler (giờ HCM — [[timezone-architecture-utc-store-hcm-display]]): refresh **trước hạn** (ví dụ còn < 6h), lock per-account (Cache lock như [[shopee-token-refresh-rotation]]).
- Refresh fail **tạm thời** (mạng/5xx) KHÔNG expire account; chỉ đánh dấu reconnect khi Zalo trả lỗi token dứt khoát (`-124`). Đây là bài học [[label-backlog-retry-abandonment]] + Shopee.
- Endpoint refresh: cùng `v4/oa/access_token`, body `app_id, app_secret, refresh_token, grant_type=refresh_token`, header `secret_key`.

## 9. Webhook nhận tin

- Route: thêm `zalo_oa` vào `whereIn` của `POST /webhook/messaging/{provider}` (`routes/webhook.php`). Nếu Zalo cần GET-handshake xác minh, xử lý **trong connector** (`verifyWebhookSignature`/handler riêng) thay vì hardcode Facebook ở `MessagingWebhookController::verify`.
- **Chữ ký:** header `X-ZEvent-Signature: sha256=<hex>`; MAC = `SHA256(app_id + raw_request_body + timestamp + oa_secret)`, so timing-safe. **BẬT verify** (ChatbotX để tắt — ta bật). `oa_secret` lấy từ config.
- `parseWebhookEvents()` → `list<MessagingWebhookEventDTO>` (1 POST có thể nhiều event):
  - `user_send_text/image/file/audio/sticker/location` → message DTO; **OA là `recipient.id`, user là `sender.id`** với nhóm `user_send*`.
  - nút bấm: Zalo `oa.query.hide` echo lại payload dạng `postback_<...>` như tin người dùng → nhận diện tiền tố `postback_`, decode `FlowPostbackPayload`, phát `PostbackReceived` (KHÔNG tạo message rác).
  - `user_seen_message` → cập nhật read receipt.
  - validate `app_id === config.app_id`.
- Map `'zalo_oa' => 'zalo_oa'` trong `ProcessMessagingWebhook::channelProviderForMessaging()`.
- Resolve `ChannelAccount` cross-tenant theo `external_shop_id = oa_id` (như pipeline hiện có). Media inbound tải qua `DownloadInboundMedia` (header `Authorization: Bearer <token>` khi tải binary từ CDN Zalo — khác với header `access_token` của Open API).

## 10. Gửi tin CS (outbound)

`OutboundMessageService` (đã có) là đường ghi duy nhất; `SendMessage` job gọi connector:
- **Endpoint:** `POST openapi.zalo.me/v3.0/oa/message/cs`, JSON `{ recipient:{ user_id }, message:{...} }`, header `access_token: <token>`.
- `sendText`: `message.text`.
- `sendMedia`: **2 bước** — upload `v2.0/oa/upload/{image,file}` (multipart, KHÔNG tự set Content-Type để giữ boundary) lấy `attachment_id`(ảnh)/`token`(file) → gắn vào `message.attachment` (`template_type:"media"` cho ảnh; `type:"file"` cho file).
- `sendInteractive`: `attachment.type="template"`, `payload.buttons[]` (**≤5**, chunk). Nút link → `oa.open.url {url}`; nút postback → `oa.query.hide` với `payload = "postback_" + FlowPostbackPayload::encode(node_id, handle)` (khớp engine resume).
- **`outboundWindow`:** policy cửa sổ tương tác CS. Ngoài cửa sổ → `OutboundWindowClosed` (không retry); composer cửa-sổ (SPEC 0033) hiển thị trạng thái. ZNS (gửi ngoài cửa sổ) để Phase 3.

## 11. Wiring (config / registry / routes)

- `IntegrationsServiceProvider`: thêm `'zalo_oa' => ZaloOaConnector::class` vào `$messagingConnectors`; bind explicit inject `config('integrations.messaging_zalo_oa')` + secret.
- `config/integrations.php`:
  ```php
  'messaging_zalo_oa' => [
      'app_id'       => env('MESSAGING_ZALO_APP_ID'),
      'app_secret'   => env('MESSAGING_ZALO_APP_SECRET'),
      'oa_secret'    => env('MESSAGING_ZALO_OA_SECRET'),   // dùng cho MAC webhook
      'redirect_uri' => env('MESSAGING_ZALO_REDIRECT_URI'),
      'api_version'  => env('MESSAGING_ZALO_API_VERSION', 'v3.0'),
  ],
  ```
  + thêm `zalo_oa` vào CSV `INTEGRATIONS_MESSAGING`.
- Thêm `zalo_oa` vào danh sách provider hợp lệ của `ChannelAccount` + route whereIn.
- Dùng `config()` không `env()` ngoài config (luật vàng).

## 12. Frontend — menu tách theo nền tảng

**Cấu trúc menu (đã làm trước, commit riêng 2026-06-30):**
- Sidebar `AppLayout.tsx`: gỡ "Tin nhắn" khỏi nhóm "Bán hàng" → tạo **group top-level "Tin nhắn"** riêng; bên trong là submenu **"Facebook"** chứa đủ trang con hiện có. Bỏ import `MessageOutlined` (group không icon; submenu Facebook dùng `FacebookFilled`). `defaultOpenKeys` đổi `messaging` → `messaging-facebook`.
- Desktop shell `appCatalog.tsx` + `AppFrame.tsx`: app "Tin nhắn" gom trang dưới nhóm con **"Facebook"**; `defaultOpenKeys` thêm `messaging-facebook`.
- Mỗi key trang giữ nguyên (`/messaging`, `/messaging/channels`, …) → không đổi route/logic, chỉ đổi cây menu.

**Phần Zalo (Phase 1 này) thêm vào:**
- Thêm submenu **"Zalo OA"** song song "Facebook" dưới group "Tin nhắn" (cả 2 shell), **mỗi nền tảng đủ trang con riêng** (Hộp thư, Kết nối Zalo OA, Mẫu tin, Tự động trả lời, Kịch bản, AI training). KHÔNG thêm link chết — submenu Zalo chỉ thêm khi trang/route Zalo đã build.
- Trang con dùng chung component nhưng **lọc theo `provider`** (Facebook vs `zalo_oa`); route Zalo có thể dùng query (`?platform=zalo_oa`) hoặc path con (`/messaging/zalo/...`) — chốt khi build FE Phase 1.
- **API:** `GET /api/v1/messaging/channels` trả kèm `provider` + `capabilities`; FE mỗi nền tảng tự lọc kênh theo provider của mình. Bỏ giả định cứng `provider='facebook_page'` ở `MessagingChannelController::index` (lọc theo provider truyền vào thay vì hardcode).
- **Trang Kết nối:** mỗi nền tảng có nút kết nối riêng; Zalo có **"Kết nối Zalo OA"** (icon font, không emoji — [[ui-use-font-icons-not-emoji]]).
- **Inbox & composer:** render theo capability (ẩn/disable nút interactive/template nếu kênh không hỗ trợ — [[ui-order-actions-toolbar]]); tôn trọng `outboundWindow`. Dùng `useCurrentTenantId()` ([[fe-tenant-id-use-hook-with-fallback]]).
- **Flow editor:** đã provider-agnostic; tạo flow với `provider='zalo_oa'`, lọc trigger phù hợp (`inbox_first_message/keyword/any`; ẩn `comment_*` cho Zalo).

## 13. Test & xác minh (code-first, không credentials)

- `Http::fake` cho `oauth.zaloapp.com` + `openapi.zalo.me`; `Queue::fake`/`Event::fake` theo baseline Messaging. Đăng ký `ZaloOaConnector` vào `MessagingRegistry` trong test (như [[messaging-facebook-not-enabled-in-test-env]]).
- Ca test tối thiểu:
  1. `verifyWebhookSignature` đúng/sai (MAC).
  2. `parseWebhookEvents`: text/image/postback/seen → DTO đúng; OA vs user id đúng chiều.
  3. Inbound → tạo `Conversation`/`Message`, dedupe theo `external_message_id`.
  4. Outbound: build payload `message/cs` đúng (text/media 2-bước/nút ≤5, postback encode khớp `FlowPostbackPayload`).
  5. OAuth callback: đổi token, tạo `ChannelAccount`, lưu refresh.
  6. Refresh cron: xoay vòng refresh_token, không expire khi lỗi tạm thời, lock per-account.
  7. Capability gating: `outbound.utility_template=false` → node template fail-soft.
- Quality gate: `pint --test`, `phpstan` (level 5), `php artisan test`, `npm run lint && typecheck && build`. Lưu ý baseline FE/BE chưa xanh toàn cục ([[test-verify-baseline]]) — không phá thêm.

---

## 14. Phác các phase sau (sẽ spec riêng)

- **Phase 2 — Flow builder mở rộng:** thêm node `delay` (hẹn giờ; cần bảng cursor kiểu `ContactOnSmartDelay` + command scanner mỗi phút atomically claim due rows, idempotent jobId — học `scan-smart-delay` của ChatbotX), `set_tag` (gọi `v2.0/oa/tag/*`), `user_input` (hỏi-đáp, lưu biến vào `FlowRun.context`, replyFormat number/text/email/phone…, auto-skip), `set_field`, `split_traffic` (A/B theo trọng số). Đăng ký `NodeExecutorRegistry` + FE palette + `FlowGraphValidator`. Provider-agnostic; chỉ `set_tag` đặc thù Zalo (segregated capability `contact.tag`).
- **Phase 3 — ZNS:** `ZaloOaConnector implements UtilityTemplateConnector`; CRUD/duyệt template ZNS, gửi `KIND_UTILITY_TEMPLATE` ngoài cửa sổ; map đầy đủ quota/permission error codes; cờ capability `outbound.utility_template=true`.
- **Phase 4 — Broadcast:** gửi hàng loạt theo tag/segment follower; job phân mảnh + quota-aware + dừng khi `-115/-1441`; ngoài cửa sổ bắt buộc ZNS (phụ thuộc Phase 3); UI chọn segment + lịch (giờ HCM).

## 15. Rủi ro & lưu ý

- **Chưa có credentials:** mọi giá trị protocol (endpoint/scope/chữ ký) phải đối chiếu tài liệu Zalo Open Platform khi onboard app thật; coi §6–§10 là "theo ChatbotX, cần verify".
- **Cửa sổ tương tác CS** của Zalo do server Zalo quyết, lộ qua error codes — không tự suy đoán ở client.
- **Queued listener** (flow/auto-reply) cho `zalo_oa` phải chắc supervisor Horizon có queue tương ứng, nếu không job kẹt im lặng ([[messaging-autoreply-dev-gotchas]]).
- **Prod baked image** cần redeploy để có connector mới; deploy KHÔNG tự migrate ([[prod-ops-ssh-and-deploy]]).
