# Admin Redesign — Phase 2c: Communications Group Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Retrofit the "TRUYỀN THÔNG" (Communications) sidebar group — Announcements (popup
thông báo), Desktop Backgrounds (hình nền Desktop), Notification Emails (email thông báo), and
Broadcast — to the Drawer edit/create convention established elsewhere in the admin panel
(`AdminUserFormDrawer.tsx`), per
`docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md` §5.2: *"Inline Card form
removed entirely"*. Delete actions in this group are Standard risk tier (§5.1 — no cross-cutting
impact) and stay/become `Popconfirm`, not `useReasonConfirm`.

**Architecture:** Pure frontend work in `app/resources/js/admin/pages/`. Three pages
(`AdminAnnouncementsPage.tsx`, `AdminDesktopBackgroundsPage.tsx`,
`AdminNotificationEmailsPage.tsx`) each have their inline `<Card>`-wrapped create/edit `<Form>`
extracted into an Ant Design `<Drawer>`, triggered by a "Thêm/Tạo mới" button placed where the
`Card`'s form used to sit — matching the trigger-button-above-table convention already used in
`AdminUsersPage.tsx` (`<Button onClick={() => setEditingAdmin('new')}>`) and the Drawer structural
shape in `AdminUserFormDrawer.tsx` (`<Drawer open title width={420} onClose destroyOnHidden>` +
`<Form layout="vertical">` inside). No new components, no new dependencies, no backend changes —
all mutation hooks (`useCreateAnnouncement`, `useUpdateDesktopBackground`,
`useTestAdminNotificationEmail`, etc.) are reused as-is from `admin/lib/*`. The fourth page in the
group, `AdminBroadcastsPage.tsx`, is a one-shot "compose and send" action rather than a
create/edit surface for a list item, so it is explicitly **not** converted — see Task 4, a
decision-record task with no code changes.

**Tech Stack:** React 18, Ant Design 5 (`Drawer`, `Form`, `Popconfirm`), TypeScript — no new
dependencies (`recharts`, TanStack Query, etc. are not touched by this phase).

## Global Constraints

- Admin-only: do not touch `resources/js/app.tsx` or any component shared with the tenant-facing
  app.
- User-facing strings are Vietnamese; code/identifiers are English (per `CLAUDE.md`).
- No visual/theme changes beyond the container type (Card → Drawer) — keep existing colors, Ant
  Design defaults, field layout/order, and every existing behavior (uploads, rich text editor,
  test-email action, date-range picker) working exactly as before, just inside a different
  container.
- Icons from `@ant-design/icons` only, never emoji (memory `ui-use-font-icons-not-emoji`).
- **No JS test runner exists in this repo** (`package.json` has no vitest/jest — see
  [[test-verify-baseline]]). Every frontend task's "test" step is:
  `npm run typecheck && npm run lint && npm run build` (run from `app/`) plus a manual
  browser-verification script with exact steps — there is no automated component test to write.
- Run all `npm run *` commands from `app/` (per `CLAUDE.md`: all Node/PHP commands run from
  `app/`, not the repo root).
- Phase 0 (`docs/superpowers/plans/2026-07-21-admin-redesign-phase0-foundation.md`) already fixed
  `AdminBroadcastsPage.tsx`'s pagination bug — do not redo that fix. This phase does not touch that
  file's pagination code at all (see Task 4).

---

### Task 1: `AdminAnnouncementsPage.tsx` — Card → Drawer

**Files:**
- Modify: `app/resources/js/admin/pages/AdminAnnouncementsPage.tsx` (currently 149 lines)

**Interfaces:**
- Consumes (all unchanged, already imported today): `@admin/lib/announcements`
  (`useAdminAnnouncements`, `useCreateAnnouncement`, `useUpdateAnnouncement`,
  `useDeleteAnnouncement`, `type AdminAnnouncement`), `@admin/components/RichTextEditor`
  (`RichTextEditor` — TipTap editor with image/video upload to R2, keyed by `editorKey` to force a
  clean remount between "new" and "edit #N" — this remount mechanism must be preserved unchanged),
  `@/components/PageHeader`, `@/lib/api` (`errorMessage`), `@/lib/format` (`formatDateShort`).
- Produces: nothing consumed elsewhere — leaf page.
- Confirmed by reading `app/app/Modules/Admin/Http/routes.php` lines 117-122: the announcement
  resource has `index`, `store`, `media` (image/video upload endpoint used internally by
  `RichTextEditor`), `update`, `destroy` — no other fields/endpoints exist beyond what the current
  form already uses (`title`, `body_html`, `is_active`, `dismiss_label`, `starts_at`/`ends_at`
  range). Nothing is being dropped.

**Design decision:** the current Card's header already carries the "Tạo mới" reset button when
editing. The Drawer trigger replaces that: a single "Tạo popup mới" button moves to
`PageHeader`'s `extra` slot (this page already renders a `PageHeader`, unlike the other two pages
in this group which get a toolbar button on the `Card` `extra` instead — see Tasks 2-3).

- [ ] **Step 1: Replace the whole file content**

Write the complete new `app/resources/js/admin/pages/AdminAnnouncementsPage.tsx`:

```tsx
import { useState } from 'react';
import { App, Button, Card, DatePicker, Drawer, Form, Input, Popconfirm, Space, Switch, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { DeleteOutlined, EditOutlined, NotificationOutlined, PlusOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { formatDateShort } from '@/lib/format';
import { RichTextEditor } from '@admin/components/RichTextEditor';
import {
    useAdminAnnouncements,
    useCreateAnnouncement,
    useDeleteAnnouncement,
    useUpdateAnnouncement,
    type AdminAnnouncement,
} from '@admin/lib/announcements';

interface FormShape {
    title: string;
    is_active: boolean;
    dismiss_label: string;
    range?: [Dayjs, Dayjs];
}

/**
 * SPEC 0037 — quản lý popup announcement toàn hệ thống. Tạo/sửa với editor TipTap
 * (chèn ảnh/video upload R2), bật/tắt, lịch chiếu tuỳ chọn. Hiển thị popup giữa màn hình
 * cho user (1 lần/tab). Form tạo/sửa dùng Drawer (redesign 2026-07-21 §5.2 — bỏ Card nội tuyến
 * làm form). Xoá popup vẫn dùng Popconfirm — tier Standard theo §5.1 (không ảnh hưởng chéo tenant
 * khác), không cần lý do.
 */
export function AdminAnnouncementsPage() {
    const { message } = App.useApp();
    const { data, isFetching } = useAdminAnnouncements();
    const create = useCreateAnnouncement();
    const update = useUpdateAnnouncement();
    const remove = useDeleteAnnouncement();
    const [form] = Form.useForm<FormShape>();
    const [open, setOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [bodyHtml, setBodyHtml] = useState('');
    const [editorKey, setEditorKey] = useState('new');

    const openCreate = () => {
        form.resetFields();
        setBodyHtml('');
        setEditingId(null);
        setEditorKey('new-' + Date.now());
        setOpen(true);
    };

    const openEdit = (a: AdminAnnouncement) => {
        setEditingId(a.id);
        setBodyHtml(a.body_html);
        setEditorKey('edit-' + a.id);
        form.setFieldsValue({
            title: a.title,
            is_active: a.is_active,
            dismiss_label: a.dismiss_label,
            range: a.starts_at && a.ends_at ? [dayjs(a.starts_at), dayjs(a.ends_at)] : undefined,
        });
        setOpen(true);
    };

    const submit = (v: FormShape) => {
        const input = {
            title: v.title,
            body_html: bodyHtml,
            is_active: v.is_active ?? false,
            dismiss_label: v.dismiss_label || 'Đã hiểu',
            starts_at: v.range?.[0]?.toISOString() ?? null,
            ends_at: v.range?.[1]?.toISOString() ?? null,
        };
        const opts = {
            onSuccess: () => { message.success(editingId ? 'Đã cập nhật.' : 'Đã tạo popup.'); setOpen(false); },
            onError: (e: unknown) => message.error(errorMessage(e, 'Lưu lỗi.')),
        };
        if (editingId) update.mutate({ id: editingId, ...input }, opts);
        else create.mutate(input, opts);
    };

    const columns: ColumnsType<AdminAnnouncement> = [
        { title: 'ID', dataIndex: 'id', width: 60 },
        { title: 'Tiêu đề', dataIndex: 'title' },
        {
            title: 'Trạng thái', dataIndex: 'is_active', width: 110,
            render: (v: boolean) => (v ? <Tag color="green">Đang bật</Tag> : <Tag>Tắt</Tag>),
        },
        {
            title: 'Lịch chiếu', key: 'window', width: 220,
            render: (_, r) => (r.starts_at || r.ends_at)
                ? `${formatDateShort(r.starts_at)} → ${formatDateShort(r.ends_at)}`
                : 'Luôn hiện',
        },
        {
            title: '', key: 'actions', width: 120,
            render: (_, r) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(r)} />
                    <Popconfirm
                        title="Xoá popup này?"
                        onConfirm={() => remove.mutate(r.id, {
                            onSuccess: () => { message.success('Đã xoá.'); if (editingId === r.id) setOpen(false); },
                        })}
                    >
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <>
            <PageHeader
                title="Popup thông báo"
                subtitle="Hiện popup giữa màn hình cho mọi user — fix bug, tạm dừng dịch vụ... (1 lần/tab trình duyệt)."
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Tạo popup mới</Button>}
            />

            <Card title="Danh sách popup">
                <Table rowKey="id" size="small" columns={columns} dataSource={data?.data ?? []} loading={isFetching} pagination={{ pageSize: 20 }} />
            </Card>

            <Drawer
                open={open}
                title={editingId ? `Sửa popup #${editingId}` : 'Tạo popup mới'}
                width={640}
                onClose={() => setOpen(false)}
                destroyOnHidden
            >
                <Form form={form} layout="vertical" initialValues={{ is_active: true, dismiss_label: 'Đã hiểu' }} onFinish={submit}>
                    <Form.Item name="title" label="Tiêu đề" rules={[{ required: true, max: 255 }]}>
                        <Input placeholder="VD: Thông báo bảo trì hệ thống" />
                    </Form.Item>

                    <Form.Item label="Nội dung" required>
                        <RichTextEditor key={editorKey} value={bodyHtml} onChange={setBodyHtml} />
                    </Form.Item>

                    <Space size="large" wrap align="start">
                        <Form.Item name="is_active" label="Bật hiển thị" valuePropName="checked">
                            <Switch />
                        </Form.Item>
                        <Form.Item name="dismiss_label" label="Nhãn nút xác nhận">
                            <Input style={{ width: 180 }} placeholder="Đã hiểu" />
                        </Form.Item>
                    </Space>

                    <Form.Item name="range" label="Lịch chiếu (tuỳ chọn — để trống = luôn hiện)">
                        <DatePicker.RangePicker showTime format="DD/MM/YYYY HH:mm" style={{ width: '100%' }} />
                    </Form.Item>

                    <Space>
                        <Button type="primary" htmlType="submit" loading={create.isPending || update.isPending} icon={<NotificationOutlined />}>
                            {editingId ? 'Cập nhật' : 'Tạo popup'}
                        </Button>
                        <Button onClick={() => setOpen(false)}>Huỷ</Button>
                    </Space>
                    <Typography.Paragraph type="secondary" style={{ marginTop: 12 }}>
                        Popup hiện 1 lần mỗi tab; user mở tab mới sẽ thấy lại.
                    </Typography.Paragraph>
                </Form>
            </Drawer>
        </>
    );
}
```

Note on `width={640}`: wider than `AdminUserFormDrawer`'s `420` because this form embeds
`RichTextEditor` (a toolbar + `min-height: 200px` content area) which is cramped at 420-480px —
match the wider width already used by other rich-content Drawers in the codebase if one exists at
implementation time (grep `width={6` under `admin/pages/` for precedent); 640 is otherwise a
reasonable default.

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: all three succeed with no new errors.

- [ ] **Step 3: Manual browser verification**

Start the dev stack (`composer dev` from `app/`), log into `/admin/login`, go to
"TRUYỀN THÔNG / Popup thông báo":
1. Confirm the page no longer shows an always-visible "Tạo popup mới" `Card` form above the table
   — only the "Danh sách popup" table and a "Tạo popup mới" button in the top-right header area.
2. Click "Tạo popup mới" — a Drawer slides in from the right with an empty form (title, rich-text
   editor, bật hiển thị switch, nhãn nút xác nhận, lịch chiếu range picker).
3. Type a title, type/format some text in the rich-text editor (bold, a bullet list), upload an
   image via the editor's image button, and submit. Confirm: success toast "Đã tạo popup.", Drawer
   closes, the new row appears in the table with the correct title and status tag.
4. Click the edit (pencil) icon on that row — Drawer reopens titled "Sửa popup #<id>", pre-filled
   with the exact title, rich-text content (including the image/formatting from step 3), switch
   state, and dismiss label. Change the title, submit. Confirm: success toast "Đã cập nhật.",
   Drawer closes, table row reflects the new title.
5. Click "Tạo popup mới" again right after editing — confirm the Drawer opens **empty** (not
   pre-filled with the previous edit's data — validates the `editorKey` remount + `form.resetFields`
   still work after the Card→Drawer change).
6. Click the delete (trash) icon on the test row, confirm the `Popconfirm` inline confirmation
   (not a Drawer, not a full modal with a reason field) appears, confirm — row disappears, no
   Drawer flashes open.
7. Open browser devtools console — confirm zero errors/warnings while performing steps 1-6.
8. Delete the test row created in this verification pass so the announcements list is left clean.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/AdminAnnouncementsPage.tsx
git commit -m "refactor(admin): chuyển form Popup thông báo từ Card sang Drawer"
```

---

### Task 2: `AdminDesktopBackgroundsPage.tsx` — Card → Drawer

**Files:**
- Modify: `app/resources/js/admin/pages/AdminDesktopBackgroundsPage.tsx` (currently 108 lines)

**Interfaces:**
- Consumes (all unchanged): `../lib/desktopBackgrounds`
  (`useAdminDesktopBackgrounds`, `useCreateDesktopBackground`, `useUpdateDesktopBackground`,
  `useDeleteDesktopBackground`, `uploadDesktopBackgroundMedia`, `type AdminDesktopBackground`).
- Confirmed by reading `app/app/Modules/Admin/Http/routes.php` lines 125-129: `index`, `store`,
  `media` (image upload), `update`, `destroy` — matches the current form's fields exactly (`name`,
  `image_url`/`image_path` via upload, `is_active`, `position`). Nothing dropped.

**Scope note beyond the pure Card→Drawer swap:** the current delete action uses
`modal.confirm({ title, onOk })` — a bare Ant Design imperative confirm with no reason field. This
is functionally the same risk tier as `Popconfirm` (Standard, §5.1 — no reason required) but is a
*third* ad-hoc confirmation implementation the design spec's problem statement (§1) calls out
("same class of risky action... confirmed 3-4 different ways"). Since this file is already being
touched for the Drawer conversion, and the spec explicitly says this page's delete action "stays
Popconfirm" (§5.2's file list + this plan's brief), converting `modal.confirm` → `Popconfirm` here
is folded into this task rather than deferred — it is the same one-line-of-intent change already
required, not scope creep.

- [ ] **Step 1: Replace the whole file content**

Write the complete new `app/resources/js/admin/pages/AdminDesktopBackgroundsPage.tsx`:

```tsx
// SPEC 0039 — quản lý thư viện hình nền màn Desktop (giao diện v2). Form tạo/sửa dùng Drawer
// (redesign 2026-07-21 §5.2 — bỏ Card nội tuyến làm form). Xoá dùng Popconfirm — tier Standard
// theo §5.1 (trước đây modal.confirm không lý do, cùng tier, gộp thống nhất về Popconfirm).
import { useState } from 'react';
import { App, Button, Card, Drawer, Form, Image, Input, InputNumber, Popconfirm, Space, Switch, Table, Tag, Upload } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined, UploadOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import {
    type AdminDesktopBackground,
    useAdminDesktopBackgrounds,
    useCreateDesktopBackground,
    useUpdateDesktopBackground,
    useDeleteDesktopBackground,
    uploadDesktopBackgroundMedia,
} from '../lib/desktopBackgrounds';

interface FormShape {
    name: string;
    is_active: boolean;
    position: number;
}

export function AdminDesktopBackgroundsPage() {
    const { data: rows = [], isLoading } = useAdminDesktopBackgrounds();
    const create = useCreateDesktopBackground();
    const update = useUpdateDesktopBackground();
    const remove = useDeleteDesktopBackground();
    const { message } = App.useApp();
    const [form] = Form.useForm<FormShape>();
    const [open, setOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [image, setImage] = useState<{ url: string; path: string } | null>(null);
    const [uploading, setUploading] = useState(false);

    const openCreate = () => {
        form.resetFields();
        setEditingId(null);
        setImage(null);
        setOpen(true);
    };

    const openEdit = (bg: AdminDesktopBackground) => {
        setEditingId(bg.id);
        setImage({ url: bg.image_url, path: bg.image_path });
        form.setFieldsValue({ name: bg.name, is_active: bg.is_active, position: bg.position });
        setOpen(true);
    };

    const submit = (v: FormShape) => {
        if (!image) { message.error('Vui lòng tải ảnh nền lên.'); return; }
        const input = { name: v.name, image_url: image.url, image_path: image.path, is_active: v.is_active, position: v.position };
        const opts = { onSuccess: () => { message.success('Đã lưu hình nền.'); setOpen(false); }, onError: () => message.error('Lưu thất bại.') };
        if (editingId) update.mutate({ id: editingId, ...input }, opts);
        else create.mutate(input, opts);
    };

    const columns: ColumnsType<AdminDesktopBackground> = [
        { title: 'Ảnh', dataIndex: 'image_url', width: 120, render: (url: string) => <Image src={url} width={96} height={54} style={{ objectFit: 'cover', borderRadius: 6 }} /> },
        { title: 'Tên', dataIndex: 'name' },
        { title: 'Thứ tự', dataIndex: 'position', width: 90 },
        { title: 'Trạng thái', dataIndex: 'is_active', width: 120, render: (a: boolean) => <Tag color={a ? 'green' : 'default'}>{a ? 'Đang bật' : 'Tắt'}</Tag> },
        {
            title: 'Thao tác', width: 140, render: (_, bg) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(bg)} />
                    <Popconfirm
                        title={`Xoá hình nền "${bg.name}"?`}
                        onConfirm={() => remove.mutate(bg.id, { onSuccess: () => message.success('Đã xoá.') })}
                    >
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Card
                title="Thư viện hình nền" size="small"
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Thêm hình nền</Button>}
            >
                <Table rowKey="id" size="small" loading={isLoading} columns={columns} dataSource={rows} pagination={false} />
            </Card>

            <Drawer
                open={open}
                title={editingId ? 'Sửa hình nền' : 'Thêm hình nền'}
                width={420}
                onClose={() => setOpen(false)}
                destroyOnHidden
            >
                <Form form={form} layout="vertical" initialValues={{ is_active: true, position: 0 }} onFinish={submit}>
                    <Form.Item label="Ảnh nền" required>
                        <Upload
                            accept="image/*" listType="picture-card" maxCount={1} showUploadList={false}
                            customRequest={async ({ file, onSuccess, onError }) => {
                                setUploading(true);
                                try { setImage(await uploadDesktopBackgroundMedia(file as File)); onSuccess?.({}); }
                                catch (e) { message.error('Tải ảnh thất bại.'); onError?.(e as Error); }
                                finally { setUploading(false); }
                            }}
                        >
                            {image
                                ? <img src={image.url} alt="nền" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                                : <div>{uploading ? '...' : <><UploadOutlined /> <div>Tải lên</div></>}</div>}
                        </Upload>
                    </Form.Item>
                    <Form.Item name="name" label="Tên" rules={[{ required: true, max: 120 }]}>
                        <Input placeholder="VD: Biển xanh" />
                    </Form.Item>
                    <Form.Item name="position" label="Thứ tự hiển thị">
                        <InputNumber min={0} max={9999} style={{ width: 140 }} />
                    </Form.Item>
                    <Form.Item name="is_active" label="Bật cho người dùng chọn" valuePropName="checked">
                        <Switch />
                    </Form.Item>
                    <Space>
                        <Button type="primary" htmlType="submit" icon={<PlusOutlined />} loading={create.isPending || update.isPending}>
                            {editingId ? 'Cập nhật' : 'Thêm'}
                        </Button>
                        <Button onClick={() => setOpen(false)}>Huỷ</Button>
                    </Space>
                </Form>
            </Drawer>
        </Space>
    );
}
```

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: all three succeed. Watch specifically for an "unused variable `modal`" lint error if any
leftover `const { message, modal } = App.useApp();` destructure survives — the rewrite above
destructures only `message`.

- [ ] **Step 3: Manual browser verification**

On "TRUYỀN THÔNG / Hình nền Desktop":
1. Confirm the page shows one `Card` ("Thư viện hình nền") with a table and a "Thêm hình nền"
   button in its header — no always-visible upload form above the table.
2. Click "Thêm hình nền" — Drawer opens empty (no image preview, empty name, position 0, switch
   on).
3. Upload an image (the upload tile shows "..." while uploading, then the preview), fill in a
   name, submit. Confirm: success toast "Đã lưu hình nền.", Drawer closes, new row appears with
   the thumbnail, name, and position.
4. Click the edit icon on that row — Drawer reopens with the same image thumbnail pre-loaded, name
   and position pre-filled. Change the name, submit. Confirm the table row's name updates and the
   thumbnail is unchanged (upload state carried over correctly from `bg.image_url`/`image_path`).
5. Click the delete icon on the test row — confirm a `Popconfirm` (small inline popover, not a
   full `Modal.confirm` dialog box) appears with the title `Xoá hình nền "<name>"?`, confirm — row
   disappears.
6. Open browser devtools console — confirm zero errors/warnings through steps 1-5.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/AdminDesktopBackgroundsPage.tsx
git commit -m "refactor(admin): chuyển form Hình nền Desktop từ Card sang Drawer, xoá dùng Popconfirm"
```

---

### Task 3: `AdminNotificationEmailsPage.tsx` — Card → Drawer, preserve the "gửi test" action

**Files:**
- Modify: `app/resources/js/admin/pages/AdminNotificationEmailsPage.tsx` (currently 120 lines)

**Interfaces:**
- Consumes (all unchanged): `../lib/adminNotificationEmails` (`useAdminNotificationEmails`,
  `useAdminNotificationTypes`, `useCreateAdminNotificationEmail`,
  `useUpdateAdminNotificationEmail`, `useDeleteAdminNotificationEmail`,
  `useTestAdminNotificationEmail`, `type AdminNotificationEmail`).
- Confirmed by reading `app/app/Modules/Admin/Http/routes.php` lines 161-171: `index`, `types`,
  `store`, `update`, `destroy`, and `POST notification-emails/{id}/test` — the test route only
  makes sense against an **already-persisted row** (it sends a real test email to `r.email` as
  currently saved), not against in-progress unsaved Drawer form state.

**Design decision on the "test" action:** today the test button lives as an icon button in the
table's per-row "Thao tác" column (`<Button icon={<MailOutlined />} onClick={() =>
test.mutate(r.id, ...)} />`), fully independent from the Card form. Converting the form to a
Drawer does not touch this at all — **the test button stays exactly where it is, in the table
row**, because:
1. It operates on the persisted record (`r.id`, `r.email`), not on unsaved Drawer form fields — it
   is not part of "create/edit a record" the way §5.2 scopes Drawers, it's a standalone read-mostly
   action on an existing row (same category as "test connectivity" buttons elsewhere in the admin
   panel per §5.4, not a form field).
2. Moving it into the edit-Drawer would only be reachable while editing that specific row, adding
   a click (open Drawer → find test button) for something that today is a single click from the
   list — a regression, not an improvement.

No behavior change to this action is needed; this task only removes the inline Card and its form,
replacing it with a Drawer, and (matching Task 2's reasoning) converts the `modal.confirm` delete
to `Popconfirm`.

- [ ] **Step 1: Replace the whole file content**

Write the complete new `app/resources/js/admin/pages/AdminNotificationEmailsPage.tsx`:

```tsx
// SPEC 2026-07-15 — quản lý email nhận thông báo admin (CSKH mới, user xác minh email...).
// Form tạo/sửa dùng Drawer (redesign 2026-07-21 §5.2 — bỏ Card nội tuyến làm form). Xoá dùng
// Popconfirm — tier Standard theo §5.1 (trước đây modal.confirm không lý do, cùng tier, gộp
// thống nhất về Popconfirm). Nút "Gửi email test" GIỮ NGUYÊN trên từng dòng bảng — thao tác trên
// bản ghi đã lưu, độc lập với form tạo/sửa, không chuyển vào Drawer (xem lý do trong plan).
import { useState } from 'react';
import { App, Button, Card, Checkbox, Drawer, Form, Input, Popconfirm, Space, Switch, Table, Tag } from 'antd';
import { DeleteOutlined, EditOutlined, MailOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import {
    type AdminNotificationEmail,
    useAdminNotificationEmails,
    useAdminNotificationTypes,
    useCreateAdminNotificationEmail,
    useUpdateAdminNotificationEmail,
    useDeleteAdminNotificationEmail,
    useTestAdminNotificationEmail,
} from '../lib/adminNotificationEmails';

interface FormShape {
    email: string;
    label?: string;
    is_active: boolean;
    notification_types: string[];
}

export function AdminNotificationEmailsPage() {
    const { data: rows = [], isLoading } = useAdminNotificationEmails();
    const { data: types = [] } = useAdminNotificationTypes();
    const create = useCreateAdminNotificationEmail();
    const update = useUpdateAdminNotificationEmail();
    const remove = useDeleteAdminNotificationEmail();
    const test = useTestAdminNotificationEmail();
    const { message } = App.useApp();
    const [form] = Form.useForm<FormShape>();
    const [open, setOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const openCreate = () => {
        form.resetFields();
        setEditingId(null);
        setOpen(true);
    };

    const openEdit = (r: AdminNotificationEmail) => {
        setEditingId(r.id);
        form.setFieldsValue({
            email: r.email, label: r.label ?? undefined, is_active: r.is_active,
            notification_types: r.notification_types,
        });
        setOpen(true);
    };

    const submit = (v: FormShape) => {
        const input = { email: v.email, label: v.label ?? null, is_active: v.is_active, notification_types: v.notification_types };
        const opts = { onSuccess: () => { message.success('Đã lưu.'); setOpen(false); }, onError: () => message.error('Lưu thất bại.') };
        if (editingId) update.mutate({ id: editingId, ...input }, opts);
        else create.mutate(input, opts);
    };

    const columns: ColumnsType<AdminNotificationEmail> = [
        { title: 'Email', dataIndex: 'email' },
        { title: 'Nhãn', dataIndex: 'label', render: (l: string | null) => l ?? '—' },
        {
            title: 'Nhận thông báo', dataIndex: 'notification_types',
            render: (codes: string[]) => (
                <Space size={4} wrap>
                    {codes.length === 0 && <Tag>Chưa chọn</Tag>}
                    {codes.map((c) => <Tag key={c} color="blue">{types.find((t) => t.code === c)?.label ?? c}</Tag>)}
                </Space>
            ),
        },
        { title: 'Trạng thái', dataIndex: 'is_active', width: 110, render: (a: boolean) => <Tag color={a ? 'green' : 'default'}>{a ? 'Đang bật' : 'Tắt'}</Tag> },
        {
            title: 'Thao tác', width: 170, render: (_, r) => (
                <Space>
                    <Button
                        size="small" icon={<MailOutlined />} loading={test.isPending}
                        onClick={() => test.mutate(r.id, {
                            onSuccess: () => message.success(`Đã gửi email test tới ${r.email}.`),
                            onError: () => message.error('Gửi test thất bại.'),
                        })}
                    />
                    <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(r)} />
                    <Popconfirm
                        title={`Xoá email "${r.email}"?`}
                        onConfirm={() => remove.mutate(r.id, { onSuccess: () => message.success('Đã xoá.') })}
                    >
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Card
                title="Danh sách email nhận thông báo" size="small"
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Thêm email</Button>}
            >
                <Table rowKey="id" size="small" loading={isLoading} columns={columns} dataSource={rows} pagination={false} />
            </Card>

            <Drawer
                open={open}
                title={editingId ? 'Sửa email nhận thông báo' : 'Thêm email nhận thông báo'}
                width={480}
                onClose={() => setOpen(false)}
                destroyOnHidden
            >
                <Form form={form} layout="vertical" initialValues={{ is_active: true, notification_types: [] }} onFinish={submit}>
                    <Form.Item name="email" label="Email" rules={[{ required: true, type: 'email', max: 255 }]}>
                        <Input placeholder="admin@cmbcoreseller.com" />
                    </Form.Item>
                    <Form.Item name="label" label="Nhãn (tuỳ chọn)">
                        <Input placeholder="VD: Đội vận hành" maxLength={120} />
                    </Form.Item>
                    <Form.Item
                        name="notification_types" label="Loại thông báo nhận"
                        rules={[{ required: true, message: 'Chọn ít nhất 1 loại thông báo.' }]}
                    >
                        <Checkbox.Group options={types.map((t) => ({ label: t.label, value: t.code }))} />
                    </Form.Item>
                    <Form.Item name="is_active" label="Bật nhận thông báo" valuePropName="checked">
                        <Switch />
                    </Form.Item>
                    <Space>
                        <Button type="primary" htmlType="submit" icon={<PlusOutlined />} loading={create.isPending || update.isPending}>
                            {editingId ? 'Cập nhật' : 'Thêm'}
                        </Button>
                        <Button onClick={() => setOpen(false)}>Huỷ</Button>
                    </Space>
                </Form>
            </Drawer>
        </Space>
    );
}
```

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: all three succeed with no new errors.

- [ ] **Step 3: Manual browser verification**

On "HỆ THỐNG / Email thông báo":
1. Confirm the page shows one `Card` ("Danh sách email nhận thông báo") with a table and a
   "Thêm email" button in its header — no always-visible form above the table.
2. Confirm each existing row still shows 3 action icons: mail (test), pencil (edit), trash
   (delete) — same set as before, same order.
3. Click "Thêm email" — Drawer opens empty (email, nhãn, notification-type checkboxes, switch on).
4. Fill email + at least one notification type checkbox, submit. Confirm: success toast "Đã lưu.",
   Drawer closes, new row appears with the correct email and type tags.
5. Click the mail icon on that new row — confirm it sends the test email exactly as before
   (success toast "Đã gửi email test tới <email>.") **without opening the Drawer** — this proves
   the test action was not accidentally folded into the edit surface.
6. Click the edit (pencil) icon on the row — Drawer opens pre-filled with the email, label,
   checked notification types, and switch state. Change the label, submit. Confirm the table row's
   label updates.
7. Click the delete icon on the test row — confirm a `Popconfirm` (inline popover, not a
   `Modal.confirm` dialog) appears with title `Xoá email "<email>"?`, confirm — row disappears.
8. Open browser devtools console — confirm zero errors/warnings through steps 1-7.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/AdminNotificationEmailsPage.tsx
git commit -m "refactor(admin): chuyển form Email thông báo từ Card sang Drawer, xoá dùng Popconfirm"
```

---

### Task 4: `AdminBroadcastsPage.tsx` — decision record, no code changes

**Files:** none — this task makes no code changes. It exists to record, and have an implementer
explicitly re-verify, why this page is excluded from the Card→Drawer conversion applied to the
other three pages in this group.

**Reasoning (re-verify by reading `app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx`
before proceeding to Task 1-3, or after — order doesn't matter since no edits happen here):**

The design spec's own rule for when Drawer applies (§5.2) is: *"Becomes the default for anything
with more than ~3 fields **or that also displays existing record detail**"* — i.e. Drawer is for
editing/creating an item that belongs to a list (an announcement, a background, a notification
email — each is a row you can revisit and change). `AdminBroadcastsPage.tsx`'s form (lines 41-88
of the current file) is different in kind: it is a single fire-and-forget action — "compose an
email and send it now" — with no persisted draft state, no "edit an existing broadcast" affordance
(broadcasts, once sent, are immutable history rows in the "Lịch sử broadcast" table below), and no
detail view to display alongside the form. Putting a one-shot send action behind a Drawer would
add a click (open Drawer → fill form → send) to reach a form that today is directly visible and
usable the moment the page loads — for a page whose whole job is "compose quickly, send," that is
worse UX, not better, and does not match what the spec's Drawer criterion is actually targeting.

`AdminBroadcastsPage.tsx`'s pagination bug (`Table`'s `pagination` prop missing `current`/
`onChange`) was already fixed in Phase 0 Task 3 (commit message
`fix(admin): sửa phân trang Broadcast không đổi khi bấm sang trang khác`) — re-reading the current
file, the pagination block already has `current={page}` / `onChange={setPage}` wired, confirming
Phase 0 landed. No pagination work remains here.

No other part of this page matches any of spec §5's other standardization rules in a way that
requires a change: it has no delete/suspend/disable action (nothing to risk-tier), its one filter
is the audience `Radio.Group` (already the spec's target pattern, not `Segmented` — nothing to
convert), and it has no empty-table special-case beyond the default (a `Table` with zero broadcast
rows renders the Ant Design default "No data", which is outside this phase's explicit scope — the
§5.5 empty-state pass is Phase 2d's "Support Requests / Audit Logs" batch and Phase 0's
`Empty`/loading convention only lists other files, not this one).

- [ ] **Step 1: Confirm the reasoning holds**

Read `app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx` in full. Confirm:
1. The compose form (subject, audience radio group, optional tenant picker, markdown body,
   "Gửi broadcast" submit button) has no "edit an existing broadcast" path — the table below is
   read-only history, no row click/edit action exists.
2. The `Table`'s `pagination` prop already includes `current`/`onChange` (Phase 0's fix) —
   confirms no regression and no leftover Phase-0 work.

If either check fails (e.g. Phase 0's fix was reverted, or a "duplicate this broadcast" / "edit
draft" feature was added to this page since this plan was written), stop and re-scope: that would
change the applicability of the "one-shot action" reasoning above, and the page would need its own
proper Task 1-3-style conversion instead of this no-op record.

- [ ] **Step 2: No implementation, no verification script, no commit**

This task intentionally ends here. Do not create a Drawer for this page, do not touch
`AdminBroadcastsPage.tsx`, and do not run `git commit` for this task — there is nothing to commit.

---

## Phase 2c self-review checklist

- All three converted pages (`AdminAnnouncementsPage.tsx`, `AdminDesktopBackgroundsPage.tsx`,
  `AdminNotificationEmailsPage.tsx`) have **zero** remaining `<Card>` wrapping a `<Form>` for
  create/edit — grep each file for `Card` + `Form` co-occurrence if unsure; the only `<Card>` left
  in each should wrap the list `<Table>`.
- Every field that existed in the old inline Card form still exists in the new Drawer form, with
  the same `name`, `rules`, and default/initial value — cross-check each Task's "before" (this
  plan's Required Reading section / the original file listings above) against its "after".
  Nothing was silently dropped in the Card→Drawer move.
  - Announcements: `title`, rich-text `body_html`, `is_active`, `dismiss_label`, `range`
    (`starts_at`/`ends_at`).
  - Desktop Backgrounds: image upload (`image_url`/`image_path`), `name`, `position`, `is_active`.
  - Notification Emails: `email`, `label`, `notification_types`, `is_active`, plus the row-level
    "gửi test" action untouched.
- All three pages' delete actions are `Popconfirm` — none use `useReasonConfirm` (Standard tier
  per spec §5.1) and none use a bare `Modal.confirm` anymore (Desktop Backgrounds and Notification
  Emails were converted from `modal.confirm` to `Popconfirm` as part of this phase — verify the
  `App.useApp()` destructure in both files no longer pulls `modal` unused).
- `AdminNotificationEmailsPage.tsx`'s "gửi email test" button still lives in the table row, still
  fires independently of the Drawer, and the Drawer does not gain a redundant test button.
- `AdminBroadcastsPage.tsx` has **no diff** in this phase — confirm with
  `git diff --stat app/resources/js/admin/pages/tenants/AdminBroadcastsPage.tsx` showing no output
  once Tasks 1-3 are committed (or confirm no `git add` for this file ever ran).
- No `SIDEBAR` entry, route path, or backend controller/route changed anywhere in this phase — this
  is a pure container-swap pass on 3 files, exactly as scoped.
