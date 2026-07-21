# Admin Redesign — Phase 0: Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the shared navigation structure and interaction-pattern primitives that every later
admin-redesign phase (1, 2a-2d, 3) depends on, plus fix one standalone live bug.

**Architecture:** Pure frontend work in `app/resources/js/admin/`. Regroup the existing flat
sidebar into labeled sections and add a breadcrumb to the header (`AdminLayout.tsx`), add one new
reusable component (`useReasonConfirm` hook) that later phases will wire into individual pages,
and fix `AdminBroadcastsPage`'s broken pagination. No backend changes in this phase.

**Tech Stack:** React 18, Ant Design 5 (`Menu` with `type: 'group'`, `Modal.confirm`, `Form`),
TypeScript, React Router, TanStack Query (already in use — no new dependencies).

## Global Constraints

- Admin-only: do not touch `resources/js/app.tsx` or any shared component used by the tenant-facing
  app (e.g. `resources/js/components/PageHeader.tsx` is shared — do not modify it; the breadcrumb
  lives in `AdminLayout.tsx`'s own `Layout.Header`, which is admin-only).
- No visual/theme changes — keep the existing navy (`#0F172A`) sidebar theme, Ant Design defaults.
- User-facing strings are Vietnamese; code/identifiers are English (per `CLAUDE.md`).
- Icons from `@ant-design/icons` only, never emoji (memory `ui-use-font-icons-not-emoji`).
- **No JS test runner exists in this repo** (`package.json` has no vitest/jest — see
  [[test-verify-baseline]]). Every frontend task's "test" step is:
  `npm run typecheck && npm run lint && npm run build` (run from `app/`) plus a manual
  browser-verification script with exact steps — there is no automated component test to write.
- Run all `npm run *` commands from `app/` (per `CLAUDE.md`: all Node/PHP commands run from `app/`,
  not the repo root).

---

### Task 1: Regroup the sidebar and add a header breadcrumb

**Files:**
- Modify: `app/resources/js/admin/AdminLayout.tsx` (full rewrite of the `SIDEBAR_ITEMS` constant
  and the `AdminLayout` component body — file is currently 111 lines, see current content below)

**Interfaces:**
- Consumes: nothing new — `react-router-dom` (`useLocation`, `useNavigate`, `Outlet`), `antd`
  (`Layout`, `Menu`, `Typography`, `Space`, `Button`), `./lib/adminAuth` (`useAdminLogout`,
  `useAdminMe`) — all already imported in the current file.
- Produces: nothing consumed by other tasks in this phase. Phase 2a-2d plans will add new routes'
  sidebar entries to the same `SIDEBAR` array defined here — they must follow this file's grouped
  shape, not the old flat array.

Current `AdminLayout.tsx` (for reference — you are replacing the `SIDEBAR_ITEMS` constant and the
`AdminLayout` function body, keeping all existing imports except adding `RiseOutlined` and
`EyeOutlined`):

```tsx
// Spec 2026-05-17 — sidebar + header admin. Tách hoàn toàn khỏi `AppLayout`
// của user. Theme navy/đỏ (xem `admin.tsx` ConfigProvider). Icons từ
// `@ant-design/icons` (memory `ui-use-font-icons-not-emoji`).

import { Layout, Menu, Typography, Space, Button } from 'antd';
import {
    DashboardOutlined,
    ShopOutlined,
    UserOutlined,
    SettingOutlined,
    AuditOutlined,
    TransactionOutlined,
    LogoutOutlined,
    SafetyCertificateOutlined,
    GiftOutlined,
    ProfileOutlined,
    NotificationOutlined,
    SoundOutlined,
    ApiOutlined,
    CustomerServiceOutlined,
    SolutionOutlined,
    PictureOutlined,
    AudioOutlined,
    MailOutlined,
} from '@ant-design/icons';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAdminLogout, useAdminMe } from './lib/adminAuth';

const SIDEBAR_ITEMS = [
    { key: '/admin', icon: <DashboardOutlined />, label: 'Tổng quan' },
    { key: '/admin/tenants', icon: <ShopOutlined />, label: 'Tenants' },
    { key: '/admin/users', icon: <UserOutlined />, label: 'Người dùng' },
    { key: '/admin/vouchers', icon: <GiftOutlined />, label: 'Voucher' },
    { key: '/admin/plans', icon: <ProfileOutlined />, label: 'Gói thuê bao' },
    { key: '/admin/broadcasts', icon: <NotificationOutlined />, label: 'Broadcast' },
    { key: '/admin/announcements', icon: <SoundOutlined />, label: 'Popup thông báo' },
    { key: '/admin/desktop-backgrounds', icon: <PictureOutlined />, label: 'Hình nền Desktop' },
    { key: '/admin/settings', icon: <SettingOutlined />, label: 'Hệ thống' },
    { key: '/admin/notification-emails', icon: <MailOutlined />, label: 'Email thông báo' },
    { key: '/admin/ai-providers', icon: <ApiOutlined />, label: 'Nhà cung cấp AI' },
    { key: '/admin/marketing-ai-providers', icon: <ApiOutlined />, label: 'AI Marketing' },
    { key: '/admin/ai-support', icon: <CustomerServiceOutlined />, label: 'AI Trợ giúp' },
    { key: '/admin/ai-visual-rerank', icon: <PictureOutlined />, label: 'AI chấm ảnh' },
    { key: '/admin/ai-transcription', icon: <AudioOutlined />, label: 'AI chuyển giọng nói' },
    { key: '/admin/support-requests', icon: <SolutionOutlined />, label: 'Yêu cầu CSKH' },
    { key: '/admin/audit-logs', icon: <AuditOutlined />, label: 'Nhật ký' },
    { key: '/admin/invoices', icon: <TransactionOutlined />, label: 'Lịch sử thanh toán' },
];

export function AdminLayout() {
    const navigate = useNavigate();
    const loc = useLocation();
    const { data: me } = useAdminMe();
    const logout = useAdminLogout();

    // Chọn item match nhất theo prefix: vd /admin/tenants/123 → /admin/tenants.
    const selected = SIDEBAR_ITEMS
        .map((i) => i.key)
        .filter((k) => loc.pathname === k || loc.pathname.startsWith(k + '/'))
        .sort((a, b) => b.length - a.length)
        .slice(0, 1);

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Layout.Sider width={240} style={{ background: '#0F172A' }}>
                <div style={{ color: '#fff', padding: '20px 24px', borderBottom: '1px solid #1E293B' }}>
                    <Space>
                        <SafetyCertificateOutlined />
                        <Typography.Text strong style={{ color: '#fff' }}>CMBcore Admin</Typography.Text>
                    </Space>
                </div>
                <Menu
                    theme="dark"
                    mode="inline"
                    selectedKeys={selected}
                    style={{ background: '#0F172A', borderRight: 0 }}
                    items={SIDEBAR_ITEMS}
                    onClick={(e) => navigate(e.key)}
                />
            </Layout.Sider>
            <Layout>
                <Layout.Header style={{
                    background: '#fff',
                    display: 'flex',
                    justifyContent: 'flex-end',
                    alignItems: 'center',
                    padding: '0 24px',
                    borderBottom: '1px solid #E5E7EB',
                }}>
                    <Space>
                        <Typography.Text type="secondary">
                            {me?.name} <Typography.Text code>{me?.username}</Typography.Text>
                        </Typography.Text>
                        <Button
                            icon={<LogoutOutlined />}
                            onClick={() => logout.mutate(undefined, {
                                onSuccess: () => navigate('/admin/login', { replace: true }),
                            })}
                        >
                            Đăng xuất
                        </Button>
                    </Space>
                </Layout.Header>
                <Layout.Content style={{ padding: 24, background: '#F1F5F9' }}>
                    <Outlet />
                </Layout.Content>
            </Layout>
        </Layout>
    );
}
```

- [ ] **Step 1: Replace the whole file content**

Write the complete new `app/resources/js/admin/AdminLayout.tsx`:

```tsx
// Spec 2026-05-17 (redesign 2026-07-21) — sidebar + header admin. Tách hoàn toàn khỏi
// `AppLayout` của user. Theme navy/đỏ (xem `admin.tsx` ConfigProvider). Icons từ
// `@ant-design/icons` (memory `ui-use-font-icons-not-emoji`). Sidebar gom nhóm theo
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §4.

import type { ReactNode } from 'react';
import { Layout, Menu, Typography, Space, Button } from 'antd';
import type { MenuProps } from 'antd';
import {
    DashboardOutlined,
    ShopOutlined,
    UserOutlined,
    SettingOutlined,
    AuditOutlined,
    TransactionOutlined,
    LogoutOutlined,
    SafetyCertificateOutlined,
    GiftOutlined,
    ProfileOutlined,
    NotificationOutlined,
    SoundOutlined,
    ApiOutlined,
    CustomerServiceOutlined,
    SolutionOutlined,
    PictureOutlined,
    AudioOutlined,
    MailOutlined,
    RiseOutlined,
    EyeOutlined,
} from '@ant-design/icons';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAdminLogout, useAdminMe } from './lib/adminAuth';

interface SidebarLeaf { key: string; icon: ReactNode; label: string }
interface SidebarGroup { groupLabel: string; items: SidebarLeaf[] }
type SidebarEntry = SidebarLeaf | SidebarGroup;

function isGroup(e: SidebarEntry): e is SidebarGroup {
    return 'groupLabel' in e;
}

// Cấu trúc nguồn của sidebar — Menu items (AntD) và breadcrumb header đều dẫn xuất từ đây,
// tránh 2 nguồn sự thật lệch nhau. Thêm mục mới: thêm leaf vào group phù hợp (hoặc group mới).
const SIDEBAR: SidebarEntry[] = [
    { key: '/admin', icon: <DashboardOutlined />, label: 'Tổng quan' },
    {
        groupLabel: 'KHÁCH HÀNG',
        items: [
            { key: '/admin/tenants', icon: <ShopOutlined />, label: 'Tenants' },
            { key: '/admin/users', icon: <UserOutlined />, label: 'Người dùng' },
            { key: '/admin/vouchers', icon: <GiftOutlined />, label: 'Voucher' },
            { key: '/admin/plans', icon: <ProfileOutlined />, label: 'Gói thuê bao' },
            { key: '/admin/invoices', icon: <TransactionOutlined />, label: 'Lịch sử thanh toán' },
        ],
    },
    {
        groupLabel: 'TRUYỀN THÔNG',
        items: [
            { key: '/admin/broadcasts', icon: <NotificationOutlined />, label: 'Broadcast' },
            { key: '/admin/announcements', icon: <SoundOutlined />, label: 'Popup thông báo' },
            { key: '/admin/desktop-backgrounds', icon: <PictureOutlined />, label: 'Hình nền Desktop' },
        ],
    },
    {
        groupLabel: 'CẤU HÌNH AI',
        items: [
            { key: '/admin/ai-providers', icon: <ApiOutlined />, label: 'Nhà cung cấp AI' },
            { key: '/admin/marketing-ai-providers', icon: <RiseOutlined />, label: 'AI Marketing' },
            { key: '/admin/ai-support', icon: <CustomerServiceOutlined />, label: 'AI Trợ giúp' },
            { key: '/admin/ai-visual-rerank', icon: <EyeOutlined />, label: 'AI chấm ảnh' },
            { key: '/admin/ai-transcription', icon: <AudioOutlined />, label: 'AI chuyển giọng nói' },
        ],
    },
    {
        groupLabel: 'HỆ THỐNG',
        items: [
            { key: '/admin/settings', icon: <SettingOutlined />, label: 'Cài đặt hệ thống' },
            { key: '/admin/notification-emails', icon: <MailOutlined />, label: 'Email thông báo' },
        ],
    },
    {
        groupLabel: 'HỖ TRỢ & GIÁM SÁT',
        items: [
            { key: '/admin/support-requests', icon: <SolutionOutlined />, label: 'Yêu cầu CSKH' },
            { key: '/admin/audit-logs', icon: <AuditOutlined />, label: 'Nhật ký' },
        ],
    },
];

const MENU_ITEMS: MenuProps['items'] = SIDEBAR.map((e) =>
    isGroup(e)
        ? {
            key: `group:${e.groupLabel}`,
            type: 'group' as const,
            label: e.groupLabel,
            children: e.items.map((i) => ({ key: i.key, icon: i.icon, label: i.label })),
        }
        : { key: e.key, icon: e.icon, label: e.label },
);

const ALL_LEAF_KEYS: string[] = SIDEBAR.flatMap((e) => (isGroup(e) ? e.items.map((i) => i.key) : [e.key]));

function findBreadcrumb(pathname: string): { groupLabel?: string; label: string } | null {
    for (const e of SIDEBAR) {
        if (isGroup(e)) {
            for (const i of e.items) {
                if (pathname === i.key || pathname.startsWith(i.key + '/')) {
                    return { groupLabel: e.groupLabel, label: i.label };
                }
            }
        } else if (pathname === e.key || pathname.startsWith(e.key + '/')) {
            return { label: e.label };
        }
    }
    return null;
}

export function AdminLayout() {
    const navigate = useNavigate();
    const loc = useLocation();
    const { data: me } = useAdminMe();
    const logout = useAdminLogout();

    // Chọn item match nhất theo prefix: vd /admin/tenants/123 → /admin/tenants.
    const selected = ALL_LEAF_KEYS
        .filter((k) => loc.pathname === k || loc.pathname.startsWith(k + '/'))
        .sort((a, b) => b.length - a.length)
        .slice(0, 1);

    const crumb = findBreadcrumb(loc.pathname);

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Layout.Sider width={240} style={{ background: '#0F172A' }}>
                <div style={{ color: '#fff', padding: '20px 24px', borderBottom: '1px solid #1E293B' }}>
                    <Space>
                        <SafetyCertificateOutlined />
                        <Typography.Text strong style={{ color: '#fff' }}>CMBcore Admin</Typography.Text>
                    </Space>
                </div>
                <Menu
                    theme="dark"
                    mode="inline"
                    selectedKeys={selected}
                    style={{ background: '#0F172A', borderRight: 0 }}
                    items={MENU_ITEMS}
                    onClick={(e) => navigate(e.key)}
                />
            </Layout.Sider>
            <Layout>
                <Layout.Header style={{
                    background: '#fff',
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    padding: '0 24px',
                    borderBottom: '1px solid #E5E7EB',
                }}>
                    <Typography.Text strong style={{ fontSize: 15 }}>
                        {crumb ? (crumb.groupLabel ? `${crumb.groupLabel} / ${crumb.label}` : crumb.label) : ''}
                    </Typography.Text>
                    <Space>
                        <Typography.Text type="secondary">
                            {me?.name} <Typography.Text code>{me?.username}</Typography.Text>
                        </Typography.Text>
                        <Button
                            icon={<LogoutOutlined />}
                            onClick={() => logout.mutate(undefined, {
                                onSuccess: () => navigate('/admin/login', { replace: true }),
                            })}
                        >
                            Đăng xuất
                        </Button>
                    </Space>
                </Layout.Header>
                <Layout.Content style={{ padding: 24, background: '#F1F5F9' }}>
                    <Outlet />
                </Layout.Content>
            </Layout>
        </Layout>
    );
}
```

- [ ] **Step 2: Typecheck, lint, build**

Run from `app/`:
```bash
npm run typecheck && npm run lint && npm run build
```
Expected: all three succeed with no new errors. If `RiseOutlined`/`EyeOutlined` fail to resolve,
confirm the installed `@ant-design/icons` version exports them:
```bash
ls node_modules/@ant-design/icons/es/icons/ | grep -E "^(RiseOutlined|EyeOutlined)\.js$"
```
(already confirmed present at plan-writing time — re-check only if the build fails on these names).

- [ ] **Step 3: Manual browser verification**

Start the dev stack (`composer dev` from `app/`, or `docker compose up -d` from repo root per
`CLAUDE.md`), log into `/admin/login`, then:
1. Confirm the sidebar shows 6 sections: standalone "Tổng quan", then group headers "KHÁCH HÀNG",
   "TRUYỀN THÔNG", "CẤU HÌNH AI", "HỆ THỐNG", "HỖ TRỢ & GIÁM SÁT", each with the items listed in
   `SIDEBAR` above, in that order.
2. Click "Nhà cung cấp AI" and "AI Marketing" — confirm they now show **different** icons (not
   both the API plug icon).
3. Click "Hình nền Desktop" and "AI chấm ảnh" — confirm different icons.
4. Click through at least 3 different pages (e.g. Tenants, AI Marketing, Nhật ký) and confirm the
   header breadcrumb text updates to match (e.g. "KHÁCH HÀNG / Tenants").
5. Navigate to a tenant detail page (`/admin/tenants/<id>`) and confirm the sidebar still
   highlights "Tenants" as selected (prefix match still works) and the breadcrumb still reads
   "KHÁCH HÀNG / Tenants" (detail-page breadcrumb override is out of scope for this phase).

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/AdminLayout.tsx
git commit -m "feat(admin): gom nhóm sidebar theo khu vực + thêm breadcrumb header"
```

---

### Task 2: `useReasonConfirm` — shared high-impact confirmation hook

**Files:**
- Create: `app/resources/js/admin/components/ReasonConfirmModal.tsx`

**Interfaces:**
- Consumes: `antd` (`App`, `Form`, `Input`, `Typography`), `@/lib/api` (`errorMessage` — already
  used throughout the admin pages for mutation error display).
- Produces (for Phase 2a/2b/2c plans to consume):
  ```ts
  export interface ReasonConfirmOptions {
      title: ReactNode;
      danger?: boolean;               // default false → okType='primary'; true → okType='danger'
      warningText?: ReactNode;        // optional warning paragraph shown above the reason field
      okText?: string;                // default 'Xác nhận'
      reasonLabel?: string;           // default 'Lý do (≥10 ký tự — sẽ ghi vào audit log)'
      reasonPlaceholder?: string;
      onConfirm: (reason: string) => Promise<void>;
  }
  export function useReasonConfirm(): (opts: ReasonConfirmOptions) => void
  ```
  Call pattern for consumers (documented in the file's top comment, used verbatim by Phase 2a):
  ```tsx
  const confirmWithReason = useReasonConfirm();
  confirmWithReason({
      title: 'Tạm khoá gian hàng',
      danger: true,
      onConfirm: async (reason) => {
          await suspend.mutateAsync({ tenantId: t.id, reason });
          message.success('Đã tạm khoá tenant.');
      },
  });
  ```

This replaces the `let reason = ''` mutated-in-`onChange` pattern (functionally correct today
since it's a plain closure variable, but duplicated 2-3x and validated with imperative
`if (...) { message.error(...); throw ... }` instead of antd's declarative Form validation) with
one hook using a real `Form` instance + `rules`. Declarative validation shows the "≥10 ký tự"
error **inline under the field**, not just as a global toast — a real UX improvement, not just a
refactor.

- [ ] **Step 1: Write the component**

```tsx
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.1 — "High-impact" tier
// của xác nhận rủi ro: hành động ảnh hưởng khả năng vận hành của tenant, tài khoản người khác,
// hoặc tiền (khoá tenant, khoá/mở tài khoản user, đổi gói, xoá kênh đang hoạt động...). Bắt buộc
// gõ lý do ≥10 ký tự, validate qua Form.rules (báo lỗi ngay dưới field) thay vì message.error rời
// rạc. Tier "Standard" (bật/tắt, xoá bản ghi không ảnh hưởng người khác) tiếp tục dùng
// `Popconfirm` trực tiếp — không cần qua hook này.
//
// Heuristic cho hành động mới chưa liệt kê trong spec: High-impact nếu có thể khoá tenant/user
// khỏi tài khoản của họ, đảo ngược tiền đã thu/nợ, hoặc khó hoàn tác nếu không có CSKH can thiệp.

import type { ReactNode } from 'react';
import { App, Form, Input, Typography } from 'antd';
import { errorMessage } from '@/lib/api';

export interface ReasonConfirmOptions {
    title: ReactNode;
    danger?: boolean;
    warningText?: ReactNode;
    okText?: string;
    reasonLabel?: string;
    reasonPlaceholder?: string;
    onConfirm: (reason: string) => Promise<void>;
}

export function useReasonConfirm() {
    const { modal, message } = App.useApp();
    const [form] = Form.useForm<{ reason: string }>();

    return function confirmWithReason(opts: ReasonConfirmOptions) {
        form.resetFields();
        modal.confirm({
            title: opts.title,
            okText: opts.okText ?? 'Xác nhận',
            okType: opts.danger ? 'danger' : 'primary',
            cancelText: 'Huỷ',
            content: (
                <div>
                    {opts.warningText && (
                        <Typography.Paragraph type="warning" style={{ marginBottom: 8 }}>
                            {opts.warningText}
                        </Typography.Paragraph>
                    )}
                    <Form form={form} layout="vertical" style={{ marginTop: 12 }}>
                        <Form.Item
                            name="reason"
                            label={opts.reasonLabel ?? 'Lý do (≥10 ký tự — sẽ ghi vào audit log)'}
                            rules={[{ required: true, min: 10, message: 'Lý do phải có tối thiểu 10 ký tự.' }]}
                        >
                            <Input.TextArea rows={3} placeholder={opts.reasonPlaceholder} />
                        </Form.Item>
                    </Form>
                </div>
            ),
            onOk: async () => {
                const values = await form.validateFields();
                try {
                    await opts.onConfirm(values.reason.trim());
                } catch (e) {
                    message.error(errorMessage(e));
                    throw e;
                }
            },
        });
    };
}
```

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds. This file is not imported anywhere yet, so there is no visual behavior to
manually verify in this task — Phase 2a's plan wires it into `AdminTenantDetailPage.tsx` and
`AdminUserFormDrawer.tsx`/`TenantUserDrawer.tsx` and verifies it there.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/admin/components/ReasonConfirmModal.tsx
git commit -m "feat(admin): thêm useReasonConfirm — xác nhận hành động rủi ro cao dùng chung"
```

---

### Task 3: Fix `AdminBroadcastsPage` pagination

**Files:**
- Modify: `app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx`

**Interfaces:**
- Consumes: `useAdminBroadcasts(filters: { page?: number; per_page?: number })` — already accepts
  a `page` param (defined in `app/resources/js/admin/lib/admin.tsx:636`), just never called with
  one from this page today.

**Bug:** `Table`'s `pagination` prop (line 96) sets `pageSize`/`total` but no `current`/`onChange`,
so clicking page 2 changes nothing — the query is always called with no `page` filter.

- [ ] **Step 1: Add page state and wire it through**

In `app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx`, change:

```tsx
import { App, Button, Card, Form, Input, Radio, Table, Tag, Typography } from 'antd';
```
to:
```tsx
import { useState } from 'react';
import { App, Button, Card, Form, Input, Radio, Table, Tag, Typography } from 'antd';
```

Change:
```tsx
    const { data, isFetching } = useAdminBroadcasts();
```
to:
```tsx
    const [page, setPage] = useState(1);
    const { data, isFetching } = useAdminBroadcasts({ page });
```

Change:
```tsx
                    pagination={{ pageSize: 30, total: data?.meta.pagination.total ?? 0, showSizeChanger: false }}
```
to:
```tsx
                    pagination={{
                        current: page,
                        pageSize: data?.meta.pagination.per_page ?? 30,
                        total: data?.meta.pagination.total ?? 0,
                        showSizeChanger: false,
                        onChange: setPage,
                    }}
```

(This matches the exact pagination-wiring pattern already used correctly in
`AdminTenantDetailPage.tsx`'s `AuditLogTab`/`LoginHistoryTab` — see lines 517-523 and 551-557 of
that file.)

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds.

- [ ] **Step 3: Manual browser verification**

On `/admin/broadcasts`, if there are fewer than 31 broadcast rows in the dev DB, first create
enough via the "Gửi broadcast mới" form to exceed 30 (or seed rows directly), then:
1. Confirm page 1 shows 30 rows.
2. Click page "2" in the table pagination footer.
3. Confirm the row set actually changes (different `id`/`created_at` values than page 1) — this
   is the exact bug being fixed; before this change, clicking page 2 left the same 30 rows on
   screen.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx
git commit -m "fix(admin): sửa phân trang Broadcast không đổi khi bấm sang trang khác"
```

---

## Phase 0 self-review checklist (for whoever executes this plan)

- Sidebar groups match spec §4 exactly (order, labels, group names) — diff `SIDEBAR` in
  `AdminLayout.tsx` against the spec if unsure.
- No icon is reused across two different sidebar leaves — cross-check the final `SIDEBAR` array's
  `icon` values are all distinct components.
- `useReasonConfirm` is not yet called anywhere — that's expected, Phase 2a wires it up.
- `AdminBroadcastsPage` pagination fix does not change `useAdminBroadcasts`'s signature or the
  backend endpoint — purely a frontend wiring fix.
