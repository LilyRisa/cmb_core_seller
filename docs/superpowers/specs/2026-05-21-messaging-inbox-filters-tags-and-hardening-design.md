# Messaging inbox: filter redesign + tags + phone detection + UX cleanup + logic hardening

- **Status:** Design (2026-05-21)
- **Branch:** `feature/messaging-inbox-filters-tags` (off `main` @ c1e611f)
- **Guiding constraint:** **Do NOT change core logic** — ingest/dedupe, webhook parse, send pipeline, sync paging algorithms stay intact. All changes are additive columns, derived flags, new endpoints/components, guards, and UI. Where an existing file is touched, only additive lines / guards are added.
- **Decisions (chosen as "optimal"):** SĐT chip shows the actual detected number; tag management is a modal (AntD `ColorPicker` + presets); full bundle A–D delivered in phases.

## Goals (from user)

1. **Professional inbox filter** with only **2 main tabs: `Sàn` and `Facebook`** (replaces current 3-way). Filter by **đã xem / chưa đọc / có chứa SĐT**, and **by tag**.
2. **Phone detection:** any conversation whose messages contain a VN phone number shows a **"SĐT" chip** (with the number) in the list; filterable.
3. **Tags:** create tags with a **color**, attach to conversations, filter by tag.
4. **Remove Facebook from the "Gian hàng" page** (managed only in Kết nối kênh) — it's redundant there.
5. **Logos on white background consistently** (TikTok logo is dark → needs white bg), unified across modules.
6. **Optimize UX/UI for smaller desktop screens** + remove duplication/redundancy.
7. **Fix latent logic bugs** found in audit (guards only).

## Non-goals / invariants

- No change to `MessageIngestionService::ingest()` dedupe, `Message` creation, webhook signature/parse, `SendMessage` send algorithm, or sync paging math.
- No change to the marketplace-chat Phase A–D behavior except additive guards (echo filter, event-suppression on first sync) that are themselves bug fixes.

---

## Phase 1 — Logic hardening (backend, guards only)

Each item is a guard / terminal-state setter / alignment — not a core-logic change. Source: audit 2026-05-21.

1. **Backfill never strands `sync_status='running'` on rate-limit.** In `BackfillMessagingChannel::handle` rate-limit branch, set `sync_status = SYNC_QUEUED` before `release(120)`; add a `failed(\Throwable $e)` job hook that sets `SYNC_FAILED` + `sync_error`. (`BackfillMessagingChannel.php`)
2. **Full backfill restarts from the top.** When `$sinceIso === null` (manual `/sync`, OAuth connect), reset `sync_cursor = null` at the start of `handle()`. Incremental reconcile (`$sinceIso !== null`) still resumes. (`BackfillMessagingChannel.php`)
3. **Backfill concurrency + loop safety.** Make `BackfillMessagingChannel implements ShouldBeUnique` (`uniqueId = "backfill:{id}"`, `uniqueFor = 900`); add the "cursor didn't advance → break" guard mirroring `SyncConversationsForShop`. Resolve the connector via `$account->messagingConnectorCode()` (align with `ReconcileMessagingSync`). (`BackfillMessagingChannel.php`)
4. **No auto-reply/AI on historical backlog (first poll).** In `SyncConversationsForShop`, suppress `fireEventsForNewMessage` on the **first** sync (`$meta->last_synced_at === null`) — ingest still stores messages; only event emission is gated. (`SyncConversationsForShop.php`)
5. **No auto-reply/AI to blocked conversations.** In `RunAutoReplyOnInbound` and `AiAutoModeOnInbound`, bail when `conversation.blocked_at !== null` (mirror the existing `STATUS_SPAM` bail). Add a `blocked_at` guard in `SendMessage::handle` (central choke point) → mark failed `conversation_blocked`. (`RunAutoReplyOnInbound.php`, `AiAutoModeOnInbound.php`, `SendMessage.php`)
6. **Echo filter for Shopee/TikTok.** When the inbound sender id equals the shop's own id, emit `TYPE_UNKNOWN` (mirror Facebook's `is_echo` drop) so the seller's own messages aren't ingested as inbound. Verify against existing connector tests before changing. (`ShopeeChatConnector.php`, `TikTokChatConnector.php`)
7. **Avatar relay efficiency.** In `BackfillMessagingChannel`, dispatch page-avatar relay only when `meta.page_avatar_synced_at === null`, and per-conversation avatar relay only when `conversation.buyer_avatar_path === null`. Resolve `MediaStorage` once in `ConversationResource` (skip null paths). (`BackfillMessagingChannel.php`, `ConversationResource.php`)

> Each Phase-1 change ships with a focused test (or extends an existing one) and must keep the full messaging suite green.

---

## Phase 2 — Phone detection + tags (backend, additive)

### 2.1 Data model (migrations)
- `conversations`: add `has_phone` (bool, default false, indexed with tenant_id), `detected_phone` (varchar(32) nullable).
- New table `messaging_tags`: `id`, `tenant_id` (indexed), `name` (string), `color` (string, hex like `#2563EB`), `timestamps`; unique `(tenant_id, name)`.
- (Conversation↔tag assignment reuses the **existing** `conversations.tags` JSON column, storing tag **IDs**.)

### 2.2 Phone detection
- New `PhoneDetector` helper exposing the VN-phone regex already proven in `PiiRedactor` (`(?<!\d)(?:\+84|84|0)(?:3|5|7|8|9)\d{8}(?!\d)`): `detect(string $text): ?string` (first match, normalized). PiiRedactor is refactored to reuse the same constant/regex (no behavior change).
- In `MessageIngestionService::updateConversationOnNewMessage` (additive lines only): if `!$conversation->has_phone` and the inbound message body matches, set `has_phone = true`, `detected_phone = <first>`. Covers webhook + poll + backfill (all route through ingest). No change to dedupe/creation.
- One-time backfill command `messaging:detect-phones` — scans messages of conversations where `has_phone = false`, sets the flags. Idempotent.

### 2.3 Tag CRUD + filters
- `MessagingTag` model (tenant-scoped via BelongsToTenant).
- `TagController`: `GET /messaging/tags`, `POST /messaging/tags`, `PATCH /messaging/tags/{id}`, `DELETE /messaging/tags/{id}`. Gate: `messaging.view` (list) / `messaging.reply` (mutate). Validate `name` (1..40), `color` (hex). On delete, also strip the id from any `conversations.tags`.
- Attach/detach: **reuse** existing `PATCH /messaging/conversations/{id}` with `tags` = array of tag IDs (validated against tenant's tags). No new endpoint.
- `ConversationController@index` filters (additive): `read` (`unread_count = 0`), `unread` (already exists, `unread_count > 0`), `has_phone` (`= true`), `tags` (CSV of ids → `whereJsonContains('tags', id)` OR-combined). 2-tab provider filter reuses the existing `provider` CSV param.
- `ConversationResource`: add `has_phone`, `detected_phone`, and keep `tags` (ids). (Tag name/color resolved on the FE from the tag list to avoid N+1.)

---

## Phase 3 — Frontend (UI)

### 3.1 Inbox filter redesign (`MessagingPage.tsx`)
- Replace the 4 stacked Segmented controls with: **2 main tabs** `Sàn | Facebook` (Segmented) + a **"Bộ lọc" `Popover`/`Dropdown`** containing:
  - Trạng thái đọc: `Tất cả / Đã xem / Chưa đọc` (Radio).
  - `☑ Có số điện thoại`.
  - **Thẻ:** multi-select of tags (colored chips).
  - Trạng thái xử lý: `Đang mở / Đã xong / Đã chặn` (Radio) — moved into the popover.
  - A "Quản lý thẻ" link → opens the tag modal.
- Active-filter count badge on the "Bộ lọc" button. Keep the search input (the `q` param already exists) — adds value over the removed bars.

### 3.2 Conversation list chips
- **SĐT chip:** when `c.has_phone`, render `<Tag icon={<PhoneOutlined/>}>{c.detected_phone}</Tag>`.
- **Tag chips:** render `c.tags` mapped to tag defs `{name,color}` (from `useMessagingTags`), each a colored `<Tag color={tag.color}>`.
- Provider **logo** (not bare blue Tag) via the unified `ChannelLogo`.

### 3.3 Tag management modal
- Opened from the filter. Lists tags (name + color swatch), create/edit/delete with AntD `ColorPicker` + a preset palette. Hooks: `useMessagingTags` (list), `useSaveTag`, `useDeleteTag`. Attach/detach a tag to the active conversation via the existing conversation-update hook (sets `tags` ids).

### 3.4 Remove Facebook from "Gian hàng"
- BE: `ChannelAccountController::index()` excludes `facebook_page` (prefer the existing `supports('orders.fetch')` predicate). FE: `ChannelsPage` defensively filters `provider !== 'facebook_page'`.

### 3.5 Logo consistency (white bg) across modules
- `ChannelLogo.tsx`: always **white** background (remove the TikTok→black special case); add `facebook_page`/`facebook` to `CHANNEL_META`/`CHANNEL_ICON` so FB flows through `ChannelLogo`. `ChannelBadge` + the messaging avatar reuse `ChannelLogo` so there's a single contrast rule. Inbox uses `ChannelLogo` instead of a blue text Tag.

### 3.6 Small-screen responsiveness (`MessagingPage.tsx`)
- Replace fixed `width:320`/`width:280` with flex + `minWidth`/`maxWidth`; make the right info panel collapsible (hide below ~1200px via `Grid useBreakpoint`, or a toggle). Replace `height: calc(100vh - 150px)` with a flex full-height container. Fix the `width:240` sync row in `MessagingChannelsPage`.

### 3.7 Nav dedup + polish
- Reconcile `MessagingNav` vs the `AppLayout` sidebar submenu (single source; align the "Cài đặt AI" entry). Localize raw delivery-status labels; switch `<a href>` order/customer links to `<Link>`.

---

## Test strategy
- **Backend:** TDD per task with `php artisan test`; keep the whole `Messaging` suite green after each task. New tests: phone detection, tag CRUD + filters, has_phone/read/unread/tags index filters, the Phase-1 guards (rate-limit terminal state, full-sync cursor reset, blocked auto-reply bail, echo filter).
- **Frontend:** no FE test runner → verify each task with `npm run typecheck` + `npm run lint` (no new errors in touched files) + `npm run build`; manual smoke for filter/tag/chip behavior.

## Rollout order
Phase 1 (stabilize) → Phase 2 (phone+tags backend) → Phase 3 (UI). Each phase is independently shippable and green.
