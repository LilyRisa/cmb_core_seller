import { useRef, useState } from 'react';
import { App as AntApp, Button, Card, Form, Input, Modal, Popconfirm, Space, Switch, Table, Tag, Tooltip, Typography } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { type MessageTemplate, useDeleteTemplate, useSaveTemplate, useTemplates } from '@/lib/messagingConfig';
import { MessagingNav } from '@/components/MessagingNav';

/**
 * Danh sách shortcode được hỗ trợ bởi TemplateContextBuilder + TemplateResolver
 * (backend: app/Modules/Messaging/Services/TemplateContextBuilder.php).
 * order.* là best-effort từ conversation.meta['order'].
 */
export const TEMPLATE_SHORTCODES: { token: string; label: string }[] = [
    { token: '{{buyer.name}}',           label: 'Tên người mua (sàn)' },
    { token: '{{shop.name}}',            label: 'Tên shop / trang' },
    { token: '{{customer.name}}',        label: 'Tên khách hàng (CRM)' },
    { token: '{{customer.phone}}',       label: 'SĐT khách (đã mask)' },
    { token: '{{customer.reputation}}',  label: 'Đánh giá khách' },
    { token: '{{order.code}}',           label: 'Mã đơn hàng' },
    { token: '{{order.status}}',         label: 'Trạng thái đơn' },
    { token: '{{order.total}}',          label: 'Tổng tiền đơn' },
];

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
    const bodyRef = useRef<HTMLTextAreaElement | null>(null);

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

    /** Chèn token shortcode vào vị trí con trỏ trong textarea nội dung. */
    const insertShortcode = (token: string) => {
        const ta = bodyRef.current;
        const current: string = form.getFieldValue('body') ?? '';
        if (ta) {
            const start = ta.selectionStart ?? current.length;
            const end = ta.selectionEnd ?? current.length;
            const next = current.slice(0, start) + token + current.slice(end);
            form.setFieldValue('body', next);
            // Đặt lại con trỏ sau token (requestAnimationFrame để đảm bảo DOM đã cập nhật)
            requestAnimationFrame(() => {
                ta.focus();
                const pos = start + token.length;
                ta.setSelectionRange(pos, pos);
            });
        } else {
            form.setFieldValue('body', current + token);
        }
    };

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
                    <Form.Item
                        name="shortcut_key"
                        label="Phím tắt (slash command)"
                        extra="Gõ /phimtat trong ô soạn tin để chèn nhanh mẫu này."
                    >
                        <Input placeholder="vd: cam_on" maxLength={32} prefix="/" />
                    </Form.Item>
                    <Form.Item label="Nội dung" required style={{ marginBottom: 0 }}>
                        {/* Shortcode chips — click để chèn vào vị trí con trỏ */}
                        <div style={{ marginBottom: 8, display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                            {TEMPLATE_SHORTCODES.map((sc) => (
                                <Tooltip key={sc.token} title={sc.label}>
                                    <Tag
                                        style={{ cursor: 'pointer', fontFamily: 'monospace', fontSize: 12 }}
                                        color="blue"
                                        onClick={() => insertShortcode(sc.token)}
                                    >
                                        {sc.token}
                                    </Tag>
                                </Tooltip>
                            ))}
                        </div>
                        <Form.Item name="body" rules={[{ required: true, message: 'Vui lòng nhập nội dung' }]} style={{ marginBottom: 0 }}>
                            <Input.TextArea
                                rows={4}
                                placeholder="Cảm ơn {{customer.name}} đã đặt đơn {{order.code}}!"
                                ref={(node) => {
                                    // AntD Input.TextArea exposes resizableTextArea.textArea as the underlying <textarea>
                                    bodyRef.current = (node as unknown as { resizableTextArea?: { textArea?: HTMLTextAreaElement } })?.resizableTextArea?.textArea ?? null;
                                }}
                            />
                        </Form.Item>
                    </Form.Item>
                    <Form.Item name="enabled" label="Kích hoạt" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
