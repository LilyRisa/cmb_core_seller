# Admin Redesign — Phase 3: Table Polish Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `AdminTenantsPage`'s table usable at its real column count (add `scroll={{ x }}`
sized to the actual columns, size the two currently-unsized columns explicitly), add checkbox
row-selection, and wire a new bulk-action shortcut — select tenant rows, click one button, land on
`AdminBroadcastsPage` with those tenants pre-filled as the audience. This is purely additive: the
existing manual `TenantPicker` search-and-pick flow inside the broadcast form is untouched and
stays the primary way to pick tenants when you don't already have a filtered list open.

**Architecture:** Three frontend-only files change, no backend work (verified: `POST
/admin/broadcasts` already accepts `audience.tenant_ids` as an integer array — see
`app/app/Modules/Admin/Http/Controllers/AdminBroadcastController.php:51-52`, and
`useAdminCreateBroadcast` in `app/resources/js/admin/lib/admin.tsx:647-656` already types its
`audience` param as `{ kind: string; tenant_ids?: number[] }`, which is exactly what
`AdminBroadcastsPage.tsx`'s existing "Tenant cụ thể" radio option already sends).

The pre-selected-tenant handoff uses React Router `navigate(path, { state: {...} })` +
`useLocation().state` on the receiving page. This convention already exists in the codebase (not
in `admin/` yet, but the pattern is established and safe to reuse): `resources/js/pages/marketplace/
OnChannelPage.tsx:161` does `navigate(..., { state: { listing: rep } })` with a **fully-typed
object**, not a bare ID, and `resources/js/pages/marketplace/MarketplaceEditPage.tsx:31` reads it
back via `(location.state as { listing?: ChannelListing } | null)?.listing`. This plan follows the
exact same shape: pass full `AdminTenantSummary` objects (not bare IDs) so the receiving page can
render tenant names immediately without waiting on a search round-trip — this also solves a real
UX bug that bare IDs would cause (see Task 3 rationale).

**Tech Stack:** React 18, Ant Design 5 (`Table` `rowSelection`/`scroll`, `Select`), TypeScript,
React Router (`useNavigate`, `useLocation`), TanStack Query (unchanged — no new hooks needed, the
existing `useAdminTenants`/`useAdminCreateBroadcast` are reused as-is).

## Global Constraints

- Admin-only scope: only `app/resources/js/admin/**` files change. No changes to
  `resources/js/app.tsx` or shared components used by the tenant-facing app.
- User-facing strings are Vietnamese; code/identifiers are English (per `CLAUDE.md`).
- No visual/theme changes — Ant Design defaults, existing page chrome, no new colors/spacing
  system.
- **This phase is frontend-only.** No backend route/controller/migration changes — confirmed above
  that `POST /admin/broadcasts` already accepts `audience.tenant_ids`.
- **No JS test runner exists in this repo** (`package.json` has no vitest/jest — see
  [[test-verify-baseline]]). Every task's verification step is
  `npm run typecheck && npm run lint && npm run build` (run from `app/`) plus a manual
  browser-verification script with exact numbered steps — there is no automated component test to
  write.
- Run all `npm run *` commands from `app/` (per `CLAUDE.md`).
- Do not touch `AdminBroadcastsPage.tsx`'s pagination wiring beyond what's already landed by Phase
  0 (`docs/superpowers/plans/2026-07-21-admin-redesign-phase0-foundation.md` Task 3 — adds `page`
  state + `current`/`onChange` to the history table's `pagination` prop). Task 3 below assumes that
  fix is already in the file; its full-file rewrite includes it verbatim so there's no ambiguity
  about the starting point.

---

### Task 1: `TenantPicker` — accept pre-known options so pre-filled IDs render as names, not raw numbers

**Files:**
- Modify: `app/resources/js/admin/components/TenantPicker.tsx` (full rewrite — currently 67 lines)

**Interfaces:**
- Consumes: nothing new — `antd` (`Select`, `Space`, `Typography`), `../lib/admin`
  (`useAdminTenants`), all already imported.
- Produces (for Task 3 to consume): a new optional prop and exported type:
  ```ts
  export interface TenantPickerOption { value: number; label: ReactNode }
  // new prop on TenantPicker: initialOptions?: TenantPickerOption[]
  ```

**Why this is needed:** `TenantPicker`'s dropdown `options` come only from
`useAdminTenants({ q: debounced, per_page: 20 })` — the *first 20 tenants matching the current
search term* (empty term on first render). If `AdminBroadcastsPage` sets `Form`'s `tenant_ids`
field to `[12, 47, 903]` without those IDs being present in the freshly-fetched `options` array,
Ant Design's `Select` has no label to show for those values and falls back to rendering the raw
numeric ID as the tag text — exactly the "admin doesn't know what number means what" problem
`TenantPicker`'s own top-of-file comment says it was built to solve. `initialOptions` lets the
caller seed known tenant labels (it already has them — they came from the very table row the admin
just selected) so the pre-filled tags render as real names immediately, merged with whatever the
live search subsequently returns.

- [ ] **Step 1: Replace the whole file content**

Write the complete new `app/resources/js/admin/components/TenantPicker.tsx`:

```tsx
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { Select, Space, Typography } from 'antd';
import { useAdminTenants } from '../lib/admin';

/**
 * Option tối giản để "mồi" trước danh sách khi đã biết tenant nào được chọn (VD: điều hướng từ
 * AdminTenantsPage kèm `location.state`, xem AdminBroadcastsPage) — tránh Select hiển thị số ID
 * thô vì chưa kịp tìm kiếm ra tên.
 */
export interface TenantPickerOption {
    value: number;
    label: ReactNode;
}

/**
 * Bộ chọn tenant có tìm kiếm theo mã shop / tên / email chủ shop (thay ô nhập tenant ID số
 * — giao diện không hiển thị ID nên trước đây admin không biết gõ số nào).
 * Gõ để tìm (debounce 300ms, gọi GET /admin/tenants?q=...). `mode="multiple"` để chọn nhiều tenant.
 *
 * `initialOptions` (tuỳ chọn): option đã biết trước khi có kết quả tìm kiếm — dùng khi `value`
 * được set từ nơi khác chứ không phải do admin gõ tìm tại chỗ (VD: bulk-select ở
 * AdminTenantsPage → điều hướng sang form Broadcast kèm sẵn danh sách tenant). Được gộp với kết
 * quả tìm kiếm hiện tại; nếu trùng `value`, kết quả tìm kiếm mới hơn sẽ ghi đè.
 */
export function TenantPicker({
    value,
    onChange,
    mode,
    placeholder,
    allowClear = true,
    disabled,
    style,
    initialOptions,
}: {
    value?: number | number[];
    onChange?: (value: any) => void;
    mode?: 'multiple';
    placeholder?: string;
    allowClear?: boolean;
    disabled?: boolean;
    style?: React.CSSProperties;
    initialOptions?: TenantPickerOption[];
}) {
    const [term, setTerm] = useState('');
    const [debounced, setDebounced] = useState('');
    useEffect(() => {
        const t = setTimeout(() => setDebounced(term.trim()), 300);
        return () => clearTimeout(t);
    }, [term]);

    const { data, isFetching } = useAdminTenants({ q: debounced, per_page: 20 });
    const tenants = data?.data ?? [];

    const fetchedOptions: TenantPickerOption[] = tenants.map((t) => ({
        value: t.id,
        label: (
            <Space size={6}>
                <Typography.Text>{t.name}</Typography.Text>
                <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                    · {t.code}{t.owner ? ` · ${t.owner.email}` : ''}
                </Typography.Text>
            </Space>
        ),
    }));

    // Gộp initialOptions (đã biết trước) với kết quả tìm kiếm hiện tại — fetchedOptions ghi đè
    // nếu trùng value (dữ liệu tìm kiếm mới hơn), nhưng initialOptions vẫn hiển thị cho các value
    // chưa nằm trong kết quả tìm kiếm hiện tại (VD: chưa gõ tìm gì).
    const merged = new Map<number, TenantPickerOption>();
    (initialOptions ?? []).forEach((o) => merged.set(o.value, o));
    fetchedOptions.forEach((o) => merged.set(o.value, o));
    const options = Array.from(merged.values());

    return (
        <Select
            showSearch
            allowClear={allowClear}
            disabled={disabled}
            mode={mode}
            value={value}
            placeholder={placeholder ?? 'Tìm theo mã / tên / email…'}
            style={{ width: '100%', ...style }}
            filterOption={false}
            onSearch={setTerm}
            onChange={onChange}
            notFoundContent={isFetching ? 'Đang tìm…' : (debounced ? 'Không tìm thấy' : 'Gõ để tìm…')}
            options={options}
            optionLabelProp="label"
        />
    );
}
```

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds. `TenantPicker` is used today by `AdminBroadcastsPage.tsx` without
`initialOptions` — confirm that call site still typechecks (the new prop is optional, so it must).

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/admin/components/TenantPicker.tsx
git commit -m "feat(admin): TenantPicker nhận initialOptions để mồi tên tenant biết trước"
```

---

### Task 2: `AdminTenantsPage` — column sizing, `scroll={{ x }}`, and bulk row-selection

**Files:**
- Modify: `app/resources/js/admin/pages/tenants/AdminTenantsPage.tsx` (full rewrite — currently
  152 lines)

**Interfaces:**
- Consumes: `useAdminTenants`, `type AdminTenantSummary` (both already imported from
  `@admin/lib/admin`) — `AdminTenantSummary` is reused as-is for the bulk-selection state and for
  the `state.presetTenants` payload passed to `/admin/broadcasts` (Task 3 consumes this exact
  type — no new/duplicated type needed, same reuse pattern as the existing `ChannelListing`
  precedent cited in this plan's Architecture section).
- Produces (for Task 3 to consume): a `navigate('/admin/broadcasts', { state: { presetTenants:
  AdminTenantSummary[] } })` call, triggered by a new toolbar button.

**Column-width math for `scroll={{ x }}`:** the table currently has 6 columns, 4 of which already
have an explicit `width` (`Xác minh email` 150, `Gói` 180, `Gian hàng đã kết nối` 180, `Trạng thái`
160) and 2 which don't (`Gian hàng` — name+slug+date, `Chủ sở hữu` — name+email). This task adds
explicit widths to those two (`240` for `Gian hàng`, wide enough for `"tên-shop-dài · từ
21/07/2026"`; `220` for `Chủ sở hữu`, wide enough for a typical name + email on two stacked lines)
plus a `48`px checkbox column from the new `rowSelection`. Sum: `48 + 240 + 220 + 150 + 180 + 180 +
160 = 1178`, rounded up to **`1180`** for cell border/padding slack.

- [ ] **Step 1: Replace the whole file content**

Write the complete new `app/resources/js/admin/pages/tenants/AdminTenantsPage.tsx`:

```tsx
import { useMemo, useState } from 'react';
import type { Key } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button, Card, Input, Radio, Space, Table, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import {
    CheckCircleOutlined, ExclamationCircleOutlined, LockOutlined, SearchOutlined, SendOutlined, WarningOutlined,
} from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { useAdminTenants, type AdminTenantSummary } from '@admin/lib/admin';
import { formatDate, formatDateShort } from '@/lib/format';

type FilterKind = 'all' | 'over_quota' | 'suspended';

const KIND_OPTIONS: Array<{ value: FilterKind; label: string }> = [
    { value: 'all', label: 'Tất cả' },
    { value: 'over_quota', label: 'Đang vượt mức' },
    { value: 'suspended', label: 'Đang tạm khoá' },
];

// Tổng chiều rộng cột thực tế cho scroll={{x}}: 48 (checkbox) + 240 (Gian hàng) + 220
// (Chủ sở hữu) + 150 (Xác minh email) + 180 (Gói) + 180 (Gian hàng đã kết nối) + 160 (Trạng thái)
// = 1178, làm tròn lên 1180 cho phần đệm border/padding của ô.
const TABLE_SCROLL_X = 1180;

export function AdminTenantsPage() {
    const navigate = useNavigate();
    const [q, setQ] = useState('');
    const [kind, setKind] = useState<FilterKind>('all');
    const [page, setPage] = useState(1);
    const [selectedRowKeys, setSelectedRowKeys] = useState<Key[]>([]);
    const [selectedRows, setSelectedRows] = useState<AdminTenantSummary[]>([]);

    const filters = useMemo(() => ({
        q: q.trim() || undefined,
        over_quota: kind === 'over_quota',
        suspended: kind === 'suspended',
        page, per_page: 30,
    }), [q, kind, page]);

    const { data, isLoading, isFetching } = useAdminTenants(filters);

    const columns: ColumnsType<AdminTenantSummary> = [
        {
            title: 'Gian hàng', dataIndex: 'name', key: 'name', width: 240,
            render: (_v, r) => (
                <Space direction="vertical" size={0}>
                    <Typography.Text strong>{r.name}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                        {r.slug} · từ {formatDate(r.created_at, false)}
                    </Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Chủ sở hữu', dataIndex: ['owner', 'email'], key: 'owner', width: 220,
            render: (_v, r) => r.owner ? (
                <Space direction="vertical" size={0}>
                    <Typography.Text>{r.owner.name}</Typography.Text>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.owner.email}</Typography.Text>
                </Space>
            ) : <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Xác minh email', key: 'email_verified', width: 150,
            render: (_v, r) => {
                if (!r.owner) return <Typography.Text type="secondary">—</Typography.Text>;
                return r.owner.email_verified_at ? (
                    <Tooltip title={`Xác minh ${formatDateShort(r.owner.email_verified_at)}`}>
                        <Tag color="green" icon={<CheckCircleOutlined />}>Đã xác minh</Tag>
                    </Tooltip>
                ) : (
                    <Tag color="orange" icon={<ExclamationCircleOutlined />}>Chưa xác minh</Tag>
                );
            },
        },
        {
            title: 'Gói', key: 'plan', width: 180,
            render: (_v, r) => r.subscription ? (
                <Space direction="vertical" size={0}>
                    <Tag color={planColor(r.subscription.plan_code)}>{(r.subscription.plan_code ?? '—').toUpperCase()}</Tag>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{r.subscription.status}</Typography.Text>
                </Space>
            ) : <Typography.Text type="secondary">—</Typography.Text>,
        },
        {
            title: 'Gian hàng đã kết nối', key: 'channels', width: 180,
            render: (_v, r) => {
                const { used, limit, over } = r.usage.channel_accounts;
                const limitLabel = limit < 0 ? '∞' : limit;

                return (
                    <Space size={6}>
                        <Typography.Text strong style={{ color: over ? '#cf1322' : undefined }}>
                            {used} / {limitLabel}
                        </Typography.Text>
                        {over && <Tag color="red" icon={<WarningOutlined />}>Vượt mức</Tag>}
                    </Space>
                );
            },
        },
        {
            title: 'Trạng thái', dataIndex: 'status', key: 'status', width: 160,
            render: (_v, r) => (
                <Space size={4} wrap>
                    <Tag color={r.status === 'suspended' ? 'red' : 'green'}>
                        {r.status === 'suspended' ? 'Tạm khoá' : 'Hoạt động'}
                    </Tag>
                    {r.subscription?.over_quota_warned_at && (
                        <Tooltip title={`Cảnh báo từ ${formatDateShort(r.subscription.over_quota_warned_at)}`}>
                            <Tag color={r.subscription.over_quota_locked ? 'red' : 'orange'}
                                icon={r.subscription.over_quota_locked ? <LockOutlined /> : <WarningOutlined />}>
                                {r.subscription.over_quota_locked ? 'Đã khoá' : 'Đếm 48h'}
                            </Tag>
                        </Tooltip>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div>
            <PageHeader
                title="Quản trị hệ thống — Tenant"
                subtitle="Super-admin có thể xem, hỗ trợ và can thiệp dữ liệu mọi tenant. Mọi thao tác đều ghi audit log."
            />

            <Card styles={{ body: { padding: 12 } }}>
                <Space size={12} wrap style={{ marginBottom: 12 }}>
                    <Input prefix={<SearchOutlined />} placeholder="Tìm theo tên / slug" allowClear
                        value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }}
                        style={{ width: 280 }} />
                    <Radio.Group value={kind} optionType="button" buttonStyle="solid"
                        onChange={(e) => { setKind(e.target.value as FilterKind); setPage(1); }}
                        options={KIND_OPTIONS} />
                </Space>

                <Space size={12} wrap style={{ marginBottom: 12 }}>
                    <Button
                        icon={<SendOutlined />}
                        disabled={selectedRows.length === 0}
                        onClick={() => navigate('/admin/broadcasts', { state: { presetTenants: selectedRows } })}
                    >
                        {selectedRows.length > 0
                            ? `Gửi broadcast cho ${selectedRows.length} tenant đã chọn`
                            : 'Gửi broadcast cho tenant đã chọn'}
                    </Button>
                </Space>

                <Table<AdminTenantSummary>
                    rowKey="id"
                    columns={columns}
                    dataSource={data?.data ?? []}
                    loading={isLoading || isFetching}
                    onRow={(r) => ({ onClick: () => navigate(`/admin/tenants/${r.id}`), style: { cursor: 'pointer' } })}
                    rowSelection={{
                        selectedRowKeys,
                        columnWidth: 48,
                        onChange: (keys, rows) => { setSelectedRowKeys(keys); setSelectedRows(rows); },
                    }}
                    scroll={{ x: TABLE_SCROLL_X }}
                    pagination={{
                        current: data?.meta.pagination.page ?? 1,
                        pageSize: data?.meta.pagination.per_page ?? 30,
                        total: data?.meta.pagination.total ?? 0,
                        onChange: (p) => setPage(p),
                        showSizeChanger: false,
                    }}
                    size="middle"
                />
            </Card>
        </div>
    );
}

function planColor(code: string | null | undefined): string {
    return ({ trial: 'default', starter: 'blue', pro: 'purple', business: 'gold' } as Record<string, string>)[code ?? ''] ?? 'default';
}
```

Note: Ant Design's `Table` selection-column checkbox already calls `e.stopPropagation()` internally
(confirmed in `node_modules/antd/es/table/hooks/useSelection.js`), so clicking a row's checkbox
will **not** also fire the existing `onRow` `onClick` row-navigation handler — both behaviors can
coexist without extra wiring. Step 3's manual QA confirms this holds in the browser too.

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds.

- [ ] **Step 3: Manual browser verification**

With the dev stack running and at least 3 tenants seeded, log into `/admin` and go to
`/admin/tenants`:
1. Shrink the browser window (or devtools responsive mode) to roughly 1000px wide. Confirm a
   horizontal scrollbar appears under the table and the column headers stay aligned with the body
   as you scroll it left/right.
2. Click the checkbox on row 1. Confirm: (a) you are **not** navigated to that tenant's detail
   page — you stay on `/admin/tenants`; (b) the row shows checked; (c) the toolbar button above the
   table now reads "Gửi broadcast cho 1 tenant đã chọn" and is enabled (not greyed out).
3. Check 2 more rows (3 total selected). Confirm the button text updates to "...cho 3 tenant đã
   chọn".
4. Click anywhere else on an *unchecked* row (not its checkbox) — confirm normal navigation to
   `/admin/tenants/<id>` still works (this is the pre-existing row-click-to-detail behavior, must
   be unaffected by the addition of `rowSelection`).
5. Go back to `/admin/tenants`, re-select the same 3 tenants, click the "Gửi broadcast cho 3 tenant
   đã chọn" button. Confirm you land on `/admin/broadcasts` (Task 3 verifies what happens there).
6. Open browser devtools console — confirm zero errors/warnings on this page from these changes.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/tenants/AdminTenantsPage.tsx
git commit -m "feat(admin): scroll ngang + chọn nhiều dòng + gửi broadcast từ Tenants"
```

---

### Task 3: `AdminBroadcastsPage` — consume `presetTenants` from navigation state

**Files:**
- Modify: `app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx` (full rewrite — the
  starting point below already includes Phase 0's pagination fix, per this plan's Global
  Constraints note; if you are executing this plan against a checkout where Phase 0 has not yet
  landed, this rewrite still produces the correct end state for both fixes combined)

**Interfaces:**
- Consumes: Task 2's `navigate('/admin/broadcasts', { state: { presetTenants: AdminTenantSummary[]
  } })`, via `useLocation()` from `react-router-dom`. Also consumes Task 1's `TenantPicker`
  `initialOptions` prop and its exported `TenantPickerOption` type.
- Produces: nothing consumed by later tasks — this is the last task in this phase.

- [ ] **Step 1: Replace the whole file content**

Write the complete new `app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx`:

```tsx
import { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { App, Button, Card, Form, Input, Radio, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { SendOutlined, NotificationOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { formatDate } from '@/lib/format';
import { useAdminBroadcasts, useAdminCreateBroadcast, type AdminBroadcastRow, type AdminTenantSummary } from '@admin/lib/admin';
import { TenantPicker, type TenantPickerOption } from '@admin/components/TenantPicker';
import { errorMessage } from '@/lib/api';

export function AdminBroadcastsPage() {
    const { message } = App.useApp();
    const location = useLocation();
    const navigate = useNavigate();
    const [page, setPage] = useState(1);
    const { data, isFetching } = useAdminBroadcasts({ page });
    const create = useAdminCreateBroadcast();
    const [form] = Form.useForm();
    const [presetTenants, setPresetTenants] = useState<AdminTenantSummary[]>([]);

    // Lối tắt từ AdminTenantsPage: chọn nhiều dòng tenant (checkbox) rồi bấm "Gửi broadcast cho N
    // tenant đã chọn" → điều hướng sang đây kèm `state.presetTenants` (mảng AdminTenantSummary đầy
    // đủ, không chỉ ID — để TenantPicker hiển thị đúng tên ngay, xem TenantPicker.tsx). Đây là lối
    // tắt BỔ SUNG — form "Tenant cụ thể" thủ công bên dưới (tìm & chọn qua TenantPicker) vẫn giữ
    // nguyên, vẫn hữu ích khi chưa có sẵn danh sách tenant lọc trước.
    useEffect(() => {
        const preset = (location.state as { presetTenants?: AdminTenantSummary[] } | null)?.presetTenants;
        if (preset && preset.length > 0) {
            form.setFieldsValue({ audience_kind: 'tenant_ids', tenant_ids: preset.map((t) => t.id) });
            setPresetTenants(preset);
            message.info(`Đã điền sẵn ${preset.length} tenant từ danh sách đã chọn ở trang Tenants.`);
            // Xoá state khỏi history sau khi dùng — tránh điền lại nếu admin F5 hoặc quay lại
            // trang này lần sau (history state của trình duyệt vẫn còn nếu không xoá).
            navigate(location.pathname, { replace: true, state: null });
        }
        // Chỉ chạy 1 lần lúc mount — location.state chỉ có ý nghĩa ở lần điều hướng đầu tiên tới
        // trang này, không phải mỗi khi form/location thay đổi.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const presetOptions: TenantPickerOption[] = presetTenants.map((t) => ({
        value: t.id,
        label: (
            <Space size={6}>
                <Typography.Text>{t.name}</Typography.Text>
                <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                    · {t.code}{t.owner ? ` · ${t.owner.email}` : ''}
                </Typography.Text>
            </Space>
        ),
    }));

    const columns: ColumnsType<AdminBroadcastRow> = [
        { title: 'ID', dataIndex: 'id', width: 64 },
        { title: 'Tiêu đề', dataIndex: 'subject' },
        {
            title: 'Audience', dataIndex: 'audience',
            render: (v: AdminBroadcastRow['audience']) => {
                if (v.kind === 'all_owners') return <Tag>Mọi chủ shop</Tag>;
                if (v.kind === 'all_admins_and_owners') return <Tag>Chủ + admin shop</Tag>;
                return <Tag>{v.tenant_ids?.length ?? 0} tenant cụ thể</Tag>;
            },
        },
        {
            title: 'Đã gửi', key: 'sent',
            render: (_, r) => `${r.sent_count}/${r.recipient_count}${r.skipped_count ? ` (skipped ${r.skipped_count})` : ''}`,
        },
        {
            title: 'Lúc', dataIndex: 'sent_at',
            render: (v: string | null) => formatDate(v),
        },
    ];

    return (
        <>
            <PageHeader title="Broadcast email" subtitle="Gửi thông báo cho user của tenant — bảo trì, cập nhật, khuyến mãi..." />

            <Card title="Gửi broadcast mới" style={{ marginBottom: 24 }}>
                <Form
                    form={form}
                    layout="vertical"
                    initialValues={{ audience_kind: 'all_owners' }}
                    onFinish={(v) => {
                        const audience: { kind: string; tenant_ids?: number[] } = { kind: v.audience_kind };
                        if (v.audience_kind === 'tenant_ids') {
                            audience.tenant_ids = (v.tenant_ids ?? []) as number[];
                        }
                        create.mutate({ subject: v.subject, body_markdown: v.body_markdown, audience }, {
                            onSuccess: (b) => {
                                message.success(`Đã gửi tới ${b.sent_count}/${b.recipient_count} người.`);
                                form.resetFields();
                                setPresetTenants([]);
                            },
                            onError: (e) => message.error(errorMessage(e, 'Gửi lỗi.')),
                        });
                    }}
                >
                    <Form.Item name="subject" label="Tiêu đề email" rules={[{ required: true, max: 255 }]}>
                        <Input placeholder="VD: Thông báo bảo trì hệ thống ngày 20/05" />
                    </Form.Item>

                    <Form.Item name="audience_kind" label="Đối tượng nhận">
                        <Radio.Group>
                            <Radio.Button value="all_owners"><NotificationOutlined /> Mọi chủ shop</Radio.Button>
                            <Radio.Button value="all_admins_and_owners">Chủ + admin shop</Radio.Button>
                            <Radio.Button value="tenant_ids">Tenant cụ thể</Radio.Button>
                        </Radio.Group>
                    </Form.Item>

                    <Form.Item shouldUpdate={(p, c) => p.audience_kind !== c.audience_kind} noStyle>
                        {({ getFieldValue }) => getFieldValue('audience_kind') === 'tenant_ids' && (
                            <Form.Item name="tenant_ids" label="Tenant cụ thể" rules={[{ required: true }]}>
                                <TenantPicker mode="multiple" placeholder="Tìm theo mã / tên / email…" initialOptions={presetOptions} />
                            </Form.Item>
                        )}
                    </Form.Item>

                    <Form.Item name="body_markdown" label="Nội dung (Markdown — HTML user nhập sẽ bị escape)" rules={[{ required: true, max: 50000 }]}>
                        <Input.TextArea rows={8} placeholder={'# Tiêu đề\n\nXin chào,\n\nHệ thống sẽ bảo trì lúc **22h** ngày 20/05. Vui lòng đóng giao dịch trước thời điểm này.'} />
                    </Form.Item>

                    <Button type="primary" htmlType="submit" icon={<SendOutlined />} loading={create.isPending}>
                        Gửi broadcast
                    </Button>
                    <Typography.Text type="secondary" style={{ marginLeft: 12 }}>
                        Giới hạn 5000 người/lần. Tenant suspended sẽ bị skip tự động.
                    </Typography.Text>
                </Form>
            </Card>

            <Card title="Lịch sử broadcast">
                <Table
                    rowKey="id" size="small"
                    columns={columns}
                    dataSource={data?.data ?? []}
                    loading={isFetching}
                    pagination={{
                        current: page,
                        pageSize: data?.meta.pagination.per_page ?? 30,
                        total: data?.meta.pagination.total ?? 0,
                        showSizeChanger: false,
                        onChange: setPage,
                    }}
                />
            </Card>
        </>
    );
}
```

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds. The `// eslint-disable-next-line react-hooks/exhaustive-deps` comment on the
mount-only `useEffect` matches the existing precedent at
`app/resources/js/admin/pages/tenants/AdminPlansPage.tsx:192` — confirm ESLint doesn't flag it as
an unused-disable (it should be needed since the effect references `location`, `form`, `navigate`,
`message` without listing them as deps).

- [ ] **Step 3: Manual browser verification**

Continuing from Task 2 Step 3 (3 tenants selected on `/admin/tenants`, bulk button clicked), on
`/admin/broadcasts`:
1. Confirm a message/toast appears: "Đã điền sẵn 3 tenant từ danh sách đã chọn ở trang Tenants."
2. Confirm "Đối tượng nhận" is already set to "Tenant cụ thể" (radio selected).
3. Confirm the `TenantPicker` field below shows **3 tags with the tenants' real names/emails** —
   not raw numeric IDs — and they match the 3 rows you checked on the Tenants page.
4. Type a subject and body, submit. Confirm it sends successfully to those 3 tenants (check the
   network request payload in devtools: `audience.tenant_ids` should be an array of exactly those
   3 tenant IDs).
5. Reload `/admin/broadcasts` directly (browser refresh, F5). Confirm the audience resets to the
   default "Mọi chủ shop" — i.e. the pre-fill does **not** reappear on a fresh load, proving the
   `navigate(..., { replace: true, state: null })` call cleared the history state correctly.
6. Separately, confirm the pre-existing manual flow still works unaided: go directly to
   `/admin/broadcasts` (no `state`, e.g. via the sidebar), select "Tenant cụ thể", type into the
   `TenantPicker` search box, and confirm search-and-pick still functions exactly as before (this
   is the flow this phase must not regress).
7. Open browser devtools console on `/admin/broadcasts` — confirm zero errors/warnings.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx
git commit -m "feat(admin): nhận presetTenants từ AdminTenantsPage để điền sẵn audience broadcast"
```

---

## Phase 3 self-review checklist

- `TenantPicker`'s `initialOptions` prop is optional and its existing call site (the manual
  "Tenant cụ thể" flow, unaffected by this phase) still typechecks without passing it.
- `AdminTenantsPage`'s `rowSelection` checkbox click does **not** trigger the existing
  row-click-to-detail `onRow` navigation — verified both by code (AntD's selection cell calls
  `stopPropagation` internally) and by the manual QA step in Task 2.
- The bulk-action button in `AdminTenantsPage` is **disabled when nothing is selected**, never
  hidden — matches this codebase's established toolbar convention (memory
  `ui-order-actions-toolbar`: validate-by-disable, not by hiding).
- `scroll={{ x: 1180 }}` on `AdminTenantsPage`'s table is derived from the actual sum of column
  widths (documented in Task 2), not a guessed round number.
- The existing manual `TenantPicker`-based "Tenant cụ thể" flow inside `AdminBroadcastsPage` is
  fully intact and still the only way to pick tenants when arriving at the page without
  `presetTenants` — this phase adds a shortcut, it does not replace anything.
- `presetTenants` is passed as full `AdminTenantSummary` objects through router state, not bare
  IDs — this is what lets `TenantPicker` render real names immediately instead of raw numeric IDs
  (the actual reason `TenantPicker` needed the `initialOptions` change in Task 1).
- Router `state` is cleared via `navigate(location.pathname, { replace: true, state: null })`
  after being consumed once, so refreshing `/admin/broadcasts` does not repeatedly re-apply a stale
  pre-fill.
- No backend files are touched in this phase — `POST /admin/broadcasts` already accepted
  `audience.tenant_ids` before this plan; nothing about the request contract changed.
- `npm run typecheck && npm run lint && npm run build` passes after all three tasks, and all three
  manual QA scripts (Task 1 has none — it's consumed only by Task 3's QA; Tasks 2 and 3) were
  actually run in a browser, not just assumed from reading the code.
