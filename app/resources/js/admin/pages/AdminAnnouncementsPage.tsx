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
