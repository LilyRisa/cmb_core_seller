import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { App as AntApp, Alert, Button, Card, Form, Input, Modal, Popconfirm, Radio, Space, Switch, Table, Tag, Typography } from 'antd';
import { CopyOutlined, DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { formatDate } from '@/lib/format';
import { useCan } from '@/lib/tenant';
import { useMessagingSettings } from '@/lib/messagingConfig';
import { PageMultiSelect, PageScopeTags } from '@/components/messaging/PageScope';
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
    // Kịch bản theo nền tảng: ?platform=zalo_oa ⇒ chỉ flow provider zalo_oa + tạo flow gắn provider đó.
    const [params] = useSearchParams();
    const platform = params.get('platform') ?? 'facebook_page';
    const canManage = useCan('messaging.rule.manage');
    const { data, isFetching } = useFlows(platform);
    const { data: settings } = useMessagingSettings();
    const fbAiAutoOn = settings?.auto_mode_facebook ?? false;
    const save = useSaveFlow();
    const del = useDeleteFlow();
    const dup = useDuplicateFlow();
    const [createOpen, setCreateOpen] = useState(false);
    const [form] = Form.useForm();
    const appliesAll = (Form.useWatch('applies_all_pages', form) as boolean | undefined) ?? true;

    const createFlow = () => form.validateFields().then((v: { name: string; trigger_type: FlowTriggerType; applies_all_pages?: boolean; channel_account_ids?: number[] }) => {
        save.mutate(
            {
                name: v.name,
                trigger_type: v.trigger_type,
                // Gắn provider theo nền tảng — FlowMatcher khớp flow theo provider của hội thoại,
                // nên flow Zalo PHẢI lưu provider=zalo_oa thì mới chạy cho hội thoại Zalo.
                provider: platform,
                applies_all_pages: !!v.applies_all_pages,
                channel_account_ids: v.applies_all_pages ? [] : (v.channel_account_ids ?? []),
                graph: { nodes: [{ id: 'trigger-1', type: 'trigger', position: { x: 280, y: 40 }, data: {} }], edges: [] },
            },
            {
                onSuccess: (flow) => { setCreateOpen(false); form.resetFields(); navigate(`/messaging/flows/${flow.id}/edit`); },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    });

    const columns: ColumnsType<AutomationFlow> = [
        { title: 'Tên', dataIndex: 'name', width: 220, render: (v: string, r) => (
            <Button type="link" style={{ padding: 0, maxWidth: '100%' }} onClick={() => navigate(`/messaging/flows/${r.id}/edit`)}>
                <Typography.Text ellipsis={{ tooltip: v }} style={{ color: 'inherit' }}>{v}</Typography.Text>
            </Button>
        ) },
        { title: 'Kích hoạt khi', dataIndex: 'trigger_type', width: 180, render: (t: FlowTriggerType) => TRIGGER_LABELS[t] ?? t },
        { title: 'Phạm vi trang', width: 200, render: (_, r) => (
            <PageScopeTags appliesAllPages={r.applies_all_pages} channelAccountIds={r.channel_account_ids} />
        ) },
        { title: 'Trạng thái', dataIndex: 'status', width: 130, render: (s: FlowStatus) => <Tag color={STATUS_COLOR[s]}>{STATUS_LABELS[s] ?? s}</Tag> },
        { title: 'Cập nhật', dataIndex: 'updated_at', width: 170, render: (v: string | null) => formatDate(v) },
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
            <Alert type="info" showIcon style={{ marginBottom: 12 }}
                message="Thứ tự ưu tiên"
                description={'Tin nhắn đầu tiên và tin chứa từ khoá được xử lý trước. Luồng "Mọi tin nhắn" chỉ bắt các tin còn lại. Riêng Facebook: luồng "Mọi tin nhắn" và AI tự động trả lời không thể cùng chạy.'} />
            {fbAiAutoOn && (
                <Alert type="warning" showIcon style={{ marginBottom: 12 }}
                    message="AI tự động Facebook đang bật"
                    description={'Xuất bản một luồng "Mọi tin nhắn" cho Facebook sẽ tự tắt AI tự động trả lời Facebook (chỉ một trong hai được chạy).'} />
            )}
            <Card>
                <Table<AutomationFlow> rowKey="id" size="middle" loading={isFetching} dataSource={data?.data ?? []} columns={columns} pagination={false} scroll={{ x: 'max-content' }} />
            </Card>

            <Modal open={createOpen} onCancel={() => setCreateOpen(false)} onOk={createFlow} confirmLoading={save.isPending}
                title="Tạo kịch bản mới" okText="Tạo & mở trình dựng" cancelText="Huỷ">
                <Form form={form} layout="vertical" initialValues={{ trigger_type: 'inbox_first_message', applies_all_pages: true, channel_account_ids: [] }}>
                    <Form.Item name="name" label="Tên kịch bản" rules={[{ required: true, message: 'Nhập tên kịch bản' }]}>
                        <Input placeholder="vd: Chào khách & tư vấn" maxLength={160} />
                    </Form.Item>
                    <Form.Item name="applies_all_pages" label="Áp dụng cho trang" valuePropName="checked"
                        extra="Bật = áp mọi trang. Tắt = chỉ các trang được chọn (ưu tiên hơn kịch bản 'tất cả trang'). Có thể đổi sau trong trình dựng.">
                        <Switch checkedChildren="Tất cả trang" unCheckedChildren="Chọn trang" />
                    </Form.Item>
                    {!appliesAll && (
                        <Form.Item name="channel_account_ids" label="Trang áp dụng"
                            rules={[{ required: true, type: 'array', min: 1, message: 'Chọn ít nhất 1 trang hoặc bật "Tất cả trang"' }]}>
                            <PageMultiSelect provider={platform} />
                        </Form.Item>
                    )}
                    <Form.Item name="trigger_type" label="Kích hoạt khi">
                        <Radio.Group>
                            <Space direction="vertical">
                                <Radio value="inbox_first_message">{TRIGGER_LABELS.inbox_first_message}</Radio>
                                <Radio value="inbox_keyword">{TRIGGER_LABELS.inbox_keyword}</Radio>
                                <Radio value="inbox_any">{TRIGGER_LABELS.inbox_any}</Radio>
                                {/* Zalo OA không có bình luận — chỉ Facebook có trigger comment. */}
                                {platform !== 'zalo_oa' && <Radio value="comment_any">{TRIGGER_LABELS.comment_any}</Radio>}
                            </Space>
                        </Radio.Group>
                    </Form.Item>
                    <Form.Item noStyle shouldUpdate={(p, c) => p.trigger_type !== c.trigger_type}>
                        {({ getFieldValue }) => getFieldValue('trigger_type') === 'inbox_any' && fbAiAutoOn && (
                            <Alert type="warning" showIcon
                                message='Khi xuất bản luồng "Mọi tin nhắn" này, AI tự động trả lời Facebook sẽ bị tắt (chỉ một trong hai được chạy).' />
                        )}
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
