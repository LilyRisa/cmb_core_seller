# Admin Redesign — Phase 2d: Remaining Pages (Support Requests & Audit Logs) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish the filters/search/empty-state standardization pass (spec §5.5) on the two pages
left out of Phase 2a-2c — `AdminSupportRequestsPage` and `AdminAuditLogsPage` — by converting
remaining `Segmented` status filters to `Radio.Group`, giving `AdminAuditLogsPage`'s free-text
action filter a curated autocomplete sourced from real action codes in the codebase, and adding
`<Empty description="...">` to both pages' tables per the spec's audit findings.

**Scope correction from the task brief (read the real files before trusting the spec's
predictions):**
- `AdminSupportRequestsPage.tsx` is **not** fully clean as the brief's framing suggested — it
  **does** use `Segmented` for two status filters (line 213 `FILTERS`, line 214 the "Chờ CSKH"
  toggle), matching spec §5.5's own list (`AdminInvoicesPage`, `AdminSupportRequestsPage`,
  `SystemSettingsPage` → convert to `Radio.Group`). Task 1 converts both. What *is* already
  correct, confirmed by reading the file: search already uses `Input.Search` (line 220-227, no
  change needed) and the row-click affordance already uses `onRow` + `cursor:pointer` (line 235,
  no change needed — this is genuinely the good reference the spec says it is).
- `AdminAuditLogsPage.tsx` has **no** `Segmented` usage at all (confirmed by reading the full
  file) — spec §5.5's `Segmented`→`Radio.Group` list does not name this page either, so Task 2
  does **not** touch a filter-widget-type conversion; its only filter-type change is the action
  field (plain `Input` → `AutoComplete`). There is also no free-text "search across records" box
  on this page to convert to `Input.Search` — the existing fields are `action`/`tenant_id`/
  `user_id`/date-range, none of which are a general search box, so nothing changes there either.

**Architecture:** No new backend endpoint. A distinct-audit-actions backend endpoint was
considered and rejected as out of scope for this phase (see Task 2's reasoning) — the existing
`GET /api/v1/admin/audit-logs` already supports `action` as a `LIKE` filter with `*` wildcard
(`AdminAuditLogController.php:25-29`: `str_replace('*', '%', $action)` then `where('action',
'like', $like)`), so a frontend-only curated constant list plus that existing wildcard support is
sufficient. The curated list is compiled by grepping every `AuditLog::record(...)` call and every
`'action' => '...'` array-literal audit-log write across `app/app/Modules/**/*.php` (both patterns
are used in this codebase — see Task 2 Step 1 for the exact commands and full compiled list).

**Tech Stack:** React 18, Ant Design 5 (`Radio.Group`, `AutoComplete`, `Empty`), TypeScript.
No new npm dependency — both `Radio` and `AutoComplete` are already-imported-elsewhere `antd` core
components.

## Global Constraints

- Admin-only scope (`app/resources/js/admin/`) — no change to the tenant-facing app.
- User-facing strings stay Vietnamese; code/identifiers stay English (per `CLAUDE.md`).
- No visual/theme change — this is a structure/interaction-pattern pass (spec §2 non-goals), reuse
  existing `optionType="button" buttonStyle="solid"` `Radio.Group` styling already established at
  `AdminTenantsPage.tsx:124-126` (`KIND_OPTIONS`) — don't invent new visual treatment.
- **No JS test runner in this repo** (`package.json` has no vitest/jest) — see
  [[test-verify-baseline]]. Every frontend task's verification is
  `npm run typecheck && npm run lint && npm run build` (run from `app/`) plus a manual browser
  script with exact numbered steps — never a placeholder like "test the change".
- Run all npm commands from `app/` (per `CLAUDE.md`).
- Match the `Radio.Group` convention already used elsewhere in admin pages (`AdminTenantsPage.tsx`,
  `AdminTenantDetailPage.tsx`, `AdminAiProvidersPage.tsx`): `options={[{value,label}]}`
  `optionType="button"` `buttonStyle="solid"`, driven by controlled `value`/`onChange`.
- Match the `<Empty description="...">` via `Table`'s `locale={{ emptyText: <Empty
  description="..." /> }}` convention already used across ~40 other pages in this codebase (e.g.
  `resources/js/pages/OrdersPage.tsx:827`, `resources/js/admin/pages/tenants/
  AdminTenantDetailPage.tsx:185`) — do not use the bare Ant Design default empty state, and do not
  invent a different empty-state pattern.

---

### Task 1: `AdminSupportRequestsPage` — Radio.Group filters + Empty state

**Files:**
- Modify: `app/resources/js/admin/pages/support/AdminSupportRequestsPage.tsx`

**Interfaces:**
- Consumes: `antd` (`Radio`, `Empty` — new imports; `Segmented` import removed), no data-layer
  change (`useAdminSupportConversations` from `../../lib/supportRequests` untouched).
- Produces: nothing consumed elsewhere — this is a leaf page, purely a UI-pattern swap.

- [ ] **Step 1: Swap the antd import line**

In `app/resources/js/admin/pages/support/AdminSupportRequestsPage.tsx`, line 5, replace:

```tsx
import { App, Button, Card, Drawer, Input, Segmented, Space, Spin, Table, Tag, Typography } from 'antd';
```

with:

```tsx
import { App, Button, Card, Drawer, Empty, Input, Radio, Space, Spin, Table, Tag, Typography } from 'antd';
```

- [ ] **Step 2: Convert the two `Segmented` filters to `Radio.Group`**

Replace (around line 211-219):

```tsx
            <Space style={{ marginBottom: 16, width: '100%', justifyContent: 'space-between' }} wrap>
                <Space wrap>
                    <Segmented options={FILTERS} value={status} onChange={(v) => { setStatus(v as string); setPage(1); }} />
                    <Segmented
                        options={[{ value: 'all', label: 'Tất cả' }, { value: 'awaiting', label: 'Chờ CSKH trả lời' }]}
                        value={awaiting ? 'awaiting' : 'all'}
                        onChange={(v) => { setAwaiting(v === 'awaiting'); setPage(1); }}
                    />
                </Space>
```

with:

```tsx
            <Space style={{ marginBottom: 16, width: '100%', justifyContent: 'space-between' }} wrap>
                <Space wrap>
                    <Radio.Group
                        options={FILTERS}
                        optionType="button"
                        buttonStyle="solid"
                        value={status}
                        onChange={(e) => { setStatus(e.target.value as string); setPage(1); }}
                    />
                    <Radio.Group
                        options={[{ value: 'all', label: 'Tất cả' }, { value: 'awaiting', label: 'Chờ CSKH trả lời' }]}
                        optionType="button"
                        buttonStyle="solid"
                        value={awaiting ? 'awaiting' : 'all'}
                        onChange={(e) => { setAwaiting(e.target.value === 'awaiting'); setPage(1); }}
                    />
                </Space>
```

(`FILTERS` at the top of the file — `[{ value: 'open', label: 'Đang mở' }, { value: 'closed',
label: 'Đã đóng' }, { value: '', label: 'Tất cả' }]` — is unchanged, it already has the
`{value,label}` shape `Radio.Group`'s `options` prop expects, same as `Segmented`.)

- [ ] **Step 3: Add the `Empty` state to the table**

Replace (around line 230-240):

```tsx
            <Table
                rowKey="id"
                loading={isLoading}
                dataSource={data?.data ?? []}
                columns={columns}
                onRow={(r) => ({ onClick: () => setActiveId(r.id), style: { cursor: 'pointer' } })}
                pagination={{
                    current: page, pageSize: 50, total: data?.meta.pagination.total ?? 0,
                    showSizeChanger: false, onChange: setPage,
                }}
            />
```

with:

```tsx
            <Table
                rowKey="id"
                loading={isLoading}
                dataSource={data?.data ?? []}
                columns={columns}
                onRow={(r) => ({ onClick: () => setActiveId(r.id), style: { cursor: 'pointer' } })}
                pagination={{
                    current: page, pageSize: 50, total: data?.meta.pagination.total ?? 0,
                    showSizeChanger: false, onChange: setPage,
                }}
                locale={{ emptyText: <Empty description="Chưa có hội thoại CSKH nào khớp bộ lọc." /> }}
            />
```

(The `onRow` cursor-pointer affordance on this line is **already correct** — left untouched, not
part of this task's changes, confirmed by reading the file before writing this plan.)

- [ ] **Step 4: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds with no new errors (the `Segmented` import is fully removed so no unused-import
lint error; `Radio` and `Empty` are used).

- [ ] **Step 5: Manual browser verification**

With the dev stack running (`composer dev` from `app/`, or the Docker stack per `CLAUDE.md`), log
into `/admin` and navigate to "Yêu cầu CSKH" (`/admin/support-requests`):
1. Confirm the status filter now renders as connected button-style radios ("Đang mở" / "Đã đóng" /
   "Tất cả") instead of the pill-style `Segmented` control — visually a `Radio.Group` button set,
   not `Segmented`'s rounded-track look.
2. Click each of the 3 status options — confirm the table re-filters (loading spinner briefly,
   then updated rows) exactly as before the change.
3. Click "Chờ CSKH trả lời" — confirm it also re-filters correctly and toggling back to "Tất cả"
   restores the full list.
4. If any status/awaiting combination yields zero rows (e.g. filter to "Đã đóng" + "Chờ CSKH trả
   lời" on a dev DB with no matching data), confirm the table shows "Chưa có hội thoại CSKH nào
   khớp bộ lọc." instead of Ant Design's default "No data" placeholder.
5. Click anywhere on a table row (not just the "Mở" button) — confirm it still opens the thread
   `Drawer` (this was already working; just confirming Step 1-3 didn't regress it).
6. Open browser devtools console — confirm zero errors/warnings introduced by this page.

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/admin/pages/support/AdminSupportRequestsPage.tsx
git commit -m "refactor(admin): chuẩn hoá bộ lọc CSKH sang Radio.Group + thêm Empty state"
```

---

### Task 2: `AdminAuditLogsPage` — curated action autocomplete + Empty state

**Files:**
- Create: `app/resources/js/admin/lib/auditActionCodes.ts`
- Modify: `app/resources/js/admin/pages/tenants/AdminAuditLogsPage.tsx`

**Interfaces:**
- Consumes: `antd` (`AutoComplete`, `Empty` — new imports), the new
  `AUDIT_ACTION_CODES` constant from `@admin/lib/auditActionCodes` (`@admin/*` → `admin/*` alias,
  `app/tsconfig.json:21`). No backend/data-layer change — `useAdminAuditLogs` and
  `AdminAuditLogController.php`'s existing `action` `LIKE`+`*`-wildcard filter are unchanged and
  already sufficient (see Step 1's reasoning for why no new backend endpoint is added).
- Produces: nothing consumed elsewhere — leaf page.

- [ ] **Step 1: Compile the curated action-code list (research, no file change yet)**

Real action codes are written two different ways in this codebase — `AuditLog::record($action,
...)` calls and raw `AuditLog::query()->create(['action' => '...', ...])` array literals. Both were
grepped across `app/app/Modules/**/*.php` while writing this plan:

```bash
# from app/
grep -rn "AuditLog::record(" app/Modules --include=*.php
grep -rn "'action' => '" app/Modules --include=*.php
```

This produced the full real inventory used below. **Scope decision (YAGNI judgment call):** a
"real" fix would add a backend `GET /api/v1/admin/audit-logs/actions` distinct-values endpoint so
the list can never drift from the DB. That is rejected for this phase — it's a bigger scope than a
filter-widget pass warrants (new controller, new route, new frontend hook, and the value proposition
over a curated static list is marginal: this list only needs to cover the *known, already-shipped*
action vocabulary, and the backend filter is `LIKE` + `*`-wildcard already, so an admin who types
an unlisted action code still gets a working filter — the curated list is a *convenience*, not a
correctness gate). If action codes drift significantly in the future (new modules audited), this
static list should be revisited — not a blocker for this phase.

The compiled list mixes two things intentionally:
- **Namespace wildcards** (`admin.*`, `tenant.*`, etc.) — since the backend already turns `*` into
  `%` for a `LIKE` query, these let an admin filter to "every admin action" or "every messaging
  action" without knowing every leaf code. This covers the ~50 non-`admin.*` leaf codes (messaging.*,
  tenant.*, support.*, visual_search.*, marketing.*) found in the grep without listing all of them
  individually — enumerating every leaf action from every module would make the dropdown
  unusably long for what this page's own subtitle already frames as its primary use case
  ("Action `admin.*` để xem mọi thao tác super-admin").
- **Every `admin.*` leaf code found**, listed individually — this is the namespace the page itself
  calls out as primary, so it gets full enumeration instead of just its wildcard.

- [ ] **Step 2: Create the curated constants file**

Create `app/resources/js/admin/lib/auditActionCodes.ts`:

```ts
// Curated action-code list for AdminAuditLogsPage's action-filter autocomplete.
// Compiled 2026-07-21 by grepping `AuditLog::record(...)` and `'action' => '...'` audit-log write
// call sites across app/app/Modules/**/*.php (docs/superpowers/plans/
// 2026-07-21-admin-redesign-phase2d-remaining-pages.md, Task 2, Step 1 — includes the exact grep
// commands and the YAGNI reasoning for why this stays a static frontend list instead of a new
// backend distinct-actions endpoint).
//
// This is a convenience list, not a hard constraint: the backend filter
// (AdminAuditLogController::index) already accepts free text and turns `*` into a SQL `LIKE`
// wildcard, so typing any action code — listed here or not — still filters correctly. Namespace
// wildcards below cover modules whose full leaf-action list isn't individually enumerated
// (messaging.*, tenant.*, support.*, visual_search.*, marketing.*); admin.* — this page's primary
// use case per its own subtitle — is enumerated in full.
export const AUDIT_ACTION_CODES: string[] = [
    // Namespace shortcuts (wildcard `*` -> LIKE on backend)
    'admin.*',
    'tenant.*',
    'messaging.*',
    'support.*',
    'visual_search.*',
    'marketing.*',

    // admin.* — đầy đủ, đây là nhóm hành động chính trang này phục vụ
    'admin.auth.login',
    'admin.auth.logout',
    'admin.auth.change_password',
    'admin.admin_user.create',
    'admin.admin_user.update',
    'admin.admin_user.reset_password',
    'admin.admin_user.suspend',
    'admin.admin_user.reactivate',
    'admin.user.update',
    'admin.user.reset_password',
    'admin.user.suspend',
    'admin.user.reactivate',
    'admin.tenant.suspend',
    'admin.tenant.reactivate',
    'admin.subscription.change',
    'admin.trial.extend',
    'admin.feature_override.set',
    'admin.ai_credit.adjust',
    'admin.channel_account.delete',
    'admin.invoice.create_manual',
    'admin.invoice.mark_paid',
    'admin.invoice.mark_paid.noop',
    'admin.payment.refund',
    'admin.voucher.create',
    'admin.voucher.update',
    'admin.voucher.disable',
    'admin.voucher.grant',
    'admin.plan.create',
    'admin.plan.update',
    'admin.broadcast.send',
    'admin.pro_trial.settings',
    'admin.setting.update',
    'admin.setting.reveal',
];
```

- [ ] **Step 3: Swap the antd import line in `AdminAuditLogsPage.tsx`**

Replace line 2:

```tsx
import { Card, DatePicker, Drawer, Input, Space, Table, Tag, Typography } from 'antd';
```

with:

```tsx
import { AutoComplete, Card, DatePicker, Drawer, Empty, Input, Space, Table, Tag, Typography } from 'antd';
```

(`Input` stays — still used for the `user_id` field.)

- [ ] **Step 4: Import the curated list and add an `AutoComplete` `options` constant**

After the existing imports (after line 8, `import { TenantPicker } from '@admin/components/
TenantPicker';`), add:

```tsx
import { AUDIT_ACTION_CODES } from '@admin/lib/auditActionCodes';

const ACTION_OPTIONS = AUDIT_ACTION_CODES.map((code) => ({ value: code }));
```

- [ ] **Step 5: Replace the free-text action `Input` with `AutoComplete`**

Replace (around line 63-68):

```tsx
                    <Input
                        placeholder="action (vd admin.* hoặc admin.voucher.create)"
                        value={action}
                        onChange={(e) => { setAction(e.target.value); setPage(1); }}
                        style={{ width: 280 }}
                    />
```

with:

```tsx
                    <AutoComplete
                        options={ACTION_OPTIONS}
                        value={action}
                        onChange={(v) => { setAction(v); setPage(1); }}
                        filterOption={(inputValue, option) =>
                            ((option?.value as string) ?? '').toLowerCase().includes(inputValue.toLowerCase())
                        }
                        placeholder="Action (gõ hoặc chọn, vd admin.* hoặc admin.voucher.create)"
                        allowClear
                        style={{ width: 280 }}
                    />
```

This keeps the exact same controlled `action` state / `setPage(1)` reset behavior as the old
`Input`, so `useAdminAuditLogs`'s `filters.action` wiring (`admin/lib/admin.tsx:618-633`) needs no
change — `AutoComplete`'s `value`/`onChange` are string-in-string-out, a drop-in replacement for
`Input`'s. Typing still works for any string not in the curated list (this is `AutoComplete`, not
`Select` — free text is always accepted, per the task brief's explicit requirement to keep
freeform typing available).

- [ ] **Step 6: Add the `Empty` state to the table**

Replace (around line 74-80):

```tsx
                <Table
                    rowKey="id" size="small"
                    loading={isFetching}
                    columns={columns}
                    dataSource={data?.data ?? []}
                    pagination={{ current: page, pageSize: 50, total: data?.meta.pagination.total ?? 0, showSizeChanger: false, onChange: setPage }}
                />
```

with:

```tsx
                <Table
                    rowKey="id" size="small"
                    loading={isFetching}
                    columns={columns}
                    dataSource={data?.data ?? []}
                    pagination={{ current: page, pageSize: 50, total: data?.meta.pagination.total ?? 0, showSizeChanger: false, onChange: setPage }}
                    locale={{ emptyText: <Empty description="Chưa có nhật ký nào khớp bộ lọc." /> }}
                />
```

- [ ] **Step 7: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds with no new errors. If `filterOption`'s `option` typing complains under strict
mode (Ant Design's `AutoCompleteProps['filterOption']` option type may differ slightly by antd
version), adjust the cast to match whatever `option` shape `tsc` reports rather than widening to
`any` — check `node_modules/antd/es/auto-complete/index.d.ts` for the exact signature if the first
attempt doesn't typecheck.

- [ ] **Step 8: Manual browser verification**

With the dev stack running, log into `/admin` and navigate to "Nhật ký" (audit logs,
`/admin/audit-logs` — check the exact route in `AdminApp.tsx` if unsure):
1. Click into the action field — confirm a dropdown appears listing the curated codes (namespace
   wildcards like `admin.*` first, then the full `admin.admin_user.*`/`admin.tenant.*`/etc. leaf
   codes).
2. Type `admin.vou` — confirm the dropdown filters down to `admin.voucher.create`,
   `admin.voucher.update`, `admin.voucher.disable`, `admin.voucher.grant` (substring match, not
   prefix-only).
3. Select `admin.voucher.create` from the dropdown — confirm the table filters to rows whose
   `action` is exactly `admin.voucher.create` (or empty if none exist in the dev DB — verify via
   the "Chưa có nhật ký nào khớp bộ lọc." empty state in that case, confirming Step 6 works).
4. Clear the field and type a value NOT in the curated list, e.g. `messaging.flow.create` — confirm
   it's still accepted as free text (not rejected/reset) and the table filters accordingly — this
   confirms `AutoComplete` didn't regress into `Select`-like exclusive-selection behavior.
5. Type `admin.*` (the wildcard) — confirm the table shows every row whose action starts with
   `admin.` (exercises the existing backend `*`→`%` `LIKE` translation, unchanged by this task).
6. Clear the field entirely — confirm the table returns to showing all rows (no action filter).
7. Open browser devtools console — confirm zero errors/warnings introduced by this page.

- [ ] **Step 9: Commit**

```bash
git add app/resources/js/admin/lib/auditActionCodes.ts app/resources/js/admin/pages/tenants/AdminAuditLogsPage.tsx
git commit -m "feat(admin): autocomplete mã action theo danh sách thật + Empty state cho Audit Logs"
```

---

## Phase 2d self-review checklist

- Confirmed by reading the actual files (not assumed from the spec) before writing any step:
  `AdminSupportRequestsPage.tsx` uses `Segmented` twice (needs conversion, matches spec §5.5's own
  list) but already has correct `Input.Search` and correct row-click `onRow`+cursor (no change
  needed on those two); `AdminAuditLogsPage.tsx` has zero `Segmented` usage (no conversion needed)
  and no general free-text search box beyond the action/tenant/user/date filters (nothing to
  convert to `Input.Search`).
- No backend endpoint added for audit action codes — deliberate YAGNI call documented in Task 2
  Step 1, not an oversight; the existing `action` `LIKE`+`*`-wildcard filter in
  `AdminAuditLogController.php` is unchanged and still the actual filtering mechanism, the curated
  list is a frontend convenience only.
- `AutoComplete` (Task 2) preserves free-text entry — never becomes a closed `Select` that rejects
  unlisted action codes, since new modules will add action codes this static list won't know about.
- Both tables get `locale={{ emptyText: <Empty description="..."> }}` matching the ~40-page
  existing convention in this codebase — not a bespoke empty-state component, not the bare Ant
  Design default.
- `Radio.Group` conversions (Task 1) reuse the exact `optionType="button" buttonStyle="solid"`
  styling already established at `AdminTenantsPage.tsx` — no new visual treatment invented, per
  the spec's explicit "no reskin" non-goal (§2).
- No change to `useAdminSupportConversations` / `useAdminAuditLogs` / any backend route — this
  phase is strictly frontend widget swaps, confirmed no data-shape or API-contract change in any
  step.
- Every code step above is complete, copy-pasteable code based on the actual current file content
  (read in full before this plan was written) — no "similar to X" placeholders.
