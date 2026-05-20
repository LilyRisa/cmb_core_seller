import { useState } from 'react';
import { App as AntApp, Button, Card, Form, Input, Modal, Popconfirm, Space, Switch, Table, Tag, Typography } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { type MessageTemplate, useDeleteTemplate, useSaveTemplate, useTemplates } from '@/lib/messagingConfig';
import { MessagingNav } from '@/components/MessagingNav';

/** /messaging/templates — quản lý mẫu tin trả lời nhanh (SPEC-0024 §6.2). */
export function MessagingTemplatesPage() {
    const { message } = AntApp.useApp();
    const canManage = useCan('messaging.template.manage');
    const { data, isFetching } = useTemplates();
    const save = useSaveTemplate();
    const del = useDeleteTemplate();
    const [editing, setEditing] = useState<MessageTemplate | null>(null);
    const [open, setOpen] = useState(false);
    const [form] = Form.useForm();

    const openForm = (t?: MessageTemplate) => {
        setEditing(t ?? null);
        form.setFieldsValue(t ?? { enabled: true, code: '', name: '', body: '' });
        setOpen(true);
    };

    const submit = () => form.validateFields().then((v) => {
        save.mutate({ ...(editing ? { id: editing.id } : {}), ...v }, {
            onSuccess: () => { message.success('Đã lưu mẫu tin'); setOpen(false); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    const columns: ColumnsType<MessageTemplate> = [
        { title: 'Mã', dataIndex: 'code', width: 160, render: (v) => <Typography.Text code>{v}</Typography.Text> },
        { title: 'Tên', dataIndex: 'name' },
        { title: 'Nội dung', dataIndex: 'body', ellipsis: true, render: (v) => <Typography.Text type="secondary">{v}</Typography.Text> },
        { title: 'Biến', dataIndex: 'vars', width: 200, render: (vars: string[]) => (vars ?? []).map((x) => <Tag key={x}>{`{{${x}}}`}</Tag>) },
        { title: 'Bật', dataIndex: 'enabled', width: 80, render: (v) => <Tag color={v ? 'green' : 'default'}>{v ? 'Bật' : 'Tắt'}</Tag> },
        ...(canManage ? [{ title: '', width: 90, render: (_: unknown, r: MessageTemplate) => (
            <Space size={2}>
                <Button size="small" type="text" icon={<EditOutlined />} onClick={() => openForm(r)} />
                <Popconfirm title="Xoá mẫu tin?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                    onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá'), onError: (e) => message.error(errorMessage(e)) })}>
                    <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                </Popconfirm>
            </Space>
        ) }] : []),
    ];

    return (
        <div>
            <PageHeader title="Mẫu tin nhắn" subtitle="Soạn sẵn câu trả lời nhanh, hỗ trợ biến {{customer.name}}, {{order.code}}…"
                extra={canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => openForm()}>Thêm mẫu</Button>} />
            <MessagingNav />
            <Card>
                <Table<MessageTemplate> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns} pagination={false} />
            </Card>

            <Modal open={open} onCancel={() => setOpen(false)} onOk={submit} confirmLoading={save.isPending}
                title={editing ? `Sửa mẫu — ${editing.code}` : 'Thêm mẫu tin'} okText="Lưu" cancelText="Huỷ">
                <Form form={form} layout="vertical">
                    <Form.Item name="code" label="Mã (slug)" rules={[{ required: true, pattern: /^[a-z0-9_-]+$/, message: 'Chỉ chữ thường, số, _ -' }]}>
                        <Input placeholder="vd: cam_on" disabled={!!editing} />
                    </Form.Item>
                    <Form.Item name="name" label="Tên" rules={[{ required: true }]}><Input /></Form.Item>
                    <Form.Item name="body" label="Nội dung" rules={[{ required: true }]}>
                        <Input.TextArea rows={4} placeholder="Cảm ơn {{customer.name}} đã đặt đơn {{order.code}}!" />
                    </Form.Item>
                    <Form.Item name="enabled" label="Kích hoạt" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
