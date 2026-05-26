// /admin/ai-providers — super-admin thêm/sửa/bật-tắt/test nhà cung cấp AI.
// Adapter động: anthropic | openai_compatible | manual. Nhiều instance cùng adapter
// (DeepSeek/Qwen/OpenRouter/Gemini đều openai_compatible, khác base_url/key/model).

import { useState } from 'react';
import { App, Button, Card, Form, Input, InputNumber, Modal, Radio, Select, Space, Switch, Table, Tag } from 'antd';
import { ApiOutlined, PlusOutlined, ReloadOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    useAiProviders, useCreateAiProvider, useUpdateAiProvider, useDisableAiProvider, useTestAiProvider,
    type AiProviderRow, type AiAdapter, type AiPreset, type CustomHttpConfig,
} from '../../lib/aiProviders';

const ADAPTER_LABEL: Record<AiAdapter, string> = {
    anthropic: 'Anthropic (Claude)',
    openai_compatible: 'OpenAI-compatible (GPT/DeepSeek/Qwen/OpenRouter/Gemini)',
    custom_http: 'Tùy chỉnh (HTTP)',
    manual: 'Manual (test/dev)',
};

const ADAPTERS: AiAdapter[] = ['anthropic', 'openai_compatible', 'custom_http', 'manual'];

export function AdminAiProvidersPage() {
    const { data, isLoading, refetch } = useAiProviders();
    const create = useCreateAiProvider();
    const update = useUpdateAiProvider();
    const disable = useDisableAiProvider();
    const test = useTestAiProvider();
    const { message, modal } = App.useApp();
    const [form] = Form.useForm();
    const [editing, setEditing] = useState<AiProviderRow | null>(null);
    const [open, setOpen] = useState(false);

    const adapters = data?.adapters ?? [];
    const presetsFor = (a: AiAdapter): AiPreset[] => adapters.find((x) => x.adapter === a)?.presets ?? [];

    const openCreate = () => {
        setEditing(null);
        form.resetFields();
        form.setFieldsValue({ adapter: 'openai_compatible', is_active: true, sort_order: 0 });
        setOpen(true);
    };
    const openEdit = (row: AiProviderRow) => {
        setEditing(row);
        form.setFieldsValue({
            ...row,
            api_key: '',
            headers_json: row.adapter_config?.headers ? JSON.stringify(row.adapter_config.headers, null, 2) : '',
        });
        setOpen(true);
    };

    const applyPreset = (p: AiPreset) =>
        form.setFieldsValue({ base_url: p.base_url ?? '', default_model: p.default_model ?? '', display_name: p.name });

    const submit = async () => {
        const v = await form.validateFields();
        const onErr = (e: unknown) => message.error(errorMessage(e));

        // adapter_config chỉ áp dụng cho custom_http; headers nhập dạng JSON text → parse.
        const headersJson = v.headers_json as string | undefined;
        delete v.headers_json;
        const adapter = (editing?.adapter ?? v.adapter) as AiAdapter;
        if (adapter === 'custom_http') {
            const cfg: CustomHttpConfig = { ...(v.adapter_config ?? {}) };
            if (headersJson && headersJson.trim()) {
                try {
                    cfg.headers = JSON.parse(headersJson);
                } catch {
                    message.error('Headers JSON không hợp lệ.');
                    return;
                }
            }
            v.adapter_config = cfg;
        } else {
            delete v.adapter_config;
        }

        if (editing) {
            update.mutate(
                { code: editing.code, payload: v },
                { onSuccess: () => { message.success('Đã lưu provider.'); setOpen(false); }, onError: onErr },
            );
        } else {
            create.mutate(v, {
                onSuccess: () => { message.success('Đã thêm provider.'); setOpen(false); },
                onError: onErr,
            });
        }
    };

    const runTest = (code: string) =>
        test.mutate(code, {
            onSuccess: (r) => r.ok
                ? message.success(`Kết nối OK: ${r.sample ?? ''}`)
                : message.warning(`Chưa OK (${r.reason}): ${r.message ?? ''}`),
            onError: (e) => message.error(errorMessage(e)),
        });

    const columns = [
        { title: 'Mã', dataIndex: 'code', key: 'code', render: (c: string) => <Tag>{c}</Tag> },
        { title: 'Loại (adapter)', dataIndex: 'adapter', key: 'adapter', render: (a: AiAdapter) => ADAPTER_LABEL[a] },
        { title: 'Tên hiển thị', dataIndex: 'display_name', key: 'display_name' },
        { title: 'Model', dataIndex: 'default_model', key: 'default_model' },
        {
            title: 'API key', dataIndex: 'has_api_key', key: 'has_api_key',
            render: (v: boolean) => (v ? <Tag color="green">Đã đặt</Tag> : <Tag>Chưa</Tag>),
        },
        {
            title: 'Bật', dataIndex: 'is_active', key: 'is_active',
            render: (v: boolean) => (v ? <Tag color="blue">Đang bật</Tag> : <Tag>Tắt</Tag>),
        },
        {
            title: 'Hành động', key: 'actions',
            render: (_: unknown, row: AiProviderRow) => (
                <Space>
                    <Button size="small" onClick={() => openEdit(row)}>Sửa</Button>
                    <Button size="small" icon={<ThunderboltOutlined />} loading={test.isPending} onClick={() => runTest(row.code)}>Test</Button>
                    <Button
                        size="small"
                        danger
                        onClick={() => modal.confirm({
                            title: `Tắt provider ${row.code}?`,
                            onOk: () => disable.mutate(row.code, { onSuccess: () => message.success('Đã tắt.') }),
                        })}
                    >
                        Tắt
                    </Button>
                </Space>
            ),
        },
    ];

    return (
        <Card
            title={<Space><ApiOutlined /> Nhà cung cấp AI</Space>}
            extra={
                <Space>
                    <Button icon={<ReloadOutlined />} onClick={() => refetch()}>Tải lại</Button>
                    <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Thêm provider</Button>
                </Space>
            }
        >
            <Table rowKey="code" loading={isLoading} dataSource={data?.data ?? []} columns={columns} pagination={false} />

            <Modal
                open={open}
                title={editing ? `Sửa ${editing.code}` : 'Thêm nhà cung cấp AI'}
                onCancel={() => setOpen(false)}
                onOk={submit}
                confirmLoading={create.isPending || update.isPending}
                destroyOnClose
            >
                <Form form={form} layout="vertical">
                    <Form.Item name="adapter" label="Loại API (adapter)" rules={[{ required: true }]}>
                        <Radio.Group disabled={!!editing} optionType="button" buttonStyle="solid">
                            {ADAPTERS.map((a) => (
                                <Radio.Button key={a} value={a}>{ADAPTER_LABEL[a]}</Radio.Button>
                            ))}
                        </Radio.Group>
                    </Form.Item>

                    <Form.Item shouldUpdate={(p, c) => p.adapter !== c.adapter} noStyle>
                        {() => {
                            const a = form.getFieldValue('adapter') as AiAdapter;
                            const presets = presetsFor(a);
                            return presets.length > 1 ? (
                                <Form.Item label="Mẫu nhanh">
                                    <Space wrap>
                                        {presets.map((p) => (
                                            <Button key={p.name} size="small" onClick={() => applyPreset(p)}>{p.name}</Button>
                                        ))}
                                    </Space>
                                </Form.Item>
                            ) : null;
                        }}
                    </Form.Item>

                    <Form.Item
                        name="code"
                        label="Mã (slug, duy nhất)"
                        rules={[
                            { required: !editing, message: 'Nhập mã slug' },
                            { pattern: /^[a-z0-9][a-z0-9_-]{1,31}$/, message: 'Chỉ a-z 0-9 _ - , 2-32 ký tự' },
                        ]}
                    >
                        <Input placeholder="vd: deepseek-prod" disabled={!!editing} />
                    </Form.Item>
                    <Form.Item name="display_name" label="Tên hiển thị"><Input placeholder="vd: DeepSeek (prod)" /></Form.Item>
                    <Form.Item name="base_url" label="Base URL / Endpoint" rules={[{ type: 'url', message: 'URL không hợp lệ' }]}
                        extra="Custom HTTP: nhập URL endpoint đầy đủ (vd https://llm.vn/v1/chat).">
                        <Input placeholder="https://api.deepseek.com" />
                    </Form.Item>
                    <Form.Item name="default_model" label="Model mặc định"><Input placeholder="deepseek-chat" /></Form.Item>
                    <Form.Item name="api_key" label="API key" extra={editing ? 'Để trống = giữ nguyên key cũ.' : undefined}>
                        <Input.Password placeholder="sk-..." />
                    </Form.Item>

                    {/* Cấu hình riêng adapter custom_http (SPEC-0026). */}
                    <Form.Item shouldUpdate={(p, c) => p.adapter !== c.adapter} noStyle>
                        {() => (form.getFieldValue('adapter') as AiAdapter) === 'custom_http' ? (
                            <>
                                <Form.Item name={['adapter_config', 'method']} label="HTTP method" initialValue="POST">
                                    <Select options={['POST', 'PUT', 'GET'].map((m) => ({ value: m, label: m }))} />
                                </Form.Item>
                                <Form.Item name="headers_json" label="Headers (JSON)" extra="Có thể dùng {{api_key}} / {{model}}.">
                                    <Input.TextArea rows={3} placeholder={'{"Authorization":"Bearer {{api_key}}"}'} />
                                </Form.Item>
                                <Form.Item
                                    name={['adapter_config', 'request_template']}
                                    label="Body template (JSON)"
                                    rules={[{ required: true, message: 'Nhập body template' }]}
                                    extra="Placeholder: {{model}} {{system}} {{messages_json}} {{last_user_message}} {{buyer_name}} {{api_key}}"
                                >
                                    <Input.TextArea rows={5} placeholder={'{"model":"{{model}}","system":"{{system}}","messages":{{messages_json}}}'} />
                                </Form.Item>
                                <Form.Item
                                    name={['adapter_config', 'response_path']}
                                    label="Đường dẫn trả lời (JSON path)"
                                    rules={[{ required: true, message: 'Nhập response path' }]}
                                >
                                    <Input placeholder="data.reply.text" />
                                </Form.Item>
                                <Form.Item name={['adapter_config', 'usage', 'prompt_path']} label="JSON path token vào (tuỳ chọn)">
                                    <Input placeholder="usage.prompt_tokens" />
                                </Form.Item>
                                <Form.Item name={['adapter_config', 'usage', 'completion_path']} label="JSON path token ra (tuỳ chọn)">
                                    <Input placeholder="usage.completion_tokens" />
                                </Form.Item>
                            </>
                        ) : null}
                    </Form.Item>

                    <Form.Item name="sort_order" label="Thứ tự"><InputNumber min={0} max={9999} /></Form.Item>
                    <Form.Item name="is_active" label="Kích hoạt" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </Card>
    );
}
