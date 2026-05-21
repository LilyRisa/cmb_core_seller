# Thiết kế: Hoàn thiện Chat người mua (sàn Lazada/TikTok/Shopee) — SPEC-0024 / ADR-0017

> Trạng thái: **Phase A đã duyệt thiết kế (2026-05-21)**. B/C/D sẽ brainstorm/design riêng trước khi code.
> Phạm vi của agent này: **backend + connector sàn**. KHÔNG đụng `MessagingPage.tsx` (inbox FE do agent Facebook sở hữu) và KHÔNG đụng luồng Facebook.
> Nguồn sự thật API = tài liệu chính thức trong `tailieuapi_itiktok_shopee_lazada/` (Shopee gửi tin: dùng endpoint cộng đồng vì tài liệu chính thức thiếu — đã được chủ dự án chấp thuận).

## Bối cảnh & phát hiện then chốt (từ đối chiếu tài liệu chính thức)

| Sàn | Nhận | Gửi (tài liệu chính thức) | Ghi chú |
|---|---|---|---|
| Lazada IM | **KHÔNG webhook — chỉ POLLING** (`im/session/list` + `im/message/list`) | text(1), image(3 qua `image/upload` JPG/PNG ≤1MB), emoji(4), video(6), item(10006)/order(10007)/voucher(10008) | session mở qua `order_id` (≤30 ngày); recall ≤2 phút; `session/read(session_id,last_read_message_id)` |
| TikTok CS (`/customer_service/202309/`) | webhook type 14 + polling | TEXT, IMAGE (upload ≤10MB), VIDEO (cần `vid`), PRODUCT/ORDER/RETURN/COUPON/LOGISTICS card | gate quyền: cần app duyệt (1000+ seller hoặc 1M call/ngày) hoặc app "TikTok Shop Seller" tự phát triển; quota 10k/ngày; mark read = `POST .../messages/read` |
| Shopee | webchat push code 10 (text/image/video/item/...) | ⚠️ module `sellerchat` (whitelist) — tài liệu chính thức KHÔNG có endpoint chi tiết → **dùng endpoint cộng đồng** `/api/v2/sellerchat/send_message` (đã chấp thuận) | |
| Facebook | (ngoài phạm vi — agent khác) | (ngoài phạm vi) | |

**Hiện trạng backend (đã có):** model Conversation/Message/MessageAttachment đầy đủ (kind text/image/video/file/template/system; delivery_status; read tracking; attachment width/height/duration/checksum). `ConversationController` (lọc provider/status/unread/assigned/customer_id/q + markRead + update). `MessageController` (sendText/sendTemplate/sendMedia multipart). `SendMessage` job (signed URL, idempotent). `MessageIngestionService` (inbound upsert + unread++ + `DownloadInboundMedia` relay). `MessageResource` **đã sinh `download_url`** qua `MediaStorage::temporaryUrl()`.

**Thiếu (gap chính cho yêu cầu "chat hoàn chỉnh"):**
- Không có endpoint **đánh dấu chưa đọc** (chỉ có markRead).
- Không **expose capabilities** để UI gate nút gửi theo sàn.
- Connector sàn **không parse media inbound** (ảnh/video người mua gửi bị bỏ).
- **Không có polling/sync** — Lazada (poll-only) hiện KHÔNG nhận được tin; TikTok/Shopee không có backup khi miss webhook.
- Send-type sàn mới chỉ text+image (TikTok/Shopee) / chưa hoàn chỉnh (Lazada cần session_id từ poll).

## Decomposition (mỗi phase = spec→plan→code riêng)

- **A. API hỗ trợ inbox** (phase này): mark-unread + expose capabilities. Nhỏ, gỡ chặn agent inbox.
- **B. Parse media inbound (sàn):** Shopee (code 10: image/video/item) + TikTok (webhook content) → `MessageAttachment` + relay.
- **C. Polling/Sync + Activate UX:** job đồng bộ hội thoại/tin mirror luồng đồng bộ đơn hàng (`SyncOrdersForShop`). Bắt buộc cho Lazada; backup TikTok/Shopee. API "bật chat"/"đồng bộ ngay" per-shop.
- **D. Mở rộng send-type:** Lazada (image qua `image/upload`, item/order/voucher); TikTok (image upload, cards); Shopee (image qua endpoint cộng đồng).

---

## PHASE A — API hỗ trợ inbox (chi tiết, sẵn sàng code)

Provider-agnostic, không đụng FE inbox. Hai endpoint mới + 1 xác nhận.

### A1. Đánh dấu chưa đọc
`POST /api/v1/messaging/conversations/{id}/unread` → `ConversationController::markUnread(int $id, Request $request): JsonResponse`.

Hành vi (đối xứng với `markRead` ở `ConversationController.php:95-110`):
- `Gate::authorize('messaging.view')`.
- Tìm `Conversation::findOrFail($id)`.
- Lấy tin **inbound mới nhất** của hội thoại. Nếu KHÔNG có inbound nào → trả `422` `{error:{code:'NO_INBOUND', message:'Không có tin của người mua để đánh dấu chưa đọc.'}}`.
- Set `read_at = null` cho tin inbound mới nhất đó; `unread_count = max(1, unread_count)`; lưu.
- Trả `['data' => (new ConversationResource($conv))->toArray($request)]`.

Hệ quả: hội thoại xuất hiện trong filter `?unread=1` (đã có ở `ConversationController.php:41-43`).

### A2. Expose capabilities theo provider
`GET /api/v1/messaging/capabilities` → method mới `MessagingChannelController::capabilities(MessagingRegistry $registry): JsonResponse`.

- `Gate::authorize('messaging.view')`.
- Với mỗi provider trong `$registry->providers()` (chỉ provider đang bật): gọi `$registry->for($code)->capabilities()`.
- Trả:
```json
{ "data": {
  "tiktok_chat": {"outbound.text":true,"outbound.image":true,"outbound.video":false,"outbound.file":false,"outbound.template":false,"read_receipt":true,"typing":false,"inbound.webhook":true,"inbound.polling":false},
  "lazada_chat": {...}, "shopee_chat": {...}, "manual": {...}
} }
```
- FE (agent inbox) gate nút gửi theo `conversation.provider`. Emoji = text → luôn cho phép khi `outbound.text`.

### A3. download_url — đã có
`MessageResource.php:47` đã trả `download_url` (signed, TTL ngắn) cho attachment. Không cần thay đổi. Ghi nhận để agent inbox dùng render media inbound.

### Route (thêm vào `app/app/Modules/Messaging/Http/routes.php`)
Trong group `api/v1/messaging`:
- `Route::post('conversations/{id}/unread', [ConversationController::class, 'markUnread'])->whereNumber('id')->name('messaging.conversations.unread');` (đặt cạnh route `conversations/{id}/read`).
- `Route::get('capabilities', [MessagingChannelController::class, 'capabilities'])->name('messaging.capabilities');`.

### Test
- **Feature `MarkUnreadTest`**: tạo conversation + 1 tin inbound đã đọc (`read_at` set, unread_count=0) → POST `/unread` → 200, `unread_count>=1`, tin inbound `read_at=null`; xuất hiện khi `index?unread=1`. Conversation không có inbound → 422 `NO_INBOUND`.
- **Feature `MessagingCapabilitiesTest`**: bật `integrations.messaging=['tiktok_chat','shopee_chat']` → GET `/capabilities` trả map có 2 provider đó (+ `manual`) với capability đúng; KHÔNG có provider chưa bật (vd `lazada_chat`).

### Không đụng
`MessagingPage.tsx`, file Facebook, connector (chưa sửa ở phase A).

---

## PHASE B — Chuẩn hoá nội dung inbound + media (Shopee/TikTok) (chi tiết)

**Phát hiện:** `MessageIngestionService.ingest` đã tạo `Message` (kind/body) + `MessageAttachment` từ `MessageDTO.attachments` + relay qua `DownloadInboundMedia` (sẵn). NHƯNG `MessagingWebhookEventDTO` không mang `kind`/`body`/`attachments`, và `ProcessMessagingWebhook` hardcode `attachments: []`, đọc `body`/`kind` từ `payload['body']`/`payload['kind']` (chỉ `manual` set top-level). ⇒ inbound sàn: **body null, media mất**.

**B1. DTO** — thêm field optional vào `MessagingWebhookEventDTO` (backward-compat, default giữ hành vi cũ):
- `?MessageKind $kind = null`, `?string $body = null`, `array $attachments = []` (list<MediaRefDTO>).

**B2. Lưu webhook_event** — `MessagingWebhookIngestService` thêm vào payload các key chuẩn hoá (prefix `_` tránh đụng raw sàn): `_kind` (string|null), `_body` (string|null), `_attachments` (list các MediaRefDTO serialize thành array: kind,mime,size_bytes,external_url,storage_path,filename,width,height,duration_ms).

**B3. ProcessMessagingWebhook** — `rebuildDtoFromStoredPayload` đọc `_kind`/`_body`/`_attachments` (dựng lại MediaRefDTO[]); `messagingDtoFromWebhook` build `MessageDTO` với kind/body/attachments đó. **Fallback** về `payload['body']`/`payload['kind']` cũ khi thiếu `_*` (giữ manual + cũ chạy). Cho phép ingest cả message chỉ có media (body null nhưng có attachments) — nới điều kiện `messagingDtoFromWebhook` (hiện yêu cầu conv+message+buyer; vẫn giữ, chỉ bỏ ràng buộc phải có body).

**B4. ShopeeChatConnector.parseWebhookEvents** — map theo `message_type` (push code 10, tài liệu `push-025`):
- `text` → kind=Text, body=`content.text`.
- `image` → kind=Image, attachments=[MediaRef(Image, mime suy từ url, externalUrl=`content.url`, width=`thumb_width`, height=`thumb_height`)].
- `video` → kind=Video, attachments=[MediaRef(Video, externalUrl=`content.video_url`, durationMs=`duration_seconds`*1000, width/height thumb)].
- `item` → kind=Text, body=`"[Sản phẩm] item_id=<id>"` (card → text, YAGNI).
- khác → kind=Text, body=`"[<message_type>]"`.

**B5. TikTokChatConnector.parseWebhook** — webhook type 14 `content` (JSON string) → `type`+`content`:
- `TEXT` → body. `IMAGE` → attachment (url+w+h). `VIDEO` → attachment. card types → text body `"[<type>]"`.

**Test:**
- `MessagingWebhookEventDTO` carry kind/body/attachments → stored payload có `_*` → ProcessMessagingWebhook tạo Message(kind/body) + MessageAttachment(external_url, status pending) + dispatch DownloadInboundMedia.
- Shopee unit: parse push image/video/text → DTO đúng kind/body/attachments. TikTok unit tương tự.
- Regression: manual flow (top-level body) vẫn ingest đúng (fallback).

**Không đụng:** FacebookPageConnector (agent khác), `MessagingPage.tsx`.

## PHASE C — Polling/Sync + Activate (chi tiết)

**Scope đợt này:** hạ tầng sync + **Lazada polling** (Lazada IM KHÔNG có webhook → bắt buộc). TikTok/Shopee polling-backup để **follow-up** (đã có webhook). `MessagingAccountMeta` đã có sẵn cột sync (`sync_status`, `sync_cursor`, `last_synced_at`, `sync_started_at/finished_at/error`, counters) — KHÔNG cần migration.

**C2 (làm trước — unit-test bằng Http::fake) — Lazada `fetchConversations`/`fetchMessages`:**
- `capabilities()['inbound.polling'] = true` cho Lazada.
- `fetchConversations(MessagingAuthContext, $query)`: GET `/im/session/list` (ký LazadaSigner, system params giống `LazadaChatConnector::send`) với `start_time` (từ `$query['since']` ?: now), `page_size` (từ `$query['pageSize']` ?: 50), `last_session_id` (từ `$query['cursor']`). Map session → `ConversationDTO` (externalConversationId=`session_id`, buyerExternalId=`buyer_id`, buyerName=`title`, lastMessagePreview=`summary`, unreadCount=`unread_count`, lastMessageAt từ `last_message_time`). `Page(items, nextCursor=last_session_id|next_start_time, hasMore)`.
- `fetchMessages(MessagingAuthContext, $sessionId, $query)`: GET `/im/message/list` với `session_id`, `start_time`, `page_size`, `last_message_id` (cursor). Map message → `MessageDTO`: direction = `from_account_type==1`(buyer)?Inbound:Outbound; kind/body/attachments theo `template_id` (1=text→`content.txt`; 3=image→attachment `content.img_url` + w/h; 6=video→attachment; 10006/10007/10008 → text label). `externalMessageId=message_id`, `buyerExternalId=from_account_id|buyer_id`, sentAt từ `send_time`.
- Nguồn: tài liệu chính thức Lazada `im/session/list`, `im/message/list` (đã đối chiếu).

**C1 — Sync infra:**
- Job `app/app/Modules/Messaging/Jobs/SyncConversationsForShop.php`: ctor `(int $channelAccountId)`, queue `messaging-sync`, `ShouldBeUnique` uniqueId `sync-chat:{id}` (lock 900s), `$tries=3`, `backoff=[60,300,900]`.
  - Load `ChannelAccount::withoutGlobalScope(TenantScope)` + `MessagingAccountMeta`. Resolve connector qua `messagingConnectorCode()` + registry. Nếu không có hoặc `!supports('inbound.polling')` → set `sync_status='done'` (no-op) + return (KHÔNG throw — mirror gotcha).
  - `sync_status='running'`, `sync_started_at=now`. Build `MessagingAuthContext` từ account.
  - Loop `fetchConversations(['since'=>last_synced_at,'cursor'=>sync_cursor,'pageSize'=>50])` (giới hạn maxConvPages=50, lưu `sync_cursor` mỗi trang). Mỗi conversation → loop `fetchMessages($convId, ['since'=>last_synced_at,'pageSize'=>50])` (maxMsgPages=20) → `MessageIngestionService::ingest($account,$dto)` + `fireEventsForNewMessage` khi `created`.
  - Kết thúc OK: `last_synced_at=runStart->subMinutes(overlap)`, `sync_status='done'`, `sync_finished_at`, counters. Lỗi: `sync_status='failed'`, `sync_error`.
- Lịch (`routes/console.php`): `everyFiveMinutes()->onOneServer()->withoutOverlapping()` — enumerate `ChannelAccount` active + `messaging_enabled=true`, dispatch `SyncConversationsForShop`. (Gate inbound.polling trong job.)
- Endpoint `POST /api/v1/channel-accounts/{id}/resync-chat` → `ChannelAccountController::resyncChat` (mirror `resyncListings`: gate `messaging.connect`, isActive, capability `inbound.polling` qua `MessagingRegistry`, dispatch job). Route ở `routes/api.php` cạnh resync khác.

**Test:** C2 unit (Http::fake session/list + message/list → ConversationDTO/MessageDTO đúng, direction/kind/attachment). C1 feature: shop Lazada active+messaging_enabled, Http::fake Lazada → chạy job → tạo Conversation(provider=lazada_chat) + Message(s); `last_synced_at` set; resync-chat endpoint dispatch job (Queue::fake) + 422 khi connector không hỗ trợ polling.

**Không đụng:** Facebook, `MessagingPage.tsx`.

## PHASE D — Gửi ảnh đúng luồng upload-first (chi tiết)

**Thực tế API (tài liệu chính thức):** video/document gửi đi KHÔNG khả thi cho sàn (TikTok video cần `vid` từ video-upload riêng không có trong docs; Lazada video cần `video_id`; document chỉ Facebook). ⇒ giữ `outbound.video/file=false` (ném `UnsupportedOperation`). Phần làm: **gửi ẢNH đúng luồng** — hầu hết sàn yêu cầu upload ảnh lên CDN của sàn trước rồi mới gửi URL đó (không nhận URL ngoài).

**D-Lazada `sendMedia(image)`:** fetch bytes từ `media->externalUrl` (signed URL nội bộ) → `POST /image/upload` (binary, JPG/PNG ≤1MB theo doc) → lấy `data.image.url` → `send` `template_id=3` với `img_url=<url đó>` (+ width/height). Lỗi upload → RuntimeException (job retry).

**D-TikTok `sendMedia(image)`:** fetch bytes → `POST /customer_service/202309/images/upload` (multipart field `data`) → lấy `data.url`(+width/height) → `send` IMAGE với `content={url,width,height}`. Bật `outbound.image` (đã có).

**D-Shopee `sendMedia(image)`:** giữ endpoint cộng đồng (`upload_image` cộng đồng nếu có, hoặc gửi `image_url` trực tiếp) — đánh dấu "verify sandbox/cộng đồng".

**Helper:** thêm 1 hàm fetch bytes từ signed URL (Http::get) dùng chung trong mỗi connector (không cần service mới). 

**Test:** mỗi sàn `Http::fake` 2 chặng (upload → trả url; send → trả message_id) → assert `sendMedia(image)` upload trước rồi send với URL từ upload; assert video/file → `UnsupportedOperation`.

**Không đụng:** Facebook, `MessagingPage.tsx`.
