import { useState } from 'react';
import { App, Button, Card, DatePicker, Form, Input, Popconfirm, Space, Switch, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { DeleteOutlined, EditOutlined, NotificationOutlined, PlusOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
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
 * cho user (1 lần/tab).
 */
export function AdminAnnouncementsPage() {
    const { message } = App.useApp();
    const { data, isFetching } = useAdminAnnouncements();
    const create = useCreateAnnouncement();
    const update = useUpdateAnnouncement();
    const remove = useDeleteAnnouncement();
    const [form] = Form.useForm<FormShape>();
    const [editingId, setEditingId] = useState<number | null>(null);
    const [bodyHtml, setBodyHtml] = useState('');
    const [editorKey, setEditorKey] = useState('new');

    const reset = () => {
        form.resetFields();
        setBodyHtml('');
        setEditingId(null);
        setEditorKey('new-' + Date.now());
    };

    const loadForEdit = (a: AdminAnnouncement) => {
        setEditingId(a.id);
        setBodyHtml(a.body_html);
        setEditorKey('edit-' + a.id);
        form.setFieldsValue({
            title: a.title,
            is_active: a.is_active,
            dismiss_label: a.dismiss_label,
            range: a.starts_at && a.ends_at ? [dayjs(a.starts_at), dayjs(a.ends_at)] : undefined,
        });
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
            onSuccess: () => { message.success(editingId ? 'Đã cập nhật.' : 'Đã tạo popup.'); reset(); },
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
                ? `${r.starts_at ? dayjs(r.starts_at).format('DD/MM HH:mm') : '—'} → ${r.ends_at ? dayjs(r.ends_at).format('DD/MM HH:mm') : '—'}`
                : 'Luôn hiện',
        },
        {
            title: '', key: 'actions', width: 120,
            render: (_, r) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => loadForEdit(r)} />
                    <Popconfirm title="Xoá popup này?" onConfirm={() => remove.mutate(r.id, { onSuccess: () => { message.success('Đã xoá.'); if (editingId === r.id) reset(); } })}>
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <>
            <PageHeader title="Popup thông báo" subtitle="Hiện popup giữa màn hình cho mọi user — fix bug, tạm dừng dịch vụ... (1 lần/tab trình duyệt)." />

            <Card
                title={editingId ? `Sửa popup #${editingId}` : 'Tạo popup mới'}
                style={{ marginBottom: 24 }}
                extra={editingId ? <Button size="small" icon={<PlusOutlined />} onClick={reset}>Tạo mới</Button> : null}
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
                        <Form.Item name="range" label="Lịch chiếu (tuỳ chọn — để trống = luôn hiện)">
                            <DatePicker.RangePicker showTime format="DD/MM/YYYY HH:mm" />
                        </Form.Item>
                    </Space>

                    <div>
                        <Button type="primary" htmlType="submit" loading={create.isPending || update.isPending} icon={<NotificationOutlined />}>
                            {editingId ? 'Cập nhật' : 'Tạo popup'}
                        </Button>
                        <Typography.Text type="secondary" style={{ marginLeft: 12 }}>
                            Popup hiện 1 lần mỗi tab; user mở tab mới sẽ thấy lại.
                        </Typography.Text>
                    </div>
                </Form>
            </Card>

            <Card title="Danh sách popup">
                <Table rowKey="id" size="small" columns={columns} dataSource={data?.data ?? []} loading={isFetching} pagination={{ pageSize: 20 }} />
            </Card>
        </>
    );
}
