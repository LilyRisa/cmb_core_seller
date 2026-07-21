# Admin panel UX redesign — design spec

Date: 2026-07-21
Status: approved (brainstorming), ready for implementation plan

## 1. Problem statement

The admin panel (`app/resources/js/admin/`) grew page-by-page over several specs with no shared
interaction conventions. A code audit (see §2) found:

- The sidebar is a flat 18-item list with no grouping — no sense of where things live.
- The same class of risky action (suspend/disable/delete) is confirmed 3-4 different ways
  across pages, with no relationship between UI friction and actual risk.
- Edit/create forms use Modal, Drawer, or an inline Card at random depending on the page.
- API-key/secret fields are handled 3 incompatible ways (plaintext, blank-to-keep, proper
  mask+reveal).
- A 5-page AI-configuration cluster (`AdminAiProvidersPage`, `AdminAiSupportPage`,
  `AdminMarketingAiProvidersPage`, `AdminTranscriptionPage`, `AdminVisualRerankPage`) has no
  visible relationship to each other and reuses the same icon (`ApiOutlined`) for two of them.
- `AdminDashboardPage` ("Tổng quan") is a placeholder greeting card — no real data.
- `AdminBroadcastsPage`'s table pagination is wired incorrectly (page 2 never loads) — a live bug,
  not a design choice.

Full findings: see the Explore-agent audit transcript referenced in the brainstorming session
that produced this spec (not re-copied here to avoid duplication — the actionable subset is
folded into §4-§6 below).

## 2. Goals / non-goals

**Goals:**
- Standardize interaction patterns (confirmation risk-tiering, edit-surface convention, secret
  fields, filters, empty/loading states, terminology) into reusable components so every page
  behaves predictably.
- Regroup the sidebar into labeled sections; add breadcrumb/page-title to the header.
- Ship a real "Tổng quan/Báo cáo" overview page (tenant growth, revenue, ops/CS, AI usage).
- Fix the `AdminBroadcastsPage` pagination bug as part of the table-pattern pass.

**Non-goals (explicitly out of scope for this spec):**
- Visual/aesthetic redesign — theme (navy/red), Ant Design component library, typography, and
  overall page chrome stay as-is. This is a structure/pattern pass, not a reskin.
- Merging the 5 AI-configuration pages into one hub page/route. They stay separate pages; only
  their grouping (sidebar section, distinct icons) and internal patterns (secret field,
  test-before-save) are standardized.
- Any change to the tenant-facing app (`resources/js/app.tsx`) — admin only.

## 3. Architecture overview

No new frontend framework/library. Additions:
- A small `admin/components/` layer of shared interaction primitives (§5).
- One new backend endpoint + controller for the overview page (§6).
- No new routes beyond the overview endpoint; existing page routes/URLs are unchanged (this is a
  patterns/content pass, not a re-IA of URLs — only the *sidebar presentation* is grouped, the
  underlying `/admin/*` route paths in `AdminApp.tsx` don't need to move).

## 4. Navigation & information architecture

Regroup `SIDEBAR_ITEMS` in `AdminLayout.tsx` using Ant Design `Menu`'s built-in `type: 'group'`
(no new dependency):

```
Tổng quan                          (standalone, no group — landing page)

KHÁCH HÀNG
  Tenants
  Người dùng
  Voucher
  Gói thuê bao
  Lịch sử thanh toán

TRUYỀN THÔNG
  Broadcast
  Popup thông báo
  Hình nền Desktop

CẤU HÌNH AI
  Nhà cung cấp AI
  AI Marketing
  AI Trợ giúp
  AI chấm ảnh
  AI chuyển giọng nói

HỆ THỐNG
  Cài đặt hệ thống
  Email thông báo

HỖ TRỢ & GIÁM SÁT
  Yêu cầu CSKH
  Nhật ký
```

Icon de-duplication: `AdminMarketingAiProvidersPage` currently shares `ApiOutlined` with
`AdminAiProvidersPage` — give it a distinct icon (e.g. `SoundOutlined` is already taken by
Announcements; use `RadarChartOutlined` or similar marketing-flavored icon, confirm no collision
against the final icon set at implementation time). Audit the full icon list for other repeats
before finalizing.

Header (`AdminLayout.tsx` `Layout.Header`): add a left-aligned breadcrumb/page-title area (e.g.
Ant Design `Breadcrumb` or a simple `Typography.Text` derived from the matched sidebar item +
optional detail-page override via route state/context), keep the existing user-info + logout on
the right unchanged.

## 5. Interaction pattern standards

New shared components live in `admin/components/`:

### 5.1 Risk-tiered confirmation — `<ReasonConfirmModal>`

Two tiers, chosen by the action's real consequence (not by which page happens to implement it):

- **Standard** — toggle/disable/delete a record with no cross-cutting impact (disable voucher,
  disable AI provider, delete announcement/background/notification-email): keep `Popconfirm`,
  no reason field.
- **High-impact** — affects a tenant's ability to operate, another user's account, or money
  (suspend tenant, suspend/reactivate a user account, change a tenant's plan, delete an active
  channel, adjust AI credit balance): new `<ReasonConfirmModal>` component — controlled `Form`
  state (not the current uncontrolled `let reason = ''` closure pattern), `min:10` validation via
  a declarative Form rule (not imperative `if` checks), submits reason to the existing audit-log
  write. Replaces the 3 copy-pasted `modal.confirm` + manual-reason implementations in
  `AdminTenantDetailPage.tsx`, and gets newly applied to user suspend/reactivate/reset-password in
  `AdminUserFormDrawer.tsx` / `TenantUserDrawer.tsx` (currently one-click `Popconfirm`, no reason
  — promoted to high-impact tier).

  **Heuristic for actions not explicitly listed above** (future admin features): High-impact if
  the action can lock a tenant/user out of their account, reverses money already charged/owed, or
  is hard to undo without support intervention. Standard otherwise.

### 5.2 Edit/create surface convention

- **Drawer** — multi-field forms and record detail+edit (already the best pattern in
  `AdminUserFormDrawer`, `AdminTenantDetailPage`). Becomes the default for anything with more than
  ~3 fields or that also displays existing record detail.
- **Modal** — genuinely small, single-purpose actions (e.g. a 1-2 field quick create).
- **Inline Card form removed entirely** — `AdminAnnouncementsPage.tsx`, `AdminDesktopBackgroundsPage.tsx`,
  `AdminNotificationEmailsPage.tsx` convert their create/edit Card forms to Drawer.

### 5.3 Secret/API-key fields

Reuse the existing `SecretInput` component (`admin/components/SecretInput.tsx`, already used by
`SettingRow`) everywhere a credential is entered: `AdminAiProvidersPage`, `AdminAiSupportPage`,
`AdminMarketingAiProvidersPage`. Removes both the plaintext-display pattern and the
blank-means-keep pattern in favor of one mask+reveal control.

### 5.4 Test-before-save for AI credentials

Generalize the existing gate from `AdminTranscriptionPage` / `AdminVisualRerankPage` (Save
disabled until a live connectivity test succeeds) to `AdminAiProvidersPage`,
`AdminAiSupportPage`, `AdminMarketingAiProvidersPage`.

### 5.5 Filters, search, empty/loading states

- Status filters: standardize on `Radio.Group` (buttons), replacing `Segmented` usage in
  `AdminInvoicesPage`, `AdminSupportRequestsPage`, `SystemSettingsPage`.
- Search: standardize on `Input.Search`.
- `AdminAuditLogsPage`'s free-text action filter gets an autocomplete/`Select` sourced from known
  audit action codes instead of expecting the admin to type dot-namespaced strings from memory.
- Empty tables: every table gets `<Empty description="<vietnamese message>">` instead of the
  Ant Design default. `TenantUserDrawer.tsx` gets a loading `Spin` state (currently missing —
  opens with blank fields while data loads).
- Row-click affordance: any table whose row opens a detail view makes the **whole row** clickable
  (`onRow` + `cursor:pointer`), matching `AdminTenantsPage`/`AdminSupportRequestsPage`, not just a
  small text link (`AdminUsersPage`'s current pattern).

### 5.6 Terminology

Replace English action labels in `AdminUserFormDrawer.tsx` / `TenantUserDrawer.tsx` ("Suspend",
"Reactivate", "Reset password") with the Vietnamese used everywhere else ("Tạm khoá", "Mở lại",
"Đặt lại mật khẩu").

### 5.7 Bug fix bundled into this pass

`AdminBroadcastsPage.tsx` `Table` pagination is missing `current`/`onChange` wiring — page 2+
never loads. Fix alongside the table-pattern pass since it touches the same code.

## 6. New "Tổng quan/Báo cáo" page

### 6.1 Backend: `GET /api/v1/admin/dashboard/overview`

New controller `AdminDashboardController` (Admin module). Read-only, no request body. Response
shape (all under the standard `{ "data": ... }` envelope):

```jsonc
{
  "data": {
    "tenants": {
      "active_total": 0,
      "by_plan": [{ "plan_code": "starter", "plan_name": "Starter", "count": 0 }],
      "new_by_day": [{ "date": "2026-07-01", "count": 0 }],   // last 30 days
      "trial_ending_soon": [
        { "tenant_id": 0, "tenant_name": "", "trial_ends_at": "2026-07-25T00:00:00Z" }
      ]   // next 7 days
    },
    "revenue": {
      "mrr_estimate": 0,          // integer VND — see calc note below
      "invoices_this_month": { "paid_count": 0, "paid_total": 0, "pending_count": 0, "pending_total": 0 },
      "revenue_by_month": [{ "period_ym": 202607, "total": 0 }],  // last 12 months
      "active_vouchers": 0
    },
    "support": {
      "open_count": 0,
      "avg_resolution_hours": 0,          // closed_at - created_at, closed conversations only
      "recent_audit_log": [
        { "action": "admin.tenant.suspend", "actor": "", "at": "2026-07-21T03:00:00Z", "summary": "" }
      ]
    },
    "ai_usage": {
      "calls_this_month": 0,        // NOTE: call *volume*, not cost — see 6.3
      "top_tenants": [{ "tenant_id": 0, "tenant_name": "", "calls_this_month": 0 }]
    }
  }
}
```

Money fields follow the existing convention (integer VND, no floats). Dates ISO-8601 UTC, bucketed
using `app_display_tz()` per [[timezone-architecture-utc-store-hcm-display]] so "this month"/"last
30 days" match what the admin sees on screen, not raw UTC day boundaries.

### 6.2 MRR calculation

Sum `plans.price_monthly` for every `subscriptions` row whose `status` is in
`Subscription::ALIVE_STATUSES`; for rows with `billing_cycle = 'yearly'`, use
`plans.price_yearly / 12` (integer division, document the rounding rule in the implementation
plan — e.g. round down, note the resulting under-count is acceptable for an *estimate*).

### 6.3 AI usage — contract gap to resolve during implementation

`AiUsageReporter` (Billing module contract, Admin depends on it per module rules) currently
exposes `usageForUsers(array $userIds)` and `breakdownForTenant(int $tenantId)` (single tenant),
but **no system-wide "top N tenants by usage" method**. The overview endpoint needs one. Add:

```php
/**
 * Top N tenant theo lượt gọi AI tháng hiện tại (nhiều→ít).
 * @return list<array{tenant_id:int, calls_this_month:int}>
 */
public function topTenantsByUsageThisMonth(int $limit): array;
```

to the `AiUsageReporter` contract, implemented in Billing's concrete reporter. This is a small,
explicit cross-module addition — call it out as its own task in the implementation plan so it
isn't missed as "just a frontend page."

Also note: this metric is **call volume** ("lượt gọi"), not monetary cost — `ai_usage_counters`
does not track cost per the existing contract. Label the UI "Lượt gọi AI tháng này", not "Chi phí
AI", to avoid implying a cost figure that isn't tracked.

### 6.4 Frontend layout

`AdminDashboardPage.tsx` rebuilt as 5 stacked sections:

1. Quick-stat card row (4-5 `Statistic` cards): active tenants, MRR estimate, open support
   requests, AI calls this month.
2. Tenant & growth: new-tenants line/bar chart (30d), plan-distribution chart, trial-ending-soon
   list (clickable rows → tenant detail).
3. Revenue & billing: this-month paid/pending invoice summary, 12-month revenue trend chart,
   active voucher count.
4. Ops/CS: open-request count, avg resolution time, recent audit-log activity feed.
5. AI usage: this-month call volume, top-5-tenants table.

Each section is its own `Card` backed by an **independent** TanStack Query hook against a slice of
the single overview response (or the whole response cached once and sliced client-side — decide
in the implementation plan based on whether sub-sections warrant independent refetch/error
isolation). A section whose data errors shows a small inline `Alert`, not a full-page crash.

Charts: no charting library currently in `package.json` for admin — implementation plan must pick
one (recharts is already a reasonable default in this ecosystem; confirm nothing lighter is
already a transitive dependency before adding one) or use simple CSS/SVG sparklines for the
smaller trend indicators if a full chart library feels heavy for 2-3 charts. Decide at
implementation time, not in this spec.

## 7. Phasing (single plan, staged rollout)

- **Phase 0 — Foundation**: sidebar regroup + header breadcrumb (`AdminLayout.tsx`),
  `<ReasonConfirmModal>`, `Empty`/loading convention, `AdminBroadcastsPage` pagination fix.
- **Phase 1 — Overview page**: new endpoint + controller + `AdminDashboardController` test,
  rebuilt `AdminDashboardPage.tsx`. Serves as the reference implementation of Phase 0's patterns.
- **Phase 2 — Retrofit existing pages, in batches**:
  - 2a. Customer group (Tenants, Users, Vouchers, Plans, Invoices) — `ReasonConfirmModal` tiers,
    Drawer convention, Vietnamese labels.
  - 2b. AI cluster (5 pages) — distinct icons, `SecretInput`, test-before-save.
  - 2c. Communications group (Broadcasts, Announcements, Desktop Backgrounds, Notification
    Emails) — inline Card → Drawer.
  - 2d. Remaining (Support Requests, Audit Logs) — filter/empty-state standardization.
- **Phase 3 — Table polish**: bulk row-select + action on Tenants (e.g. attach to broadcast
  directly from selected rows, replacing the separate `TenantPicker` flow for that one case),
  column trimming/`scroll={{x}}` on the Tenants table.

## 8. Testing / verification

No JS test runner in this repo (`package.json` has no vitest/jest — see
[[test-verify-baseline]]). Verification per phase:

- `npm run typecheck && npm run lint && npm run build` (frontend, CI-mirroring gate).
- Manual browser QA (Playwright) of every changed page — golden path + the specific interaction
  being standardized (e.g. after Phase 0, manually trigger both confirm tiers; after Phase 1,
  load the overview page against seeded data and sanity-check every number against a direct DB
  query).
- Backend: `AdminDashboardController`'s aggregation logic gets a PHPUnit feature test (seed known
  tenants/subscriptions/invoices, assert the response numbers) — this is financial/operational
  reporting, silent miscalculation is costly. `vendor/bin/phpstan analyse` + `vendor/bin/pint
  --test` on all touched PHP files.

## 9. Open items for the implementation plan to resolve (not blocking this spec)

- Final icon assignment across the full sidebar (avoid new collisions).
- Chart library choice for §6.4 (or CSS/SVG sparkline alternative).
- Whether overview sub-sections fetch independently or slice one cached response.
- Exact rounding rule for yearly→monthly MRR conversion.
