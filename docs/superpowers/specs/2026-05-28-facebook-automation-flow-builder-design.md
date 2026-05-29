# Thiết kế: Trình xây dựng kịch bản tự động (Automation Flow Builder) — Facebook Comment + Inbox

- **Trạng thái:** Draft (chờ chủ dự án duyệt trước khi → Reviewed / lập plan)
- **Ngày / Tác giả:** 2026-05-28 · Codegen
- **Module:** Messaging (sub-domain `Flows` mới) · Integrations/Messaging · Integrations/Ai
- **Liên quan:** SPEC-0024 (Omnichannel Messaging), thiết kế `2026-05-26-comment-autoreply-and-unified-composer-design.md`, `extensibility-rules.md` §1/§6c (capability map — core không biết tên sàn), `08-security-and-privacy.md` (PII), ADR-0017 (MessagingConnector/Registry), ADR-0018 (AiAssistant)
- **Phạm vi v1:** **Facebook Page** (sàn duy nhất đủ comment + postback + nút bấm + private reply). Sàn khác chừa sẵn qua capability, nối sau.

---

## 1. Vấn đề & mục tiêu

Hệ thống Messaging hiện đã có: hộp thư hợp nhất, **auto-reply rules phẳng** (trigger `first_message`/`keyword`/`schedule`/`order_status`/`comment_any`), comment auto-reply (public/private/AI) cho Facebook, AI suggest + auto-mode (guardrail intent), composer dùng chung, template (text + ảnh + biến), capability map cho connector.

**Còn thiếu (yêu cầu mới):**
1. **Luồng kịch bản nhiều bước có rẽ nhánh** — hiện rule chỉ "1 trigger → 1 hành động", không làm được "gửi tin có nút → khách bấm nút A → gửi tiếp X; bấm B → hỏi SĐT → gắn thẻ → bàn giao NV".
2. **Tin nhắn tương tác có nút bấm** (button/generic template) + **postback** dẫn sang bước tiếp theo — FB connector đã subscribe `messaging_postbacks` nhưng **chưa có handler**.
3. **Auto-comment theo từng post cụ thể** (hiện `comment_any` áp cho mọi bài).
4. **Giao diện kéo-thả trực quan**, người dùng **không phải nhập JSON thô**.

**Mục tiêu:** một **trình xây dựng kịch bản dạng sơ đồ node kéo-thả** cho phép owner/staff dựng luồng tự động cho (a) **bình luận trên post Facebook cụ thể** và (b) **inbox khách**, với nội dung theo **kịch bản cố định / mẫu / AI**, có **nút bấm chuyển bước**, đính kèm **đa phương tiện**, tất cả qua form trực quan.

**Tiêu chí thành công:**
- Owner dựng được 1 luồng comment-trên-post: comment chứa keyword → tự trả lời công khai + nhắn riêng tin có nút "Xem sản phẩm / Nhận ưu đãi" → bấm nút → gửi bước kế → idempotent, tôn trọng cửa sổ 24h.
- Dựng được luồng inbox: tin đầu → menu nút → rẽ nhánh theo nút/keyword/intent → thu thập SĐT → gắn thẻ / bàn giao NV.
- Tạo template nút bấm + carousel hoàn toàn bằng form (không JSON).
- Mỗi lượt khách chỉ chạy luồng 1 lần đúng trạng thái (không spam), audit đầy đủ.

---

## 2. Trong / ngoài phạm vi

### Trong (v1 — Facebook)
- Sub-domain `Flows`: model `automation_flows` (đồ thị node+edge dạng jsonb), `flow_runs` (state máy chạy theo hội thoại), mở rộng `message_templates` cho tin tương tác.
- `FlowEngine` thực thi node, giữ state theo hội thoại, tiến bước khi có inbound / postback.
- Loại node v1: **Trigger** (entry) · **Gửi tin** (text / template / tin-có-nút) · **Trả lời comment** (công khai / riêng) · **Chờ trả lời** · **Chờ bấm nút (postback)** · **Điều kiện/rẽ nhánh** (theo nút, keyword, intent AI, đã-có-SĐT, trạng thái đơn) · **AI trả lời** (qua `AiSuggestionService` + guardrail) · **Thu thập thông tin** (lưu biến, vd SĐT) · **Hành động** (gắn thẻ / gán NV / bàn giao người thật / set field) · **Delay** · **Kết thúc**.
- Trigger v1: `comment_on_post` (chọn post_id cụ thể), `comment_any`, `inbox_first_message`, `inbox_keyword`, `inbox_any`.
- `parsePostback` cho `FacebookPageConnector` → event `PostbackReceived` → `FlowEngine` tiến node.
- Capability mới `outbound.interactive` (nút bấm/carousel). FB = true.
- FE: trang danh sách flow + **canvas kéo-thả** (reactflow) + drawer cấu hình từng node (form) + **trình tạo tin nút bấm/carousel** + **post picker** (chọn bài đăng FB).
- Giữ nguyên `auto_reply_rules` phẳng (bổ sung, không thay thế).
- RBAC: dùng lại `messaging.rule.manage` (đặt tên hiển thị "Kịch bản tự động"); Billing gate dùng lại `plan.feature:messaging_*`.
- Idempotency, audit log, PII redaction (khi node AI gọi LLM), tôn trọng cửa sổ 24h + message tag.

### Ngoài phạm vi (slice sau / spec khác)
- Node-graph cho **TikTok/Shopee/Lazada** (chỉ chừa capability; chưa có nút bấm/postback/private reply trên các sàn này).
- A/B test nhánh, lập lịch gửi hàng loạt / broadcast marketing (vision-and-scope cấm broadcast).
- Phân tích nâng cao (funnel chi tiết); v1 chỉ đếm cơ bản (vào luồng / hoàn tất / bàn giao).
- Templating đa ngôn ngữ tự động.
- Kéo-thả cho `auto_reply_rules` cũ (giữ form hiện tại).
- Import/export flow JSON cho người dùng cuối (nội bộ có thể có, không phải UX chính).

---

## 3. Nguyên tắc kiến trúc (BẮT BUỘC)

- **Core không bao giờ `if ($provider === ...)`** (`extensibility-rules.md`). Mọi năng lực sàn khai qua `MessagingConnector::capabilities()`; node kiểm `supports()` trước khi gửi. Sàn thiếu năng lực → node ghi `failed` + log, **không** ném vỡ luồng, không spam.
- **Gửi đi luôn qua `OutboundMessageService` / `CommentReplyService`** (đã có) ⇒ audit + `OutboundWindowGuard` 24h chạy đồng nhất cho cả NV lẫn engine.
- **AI luôn qua `AiSuggestionService::draftForAuto()`** ⇒ guardrail intent + PII redaction + ghi `ai_assistant_runs` dùng chung 1 nguồn.
- **Flow là lớp nâng cao bổ sung** cho `auto_reply_rules`; không migrate rule cũ.
- Mọi bảng có `tenant_id` + `BelongsToTenant`. Đồ thị flow lưu **jsonb** (không bảng node/edge riêng — đơn giản, versionable).

---

## 4. Dữ liệu

### 4.1 `automation_flows`
```
id bigint PK,
tenant_id NOT NULL,
name string,
provider string default 'facebook_page',
status enum(draft|active|paused|archived) default 'draft' index,
trigger_type enum(comment_on_post|comment_any|inbox_first_message|inbox_keyword|inbox_any),
trigger_config jsonb default '{}',  -- { post_ids:[...], keywords:[...], match:'any|all' }
graph jsonb default '{}',           -- { nodes:[{id,type,position,data}], edges:[{id,source,target,sourceHandle}] }
version int default 1,
enabled bool default true,
created_by? FK users,
created_at, updated_at,
INDEX (tenant_id, provider, status, enabled)
```
- `graph` do canvas (reactflow) sinh; **người dùng không bao giờ sửa JSON tay**.
- Publish = validate đồ thị (xem §5.4) rồi `status=active`. Draft chỉnh thoải mái.

### 4.2 `flow_runs` (state máy thực thi theo hội thoại)
```
id bigint PK,
tenant_id NOT NULL,
flow_id FK automation_flows,
conversation_id FK conversations,
current_node_id string?,            -- node đang chờ input/postback; null nếu vừa enter
status enum(active|waiting|completed|ended|failed) default 'active' index,
context jsonb default '{}',         -- biến thu thập: { phone, last_button, ... }
entered_at timestamptz,
last_advanced_at timestamptz,
expires_at timestamptz?,            -- node chờ có timeout → tự ended
UNIQUE (flow_id, conversation_id) WHERE status IN ('active','waiting')
```
- Một hội thoại chỉ có **1 run đang chạy / flow** (unique partial) → idempotent, không double-enter.
- Postback/inbound đến → tìm run `waiting` của hội thoại → tiến theo node hiện tại.

### 4.3 Mở rộng `message_templates` (tin tương tác)
```
+ kind enum(text|button|generic) default 'text',
+ structure jsonb default '{}'
```
- `button`: `{ text, buttons:[ Button ] }` (tối đa 3 nút — giới hạn FB).
- `generic` (carousel): `{ cards:[ { title, subtitle?, image_path?, buttons:[ Button ] } ] }` (tối đa 10 card, 3 nút/card).
- `Button` = `{ type:'postback'|'url'|'flow', label, payload?, url?, target_node_id? }`:
  - `postback` → gửi payload, engine bắt qua webhook.
  - `flow` (đường tắt UX) → builder tự sinh payload trỏ `target_node_id` (vẫn là postback dưới nền).
  - `url` → mở web (web_url button).
- Đính kèm media dùng `attachments` jsonb sẵn có. Lọc theo `scope.thread_types` (đã có).

### 4.4 `flow_runs` audit / analytics
- Tái dùng `auto_reply_runs` cho idempotency cấp node-gửi (key = `flow:{flow_id}:node:{node_id}:conv:{conversation_id}` hoặc `:msg:{external_message_id}` cho comment).
- Đếm cơ bản: thêm cột đếm runtime hoặc bảng nhẹ `flow_stats` (entries/completions/handoffs) — chốt ở plan (có thể tính từ `flow_runs`).

### 4.5 Migration
- Reversible (`down()` đủ). Thêm 2 bảng + 2 cột `message_templates`. Không phá schema module khác.
- `flow_runs` cân nhắc partition theo `entered_at` tháng nếu lớn (theo dõi; v1 chưa bắt buộc).

### 4.6 Domain events
- Phát: `Flows\Events\FlowRunStarted`, `FlowRunAdvanced`, `FlowRunCompleted`, `FlowRunFailed`, `FlowHandoffRequested{conversation_id}`.
- Lắng nghe (entry points): `MessageReceived` (inbox triggers), `CommentReceived` (comment triggers), **`PostbackReceived` (mới)**, `OrderStatusChanged` (điều kiện node theo đơn — qua context).

---

## 5. Backend — FlowEngine & thực thi

### 5.1 Entry (kích hoạt luồng)
- Listener mới `StartFlowOnInbound` (nghe `MessageReceived`) và `StartFlowOnComment` (nghe `CommentReceived`): tìm `automation_flows` active khớp `trigger_type` + `trigger_config` (post_ids/keywords) cho `(tenant, provider, conversation.thread_type)`.
- Khớp ⇒ `FlowEngine::start(flow, conversation, inbound)` tạo `flow_run` (unique guard) rồi `advance()`.
- **Quan hệ với rule phẳng:** nếu hội thoại đã vào 1 flow (`flow_run` active) thì listener auto-reply phẳng bỏ qua (tránh trả lời chồng). Quy tắc ưu tiên: flow > rule phẳng. (Chốt ở §11.)

### 5.2 Thực thi node (`FlowEngine`)
- `advance(flow_run)`: từ `current_node_id` (hoặc node trigger) đi theo edge, **chạy tuần tự các node "tức thì"** (gửi tin, điều kiện, hành động, AI) cho tới khi gặp node **"chờ"** (`wait_reply`/`wait_button`) ⇒ lưu `current_node_id`, `status=waiting`, return.
- Node "kết thúc" ⇒ `status=completed`. Không còn edge hợp lệ ⇒ `ended`.
- Mỗi node-gửi đi qua `OutboundMessageService::queueText()` / template / `CommentReplyService` (theo `conversation.thread_type` + node type), gắn `meta.flow_id/flow_run_id/node_id` để hiển thị + idempotent.
- **Chống vòng lặp:** giới hạn số node tức-thì mỗi lần advance (vd 25) + chống đi lại node đã thăm trong cùng lần advance.

### 5.3 Tiến bước khi có input
- **Inbound text** (node `wait_reply` / điều kiện keyword): `MessageReceived` → nếu hội thoại có run `waiting` ⇒ `FlowEngine::resume(run, text)`; node điều kiện so keyword/intent (intent qua `IntentClassifier` đã có) chọn edge.
- **Postback** (node `wait_button`): xem §6.
- **Timeout**: node chờ có `expires_at`; job `ExpireFlowRuns` (scheduler mỗi phút) → đi edge "hết giờ" nếu có, không thì `ended`.

### 5.4 Validate đồ thị khi publish
- Có đúng 1 node trigger; mọi node tới được từ trigger; node "chờ" có ≥1 edge ra; nút `flow` trỏ `target_node_id` tồn tại; template gửi đi tồn tại + đúng `thread_type`/capability provider. Lỗi ⇒ `422` kèm danh sách node lỗi (FE highlight node đỏ).

### 5.5 Idempotency & an toàn
- `flow_runs` unique partial chống double-enter.
- Node-gửi dùng `auto_reply_runs` key để không gửi 2 lần khi job retry / webhook trùng.
- Postback dedupe theo `mid`/payload đã xử lý (lưu trong `flow_run.context._seen` hoặc `webhook_events`).
- Comment: window_key theo `external_comment_id` (giống thiết kế comment auto-reply hiện có).

---

## 6. Postback (mảnh còn thiếu của FB connector)

- `FacebookPageConnector::parseWebhookEvents`: bổ sung nhánh `messaging[].postback` → DTO `kind=postback`, `payload`, `sender`, `mid`.
- `ProcessMessagingWebhook`: nếu event là postback ⇒ phát **`PostbackReceived{conversation_id, payload, external_message_id}`** (không phát `MessageReceived`).
- Listener `AdvanceFlowOnPostback`: tìm `flow_run` waiting của hội thoại; payload mã hoá `{flow_run_id?, node_id, edge}` (builder sinh) ⇒ `FlowEngine::resume(run, postback)` chọn edge theo payload.
- Gửi tin có nút: connector thêm `sendInteractive(auth, conversationId, structure, opts)` (button/generic template Send API). Capability `outbound.interactive`. Tôn trọng `OutboundWindowGuard` (ngoài 24h chỉ gửi nếu có message tag hợp lệ).

---

## 7. Connector — capability & method (provider-agnostic)

| Capability | Ý nghĩa | FB v1 |
|---|---|---|
| `outbound.interactive` | gửi tin có nút bấm / carousel | ✅ |
| `inbound.postback` | nhận sự kiện bấm nút | ✅ |
| (đã có) `comment.reply_public/private/media`, `outbound.image/video/file/template` | … | ✅ |

- Method mới trên `MessagingConnector` (mặc định ném `UnsupportedOperation`): `sendInteractive(...)`, `parsePostback(...)` (hoặc gộp vào `parseWebhookEvents`).
- `FacebookPageConnector` implement đầy đủ. Sàn khác: capability=false ⇒ builder ẩn node nút bấm cho provider đó.

---

## 8. Frontend

### 8.1 Thư viện
- Thêm **`@xyflow/react`** (reactflow v12) cho canvas. (Konva đã có nhưng không phù hợp node-graph.)

### 8.2 Trang & component (theo `06-frontend/overview.md`)
- `/messaging/flows` — danh sách flow (AntD Table: tên, trigger, trạng thái, bật/tắt, sửa/nhân bản/xoá).
- `/messaging/flows/:id/edit` — **Builder**:
  - **Trái:** palette node (kéo vào canvas) — nhóm: Bắt đầu, Gửi tin, Hỏi/Chờ, Rẽ nhánh, AI, Hành động, Kết thúc.
  - **Giữa:** canvas reactflow (kéo-thả, nối edge, mini-map, zoom). Node lỗi (validate) viền đỏ.
  - **Phải:** drawer cấu hình node đang chọn — **toàn form** (chọn template/nhập text, thêm nút bằng form, chọn điều kiện bằng Radio/Segmented, chọn thẻ/NV…).
  - **Trên:** cấu hình trigger (Segmented chọn loại; với `comment_on_post` mở **Post picker**), nút **Lưu nháp** / **Xuất bản** / **Tạm dừng**, nút **Chạy thử** (sandbox: mô phỏng inbound/bấm nút, không gọi sàn).
- **Trình tạo tin nút bấm / carousel**: trong drawer node "Gửi tin" → chọn kiểu (text/nút/carousel bằng Segmented) → form thêm nút (label + hành động: trả lời bước/mở URL/nhắn riêng) → thêm ảnh/video/file (dùng lại upload của `MessageComposer`).
- **Post picker**: endpoint mới `GET /messaging/channels/{id}/posts` (connector `listPosts`) → lưới bài đăng (ảnh + trích nội dung + ngày) chọn 1 hay nhiều post_id.
- Tuân chuẩn: `@ant-design/icons` (không emoji), `Radio.Group`/`Segmented` thay `<Select>` khi ít lựa chọn, **tuyệt đối không ô nhập JSON thô**.

### 8.3 React Query keys
`['messaging','flows',filters]`, `['messaging','flow',id]`, `['messaging','channel',id,'posts']`.

---

## 9. Tài liệu Facebook (đối chiếu — đã xác nhận còn hỗ trợ 2026)

- **Button template / Generic template** (Send API) + **`messaging_postbacks`** webhook (nút postback gửi payload tuỳ biến). Nguồn: Meta Messenger Platform docs (templates/buttons/postback), cập nhật 2026-04.
- **Private reply comment**: 1 lần / comment, trong **7 ngày**, cần `pages_manage_engagement` + `pages_read_user_content`.
- **Feed webhook** comment đã có `post_id` + `comment_id` (connector đã parse) ⇒ đủ cho `comment_on_post`.
- **Cửa sổ 24h** + message tag (`CONFIRMED_EVENT_UPDATE`/`POST_PURCHASE_UPDATE`/`ACCOUNT_UPDATE`) — `OutboundWindowGuard` đã xử lý.
- **App Review/Live**: cần Advanced Access `pages_messaging`, `pages_manage_engagement`, `pages_manage_metadata`, `pages_read_user_content` + Business Verification + video demo (đã ghi ở `facebook-messenger-setup.md`).

---

## 10. Edge case & lỗi

| Tình huống | Xử lý |
|---|---|
| Webhook/postback trùng (retry) | Dedupe `mid`/payload; `flow_runs` unique; `auto_reply_runs` key node. |
| Khách bấm nút cũ sau khi luồng đã kết thúc | Run không còn `waiting` ⇒ bỏ qua hoặc khởi động lại nếu nút thuộc trigger. |
| Ngoài cửa sổ 24h | `OutboundWindowGuard` chặn node-gửi DM ⇒ node `failed` + log; nếu node có nhánh "không gửi được" thì đi nhánh đó. |
| Provider thiếu capability nút bấm | Builder ẩn node; nếu flow cũ có node đó cho provider mới ⇒ validate publish báo lỗi. |
| AI escalate (intent nhạy cảm) | `draftForAuto` trả null ⇒ node AI đi nhánh "cần người thật" / `FlowHandoffRequested` + `requires_human`. |
| Vòng lặp vô hạn trong đồ thị | Giới hạn node tức-thì/lần advance + chống thăm lại node. |
| Page token hết hạn | Node-gửi qua pipeline hiện có → `TokenExpired` → refresh/`channel_account.status=expired` + noti. |
| Sửa flow đang chạy | `flow_runs` giữ `version`/`graph` snapshot khi enter? → v1: run đọc flow hiện tại; publish thay đổi chỉ áp run mới (chốt §11). |
| Comment trên post không nằm trong `post_ids` | Trigger không khớp ⇒ không vào luồng. |

---

## 11. Câu hỏi mở (chốt khi review spec)
1. **Snapshot graph theo run** hay run luôn đọc flow mới nhất? (Đề xuất: snapshot `graph` vào `flow_run.context._graph` khi enter để ổn định.)
2. **Ưu tiên flow vs rule phẳng** khi cả hai khớp: flow thắng và chặn rule? (Đề xuất: có.)
3. **`flow_runs` partition** ngay hay để sau khi đo tải.
4. Cần **node A/B / random split** trong v1 không? (Đề xuất: không.)
5. Phân tích: bảng `flow_stats` riêng hay tính từ `flow_runs`/`auto_reply_runs`?

---

## 12. Kiểm thử

- **Unit:** `FlowEngine::advance` (đi qua node tức thì, dừng ở node chờ); chọn edge theo postback/keyword/intent; chống vòng lặp; validate đồ thị (thiếu trigger, edge treo); idempotency node-gửi.
- **Feature:** `MessageReceived`/`CommentReceived` → start flow (unique guard); postback → resume đúng edge (`Http::fake` FB); gửi tin nút bấm gọi `sendInteractive` đúng payload; comment_on_post chỉ khớp post được chọn; ngoài 24h → node failed/nhánh fallback; handoff → `requires_human`.
- **Contract:** `FacebookPageConnector::parsePostback` + `sendInteractive` (fixtures payload thật), capability gate.
- **FE:** builder lưu/đọc `graph`; validate highlight node lỗi; trình tạo nút bấm sinh `structure` đúng; post picker; chạy thử mô phỏng. (Vitest nếu có; nếu không, kiểm thủ công + Playwright luồng chính.)
- **Quality gate:** `pint --test`, `phpstan analyse`, `php artisan test`, `npm run lint && typecheck && build` (trừ 7 test GHN/fulfillment fail sẵn có trên main).

---

## 13. Phân lát triển khai

| Slice | Scope | Phụ thuộc |
|---|---|---|
| **S1** | Data model (`automation_flows`, `flow_runs`, mở rộng `message_templates`) + `FlowEngine` (node tức thì + chờ) + entry listeners (inbox/comment) + idempotency + tests BE. Chưa có UI. | — |
| **S2** | Postback: `parsePostback` + `PostbackReceived` + `AdvanceFlowOnPostback` + `sendInteractive` (FB) + capability `outbound.interactive`/`inbound.postback` + contract tests. | S1 |
| **S3** | FE canvas kéo-thả (`@xyflow/react`): list + builder + drawer cấu hình node + validate/publish. | S1,S2 |
| **S4** | Trình tạo tin nút bấm/carousel + post picker (`listPosts`) + trigger `comment_on_post` + node trả lời comment công khai/riêng (dùng `CommentReplyService`). | S2,S3 |
| **S5** | Node AI (qua `AiSuggestionService`) + điều kiện intent + thu thập thông tin + hành động (thẻ/gán NV/handoff) + đếm cơ bản + chạy thử. | S3,S4 |

---

## 14. Tiêu chí hoàn thành
- [ ] Dựng + xuất bản luồng comment-trên-post và luồng inbox bằng canvas kéo-thả, không nhập JSON.
- [ ] Tin nút bấm + carousel + đa phương tiện gửi được; bấm nút (postback) tiến đúng bước.
- [ ] `comment_on_post` chỉ chạy trên post được chọn; trả lời công khai + nhắn riêng.
- [ ] Node AI qua guardrail; điều kiện intent/keyword/SĐT/đơn rẽ nhánh đúng.
- [ ] Idempotent (không spam khi retry/trùng webhook); tôn trọng cửa sổ 24h; audit log đầy đủ.
- [ ] Core không có tên sàn; capability gate; sàn khác chừa sẵn.
- [ ] Quality gate xanh (trừ 7 test GHN/fulfillment fail sẵn có).
