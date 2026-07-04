import { useState } from 'react';
import { App as AntApp, Button, Card, Form, Input, Modal, Popconfirm, Segmented, Space, Switch, Table, Tag, Typography } from 'antd';
import { ApiOutlined, DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    type MarketingAiProviderInput, type MarketingAiProviderRow,
    useDeleteMarketingAiProvider, useMarketingAiProviders, useSaveMarketingAiProvider,
} from '../../lib/marketingAiProviders';

const { Text, Paragraph } = Typography;

/** /admin/marketing-ai-providers — provider AI RIÊNG cho phân tích marketing (tách AI messaging). */
export function AdminMarketingAiProvidersPage() {
    const { message } = AntApp.useApp();
    const { data: rows, isLoading } = useMarketingAiProviders();
    const save = useSaveMarketingAiProvider();
    const del = useDeleteMarketingAiProvider();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<MarketingAiProviderRow | null>(null);
    const [form] = Form.useForm<MarketingAiProviderInput>();

    const openNew = () => { setEditing(null); form.resetFields(); form.setFieldsValue({ adapter: 'openai_compatible', is_active: true }); setOpen(true); };
    const openEdit = (r: MarketingAiProviderRow) => { setEditing(r); form.setFieldsValue({ code: r.code, display_name: r.display_name ?? undefined, adapter: r.adapter, base_url: r.base_url ?? undefined, default_model: r.default_model ?? undefined, is_active: r.is_active, api_key: '' }); setOpen(true); };

    const submit = async () => {
        const input = await form.validateFields();
        save.mutate({ input, isNew: editing === null }, {
            onSuccess: () => { setOpen(false); message.success('Đã lưu provider.'); },
            onError: (e) => message.error(errorMessage(e, 'Không lưu được.')),
        });
    };

    return (
        <div>
            <Card
                title={<><ApiOutlined /> Provider AI Marketing (phân tích quảng cáo)</>}
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={openNew}>Thêm provider</Button>}
            >
                <Paragraph type="secondary">
                    Provider AI <Text strong>riêng</Text> dùng cho dự báo/chiến lược quảng cáo — tách hoàn toàn với AI messaging.
                    Chỉ một provider <Text strong>đang dùng</Text> tại một thời điểm.
                </Paragraph>
                <Table<MarketingAiProviderRow>
                    rowKey="code"
                    loading={isLoading}
                    dataSource={rows ?? []}
                    pagination={false}
                    columns={[
                        { title: 'Code', dataIndex: 'code', key: 'code' },
                        { title: 'Tên', dataIndex: 'display_name', key: 'name', render: (v: string | null) => v ?? '—' },
                        { title: 'Adapter', dataIndex: 'adapter', key: 'adapter', render: (v: string) => <Tag>{v}</Tag> },
                        { title: 'Model', dataIndex: 'default_model', key: 'model', render: (v: string | null) => v ?? '—' },
                        { title: 'API key', dataIndex: 'has_key', key: 'key', render: (v: boolean) => v ? <Tag color="green">đã có</Tag> : <Tag>trống</Tag> },
                        { title: 'Đang dùng', dataIndex: 'is_active', key: 'active', render: (v: boolean) => v ? <Tag color="blue">active</Tag> : '—' },
                        {
                            title: '', key: 'actions', render: (_: unknown, r: MarketingAiProviderRow) => (
                                <Space>
                                    <Button size="small" onClick={() => openEdit(r)}>Sửa</Button>
                                    <Popconfirm title="Xoá provider?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }} onConfirm={() => del.mutate(r.code, { onSuccess: () => message.success('Đã xoá.') })}>
                                        <Button size="small" danger icon={<DeleteOutlined />} />
                                    </Popconfirm>
                                </Space>
                            ),
                        },
                    ]}
                />
            </Card>

            <Modal open={open} title={editing ? `Sửa ${editing.code}` : 'Thêm provider AI marketing'} onCancel={() => setOpen(false)} onOk={submit} confirmLoading={save.isPending} okText="Lưu" cancelText="Huỷ">
                <Form form={form} layout="vertical">
                    <Form.Item name="code" label="Code" rules={[{ required: true, pattern: /^[a-z0-9][a-z0-9_-]*$/, message: 'chữ thường/số/-/_' }]}>
                        <Input disabled={editing !== null} placeholder="forecast-openai" />
                    </Form.Item>
                    <Form.Item name="display_name" label="Tên hiển thị"><Input placeholder="Forecast GPT" /></Form.Item>
                    <Form.Item name="adapter" label="Adapter" rules={[{ required: true }]}>
                        <Segmented options={[{ label: 'OpenAI-compatible', value: 'openai_compatible' }, { label: 'Anthropic', value: 'anthropic' }, { label: 'Manual (stub)', value: 'manual' }]} />
                    </Form.Item>
                    <Form.Item name="api_key" label={editing ? 'API key (để trống = giữ nguyên)' : 'API key'}><Input placeholder="sk-..." /></Form.Item>
                    <Form.Item name="base_url" label="Base URL (tuỳ chọn)"><Input placeholder="https://api.openai.com/v1" /></Form.Item>
                    <Form.Item name="default_model" label="Model"><Input placeholder="gpt-4o-mini" /></Form.Item>
                    <Form.Item name="is_active" label="Đang dùng" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
