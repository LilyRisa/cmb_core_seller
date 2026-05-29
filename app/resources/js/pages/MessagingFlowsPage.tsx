import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { App as AntApp, Button, Card, Form, Input, Modal, Popconfirm, Radio, Space, Table, Tag } from 'antd';
import { CopyOutlined, DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { MessagingNav } from '@/components/MessagingNav';
import {
    type AutomationFlow,
    type FlowStatus,
    type FlowTriggerType,
    STATUS_LABELS,
    TRIGGER_LABELS,
    useDeleteFlow,
    useDuplicateFlow,
    useFlows,
    useSaveFlow,
} from '@/lib/messagingFlows';

const STATUS_COLOR: Record<FlowStatus, string> = {
    draft: 'default',
    active: 'green',
    paused: 'orange',
    archived: 'default',
};

/** /messaging/flows — danh sách kịch bản tự động (Flow Builder S3). */
export function MessagingFlowsPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const canManage = useCan('messaging.rule.manage');
    const { data, isFetching } = useFlows();
    const save = useSaveFlow();
    const del = useDeleteFlow();
    const dup = useDuplicateFlow();
    const [createOpen, setCreateOpen] = useState(false);
    const [form] = Form.useForm();

    const createFlow = () => form.validateFields().then((v: { name: string; trigger_type: FlowTriggerType }) => {
        save.mutate(
            {
                name: v.name,
                trigger_type: v.trigger_type,
                graph: { nodes: [{ id: 'trigger-1', type: 'trigger', position: { x: 280, y: 40 }, data: {} }], edges: [] },
            },
            {
                onSuccess: (flow) => { setCreateOpen(false); form.resetFields(); navigate(`/messaging/flows/${flow.id}/edit`); },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    });

    const columns: ColumnsType<AutomationFlow> = [
        { title: 'Tên', dataIndex: 'name', render: (v: string, r) => (
            <Button type="link" style={{ padding: 0 }} onClick={() => navigate(`/messaging/flows/${r.id}/edit`)}>{v}</Button>
        ) },
        { title: 'Kích hoạt khi', dataIndex: 'trigger_type', width: 200, render: (t: FlowTriggerType) => TRIGGER_LABELS[t] ?? t },
        { title: 'Trạng thái', dataIndex: 'status', width: 130, render: (s: FlowStatus) => <Tag color={STATUS_COLOR[s]}>{STATUS_LABELS[s] ?? s}</Tag> },
        { title: 'Cập nhật', dataIndex: 'updated_at', width: 170, render: (v: string | null) => (v ? new Date(v).toLocaleString('vi-VN') : '—') },
        ...(canManage ? [{ title: '', width: 130, render: (_: unknown, r: AutomationFlow) => (
            <Space size={2}>
                <Button size="small" type="text" icon={<EditOutlined />} onClick={() => navigate(`/messaging/flows/${r.id}/edit`)} />
                <Button size="small" type="text" icon={<CopyOutlined />} loading={dup.isPending}
                    onClick={() => dup.mutate(r.id, { onSuccess: () => message.success('Đã nhân bản'), onError: (e) => message.error(errorMessage(e)) })} />
                <Popconfirm title="Xoá kịch bản?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }}
                    onConfirm={() => del.mutate(r.id, { onSuccess: () => message.success('Đã xoá'), onError: (e) => message.error(errorMessage(e)) })}>
                    <Button size="small" type="text" danger icon={<DeleteOutlined />} />
                </Popconfirm>
            </Space>
        ) }] : []),
    ];

    return (
        <div>
            <PageHeader title="Kịch bản tự động" subtitle="Dựng luồng trả lời tự động cho tin nhắn & bình luận Facebook bằng sơ đồ kéo-thả."
                extra={canManage && <Button type="primary" icon={<PlusOutlined />} onClick={() => { form.resetFields(); setCreateOpen(true); }}>Tạo kịch bản</Button>} />
            <MessagingNav />
            <Card>
                <Table<AutomationFlow> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns} pagination={false} />
            </Card>

            <Modal open={createOpen} onCancel={() => setCreateOpen(false)} onOk={createFlow} confirmLoading={save.isPending}
                title="Tạo kịch bản mới" okText="Tạo & mở trình dựng" cancelText="Huỷ">
                <Form form={form} layout="vertical" initialValues={{ trigger_type: 'inbox_first_message' }}>
                    <Form.Item name="name" label="Tên kịch bản" rules={[{ required: true, message: 'Nhập tên kịch bản' }]}>
                        <Input placeholder="vd: Chào khách & tư vấn" maxLength={160} />
                    </Form.Item>
                    <Form.Item name="trigger_type" label="Kích hoạt khi">
                        <Radio.Group>
                            <Space direction="vertical">
                                <Radio value="inbox_first_message">{TRIGGER_LABELS.inbox_first_message}</Radio>
                                <Radio value="inbox_keyword">{TRIGGER_LABELS.inbox_keyword}</Radio>
                                <Radio value="inbox_any">{TRIGGER_LABELS.inbox_any}</Radio>
                                <Radio value="comment_any">{TRIGGER_LABELS.comment_any}</Radio>
                            </Space>
                        </Radio.Group>
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
