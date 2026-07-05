# AI Messaging Enhancements — Design

Date: 2026-07-05
Status: Approved (pending implementation plan)

Four independent-but-cohesive features in the AI/messaging cluster, shipped as one spec:

1. Per-user AI usage counter in admin
2. AI sends product images to customers on request
3. Per-page shop/owner business info fed into AI replies
4. Worker verification for vector re-rank + transcription (+ one queue bug fix)

Golden rules honored: core never learns a provider name; modules talk only through `Contracts/`; every business table carries `tenant_id` + `BelongsToTenant`; sync/AI work is idempotent. User-facing strings Vietnamese; code/identifiers English.

---

## Feature 1 — Per-user AI usage counter (admin)

### Problem
`AiCreditService::record()` (module Billing, `app/app/Modules/Billing/Services/AiCreditService.php`) is the single choke point every AI feature passes through after a successful provider call, but it only receives `tenantId`. There is no per-user attribution and no per-feature breakdown. `ai_credit_wallets` is per-tenant only. `ai_assistant_runs.created_by` exists but is populated only on the manual messaging `suggest()` path (auto-reply and non-messaging features never write it), so it cannot answer "AI calls per user".

### Design

**New table `ai_usage_counters`** (owned by Billing, migration in `app/app/Modules/Billing/Database/Migrations/`):

| column      | type                | notes                                                        |
|-------------|---------------------|--------------------------------------------------------------|
| id          | bigint pk           |                                                              |
| tenant_id   | fk index            | `BelongsToTenant`                                            |
| user_id     | fk nullable         | NULL = system/auto (no auth user, e.g. queued auto-reply)    |
| period_ym   | unsigned int        | `YYYYMM` (e.g. 202607) — cheap month bucketing               |
| feature     | string(24)          | `messaging` / `marketing` / `products` / `visual` / `transcription` / `intent` |
| count       | unsigned int        | incremented per billable AI unit                             |

- Unique `(tenant_id, user_id, period_ym, feature)`; index `(tenant_id, period_ym)`.
- Upsert-increment (`updateOrCreate` + `increment`, or atomic `INSERT ... ON CONFLICT`) so it is idempotent-safe under concurrency.

**Metering integration:**
- Extend the `AiCreditMeter` contract + `AiCreditService::record()` signature to `record(int $tenantId, int $n = 1, ?string $feature = null, ?int $userId = null)`. Keeping the two new params optional preserves existing behavior for any caller not yet updated.
- `record()` writes the wallet debit as today, then best-effort increments `ai_usage_counters`. `userId` defaults to a resolved current user (`Auth::id()`), which is present in web requests (suggest, product description, visual lookup) and NULL in queued jobs (auto-reply, transcription) — exactly the desired "system" attribution.
- `period_ym` derived from the current time inside the service (request-time; no `Date::now()` concerns in normal runtime — only workflow scripts forbid it).
- Update the ~7 `record()` call sites to pass `feature`:
  - `Messaging\Services\AiSuggestionService` (auto + suggest) → `messaging`
  - `Messaging\Services\IntentClassifier` → `intent`
  - `Marketing\Services\LlmMarketingAnalysisClient` → `marketing`
  - `Products\Services\ProductDescriptionService` → `products`
  - `VisualSearch\Services\VisionReRanker` → `visual`
  - `Messaging\Jobs\TranscribeInboundAudio` → `transcription`
  - `Channels\Services\ShopHealthAnalysisService` (uses `consume()`) → tag via the same mechanism where a successful unit is recorded.

**Reporting contract (module boundary):**
- New contract `AiUsageReporter` in Billing exposing e.g. `perUserSummary(int $tenantId, ?int $periodYm): array` and `userBreakdown(int $tenantId, int $userId): array`. Admin depends on this contract, never on the Billing table directly.

**Admin surface:**
- Backend: `AdminUserController` — enrich the tenant-user list (`present()`) with `ai_usage: { this_month, all_time }`; add `GET api/v1/admin/users/{id}/ai-usage` returning a per-month + per-feature breakdown via `AiUsageReporter`.
- Frontend: `app/resources/js/admin/pages/users/AdminUsersPage.tsx` — add a "Lượt AI (tháng này / tổng)" column to the tenant-user tab (`tenantCols`). A detail drawer/expand shows the per-month + per-feature breakdown. System/NULL usage surfaces as a "Hệ thống" row so per-tenant totals reconcile.

### Decisions locked
- Scope = **all AI features**, attributed to user where a request user exists, else system/NULL.
- Admin column shows **this-month + all-time**; breakdown available on drill-in (no chart in v1).

---

## Feature 2 — AI sends product images on request

### Problem
The outbound media transport is already complete end-to-end: `OutboundMessageService::queueMedia` → `SendMessage::sendMediaForMessage` → `MediaStorage::temporaryUrl` (signed URL) → `connector->sendMedia` (Facebook implemented, capability-gated for others). Product images live in module VisualSearch (`visual_training_items` / `visual_training_images`, disk `config('visual_search.media_disk')`). The gap: the AI reply path only returns text (`AiReplyDTO` has no attachment field, auto path only calls `queueText`), and there is no text→product lookup — only image→product (`VisualMatcher::lookup`).

### Design

**Text→product lookup (VisualSearch module):**
- Add `findByName(int $tenantId, string $text, PageScope $scope): VisualMatchResult` to the VisualSearch search contract (`VisualItemSearch`), alongside the existing image `lookup`. Matches `visual_training_items.name` / `ref_code` (case-insensitive, mirroring `Support\SkuSearch` two-tier: code exact-CI first, then title LIKE-CI), scoped per page (`applies_all_pages` OR `visual_training_item_page`). Returns the same tri-state `VisualMatchResult` (matched / ambiguous / not_found) already used by the image path.

**Intent + branch in `AiSuggestionService`:**
- Add candidate intent `image_request` to `IntentClassifier` (customer wants to see a product photo).
- In `draftAutoReply` / `autoRespond`, when intent = `image_request`:
  1. Resolve product from the current turn's text via `findByName`.
  2. **Matched (1 item):** send its images via `OutboundMessageService::queueMedia` — primary image first, capped by `config('messaging.ai.image_reply.max_images', 3)` — plus a short caption. Auto-mode sends directly; suggest-mode populates `MessageDraft.suggested_attachments` (the schema slot that is currently always `[]`).
  3. **Ambiguous / not_found:** fall through to normal text generation, with a prompt directive telling the AI to ask which product ("Bạn muốn xem sản phẩm nào ạ?"). This covers the "chưa rõ thì hỏi lại" requirement; the follow-up turn where the customer names the product re-enters branch (2).
- This makes all three requested sub-cases one flow: (a) unclear → ask; (b) name given → fetch & send; (c) name embedded in an image request → same `findByName` on current-turn text → send.

**Notes / boundaries:**
- AiSuggestionService already depends on VisualSearch via the search contract — no new cross-module coupling beyond the added contract method.
- Images sourced from `visual_training_images.storage_path`; `queueMedia` accepts `storage_path` and `SendMessage` generates the signed URL. No new storage.
- Capability-gated send: non-Facebook connectors that lack `outbound.image` throw `UnsupportedOperation`; the branch must degrade to a text reply ("Em gửi ảnh qua kênh khác giúp anh/chị nhé") rather than error. No provider-name checks in core.

### Decisions locked
- Image source = **VisualSearch training items**.
- Send mode = **follows AI mode** (auto sends, suggest drafts).
- Send **primary image first**, cap via config (default 3).

---

## Feature 3 — Per-page shop/owner business info

### Problem
There is no per-page "business info" concept. The only prompt knobs are a single global `messaging.ai.system_prompt` and per-page RAG knowledge docs. A customer asking "shop ở đâu / số điện thoại?" is only answerable today if the seller happened to upload it as a knowledge doc.

### Design

**Storage:** new nullable json column `business_info` on `messaging_account_meta` (1-to-1 companion of `channel_accounts`, PK `channel_account_id`), model `MessagingAccountMeta`. Cast `array`. Fields (fixed set + free-form note):
`shop_name, phone, address, email, warranty_policy, working_hours, website, extra_note`.
(Not encrypted — business-public contact info, unlike the existing encrypted `settings` bag.)

**Backend:**
- `MessagingChannelController::index` — include `business_info` in the per-page payload.
- New `PATCH api/v1/messaging/channels/{id}/business-info` (single page) and `PATCH api/v1/messaging/channels/business-info` (bulk: body `{ channel_account_ids: [], business_info: {} }`). Both write via `MessagingAccountMeta::updateOrCreate` (same pattern as the existing `aiMode` endpoint). Gated by `messaging.ai.config`.

**AI wiring:**
- New private `withBusinessInfo(AiContext, Conversation)` in `AiSuggestionService`, mirroring `withAdContext`/`withVisualContext`. Loads `MessagingAccountMeta.business_info` for `conv->channel_account_id`, renders a "# Thông tin cửa hàng" block, appends to `systemPromptExtra`. Injected directly (not via RAG) because it is small, deterministic, and always relevant. Surfaces through `ReplyPersona::instructions()` to all AI connectors unchanged.
- Empty/absent business_info → no block appended (no behavior change for pages that skip it).

**UI:**
- `app/resources/js/pages/MessagingChannelsPage.tsx` — a "Thông tin cửa hàng" card/drawer per connected page (form over the fixed fields + note). An "Áp dụng cho nhiều page" action reuses `components/messaging/PageScope.tsx` `PageMultiSelect` to bulk-save the same info to several pages.
- Types + mutation hook in `app/resources/js/lib/messagingConfig.tsx` (`MessagingChannel` gains `business_info`; new `useSetChannelBusinessInfo()` mirroring `useSetChannelAiMode()`).

### Decisions locked
- **Fixed field set + free-form note.**
- Stored per page; **bulk apply to multiple pages** supported.

---

## Feature 4 — Worker verification + queue bug fix

### Verification result (no change needed)
- **Transcription (Groq Whisper):** fully async in a worker — `Messaging\Jobs\TranscribeInboundAudio` on queue `messaging-media`, two queue hops from the webhook HTTP request (`ProcessMessagingWebhook` → `DownloadInboundMedia` → `TranscribeInboundAudio`).
- **Training-image vectors:** async in a worker — `VisualSearch\Jobs\EmbedTrainingImage` on queue `visual-index`.
- **Vision re-rank in AI messaging:** already in a worker — `AiSuggestionService::withVisualContext` runs inside job `RespondWithAiAutoReply` (queue `messaging-ai`). Only the interactive `VisualLookupController` HTTP API runs re-rank inline, which is intentional (returns results synchronously). **Left as-is per decision.**
- All relevant queues (`messaging-webhooks`, `messaging-media`, `messaging-ai`, `visual-index`, `messaging`) map to Horizon supervisors in `config/horizon.php`.

### Bug fix (in scope)
- Listener `Messaging\Listeners\PushWebOnNewMessage` declares `public string $queue = 'messaging-bg'`, but no Horizon supervisor consumes `messaging-bg` → its web-push jobs would pile up unconsumed. Fix: add `messaging-bg` to `supervisor-messaging-bg`'s queue list in `config/horizon.php` (one-line, preserves the listener's intent). Verify no other orphaned queue names while there.

---

## Cross-cutting

- **Migrations required** (dev SQLite auto; prod deploy does NOT auto-migrate — `RUN_MIGRATIONS=false`, so migrations must be run manually after deploy): `ai_usage_counters`, `messaging_account_meta.business_info`.
- **No backfill** for `ai_usage_counters` (counting starts forward from deploy; document this).
- **Config keys:** `messaging.ai.image_reply.max_images` (default 3).
- **Docs:** update `docs/05-api/endpoints.md` for the new admin AI-usage and channel business-info endpoints; note the new per-page business-info concept where per-page scoping (SPEC 0035) is documented.
- **Tests:** feature tests for (1) `record()` writing `ai_usage_counters` with/without user + admin endpoint aggregation; (2) `image_request` intent → `findByName` matched → `queueMedia` dispatched, ambiguous → text ask; (3) business_info persisted + injected into `systemPromptExtra`; (4) assert `PushWebOnNewMessage` queue is consumed by a configured supervisor.
- **Baseline caveat:** repo has ~7 pre-existing GHN/fulfillment test failures on main; only new/related tests must go green.
