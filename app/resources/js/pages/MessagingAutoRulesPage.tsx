import { useState } from 'react';
import { App as AntApp, Button, Card, Form, Input, InputNumber, Modal, Popconfirm, Select, Space, Switch, Table, Tag } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { MessagingNav } from '@/components/MessagingNav';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { type AutoReplyRule, type RuleTrigger, useAutoRules, useDeleteRule, useSaveRule } from '@/lib/messagingConfig';

const TRIGGER_LABELS: Record<RuleTrigger, string> = {
    first_message: 'Chào lần đầu',
    schedule: 'Theo lịch (vắng mặt)',
    order_status: 'Theo trạng thái đơn',
    away_no_response: 'NV chưa trả lời',
};

/** /messaging/auto-rules — quản lý quy tắc trả lời tự động (SPEC-0024 §6.2). */
export function MessagingAutoRulesPage() {
    const { message } = AntApp.useApp();
    const canManage = useCan('messaging.rule.manage');
    const { data, isFetching } = useAutoRules();
    const save = useSaveRule();
    const del = useDeleteRule();
    const [editing, setEditing] = useState<AutoReplyRule | null>(null);
    const [open, setOpen] = useState(false);
    const [form] = Form.useForm();
    const trigger = Form.useWatch('trigger', form) as RuleTrigger | undefined;

    const openForm = (r?: AutoReplyRule) => {
        setEditing(r ?? null);
        form.setFieldsValue(r
            ? { ...r, raw_text: r.action?.raw_text, minutes: r.trigger_config?.minutes, order_status: r.trigger_config?.order_status }
            : { enabled: true, trigger: 'first_message', cooldown_seconds: 3600, priority: 100 });
        setOpen(true);
    };

    const submit = () => form.validateFields().then((v) => {
        const trigger_config: Record<string, unknown> =
            v.trigger === 'away_no_response' ? { minutes: v.minutes ?? 15 }
                : v.trigger === 'order_status' ? { order_status: v.order_status }
                    : v.trigger === 'schedule' ? { window: v.window, tz: 'Asia/Ho_Chi_Minh' }
                        : {};
        const payload = {
            ...(editing ? { id: editing.id } : {}),
            name: v.name, trigger: v.trigger, enabled: v.enabled, cooldown_seconds: v.cooldown_seconds, priority: v.priority,
            trigger_config, action: { kind: 'raw' as const, raw_text: v.raw_text },
        };
        save.mutate(payload, {
            onSuccess: () => { message.success('Đã lưu quy tắc'); setOpen(false); },
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    const columns: ColumnsType<AutoReplyRule> = [
        { title: 'Tên', dataIndex: 'name' },
        { title: 'Kích hoạt khi', dataIndex: 'trigger', width: 200, render: (t: RuleTrigger) => <Tag color="blue">{TRIGGER_LABELS[t]}</Tag> },
        { title: 'Nội dung', render: (_, r) => <span style={{ color: '#64748B' }}>{r.action?.raw_text ?? '—'}</span> },
        { title: 'Cooldown', dataIndex: 'cooldown_seconds', width: 110, render: (v) => `${Math.round(v / 60)} phút` },
        { title: 'Bật', dataIndex: 'enabled', width: 70, render: (v) => <Tag color={v ? 'green' : 'default'}>{v ? 'Bật' : 'Tắt'}</Tag> },
        ...(canManage ? [{ title: '', width: 90, render: (_: unknown, r: AutoReplyRule) => (
            <Space size={2}>
                <Button size="small" type="text" icon={<EditOutlined />} onClick={() => openForm(r)} />
                <Popconfirm title="Xoá quy tắc?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                    onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá'), onError: (e) => message.error(errorMessage(e)) })}>
                    <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                </Popconfirm>
            </Space>
        ) }] : []),
    ];

    return (
        <div>
            <PageHeader title="Trả lời tự động" subtitle="Chào mừng, trả lời theo lịch/vắng mặt, theo trạng thái đơn — chống spam bằng cooldown."
                extra={canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => openForm()}>Thêm quy tắc</Button>} />
            <MessagingNav />
            <Card>
                <Table<AutoReplyRule> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns} pagination={false} />
            </Card>

            <Modal open={open} onCancel={() => setOpen(false)} onOk={submit} confirmLoading={save.isPending}
                title={editing ? 'Sửa quy tắc' : 'Thêm quy tắc'} okText="Lưu" cancelText="Huỷ">
                <Form form={form} layout="vertical">
                    <Form.Item name="name" label="Tên" rules={[{ required: true }]}><Input /></Form.Item>
                    <Form.Item name="trigger" label="Kích hoạt khi" rules={[{ required: true }]}>
                        <Select options={Object.entries(TRIGGER_LABELS).map(([value, label]) => ({ value, label }))} />
                    </Form.Item>
                    {trigger === 'away_no_response' && (
                        <Form.Item name="minutes" label="Sau bao nhiêu phút NV chưa trả lời"><InputNumber min={1} max={1440} style={{ width: '100%' }} /></Form.Item>
                    )}
                    {trigger === 'order_status' && (
                        <Form.Item name="order_status" label="Trạng thái đơn" rules={[{ required: true }]}>
                            <Select options={['delivered', 'shipped', 'cancelled', 'processing'].map((s) => ({ value: s, label: s }))} />
                        </Form.Item>
                    )}
                    {trigger === 'schedule' && (
                        <Form.Item name="window" label="Khung giờ (vd 22:00-08:00)" rules={[{ required: true }]}><Input placeholder="22:00-08:00" /></Form.Item>
                    )}
                    <Form.Item name="raw_text" label="Nội dung trả lời" rules={[{ required: true }]}><Input.TextArea rows={3} /></Form.Item>
                    <Space size="large">
                        <Form.Item name="cooldown_seconds" label="Cooldown (giây)"><InputNumber min={0} max={86400} /></Form.Item>
                        <Form.Item name="priority" label="Ưu tiên"><InputNumber min={0} max={1000} /></Form.Item>
                        <Form.Item name="enabled" label="Bật" valuePropName="checked"><Switch /></Form.Item>
                    </Space>
                </Form>
            </Modal>
        </div>
    );
}
