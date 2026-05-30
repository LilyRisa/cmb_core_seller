# SPEC 0024: Hộp thư hợp nhất đa nền tảng (Omnichannel Messaging) + AI hỗ trợ trả lời

> **Cập nhật 2026-05-30 — Redesign UI bình luận Facebook:** bài viết gốc render dưới dạng **Post Card** nổi bật (avatar/tên page + thời gian đăng + nội dung đầy đủ có "Xem thêm" + ảnh + link) ghim đầu vùng cuộn cột giữa, bình luận hiển thị bên dưới — thay banner mỏng cắt 80 ký tự (dễ nhầm bài viết với tin nhắn). Bổ sung 2 meta key cho conversation comment: `fb_post_picture` (Graph `full_picture` lấy khi `fetchCommentThreads`/backfill) + `fb_post_created_time`; expose qua `ConversationResource.comment` thành `post_picture` / `post_created_time` (ISO-8601). `full_picture` là CDN hết hạn ⇒ chỉ preview, FE tự ẩn ảnh khi load lỗi; convo cũ hiện ảnh sau lần backfill kế tiếp. Component FE: `components/messaging/CommentPostCard.tsx`.

- **Trạng thái:** Draft (chờ duyệt §11 trước khi → Reviewed)
- **Phase đề xuất:** **7.x** (xem §0 — đẩy từ Phase 8+ backlog lên; cần roadmap update đồng thuận chủ dự án)
- **Module backend liên quan:** **Messaging (mới)** · Channels · Orders · Customers · Settings · Notifications · Admin · Billing
- **Tác giả / Ngày:** Codegen draft · 2026-05-19
- **Liên quan:** ADR-0017, ADR-0018, ADR-0019, ADR-0020, ADR-0021 (đều mới — draft cùng spec này) · ADR-0004 (connector/registry) · ADR-0007 (webhook + polling) · SPEC-0018 (Billing — pattern `PaymentRegistry`) · SPEC-0023 (Admin toolbox — `system_settings`) · `04-channels/README.md` · `08-security-and-privacy.md` · `07-infra/queues-and-scheduler.md`

---

## 0. Phase context — cảnh báo trước khi đọc

`roadmap.md` Phase 8+ và `vision-and-scope.md` §4 nói **"Chat hợp nhất đa sàn: để rất sau (Phase 7+), không phải mục tiêu chính"**. Hiện đang Phase 6.4 (Billing) + 6.5 (Automation/Notifications) chưa đóng. Theo `ways-of-working.md` §1.1, **không** làm xen feature ngoài phase.

Spec này được viết để (a) khoá kiến trúc trước khi quên, (b) cho chủ dự án quyết: nâng feature lên Phase 7.x (cùng với hoặc sau Accounting) hay giữ ở Phase 8+. **Không bắt đầu code khi spec còn `Draft` và roadmap chưa update.** Phụ thuộc bắt buộc phải xong trước:

- Phase 6.5 — **Rules engine + Notifications channels mở rộng** (auto-reply theo order status dùng chính engine này; alert "có tin mới" cần in-app/web push).
- Phase 4 — **Shopee connector** (chat Shopee dùng cùng OAuth token với orders).
- Phase 8 — **Reverb realtime** (chat không có realtime ⇒ phải polling, UX kém — fallback document rõ trong §6).

---

## 1. Vấn đề & mục tiêu

NV chăm sóc khách hàng (CSKH) hiện phải mở **4 ứng dụng** (Shopee Seller / TikTok Shop / Lazada Seller / Facebook Page) để trả lời buyer. Hệ quả:
- Bỏ lỡ tin → SLA chậm → đánh giá thấp shop.
- Không đo lường được (thời gian trả lời, số lượng, intent).
- Trả lời thủ công lặp đi lặp lại (giờ giao hàng, chính sách đổi trả).
- Không tận dụng được context đơn hàng / lịch sử khách đã có sẵn trong app.

**Mục tiêu:** một **hộp thư hợp nhất** trong CMBcoreSeller cho mọi tin nhắn buyer từ 4 nền tảng trên, đồng bộ thời gian thực, có template / auto-reply / AI gợi ý trả lời, link sang đơn + khách để xem lịch sử ngay trong khi trả lời.

**Tiêu chí thành công:**
- NV mở 1 trang `/messaging` thay vì 4 app.
- Tin từ sàn về app **≤ 10 giây** (webhook) hoặc **≤ 5 phút** (polling backup khi webhook hỏng).
- Gửi text/ảnh/video/file 2 chiều thành công ≥ 99% trong 24h window cho phép gửi.
- Auto-reply theo lịch / theo trạng thái đơn / khi vắng mặt — chạy đúng & idempotent (không spam khách).
- AI gợi ý trả lời có context (đơn + lịch sử khách + KB), NV chấp nhận ≥ 60% gợi ý (KPI dài hạn).

---

## 2. Trong / ngoài phạm vi

### Trong (v1)
- Module mới `Messaging`.
- Hai trục mở rộng mới:
  - `MessagingConnector` + `MessagingRegistry` cho 4 nền tảng (xem ADR-0017).
  - `AiAssistantConnector` + `AiAssistantRegistry` cho LLM (xem ADR-0018).
- Inbound: webhook + polling backup (ADR-0007 áp y nguyên).
- Outbound: text, image, video, file, template snippet, ai-suggestion-accept.
- Conversation link tự động sang `customers` (qua `CustomerProfileContract`) và best-effort sang `orders` gần nhất của buyer.
- Template tin nhắn (CRUD theo tenant).
- Auto-reply 4 trigger: **schedule** (giờ hành chính / vắng mặt), **order_status** (vd `delivered` → cảm ơn), **away_no_response** (NV chưa trả lời sau N phút), **first_message** (chào mừng).
- AI **suggestion mode** mặc định (NV duyệt rồi gửi); **auto-mode** opt-in per tenant với guardrail (intent classify + escalate keyword). Provider do **super-admin** thêm vào `system_settings`; tenant chỉ chọn 1 provider trong list active.
- AI **training**: tenant upload tài liệu (FAQ, chính sách, mô tả SP) → RAG store (pgvector hoặc Meilisearch hybrid) → AI dùng làm context khi sinh reply.
- Realtime FE qua **Laravel Reverb** (kéo Phase 8 backlog vào — bắt buộc cho UX); fallback polling 10s nếu Reverb chưa bật.
- RBAC: role mới `staff_cs` + permission `messaging.*`.
- Billing gating: `plan.feature:messaging_inbox` (Pro+) + `plan.feature:messaging_ai` (Business) + `plan.limit:messaging_ai_replies_monthly`.
- Multi-tenant cách ly: `tenant_id` mọi bảng + media trong `tenants/{id}/messaging/...`.
- Partition `messages` theo tháng (RANGE `created_at`) — extend `MonthlyPartition`/`PartitionRegistry`.
- Data deletion: xử lý `data_deletion` webhook + disconnect shop (90 ngày).
- Audit log mọi action mutating (gửi tin, đổi rule, đổi assignment).

### Ngoài phạm vi (làm sau / spec khác)
- **Bulk broadcast / marketing message** — `vision-and-scope.md` §4 cấm rõ; backlog "CRM marketing đầy đủ".
- **Assignment / round-robin** giữa nhiều NV — UI có cột `assigned_user_id` nhưng v1 chỉ gán tay; engine tự gán = SPEC sau.
- **CSAT survey** sau khi đóng conversation.
- **SLA dashboard** chi tiết (first response time, resolution time) — chỉ stats cơ bản v1.
- **Sticker / voice / location / quick reply / carousel** đặc thù — chỉ map ở DTO nếu sàn trả; UI v1 không build.
- **Zalo OA / Instagram DM / WhatsApp / Telegram** — backlog, cùng pattern (thêm connector mới).
- **Chuyển hội thoại sang ticketing system ngoài** (Zendesk, Freshdesk).
- **Voice/video call**.
- **Bot rule engine phức tạp** (flowchart conditional reply) — v1 chỉ 4 trigger phẳng.

---

## 3. Câu chuyện người dùng

### 3.1 NV chăm sóc khách hàng (`staff_cs`)
1. Mở `/messaging` → thấy inbox 3 cột:
   - Trái: list conversation, badge unread, lọc theo nền tảng / trạng thái / chưa gán / có đơn pending.
   - Giữa: thread tin nhắn của conversation đang mở (auto-mark-read sau 2s focus).
   - Phải: panel khách hàng (reputation, lifetime stats từ `customers`) + đơn hàng liên quan (deep link sang `/orders/:id`).
2. Bấm tin nhắn → ô soạn ở dưới: text + button upload ảnh/video/file + button chèn template + button "AI gợi ý".
3. Gửi → tin hiện ngay (optimistic, status `pending`) → backend dispatch job → status `sent` qua Reverb event.
4. Tin buyer mới đến → Reverb event → list conv re-order + badge +1 + notification sound (per `notification_preferences`).

### 3.2 Owner cấu hình auto-reply (`messaging.rule.manage`)
1. Vào `/messaging/auto-rules` → bấm "Thêm quy tắc".
2. Chọn trigger: schedule / order_status / away_no_response / first_message.
3. Cấu hình:
   - schedule: chọn khung giờ (vd `22:00–08:00`, tz `Asia/Ho_Chi_Minh`) + nội dung trả lời.
   - order_status: chọn trạng thái (`delivered` / `cancelled` / …) + delay (vd 30 phút sau khi đổi) + template hoặc raw text.
   - away_no_response: ngưỡng phút (vd 15) + nội dung.
   - first_message: nội dung chào (chỉ trigger 1 lần / conversation).
4. Filter (optional): chỉ áp cho provider X / khách có tag Y.
5. Bật / tắt; priority (nếu trùng trigger).

### 3.3 Super-admin thêm AI provider (`/admin/ai-providers`)
1. Login admin SPA → vào trang `AI Providers`.
2. Thêm provider: chọn code (`claude` / `openai` / `gemini` / `local_llm`) + tên hiển thị + API key (encrypted) + model default + base URL (cho local) + capability flags (suggest / auto / intent / rag).
3. Test connection.
4. Active / inactive.
5. Tenant trong `/settings/messaging` thấy dropdown các provider `active`, chọn 1.

> **Cập nhật (2026-05-21) — adapter động (đã triển khai).** Tách `code` (slug **instance tự do**, vẫn là PK, vd `deepseek-prod`/`qwen-cheap`/`openrouter-fb`) khỏi cột mới **`adapter`** (`anthropic` | `openai_compatible` | `manual`) — `adapter` chọn connector, nên thêm được **không giới hạn** provider. Gemini/DeepSeek/Qwen/OpenRouter đều là instance của `openai_compatible` (khác `base_url`/`api_key`/`default_model`; Gemini dùng endpoint OpenAI-compat). Registry resolve theo `adapter` và **inject instance code** vào connector (`container->makeWith(['code'=>$code])`). Config sống ở **bảng `ai_providers`** (KHÔNG còn `system_settings`); `capabilities` đọc từ connector class (admin không tự khai). Hardening: `base_url` bắt buộc HTTPS + chặn host nội bộ (rule `SafeProviderUrl`, chống SSRF); rate-limit `ai-suggestion` 20/phút/tenant; circuit breaker cho intent-classify (ngừng gọi provider lỗi sau 5 lần/2 phút). Chi tiết: `docs/superpowers/specs/2026-05-21-ai-providers-multi-adapter-and-hardening-design.md` + plan cùng tên trong `docs/superpowers/plans/`.

### 3.4 Owner upload tài liệu AI training
1. `/messaging/knowledge` → upload PDF / DOCX / TXT / URL.
2. Backend chunk + embed → ghi `ai_knowledge_chunks`.
3. Khi NV bấm "AI gợi ý" → orchestrator retrieve top-K chunks → đưa vào prompt → LLM trả gợi ý → NV duyệt → gửi.

---

## 4. Hành vi & quy tắc nghiệp vụ

### 4.1 Idempotency
- Inbound message dedupe bằng `messages` unique `(conversation_id, external_message_id)`. Cùng webhook chạy 2 lần ⇒ 1 row.
- Outbound: ghi `messages` (`delivery_status='pending'`) **trước** khi dispatch `SendMessage` job. Job retry không tạo row mới — chỉ update status.
- Auto-reply: lock per `(conversation_id, rule_id, trigger_window)` qua Redis ⇒ rule chạy 2 lần cùng window không spam.

### 4.2 Outbound window guard
- Facebook Page có **24h reply window**: nếu last inbound > 24h, **chỉ** gửi được tin có `message_tag` (CONFIRMED_EVENT_UPDATE / POST_PURCHASE_UPDATE / ACCOUNT_UPDATE). Connector khai báo `outboundWindow()` → `OutboundWindowGuard` chặn ở service trước khi dispatch.
- Shopee / TikTok / Lazada: chính sách khác (không có hard 24h nhưng có chính sách spam). Connector tự đặt policy.
- Vi phạm window ⇒ `422 OUTBOUND_WINDOW_CLOSED` + gợi ý FE dùng template tag.

### 4.3 Auto-reply không spam
- Mỗi rule có `cooldown_seconds` per conversation (default 3600s) — không gửi cùng auto-reply lặp lại trong cooldown.
- Auto-reply **không** trigger trên message do auto-reply gửi ra (cờ `messages.sent_by_ai` + `messages.meta.auto_rule_id` để engine bỏ qua).
- `first_message`: chỉ chạy khi `conversation.message_count === 1` (race-safe qua `lockForUpdate`).
- `away_no_response`: skip nếu trong khung giờ hành chính rule schedule đã trả lời.

### 4.4 Liên kết khách hàng & đơn
- Khi nhận `MessageReceived`:
  - Listener `LinkConversationToCustomer` (queue `customers`): nếu inbound có SĐT / email / external_buyer_id ⇒ tìm `customers` theo `phone_hash` (Phase 2 SPEC-0002) → set `conversations.customer_id`.
  - Listener `LinkConversationToOrder`: tìm đơn của customer trong 30 ngày gần nhất, ưu tiên `processing/shipped` ⇒ set `conversations.order_id` (best-guess, NV có thể đổi tay).
- Không bao giờ override `customer_id` đã có (race-safe: chỉ set khi `customer_id IS NULL`).

### 4.5 Trạng thái & state machine
- Conversation: `open → snoozed (snoozed_until)` → tự về `open` khi quá hạn hoặc có tin mới.
- `open → resolved`: NV bấm đánh dấu xong; tin mới sau đó ⇒ tự `open` lại.
- `open → spam`: NV bấm; mọi auto-reply bị bypass; tin mới giữ trong `spam` (NV unhide).
- Message delivery: `pending → sent → delivered → read`. `pending → failed` (kèm `failure_code`).

### 4.6 AI guardrails
- AI **không bao giờ** gửi tin mà không qua `MessageSendService` (cùng pipeline với NV) — đảm bảo audit + window guard chạy.
- Auto-mode: trước khi sinh reply, `IntentClassifier` chạy; intent ∈ `{complaint, refund, urgent, legal_threat, abuse}` ⇒ **không gửi**, chỉ `notify` NV (`MessageReceived.requires_human=true`).
- Mọi prompt gửi LLM ngoài phải qua `PiiRedactor` (regex SĐT 10 số, email, STK ngân hàng — redact thành placeholder; placeholder map giữ ở server, không qua wire).
- Token cost ghi `ai_assistant_runs` ⇒ super-admin dashboard charge per tenant.

### 4.7 Phân quyền (RBAC)
- Role mới `staff_cs` (Customer Service): `messaging.view`, `messaging.reply`, `customers.view`, `customers.view_phone`, `orders.view`.
- Permission strings:
  - `messaging.view` — xem inbox & template & rule.
  - `messaging.reply` — gửi tin (kể cả accept AI suggestion).
  - `messaging.template.manage` — CRUD template.
  - `messaging.rule.manage` — CRUD auto-reply rule.
  - `messaging.assign` — gán conversation cho NV (v1: cấp owner/admin).
  - `messaging.ai.config` — chọn AI provider của tenant.
  - `messaging.ai.train` — upload/xoá knowledge doc.
  - `messaging.admin.providers` — super-admin chỉ.
- `staff_order` mặc định cũng có `messaging.view/reply` (NV đơn thường cũng chat).
- `viewer/accountant/staff_warehouse` mặc định không có.

### 4.8 Billing gating
- `plan.feature:messaging_inbox` cho Pro+. Gói Starter/Trial truy cập ⇒ `402 PLAN_FEATURE_LOCKED`.
- `plan.feature:messaging_ai` chỉ Business.
- `plan.limit:messaging_ai_replies_monthly` (default Pro=0, Business=∞ — config trong `plans.limits`). Counter chạy chung pattern `usage_counters`.
- **Không** giới hạn số tin nhắn / số conversation (nhất quán với "không giới hạn đơn" — `multi-tenancy-and-rbac.md` §5).

### 4.9 Tác động lên module khác
- `Orders`: thêm field `unread_messages_count` trên `OrderResource` (qua `MessageInboxContract`) — hiện badge "có tin mới" trên đơn. **Không** cột mới trong `orders` (Orders không sở hữu — đếm runtime cache 60s).
- `Customers`: trang `/customers/:id` thêm tab "Tin nhắn" (đọc qua `MessageInboxContract`).
- `Channels`: ChannelAccount thêm cột boolean `messaging_enabled` (mặc định `false`; bật khi connect provider có messaging capability). **Không** đổi schema khác.
- `Settings`: `automation_rules` schema (Phase 6.5) extend thêm `trigger.type='messaging.*'` — Messaging dispatch event `MessagingTriggerFired`, engine generic xử lý → action handler `messaging.reply`.

---

## 5. Dữ liệu

Mọi bảng có `tenant_id` + `BelongsToTenant` global scope (rule §1 `02-data-model/overview.md`). Bảng chi tiết:

### 5.1 `messaging_account_meta` (1-1 `channel_accounts`)
```
channel_account_id PK FK channel_accounts (cascade),
tenant_id NOT NULL,
messaging_enabled bool default false,
last_inbound_at timestamptz?,
last_outbound_at timestamptz?,
outbound_window_meta jsonb,    -- snapshot từ connector.outboundWindow()
ai_enabled bool default false,
settings jsonb default '{}'    -- per-shop overrides
```

### 5.2 `conversations` (partition RANGE `last_message_at` tháng)
```
id bigint PK,
tenant_id NOT NULL,
channel_account_id NOT NULL FK,
provider string,                  -- denormalized cho query nhanh
external_conversation_id string,  -- buyer-shop pair / page-user pair / lazada-thread-id
buyer_external_id string,
buyer_name string?,
buyer_avatar_url string?,
customer_id? FK customers,        -- Phase 2 SPEC-0002, set qua listener
order_id? FK orders,              -- best-guess link
status enum(open|snoozed|resolved|spam) default 'open' index,
snoozed_until timestamptz?,
unread_count int default 0,
message_count int default 0,
last_message_at timestamptz index,
last_message_preview string(200),
last_inbound_at timestamptz?,
last_outbound_at timestamptz?,
assigned_user_id? FK users,
tags jsonb default '[]',
meta jsonb default '{}',
created_at, updated_at,
UNIQUE (channel_account_id, external_conversation_id)
```
Index: `(tenant_id, status, last_message_at DESC)`, partial `(tenant_id) WHERE unread_count > 0`, GIN `tags`, `(tenant_id, customer_id, last_message_at DESC)`.

### 5.3 `messages` (partition RANGE `created_at` tháng — bảng lớn nhất, dự kiến 10× orders)
```
id bigint PK,
tenant_id NOT NULL,
conversation_id NOT NULL FK,
external_message_id string?,
direction enum(inbound|outbound) NOT NULL,
kind enum(text|image|video|file|template|system) NOT NULL,
body text,                         -- text content / template-resolved text
attachments_count int default 0,
sent_by_user_id? FK users,         -- NULL khi inbound hoặc auto
sent_by_ai bool default false,
delivery_status enum(pending|sent|delivered|read|failed) default 'pending',
failure_code string?,
reply_to_message_id? FK self,
sent_at timestamptz,
delivered_at timestamptz?,
read_at timestamptz?,
raw_payload jsonb,                 -- purge sau 30 ngày (job PrunePayloads)
meta jsonb default '{}',           -- {auto_rule_id?, template_id?, ai_run_id?}
created_at NOT NULL index,
UNIQUE (conversation_id, external_message_id) WHERE external_message_id IS NOT NULL
```
Index: `(conversation_id, created_at DESC)`, `(tenant_id, created_at DESC)`.

### 5.4 `message_attachments`
```
id bigint PK,
tenant_id NOT NULL,
message_id NOT NULL FK (cascade),
kind enum(image|video|file|audio),
mime string,
size_bytes bigint,
storage_path string,             -- MinIO: tenants/{id}/messaging/{yyyy/mm}/{uuid}.{ext}
external_url string?,            -- URL gốc từ sàn (cache để re-fetch)
checksum string?,                -- sha256 hex
width int?, height int?, duration_ms int?,
status enum(pending|downloaded|failed) default 'pending',
created_at
```

### 5.5 `message_templates`
```
id bigint PK,
tenant_id NOT NULL,
code string,                     -- slug; UNIQUE per tenant
name string,
body text,                       -- hỗ trợ variables: {{customer.name}}, {{order.code}}, …
vars jsonb default '[]',         -- declared variables list
attachments jsonb default '[]',  -- [{storage_path, kind}] đính kèm template
scope jsonb default '{}',        -- {providers: ['facebook_page'], ...}
shortcut_key string?,
enabled bool default true,
created_by? FK users,
created_at, updated_at,
UNIQUE (tenant_id, code)
```

### 5.6 `auto_reply_rules`
```
id bigint PK,
tenant_id NOT NULL,
name string,
trigger enum(schedule|order_status|away_no_response|first_message),
trigger_config jsonb,            -- xem 5.6.x bên dưới
filter jsonb default '{}',       -- {providers, customer_tags, keywords}
action jsonb,                    -- {kind:'template'|'raw'|'ai_reply', template_id?, raw_text?, ai_prompt_extra?}
cooldown_seconds int default 3600,
enabled bool default true,
priority int default 100,        -- thấp = chạy trước khi trùng trigger
created_by? FK users,
created_at, updated_at
```
`trigger_config` schema:
- `schedule`: `{window:'22:00-08:00', tz:'Asia/Ho_Chi_Minh', days:['mon'..'sun']}`
- `order_status`: `{order_status:'delivered', delay_minutes:30}`
- `away_no_response`: `{minutes:15, business_hours_only:true}`
- `first_message`: `{}`

### 5.7 `auto_reply_runs` (audit + cooldown lookup)
```
id bigint PK,
tenant_id NOT NULL,
rule_id FK (cascade),
conversation_id FK,
window_key string,               -- 'YYYY-MM-DD-HH' hoặc 'order:{id}:status:{s}' — idempotency key
fired_at timestamptz,
message_id? FK messages,
status enum(fired|skipped_cooldown|skipped_filter|failed),
error string?,
UNIQUE (rule_id, conversation_id, window_key)
```

### 5.8 `ai_knowledge_documents` (RAG)
```
id bigint PK,
tenant_id NOT NULL,
title string,
source enum(upload|url|inline),
storage_path string?,            -- file gốc trên MinIO
url string?,
inline_text text?,
chunk_count int default 0,
embedding_provider_code string,
embedding_model string,
embedding_version int,
indexed_at timestamptz?,
status enum(pending|ready|failed),
error string?,
created_by FK users,
created_at, updated_at
```

### 5.9 `ai_knowledge_chunks` (vector store — pgvector)
```
id bigint PK,
tenant_id NOT NULL,
document_id FK (cascade),
chunk_index int,
chunk_text text,
embedding vector(1536),          -- pgvector; dimension theo embedding model
token_count int,
created_at
```
Index HNSW trên `embedding` (filter by `tenant_id` trước).

### 5.10 `ai_assistant_runs` (cost audit)
```
id bigint PK,
tenant_id NOT NULL,
conversation_id? FK,
message_id? FK,
provider_code string,            -- 'claude' | 'openai' | ...
model string,
mode enum(suggest|auto|intent|rag),
prompt_tokens int,
completion_tokens int,
cost_micro_vnd bigint,           -- ước tính từ giá super-admin nhập
duration_ms int,
status enum(success|error|timeout|blocked_by_guardrail),
error string?,
created_by? FK users,            -- NULL nếu system (auto)
created_at index
```

### 5.11 `message_drafts` (AI suggestion chờ NV duyệt)
```
id bigint PK,
tenant_id NOT NULL,
conversation_id FK (cascade),
ai_run_id FK,
draft_text text,
suggested_attachments jsonb default '[]',
status enum(pending|accepted|rejected|expired),
created_at,
accepted_at?, accepted_by? FK users, accepted_message_id? FK messages,
expires_at                       -- tự xoá sau 1h
```

### 5.12 Mở rộng bảng đã có (không phá schema module khác)
- `channel_accounts`: thêm cột `messaging_enabled bool default false` (Channels migration; Messaging KHÔNG tự sửa bảng của Channels — phối hợp PR).
- `roles` permission map: thêm `staff_cs` role + `messaging.*` permissions (Tenancy).
- `plans.limits`: thêm key `messaging_ai_replies_monthly`. `plans.features`: thêm `messaging_inbox`, `messaging_ai` (Billing seeder).
- `system_settings`: thêm group `ai_providers` (Admin).
- `audit_logs`: thêm action prefix `messaging.*` (Tenancy).
- `automation_rules` (Phase 6.5): không đổi schema — `trigger.type='messaging.*'` chỉ là string mới.

### 5.13 Migration & partition
- Reversible — mọi migration có `down()`.
- `messages` + `conversations` partition theo tháng — `MonthlyPartition` registry add 2 bảng này.
- `auto_reply_runs` partition theo `fired_at` tháng (giữ 90 ngày rồi prune).
- `ai_assistant_runs` partition theo `created_at` tháng (giữ 365 ngày).

### 5.14 Domain events
**Phát:**
- `Messaging\Events\ConversationCreated` `{conversation_id}`
- `Messaging\Events\MessageReceived` `{message_id, conversation_id, requires_human:bool}`
- `Messaging\Events\MessageSent` `{message_id, conversation_id}`
- `Messaging\Events\MessageFailed` `{message_id, failure_code}`
- `Messaging\Events\MessagingTriggerFired` `{trigger_type, conversation_id, context}` — cho Settings/AutomationRule engine

**Lắng nghe:**
- `Orders\Events\OrderStatusChanged` → `AutoReplyOnOrderStatus` (matches rule `order_status`).
- `Orders\Events\OrderUpserted` → `LinkConversationToOrder` (best-guess link).
- `Channels\Events\DataDeletionRequested` → `PurgeMessagingDataForBuyer`.
- `Channels\Events\ChannelAccountRevoked` → `AnonymizeMessagingDataForShop` (delay 90 ngày).
- `Customers\Events\CustomerLinked` → cập nhật `conversations.customer_id` ngược.

---

## 6. API & UI

### 6.1 Endpoints (cập nhật `docs/05-api/endpoints.md`)

**Webhook (public, verify chữ ký):**
| Method | Path | Note |
|---|---|---|
| POST | `/webhook/messaging/shopee` | dùng `ShopeeMessagingWebhookVerifier` |
| POST | `/webhook/messaging/tiktok` | dùng `TikTokMessagingWebhookVerifier` |
| POST | `/webhook/messaging/lazada` | dùng `LazadaMessagingWebhookVerifier` |
| POST | `/webhook/messaging/facebook` | HMAC SHA256 `X-Hub-Signature-256` |
| GET | `/webhook/messaging/facebook` | Verify hub.challenge (Meta setup) |

Tất cả qua **một** `MessagingWebhookController@handle($provider)`, khác biệt nằm trong connector. Trả `200 {ok:true}` nhanh; dispatch `ProcessMessagingWebhook` lên queue `messaging-webhooks`.

**OAuth callback (Facebook chỉ; 3 sàn còn lại reuse `/oauth/{provider}/callback` hiện có):**
| Method | Path |
|---|---|
| GET | `/oauth/facebook_page/callback` |

**REST `/api/v1/messaging/*`** (auth Sanctum + tenant + permission gate):
| Method | Path | Permission | Mô tả |
|---|---|---|---|
| GET | `/messaging/conversations` | `messaging.view` | List + filter `provider`, `status`, `unread`, `assigned`, `customer_id`, `q`. Page-based 20/page. |
| GET | `/messaging/conversations/{id}` | `messaging.view` | Detail + last 50 messages (cursor `before_message_id` cho lazy load). |
| POST | `/messaging/conversations/{id}/messages` | `messaging.reply` | `{ kind:'text', body }` — text. |
| POST | `/messaging/conversations/{id}/messages/media` | `messaging.reply` | multipart `{ kind:'image|video|file', file }` — upload → MinIO → dispatch SendMessage. |
| POST | `/messaging/conversations/{id}/messages/template` | `messaging.reply` | `{ template_id, vars }` — resolve + send. |
| POST | `/messaging/conversations/{id}/read` | `messaging.view` | mark all read (reset unread_count). |
| PATCH | `/messaging/conversations/{id}` | `messaging.view` (status) / `messaging.assign` (assign) | `{ status?, assigned_user_id?, tags?, snoozed_until? }`. |
| POST | `/messaging/conversations/{id}/ai-suggestion` | `messaging.reply` + tenant.feature `messaging_ai` | Dispatch `GenerateAiSuggestion` (sync wait ≤30s). Trả `{ draft_id, draft_text, suggested_attachments }`. |
| POST | `/messaging/conversations/{id}/ai-suggestion/{draftId}/accept` | `messaging.reply` | Send draft như message thường, mark draft `accepted`. |
| DELETE | `/messaging/conversations/{id}/ai-suggestion/{draftId}` | `messaging.reply` | Reject draft (mark `rejected` — cho audit). |
| GET | `/messaging/templates` | `messaging.view` | List. |
| POST/PATCH/DELETE | `/messaging/templates[/{id}]` | `messaging.template.manage` | CRUD. |
| GET | `/messaging/auto-reply-rules` | `messaging.view` | List. |
| POST/PATCH/DELETE | `/messaging/auto-reply-rules[/{id}]` | `messaging.rule.manage` | CRUD. |
| GET | `/messaging/knowledge-docs` | `messaging.view` | List. |
| POST | `/messaging/knowledge-docs` | `messaging.ai.train` | Upload (multipart) / URL / inline. Dispatch `IndexKnowledgeDoc`. |
| DELETE | `/messaging/knowledge-docs/{id}` | `messaging.ai.train` | Xoá + chunks. |
| GET | `/messaging/stats` | `messaging.view` | `{ open, unread, snoozed, by_provider, avg_first_response_minutes_7d }`. |
| GET | `/tenant/settings/messaging` | `messaging.ai.config` | `{ ai_provider_code?, available_providers:[{code,name}], away_hours, fallback_template_id? }`. |
| PATCH | `/tenant/settings/messaging` | `messaging.ai.config` | `{ ai_provider_code?, away_hours?, fallback_template_id? }`. |

**Admin SPA `/api/v1/admin/*`** (admin guard, không cần tenant):
| Method | Path | Mô tả |
|---|---|---|
| GET | `/admin/ai-providers` | List provider trong `system_settings` group `ai_providers`. |
| POST | `/admin/ai-providers` | `{ code, display_name, api_key, base_url?, default_model, capabilities, pricing }` — encrypted. |
| PATCH | `/admin/ai-providers/{code}` | Update fields. |
| DELETE | `/admin/ai-providers/{code}` | Soft (`is_active=false`); tenant đang dùng ⇒ fallback null + UI cảnh báo. |
| POST | `/admin/ai-providers/{code}/test` | Test connection (sinh 1 reply "hello"). |
| GET | `/admin/messaging/ai-usage` | Per-tenant per-month cost report. |

### 6.2 UI sitemap (cập nhật `docs/06-frontend/overview.md` §5)

Thêm vào `resources/js/features/messaging/`:
- `/messaging` — Inbox 3 cột (ConversationList | MessageThread | SidePanel).
- `/messaging/templates` — quản lý mẫu tin (AntD Table + drawer).
- `/messaging/auto-rules` — quản lý quy tắc tự động.
- `/messaging/knowledge` — tài liệu AI (upload + index status).
- `/messaging/stats` — số liệu chăm sóc cơ bản.
- `/settings/messaging` — chọn AI provider + giờ vắng mặt + fallback template.

Admin SPA (`resources/js/admin/`):
- `/admin/ai-providers` — CRUD provider (chỉ super-admin).
- `/admin/messaging/ai-usage` — cost report.

### 6.3 FE rules tuân `06-frontend/overview.md`
- `useInbox` hook subscribe Reverb channel `private-tenant.{id}.messaging`.
- `@ant-design/icons` cho mọi icon (§4.12). Không emoji.
- `<Radio.Group>` / `<Segmented>` cho chọn status / provider / trigger type (§4.13).
- `<MessageBubble>`, `<AttachmentPreview>`, `<ProviderBadge>` component dùng chung.
- React Query keys: `['messaging', 'conversations', filters]`, `['messaging', 'conversation', id]`, `['messaging', 'messages', conversationId, cursor]`.
- Optimistic update khi gửi tin: tạo placeholder `id:'tmp-…'`, status `pending`; server confirm → swap.

### 6.4 Job mới (cập nhật `docs/07-infra/queues-and-scheduler.md`)

| Queue | Job | Tần suất / Trigger | Note |
|---|---|---|---|
| `messaging-webhooks` (supervisor `critical`) | `ProcessMessagingWebhook` | webhook | tries 5, backoff 10/30/60/300/900s. Resolve channel_account → upsert message idempotent. |
| `messaging-inbound` | `PollConversations` | scheduler mỗi 5' per shop | `ShouldBeUnique` per (tenant, channel_account_id), throttle Redis per (provider, shop). |
| `messaging-media` | `DownloadInboundMedia` | event `MessageReceived` có attachment | tries 3, backoff 30/120/600s. Lưu MinIO. |
| `messaging-outbound` | `SendMessage` | mọi outbound | tries 4, backoff 5/30/120/600s. Tôn trọng 429/Retry-After. |
| `messaging-ai` | `GenerateAiSuggestion`, `ClassifyIntent`, `IndexKnowledgeDoc` | inline + queue | timeout cứng 30s, retry 1 lần. |

**Scheduler:**
- mỗi 5': `PollConversations` cho mỗi `messaging_enabled` shop active (rải đều).
- mỗi 1': `AutoReplyScheduler::tick` — quét rule `schedule` & `away_no_response`.
- mỗi 30': `CheckMessagingTokenHealth` (token Facebook Page hết hạn → reconnect notice).
- hằng ngày 03:00: `PruneMessagingPayloads` (raw_payload > 30 ngày).
- hằng ngày: `EnsureMessagingPartitions` (extend `db:partitions:ensure`).
- hằng ngày: `PruneAiSuggestionDrafts` (drafts expired > 24h).
- hằng tuần: `CheckMessagingWebhookHeartbeat` (cảnh báo sàn ngừng gửi event).

### 6.5 Connector method dùng (KHÔNG `if ($provider===…)` ở core)
Mọi method gọi qua `MessagingRegistry::for($provider)`. Connector thiếu method ⇒ `UnsupportedOperation`. Core kiểm `supports()` trước.

---

## 7. Edge case & lỗi

| Tình huống | Xử lý |
|---|---|
| Webhook trùng (retry) | Dedupe `messages (conversation_id, external_message_id)` UNIQUE. |
| Webhook trễ / out-of-order | Cập nhật `messages.created_at` từ sàn (`external_sent_at`); UI sort theo `external_sent_at` nếu có, fallback `created_at`. |
| Token hết hạn | Connector throws `TokenExpired` → `MessageSendService` dispatch refresh + retry. Hết refresh ⇒ `channel_account.status=expired` + notification. |
| 429 / rate limit | Retry với backoff respect `Retry-After`. |
| Buyer block / conversation đã đóng (Shopee/TikTok) | Connector throws `ConversationClosed` → `delivery_status=failed` `failure_code='conversation_closed'`. NV nhận noti. |
| Window 24h (Facebook) | `OutboundWindowGuard` chặn ⇒ `422 OUTBOUND_WINDOW_CLOSED` + gợi ý dùng template tag. |
| Media quá lớn / sai MIME | Validate FE + BE: max 25MB image, 100MB video, 25MB file; MIME whitelist. Sai ⇒ `422 ATTACHMENT_INVALID`. |
| Media link sàn hết hạn trước khi `DownloadInboundMedia` chạy | Job throws → `message_attachments.status=failed`; UI hiện "Không tải được media"; nếu sàn cho re-fetch qua API ⇒ thử lại. |
| SKU chưa ghép / KH chưa link | Side panel hiển thị "chưa khớp khách / chưa khớp đơn" — không chặn gửi tin. |
| AI timeout / provider down | `GenerateAiSuggestion` fail ⇒ UI hiện "AI không phản hồi, vui lòng thử lại"; auto-mode rule fallback: gửi `fallback_template_id` nếu có, nếu không ⇒ chỉ noti NV. |
| AI vượt monthly limit | `402 PLAN_LIMIT_REACHED { details: { used, limit, period } }`. UI banner "Đã dùng hết AI replies tháng này — nâng cấp". |
| Auto-reply trùng window | `auto_reply_runs (rule_id, conversation_id, window_key)` UNIQUE ⇒ insert fail = skip silently. |
| Conversation thuộc shop bị deauthorized | `messaging.send` ⇒ `409 CHANNEL_ACCOUNT_INACTIVE`. |
| Reverb chưa bật | FE detect `meta.realtime_enabled=false` → fallback polling 10s. Document trong UI. |
| Tenant đổi AI provider giữa chừng | Pending suggestions / runs với provider cũ vẫn hoàn tất; mới dùng provider mới. |
| Super-admin xoá AI provider đang được tenant dùng | Soft (`is_active=false`); tenant settings hiển thị "Provider đã ngừng — chọn lại"; AI mode disable tạm. |

---

## 8. Bảo mật & dữ liệu cá nhân (đối chiếu `08-security-and-privacy.md`)

### 8.1 PII trong nội dung chat
- Buyer thường gõ SĐT / địa chỉ / STK / ảnh CMND. **Mọi cột chứa nội dung tin nhắn = PII cấp cao.**
- `message_attachments` cũng có thể chứa ảnh PII (chứng minh nhân dân, hoá đơn).
- Mask khi hiển thị cho role thấp (regex SĐT 10 số → `0xx xxx 123`). Permission `customers.view_phone` mở rộng cho messaging.

### 8.2 Encrypt at rest
- `messaging_account_meta.settings`, mọi token / API key trong `system_settings` ai_providers — encrypted cast.
- AI provider API key: chỉ ở `system_settings` (Admin scope), KHÔNG lộ ra tenant.

### 8.3 Data deletion
- `data_deletion` webhook (Shopee/TikTok/Lazada) → `PurgeMessagingDataForBuyer` job:
  - Tìm conversations match buyer external_id → delete attachments file MinIO → delete messages → soft-delete conversation.
  - Audit log.
- Disconnect shop → `AnonymizeMessagingDataForShop` delay 90 ngày (đồng bộ với `customers.anonymize_after_days`):
  - Clear `body` text → `'[anonymized]'`, drop attachments, keep metadata (counts).

### 8.4 PII redaction trước khi gọi LLM ngoài
- `PiiRedactor` helper (regex SĐT 10 số, email, STK 9-14 số sau từ khoá "stk/tk/account") → thay placeholder `[PHONE_1]`, `[EMAIL_1]`. Mapping giữ server.
- AI provider nội bộ (`local_llm`) có thể bypass redact (config-able per provider).
- Ghi nhật ký: số lượng PII redacted per run trong `ai_assistant_runs.meta.redacted_count`.

### 8.5 Signed URL cho media
- MinIO signed URL TTL 5 phút. FE re-fetch khi expired.
- Cấm hot-link từ ngoài tenant.

### 8.6 Webhook verify
- Mỗi connector implement `verifyWebhookSignature(Request)`. Sai ⇒ `401`, không lưu, không log payload.

### 8.7 Audit log
- Action mutating: `messaging.message.send`, `messaging.conversation.assign`, `messaging.conversation.status_change`, `messaging.rule.create/update/delete`, `messaging.template.*`, `messaging.ai.config_change`, `messaging.knowledge.upload`.

### 8.8 Rate-limit gửi tin
- Per tenant per shop: 60 msg/phút mặc định (config), trả `429`. Chống lock account sàn.
- Per user per phút: 30 msg (chống NV gửi nhầm).

---

## 9. Kiểm thử

### 9.1 Unit
- `OutboundWindowGuard`: 24h Facebook rule, allow with tag, block without.
- `PiiRedactor`: regex SĐT, email, STK.
- `AutoReplyMatcher`: schedule trong / ngoài window, day-of-week, tz conversion.
- `MessageIngestionService`: idempotency dedupe.
- `TemplateResolver`: variable interpolation `{{customer.name}}`.

### 9.2 Feature
- Webhook → MessageIngestion (idempotent: chạy 2 lần = 1 message).
- Polling → IngestionService (same dedup).
- Send text → MinIO → SendMessage job → connector mocked → `messages.status=sent`.
- Auto-reply order_status: order `delivered` → rule fires → message gửi → cooldown 1h.
- Auto-reply away_no_response: timer fires đúng ngưỡng → message gửi → NV trả lời sau ⇒ next time không fire.
- AI suggestion → draft tạo → accept → message sent.
- Billing: `messaging_inbox=false` tenant ⇒ 402.
- Billing: vượt `messaging_ai_replies_monthly` ⇒ 402.
- RBAC: `staff_warehouse` không có `messaging.view` ⇒ 403.
- Data deletion: webhook → messages purged, attachments deleted.
- Disconnect shop → after 90 ngày → anonymize job → body cleared.

### 9.3 Contract test (mỗi connector)
- Fixtures payload thật (sandbox) cho mỗi message kind (text/image/video/file/sticker).
- `Http::fake()` cho outbound API.
- Assert DTO chuẩn output.
- Verify signature happy/unhappy path.

### 9.4 FE (Vitest + Playwright cho inbox)
- Hook `useInbox` Reverb event → cache update.
- Send optimistic flow.
- AI suggestion accept flow.
- Polling fallback khi không có Reverb.

---

## 10. Tiêu chí hoàn thành (Acceptance criteria)

- [ ] 5 ADR (0017–0021) merge trước hoặc cùng spec.
- [ ] Roadmap update: feature di chuyển vào phase chính thức (7.x hoặc 8.x) với rationale.
- [ ] `extensibility-rules.md` thêm 2 trục mở rộng (Messaging + AI).
- [ ] `modules.md` thêm row `Messaging` + dep graph.
- [ ] `02-data-model/overview.md` thêm Messaging tables.
- [ ] `05-api/endpoints.md` thêm section Messaging.
- [ ] `06-frontend/overview.md` thêm `/messaging/*` sitemap.
- [ ] `07-infra/queues-and-scheduler.md` thêm 5 queue mới + scheduler.
- [ ] `08-security-and-privacy.md` thêm section "Messaging PII".
- [ ] `04-channels/README.md` link sang `04-messaging/README.md` mới.
- [ ] 4 connector cài đủ: Facebook (đầu tiên — API ổn), TikTok, Shopee, Lazada (rủi ro cao — có thể đẩy backlog).
- [ ] AI: Claude + OpenAI provider seed (super-admin tự cấu hình API key).
- [ ] Test ≥ ngưỡng coverage chung; contract test xanh cho mỗi connector.
- [ ] FE: inbox functional + Reverb realtime (hoặc polling fallback có UI badge "degraded").
- [ ] Billing: 2 feature flag + 1 limit hoạt động.
- [ ] Khả năng demo: kết nối 1 Facebook Page → buyer test gửi tin → app nhận trong 10s → NV gửi reply → buyer thấy.

---

## 11. Câu hỏi mở (chốt trước khi → Reviewed)

1. **Cost model AI**: super-admin trả LLM cost rồi charge tenant qua `plan.limit:messaging_ai_replies_monthly`? Hay cho phép tenant tự nhập API key của họ? **Mặc định spec này: super-admin chịu cost, gate bằng limit gói** — confirm?
2. **Auto-mode AI v1**: bật ngay hay chỉ suggest? **Đề xuất: chỉ suggest cho 6 tháng đầu**, theo dõi rồi mở auto-mode dưới gate `plan.feature:messaging_ai_auto` mới.
3. **Lazada IM API**: cần xác nhận sàn còn hỗ trợ qua Open Platform không. Nếu không ⇒ đẩy Lazada xuống "best-effort/backlog", v1 chỉ cam kết 3 nền tảng (Facebook + TikTok + Shopee).
4. **Reverb**: kéo Phase 8 vào dependency bắt buộc trước feature này, hay chấp nhận polling-only v1?
5. **DPA / zero-retention với LLM provider** (Claude/OpenAI/Gemini): có hợp đồng dữ liệu chính thức không? Nếu không ⇒ chỉ enable cho `local_llm` (self-host) ở thị trường nhạy cảm.
6. **Phase number**: 7.x (cùng Accounting) hay tách 7.6 / 8.0 riêng? Cần chủ dự án quyết để update roadmap.
7. **Role `staff_cs`** vs gộp permission vào `staff_order`: tách role riêng (clear permission boundary) hay chỉ thêm permission cho role hiện có? **Đề xuất: tách `staff_cs` riêng** vì BigSeller / Haravan / Sapo cũng có role CSKH riêng.

---

## 12. Phụ lục — Lộ trình triển khai (slice)

Sau khi spec → `Reviewed`, đề xuất chia thành 7 SPEC con (mỗi cái 1–6 tuần) để PR nhỏ, không nhánh sống lâu:

| Slice | SPEC con (đề xuất) | Scope | Phụ thuộc | Ước lượng |
|---|---|---|---|---|
| S1 | 0025-messaging-foundation | Module skeleton, MessagingConnector interface, MessagingRegistry, MessagingWebhookController, MessageIngestionService + tables + partition + RBAC + Billing flag + inbox UI 3 cột | Reverb hoặc polling fallback | 4–6 tuần |
| S2 | 0026-messaging-facebook | FacebookConnector (OAuth Page + Send API + webhook + window guard), contract test | S1 | 3 tuần |
| S3 | 0027-messaging-templates | Template CRUD + variable resolver + attachment kèm template + shortcut key | S1 | 2 tuần |
| S4 | 0028-messaging-tiktok-shopee | TikTokConnector + ShopeeConnector messaging, contract tests | API approvals, S2 | 4 + 4 tuần (parallel) |
| S5 | 0029-messaging-auto-reply | 4 trigger + auto_reply_rules + cooldown + wire vào AutomationRule (Phase 6.5) | Phase 6.5 done, S2 | 3 tuần |
| S6 | 0030-messaging-ai-suggest | AiAssistantConnector + ClaudeConnector + OpenAiConnector + admin providers UI + tenant chọn + suggestion drafts + RAG basic | S1 + system_settings | 4 tuần |
| S7 | 0031-messaging-ai-auto-guardrail | Auto-mode + IntentClassifier + escalate rules + dashboard | S6 + 3 tháng vận hành suggest | 3 tuần |
| S8 | 0032-messaging-lazada (best-effort) | LazadaConnector messaging hoặc đẩy backlog | Lazada partnership | TBD |

**Tổng tối thiểu (S1–S6)** ≈ 22–26 tuần cho team 2–4 dev = ~5–6 tháng.

---

> **Reviewers cần kiểm:** (1) không vi phạm `extensibility-rules.md`, (2) không phụ thuộc vòng module, (3) không gắn tên nền tảng vào core, (4) idempotency mọi pipeline, (5) RBAC + Billing gating đầy đủ, (6) PII xử lý đúng `08-security-and-privacy.md`, (7) partition + queue + scheduler đã document.
