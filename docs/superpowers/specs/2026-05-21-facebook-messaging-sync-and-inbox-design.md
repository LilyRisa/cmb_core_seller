# Nâng cấp nhắn tin Facebook Page: avatar · đồng bộ · gửi media · quản lý inbox

- **Trạng thái:** Design draft (2026-05-21)
- **Phase:** Phase 7.x — bổ khuyết SPEC-0024 (Omnichannel Messaging), slice S2 (Facebook). Connector + UI kết nối/quản lý đã có; spec này bổ sung: backfill lịch sử, avatar, gửi media/emoji ở UI, quản lý inbox (đánh dấu chưa đọc, lọc chưa đọc, chặn người dùng).
- **Kiến trúc đồng bộ:** Hướng A — backfill **tổng quát ở core** điều khiển bằng capability connector (tôn trọng ADR-0017: core không hard-code provider). Facebook hiện thực `fetchConversations`/`fetchMessages`; sàn khác bật sau bằng cách hiện thực 2 hàm.
- **Module backend:** `Messaging` (job backfill, endpoint sync/block/unread, ingest), `Integrations/Messaging/Facebook` (connector). Không sửa controller/pipeline lõi (ADR-0017).
- **Module FE:** `resources/js` — `MessagingChannelsPage`, `MessagingPage`, `lib/messaging.tsx`, `lib/messagingConfig.tsx`.
- **Liên quan:** `docs/04-channels/facebook-messenger-setup.md`, `docs/specs/0024-omnichannel-messaging.md`, ADR-0017 (connector registry), ADR-0019 (reuse channel token), ADR-0020 (storage/partition media). `FacebookPageConnector`, `MessageIngestionService`, `MessageController@sendMedia` (đã có), `ConversationController`, `MessagingChannelController`, `messaging_account_meta`.

> **API chính chủ Meta đã đọc & đối chiếu** (đối tượng spec bám theo):
> [Page `/conversations` edge](https://developers.facebook.com/docs/graph-api/reference/page/conversations/) ·
> [Send API](https://developers.facebook.com/docs/messenger-platform/reference/send-api) ·
> [Attachment Upload API](https://developers.facebook.com/docs/messenger-platform/reference/attachment-upload-api) ·
> [User Profile API](https://developers.facebook.com/docs/messenger-platform/identity/user-profile) ·
> [Sender Actions](https://developers.facebook.com/docs/messenger-platform/send-messages/sender-actions) ·
> [Messaging policy & 24h window](https://developers.facebook.com/docs/messenger-platform/policy/policy-overview).

---

## 1. Vấn đề & mục tiêu

SPEC-0024 đã có connector Facebook (gửi text/ảnh/video/file, parse webhook nhận tin/delivered/read), OAuth kết nối page, UI kết nối/ngắt page, **backend gửi media** (`POST /conversations/{id}/messages/media` → MinIO → `SendMessage` → `connector.sendMedia`), và `index` đã hỗ trợ `?unread=true`, `markRead`. Tuy vậy còn các khoảng trống mà người dùng yêu cầu:

- **G1 — Không avatar:** UI kết nối chỉ hiện tên page + tag; không avatar page. Inbox có cột `buyer_avatar_url` nhưng không hiển thị và không được điền.
- **G2 — Không đồng bộ lịch sử:** `FacebookPageConnector::fetchConversations()`/`fetchMessages()` **cố ý ném `UnsupportedOperation`** (chỉ webhook real-time). Khi kết nối page, các hội thoại/tin nhắn cũ **không** được kéo về ⇒ inbox trống cho tới khi có tin mới.
- **G3 — Không tiến trình/đếm tin trên UI kết nối:** không hiển thị đang đồng bộ, không hiển thị số lượng tin sau đồng bộ.
- **G4 — Composer chỉ text:** UI ô soạn tin chỉ gõ text; chưa có nút đính kèm ảnh/video/tài liệu (dù BE đã sẵn), chưa có emoji; thread không render đính kèm.
- **G5 — Thiếu quản lý inbox:** không "đánh dấu chưa đọc" (vì `markRead` set `unread_count=0` khi mở), không filter "chưa đọc" trên UI, không "chặn người dùng".

**Mục tiêu:**

1. Hiển thị **avatar page** (UI kết nối) và **avatar buyer** (inbox), relay về MinIO vì URL Graph hết hạn.
2. **Tự động đồng bộ** lịch sử hội thoại/tin nhắn của **tất cả** page đã kết nối vào Hộp thư (backfill có giới hạn + đối soát định kỳ + nút thủ công).
3. UI kết nối hiển thị **tiến trình đồng bộ** và **số lượng tin nhắn** sau khi xong.
4. Composer **gửi ảnh/video/tài liệu/text/emoji**; thread render đính kèm.
5. **Đánh dấu chưa đọc** / mở để đánh dấu đã đọc; **lọc chưa đọc**; **chặn người dùng** (mức ứng dụng).

**Quyết định đã chốt (brainstorming 2026-05-21):**

| Vấn đề | Quyết định |
|---|---|
| Tổ chức | 1 spec, triển khai theo slice |
| Kiến trúc backfill | Hướng A — backfill tổng quát ở core, điều khiển bằng capability |
| Độ sâu đồng bộ | **90 ngày** + tối đa **~50 tin/hội thoại** gần nhất, có nút "Đồng bộ lại" |
| Kích hoạt đồng bộ | Tự động khi kết nối + nút thủ công + job đối soát định kỳ |
| Chặn người dùng | **Chặn đầy đủ mức ứng dụng** (ẩn + bỏ qua tin mới + chặn gửi + danh sách bỏ chặn) — Messenger **không có API chặn** |
| Emoji | Thêm thư viện **emoji-mart** ở FE |
| Avatar page | Lưu ở `messaging_account_meta` (relay MinIO) |
| Đánh dấu chưa đọc | Thêm cột `manually_unread` |
| Quyền block/mark-unread | `messaging.reply` |

---

## 2. Trong / ngoài phạm vi

**Trong:**

- Migrations: thêm cột sync-state + avatar vào `messaging_account_meta`; `blocked_at`/`blocked_by_user_id`/`manually_unread` vào `conversations`.
- Connector Facebook: `capabilities()` thêm `inbound.backfill`; hiện thực `fetchConversations`/`fetchMessages`; thêm `fetchPageProfile`/`fetchUserProfile`.
- Job core `BackfillMessagingChannel` (provider-agnostic) + ghi sync-state, reuse `MessageIngestionService` + relay media/avatar.
- Endpoint: `POST /messaging/channels/{id}/sync`; mở rộng `GET /messaging/channels` (avatar/count/sync); `POST /messaging/conversations/{id}/unread`; `POST`+`DELETE /messaging/conversations/{id}/block`.
- Sửa: `MessageIngestionService` (drop khi blocked), `ConversationController@index` (ẩn blocked, filter `blocked`, `unread` gồm `manually_unread`), `markRead` (clear `manually_unread`), `MessageController@send*` (chặn khi blocked), OAuth callback (avatar + dispatch backfill), scheduler reconcile.
- FE: avatar + tiến trình + đếm tin + nút sync (channels); composer media/emoji + render đính kèm; mark-unread/unread-filter/block (inbox); hooks tương ứng; thêm dep `emoji-mart`.
- Test BE (unit connector + feature) + FE (Vitest).

**Ngoài phạm vi (YAGNI):**

- Chặn thật phía Facebook (không có API) — chỉ chặn mức ứng dụng.
- Instagram DM (`platform=INSTAGRAM`) — cùng edge nhưng để sau.
- Gửi sticker/reaction — chỉ emoji-trong-text.
- Tải tin **cũ hơn** mốc backfill từ Graph theo yêu cầu — đã có lazy-load DB qua `before_message_id`; fetch-older từ Graph để sau.
- Realtime websocket — giữ polling hiện có (15s list / 10s thread).
- Backfill cho TikTok/Lazada/Shopee chat — chỉ để khung capability; chưa hiện thực.

---

## 3. Mô hình dữ liệu (migrations mới)

> Plan phải đọc lại migration gốc `2026_05_19_100002_create_messaging_account_meta_table.php` và `..._100003_create_conversations_table.php` để xác minh cột hiện có trước khi `addColumn`.

### 3.1 `messaging_account_meta` (1-1 mỗi channel_account)

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `page_avatar_path` | varchar(512) null | MinIO key avatar page (relay) |
| `page_avatar_synced_at` | timestamp null | mốc relay avatar gần nhất |
| `sync_status` | varchar(16) default `idle` | `idle\|queued\|running\|done\|failed` |
| `sync_total_conversations` | int null | tổng hội thoại dự kiến/đã quét (ước lượng) |
| `sync_done_conversations` | int null | số hội thoại đã nạp xong (tiến trình) |
| `sync_message_count` | int default 0 | tổng tin đã nạp (hiển thị "số lượng tin nhắn") |
| `sync_cursor` | text null | Graph `after` cursor để resume khi rate-limit |
| `sync_started_at` | timestamp null | |
| `sync_finished_at` | timestamp null | |
| `sync_error` | text null | thông điệp lỗi gần nhất |
| `last_synced_at` | timestamp null | mốc đối soát/đồng bộ thành công gần nhất |

> `sync_message_count` là counter denormalized (cập nhật bởi backfill + tăng dần khi webhook ingest) để hiển thị nhanh; không phải nguồn chân lý (có thể đối soát bằng `SUM(conversations.message_count)`).

### 3.2 `conversations`

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `blocked_at` | timestamp null | ≠ null ⇒ đã chặn buyer của hội thoại này |
| `blocked_by_user_id` | bigint null | NV thực hiện chặn (audit) |
| `manually_unread` | boolean default false | "đánh dấu chưa xem" thủ công (sống tới khi mở lại) |

> Hội thoại Facebook là cặp (page, PSID) duy nhất ⇒ chặn theo hội thoại = chặn theo PSID trên page đó. Không cần bảng block-list riêng.

---

## 4. Backend

### 4.1 Connector `FacebookPageConnector`

- `capabilities()`: thêm `'inbound.backfill' => true`.
- **`fetchConversations(MessagingAuthContext $auth, array $query = []): Page`** (thay throw):
  - `GET https://graph.facebook.com/{ver}/{page_id}/conversations?platform=MESSENGER&fields=id,updated_time,message_count,participants{id,name},snippet&limit={n}&after={cursor}&access_token=...`
  - Trả `Page{ items: [...], nextCursor }`. Mỗi item chuẩn hoá: `thread_id` (`id` của conversation, dạng `t_...`), `psid` (participant có `id` ≠ page_id), `buyer_name`, `message_count`, `updated_time`, `snippet`.
  - **Phân trang chỉ cursor** (Graph: time-based pagination không khả dụng cho edge này). Backfill dừng theo `updated_time` ở job (§4.2).
- **`fetchMessages(MessagingAuthContext $auth, string $externalConversationId, array $query = []): Page`** (thay throw, giữ nguyên chữ ký interface):
  - ⚠️ **`thread_id` vs `PSID`:** edge `/conversations` trả **thread id** Graph (`t_...`), còn `conversations.external_conversation_id` của hệ thống lưu **PSID** (Send API địa chỉ theo PSID). Trong backfill, job truyền **thread id** vào tham số `externalConversationId` của `fetchMessages` (tham số contract = "định danh hội thoại phía sàn"), đồng thời **lưu `thread_id` vào `conversations.meta.fb_thread_id`** lúc upsert để reconcile/fetch-older sau dùng lại. PSID vẫn là khoá định danh hội thoại (`participants.id != page_id`).
  - `GET /{thread_id}?fields=messages.limit({n}){id,message,created_time,from,to,attachments{mime_type,name,image_data,video_data,file_url},sticker}&access_token=...`
  - Chuẩn hoá mỗi message: `external_message_id=id`, `direction = from.id==page_id ? outbound : inbound`, `body=message`, `occurred_at=created_time`, đính kèm map sang `MediaRefDTO` (external_url tạm → relay).
- **`fetchPageProfile(MessagingAuthContext $auth): array`**: `GET /{page_id}?fields=name,picture{url}` → `{ name, avatar_url }`.
- **`fetchUserProfile(MessagingAuthContext $auth, string $psid): array`**: `GET /{psid}?fields=name,profile_pic` → `{ name, avatar_url }`. (URL hết hạn ⇒ relay ngay.)
- `Http::fake` test được toàn bộ shape (LIVE cần page token thật + app review).

### 4.2 Job core `BackfillMessagingChannel`

`App/Modules/Messaging/Jobs/BackfillMessagingChannel.php` (queue; provider-agnostic):

1. Load `channel_account` + connector; nếu `!connector->supports('inbound.backfill')` ⇒ no-op.
2. Set meta `sync_status=running`, `sync_started_at=now()`, reset counter (giữ `sync_cursor` nếu resume).
3. Relay avatar page: `fetchPageProfile` → tải ảnh → MinIO → `page_avatar_path`, `page_avatar_synced_at`.
4. Vòng phân trang `fetchConversations(after=sync_cursor, limit=N)`:
   - Với mỗi hội thoại: nếu `updated_time < now()->subDays(90)` ⇒ **dừng toàn bộ** (đã tới mốc cũ).
   - Upsert `conversations` theo PSID (buyer_name; relay `profile_pic` → `buyer_avatar_url`; lưu `meta.fb_thread_id=thread_id`); **bỏ qua/không backfill nếu `blocked_at` set**.
   - `fetchMessages(thread_id, limit=50)` → mỗi message ingest qua **`MessageIngestionService`** (dedup `UNIQUE(conversation_id, external_message_id)` ⇒ idempotent; cập nhật header). Đính kèm dispatch job relay sẵn có.
   - `sync_done_conversations++`, cộng dồn `sync_message_count`.
   - Lưu `sync_cursor = nextCursor` sau mỗi trang (resume).
5. Lỗi Graph `code 80006` (rate-limit) ⇒ `release()` job với backoff (giữ cursor). Lỗi khác ⇒ `sync_status=failed`, `sync_error`.
6. Hết trang/đạt cutoff ⇒ `sync_status=done`, `sync_finished_at`, `last_synced_at=now()`, `sync_cursor=null`.

> Throughput: backfill **không** chạy đồng bộ trong request. `fetchMessages` mỗi hội thoại có thể tách job con nếu page lớn (plan quyết định: vòng lặp đơn job + chunk, hay job-per-conversation). Mặc định spec: 1 job/loop, chunk hội thoại theo trang, idempotent nên an toàn retry.

### 4.3 Endpoints (Messaging `routes.php`)

```php
// Trong group api/v1/messaging:
Route::post('channels/{id}/sync', [MessagingChannelController::class, 'sync'])
    ->whereNumber('id')->name('messaging.channels.sync');               // messaging.connect

Route::post('conversations/{id}/unread', [ConversationController::class, 'markUnread'])
    ->whereNumber('id')->name('messaging.conversations.unread');        // messaging.view

Route::post('conversations/{id}/block', [ConversationController::class, 'block'])
    ->whereNumber('id')->name('messaging.conversations.block');         // messaging.reply
Route::delete('conversations/{id}/block', [ConversationController::class, 'unblock'])
    ->whereNumber('id')->name('messaging.conversations.unblock');       // messaging.reply
```

- **`MessagingChannelController@sync`** (`messaging.connect`): tìm page facebook của tenant ⇒ set `sync_status=queued` ⇒ dispatch `BackfillMessagingChannel`. Trả `{ ok: true }` (202).
- **`MessagingChannelController@index`** mở rộng item:
  ```
  { id, provider, shop_name, name, external_shop_id, status, messaging_enabled,
    token_expired, connected_at,
    avatar_url,                 // served URL từ page_avatar_path (signed/relay), null nếu chưa có
    message_count,              // = meta.sync_message_count
    sync: { status, total: sync_total_conversations, done: sync_done_conversations,
            message_count: sync_message_count, started_at, finished_at, last_synced_at, error } }
  ```
- **`ConversationController@markUnread`** (`messaging.view`): set `manually_unread=true`. Trả conversation.
- **`ConversationController@block`** (`messaging.reply`): set `blocked_at=now()`, `blocked_by_user_id`, `status=spam` (ẩn). Audit `messaging.conversation.blocked`.
- **`ConversationController@unblock`** (`messaging.reply`): set `blocked_at=null`, `blocked_by_user_id=null`, `status=open`. Audit `messaging.conversation.unblocked`.

### 4.4 Sửa logic có sẵn

- **`MessageIngestionService`** (ingest inbound): nếu `conversation.blocked_at != null` ⇒ vẫn lưu message (audit) nhưng **không** `unread_count++`, **không** đổi `status` khỏi spam (giữ ẩn). (Buyer bị chặn nhắn lại ⇒ không nổi inbox.)
- **`ConversationController@index`**:
  - Ẩn `blocked` mặc định: khi không truyền `status` và không `?blocked=true` ⇒ thêm `whereNull('blocked_at')` (đi kèm điều kiện ẩn spam sẵn có).
  - Filter `?blocked=true` ⇒ `whereNotNull('blocked_at')` (tab "Đã chặn").
  - `?unread=true` mở rộng: `where(fn => unread_count>0 OR manually_unread=true)`.
- **`ConversationController@markRead`**: thêm `manually_unread=false` cùng `unread_count=0`.
- **`MessageController@sendText/sendTemplate/sendMedia`**: nếu `conv->blocked_at` ⇒ `422 { code: CONVERSATION_BLOCKED }` (trước window guard).
- **`FacebookOAuthController@callback`**: sau `updateOrCreate` mỗi page ⇒ ensure `messaging_account_meta` + dispatch `BackfillMessagingChannel` (tự động đồng bộ khi kết nối).
- **Scheduler** (`routes/console.php`): `messaging:reconcile-sync` (vd `hourly()->onOneServer()`): với mỗi page `messaging_enabled` + connector `inbound.backfill`, dispatch backfill nhẹ (chỉ trang đầu, cutoff ngắn) để vá tin webhook lọt. Command `Console/Commands/ReconcileMessagingSync.php`.

---

## 5. Frontend

### 5.1 `MessagingChannelsPage.tsx` (G1+G3)

- Mỗi page: `<Avatar src={p.avatar_url} icon={<FacebookFilled/>} />` (fallback icon nếu null).
- **Tiến trình/đếm tin:**
  - `sync.status === 'running' | 'queued'` ⇒ `<Progress percent={done/total*100} size="small"/>` + "Đang đồng bộ… (done/total hội thoại)".
  - `sync.status === 'done'` ⇒ `<Text>Đã đồng bộ • {message_count} tin nhắn</Text>` + `last_synced_at` (dayjs).
  - `sync.status === 'failed'` ⇒ tag đỏ "Đồng bộ lỗi" + tooltip `error`.
- Nút **"Đồng bộ lại"** (`<SyncOutlined/>`, gate `messaging.connect`) ⇒ `useSyncChannel(id)`.
- **Polling**: `useMessagingChannels` thêm `refetchInterval` = 4s **khi có** page `queued|running`, ngược lại `false` (giống pattern KnowledgeDocs).

### 5.2 `MessagingPage.tsx` (G4+G5)

- **List hội thoại:** `<Avatar src={c.buyer_avatar_url}/>` trước tên; badge `unread_count` (đã có) + chấm khi `manually_unread`. Item của hội thoại blocked không hiện ở inbox mặc định.
- **Filter:** thêm "Chưa đọc" (Segmented hoặc `<Checkbox>`) ⇒ `?unread=true`; thêm lựa chọn xem "Đã chặn" ⇒ `?blocked=true`.
- **Menu hội thoại** (`<Dropdown>` icon `<MoreOutlined/>`): "Đánh dấu chưa đọc" (`useMarkUnread`), "Chặn người dùng"/"Bỏ chặn" (`useBlock`/`useUnblock`, `<Popconfirm>`).
- **Composer:**
  - Nút đính kèm: `<Upload>` ẩn + icon `<PictureOutlined/>` (ảnh), `<VideoCameraOutlined/>` (video), `<PaperClipOutlined/>` (tài liệu) ⇒ `useSendMedia` (multipart `kind`/`file`/`caption`/`message_tag` — endpoint BE đã có). Hiện xem trước + tiến trình upload; lỗi `ATTACHMENT_INVALID`/`OUTBOUND_WINDOW_CLOSED` ⇒ toast.
  - Nút emoji `<SmileOutlined/>` ⇒ `<Popover>` chứa `emoji-mart` Picker ⇒ chèn ký tự emoji vào vị trí con trỏ trong `<Input.TextArea>`.
  - Hội thoại blocked ⇒ khoá composer + banner "Đã chặn người dùng — Bỏ chặn để nhắn lại".
- **Thread:** render `m.attachments`: ảnh (`<Image>` thumbnail), video (`<video controls>`), file (link tải `<a>` + tên/size). Tin chỉ-đính-kèm (body null) hiện theo kind.

> **Quy ước icon (memory `ui-use-font-icons-not-emoji`):** mọi **nút/biểu tượng UI** dùng `@ant-design/icons`. Emoji ở đây là **nội dung tin nhắn** người dùng gửi cho khách (không phải icon UI) ⇒ được phép. Picker trigger vẫn dùng `<SmileOutlined/>`.

### 5.3 Hooks & types

- `messaging.tsx`: mở rộng `Conversation` (`blocked_at`, `manually_unread`); `Message.attachments` đã có. Thêm `useSendMedia`, `useMarkUnread`, `useBlockConversation`, `useUnblockConversation`. `ConversationFilters` thêm `blocked?`.
- `messagingConfig.tsx`: `MessagingChannel` thêm `avatar_url`, `message_count`, `sync`. Thêm `useSyncChannel`. `useMessagingChannels` thêm refetchInterval động.
- `package.json`: thêm `@emoji-mart/react` + `@emoji-mart/data`.

---

## 6. Data flow

- **Kết nối + tự đồng bộ:** OAuth callback upsert page → ensure meta → dispatch `BackfillMessagingChannel` → relay avatar page + phân trang conversations (≤90 ngày) → mỗi hội thoại fetch ≤50 tin → `MessageIngestionService` (dedup) → relay đính kèm/avatar buyer → cập nhật sync-state. FE poll `GET /channels` thấy `running`→`done` + `message_count`.
- **Đồng bộ lại (thủ công):** nút → `POST /channels/{id}/sync` → dispatch backfill (idempotent, dedup).
- **Đối soát định kỳ:** scheduler `messaging:reconcile-sync` → backfill nhẹ page active.
- **Real-time tin mới:** vẫn qua webhook hiện có (không đổi); ingest tăng `sync_message_count`.
- **Gửi media:** composer upload → `POST /conversations/{id}/messages/media` → MinIO → `SendMessage` → `connector.sendMedia(signed URL)`.
- **Đánh dấu chưa đọc:** `POST /conversations/{id}/unread` → `manually_unread=true`; mở hội thoại → `markRead` clear.
- **Chặn:** `POST /conversations/{id}/block` → ẩn + ingest drop tin mới + chặn gửi; bỏ chặn `DELETE`.

---

## 7. Edge case & lỗi

| Tình huống | Xử lý |
|---|---|
| Backfill chạy lại / trùng webhook | Idempotent nhờ `UNIQUE(conversation_id, external_message_id)`; counter cộng theo row thực tạo |
| Rate-limit Graph (80006) | `release()` backoff + giữ `sync_cursor` resume; `sync_status` vẫn `running` |
| Token page hết hạn giữa chừng | `sync_status=failed` + `sync_error`; UI tag "Đồng bộ lỗi" + gợi ý "Kết nối lại" |
| Avatar URL hết hạn | Relay MinIO ngay khi fetch; refresh ở backfill/reconcile |
| Page có rất nhiều hội thoại | Cutoff 90 ngày + cap 50 tin/hội thoại; cursor resume; (tuỳ plan) job-per-conversation |
| Hội thoại bị chặn nhận tin mới | Lưu nhưng không tăng unread, không nổi inbox |
| Gửi tin vào hội thoại blocked | `422 CONVERSATION_BLOCKED` (FE đã khoá composer) |
| `fetchMessages` thiếu `from`/page id | Không xác định direction ⇒ mặc định inbound + log; không vỡ ingest |
| App ở Development mode | Backfill/profile chỉ chạy với tài khoản tester; production cần App Review (ghi chú vận hành) |
| Đính kèm video lớn vượt giới hạn | BE `ATTACHMENT_INVALID` (MediaRelayService validate) ⇒ toast FE |

---

## 8. Bảo mật & quyền

- `GET /messaging/channels` **không** trả `access_token`; `avatar_url` là URL relay/served (không phải Graph token URL).
- Quyền: `sync` = `messaging.connect`; `block`/`unblock` = `messaging.reply`; `markUnread` = `messaging.view` (đọc-trạng-thái cá nhân).
- Audit: `messaging.facebook.sync.requested`, `messaging.conversation.blocked|unblocked` (prefix `messaging.*`, SPEC §8.7).
- Avatar/đính kèm là PII ⇒ ngắt kết nối page xoá luôn file MinIO (đã có ở `FacebookPageDisconnectService`; bổ sung avatar page nếu cần).
- profile_pic chỉ lấy trong ngữ cảnh messaging hợp lệ; tôn trọng app review (`Business Asset User Profile Access`).

---

## 9. Kiểm thử

**Unit (connector, `Http::fake`):**
- `fetchConversations`: map `thread_id`/`psid`/`buyer_name`/`message_count`; cursor `after`; dừng đúng khi `updated_time` < cutoff.
- `fetchMessages`: map direction echo→outbound, đính kèm→`MediaRefDTO`, sticker.
- `fetchPageProfile`/`fetchUserProfile`: trả `avatar_url`.

**Feature (BE):**
- Backfill: nạp conversations+messages, dedup khi chạy 2 lần (không nhân đôi), counter đúng, `sync_status` chuyển trạng thái, resume cursor khi 80006.
- Ingest drop khi `blocked_at` (không tăng unread).
- `markUnread` set cờ; `markRead` clear cờ.
- `index?unread=true` gồm `manually_unread`; `index?blocked=true` chỉ blocked; mặc định ẩn blocked.
- `block`/`unblock` set/clear + audit; gate `messaging.reply`; `staff_cs` theo ma trận quyền.
- `sendMedia`/`sendText` vào hội thoại blocked ⇒ 422.
- `GET /channels` resource có `avatar_url`/`message_count`/`sync`; `POST channels/{id}/sync` gate + dispatch.
- OAuth callback dispatch backfill (giả lập `/me/accounts`).

**FE (Vitest):**
- Channels: avatar render; tiến trình khi `running`; "{n} tin nhắn" khi `done`; nút "Đồng bộ lại" gọi hook; polling bật khi đang sync.
- Composer: upload ảnh/file gọi `useSendMedia`; emoji picker chèn ký tự; thread render ảnh/video/file.
- Inbox: avatar buyer; "đánh dấu chưa đọc" + chấm; filter "chưa đọc"; block/unblock + composer khoá khi blocked.

---

## 10. Tiêu chí hoàn thành

- [ ] Migrations meta (sync/avatar) + conversations (blocked/manually_unread) chạy + rollback được.
- [ ] Connector `inbound.backfill` + `fetchConversations`/`fetchMessages`/`fetchPageProfile`/`fetchUserProfile` + unit test xanh.
- [ ] `BackfillMessagingChannel` nạp + dedup idempotent + sync-state + relay avatar/đính kèm; resume khi rate-limit.
- [ ] `POST /channels/{id}/sync` + `GET /channels` mở rộng (avatar/count/sync) + dispatch backfill khi OAuth connect.
- [ ] Scheduler `messaging:reconcile-sync`.
- [ ] Block/unblock + markUnread + ingest drop + filters (`unread` gồm manually_unread, `blocked`) + send guard.
- [ ] FE channels: avatar + tiến trình + đếm tin + nút sync + polling.
- [ ] FE composer: gửi ảnh/video/tài liệu + emoji + render đính kèm.
- [ ] FE inbox: avatar buyer + đánh dấu chưa đọc + lọc chưa đọc + chặn/bỏ chặn + khoá composer khi blocked.
- [ ] Audit log mọi action mutating; không lộ token.
- [ ] Test BE + FE đạt ngưỡng coverage chung; `pint`/`phpstan`/`eslint` xanh.

---

## 11. Lộ trình triển khai (slice nhỏ, mỗi slice có test)

1. **BE-migrations:** cột meta sync/avatar + conversations blocked/manually_unread.
2. **BE-connector:** capability + `fetchConversations`/`fetchMessages`/`fetchPageProfile`/`fetchUserProfile` (unit `Http::fake`).
3. **BE-backfill:** job `BackfillMessagingChannel` + sync-state + reuse ingest/relay (feature test idempotent + rate-limit resume).
4. **BE-endpoints:** `channels/{id}/sync` + mở rộng resource `index` + dispatch backfill ở OAuth callback.
5. **BE-reconcile:** command + schedule.
6. **BE-inbox-mgmt:** block/unblock + markUnread + ingest drop + index filters + send guard.
7. **FE-channels:** avatar + tiến trình + đếm tin + nút sync + polling + types/hooks.
8. **FE-composer:** media upload + emoji-mart + render đính kèm thread.
9. **FE-inbox:** avatar buyer + mark-unread + unread filter + block/unblock + composer khoá.
10. **Test toàn bộ** BE+FE + lint/static analysis.
