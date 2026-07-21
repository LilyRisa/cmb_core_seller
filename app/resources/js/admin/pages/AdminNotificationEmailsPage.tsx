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
