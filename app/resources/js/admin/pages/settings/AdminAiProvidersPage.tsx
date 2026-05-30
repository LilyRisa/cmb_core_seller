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

// Fallback khi API chưa trả; NGUỒN CHÍNH là data.adapters (registry BE) để FE
// không lệch với adapter đã đăng ký ⇒ không chọn được adapter mà BE từ chối (422).
const ADAPTERS_FALLBACK: AiAdapter[] = ['anthropic', 'openai_compatible', 'custom_http', 'manual'];

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
    // Code provider ĐANG test — để CHỈ nút của dòng đó quay spinner (test.isPending
    // dùng chung mọi dòng ⇒ trước đây bấm 1 provider thì tất cả nút Test đều loading).
    const [testingCode, setTestingCode] = useState<string | null>(null);

    const adapters = data?.adapters ?? [];
    const presetsFor = (a: AiAdapter): AiPreset[] => adapters.find((x) => x.adapter === a)?.presets ?? [];
    // Chỉ hiển thị adapter mà BE thực sự đăng ký (registry) ⇒ FE luôn khớp BE,
    // tránh chọn adapter không tồn tại khiến tạo provider bị 422.
    const adapterChoices: AiAdapter[] = adapters.length ? adapters.map((x) => x.adapter) : ADAPTERS_FALLBACK;

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
            api_key: row.api_key ?? '',   // trang admin: hiển thị thẳng key hiện có để xem/sửa
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

    const runTest = (code: string) => {
        setTestingCode(code);
        test.mutate(code, {
            onSuccess: (r) => {
                // Tóm tắt từng năng lực: Chat / Embedding (embedding cần cho trợ lý Hỏi AI / Support).
                const parts: string[] = [];
                if (r.results?.chat) parts.push(`Chat: ${r.results.chat.ok ? 'OK' : `LỖI (${r.results.chat.reason ?? ''}${r.results.chat.message ? ' — ' + r.results.chat.message : ''})`}`);
                if (r.results?.embedding) parts.push(`Embedding: ${r.results.embedding.ok ? `OK (dim ${r.results.embedding.dimension ?? '?'})` : `LỖI (${r.results.embedding.reason ?? ''}${r.results.embedding.message ? ' — ' + r.results.embedding.message : ''})`}`);
                const detail = parts.join(' · ') || (r.message ?? '');
                // Ghi RÕ provider nào để không nhầm khi có nhiều provider.
                if (r.ok) message.success(`[${code}] Kết nối OK — ${detail}`);
                else message.warning(`[${code}] Chưa OK — ${detail}`);
            },
            onError: (e) => message.error(`[${code}] ${errorMessage(e)}`),
            onSettled: () => setTestingCode(null),
        });
    };

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
                    <Button size="small" icon={<ThunderboltOutlined />} loading={testingCode === row.code} disabled={test.isPending && testingCode !== row.code} onClick={() => runTest(row.code)}>Test</Button>
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
                            {adapterChoices.map((a) => (
                                <Radio.Button key={a} value={a}>{ADAPTER_LABEL[a] ?? a}</Radio.Button>
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

                    {/* base_url + default_model: required theo adapter (khớp validate BE).
                        SafeProviderUrl bắt buộc HTTPS + host công khai (chặn http/localhost/LAN). */}
                    <Form.Item shouldUpdate={(p, c) => p.adapter !== c.adapter} noStyle>
                        {() => {
                            const a = (editing?.adapter ?? form.getFieldValue('adapter')) as AiAdapter;
                            const needsBaseUrl = a === 'openai_compatible' || a === 'custom_http';
                            const needsModel = a === 'openai_compatible';
                            return (
                                <>
                                    <Form.Item
                                        name="base_url"
                                        label="Base URL / Endpoint"
                                        rules={[
                                            { type: 'url', message: 'URL không hợp lệ' },
                                            { required: needsBaseUrl, message: 'Nhập Base URL' },
                                        ]}
                                        extra={
                                            a === 'custom_http'
                                                ? 'Nhập URL endpoint ĐẦY ĐỦ (vd https://llm.vn/v1/chat). Phải HTTPS + host công khai.'
                                                : 'Nhập GỐC host, KHÔNG kèm /v1 (connector tự thêm /v1/chat/completions hoặc /v1/messages). Vd: https://api.deepseek.com. Phải HTTPS + host công khai (không http/localhost/IP nội bộ).'
                                        }
                                    >
                                        <Input placeholder="https://api.deepseek.com" />
                                    </Form.Item>
                                    <Form.Item
                                        name="default_model"
                                        label="Model mặc định"
                                        rules={[{ required: needsModel, message: 'Nhập model mặc định' }]}
                                    >
                                        <Input placeholder="deepseek-chat" />
                                    </Form.Item>
                                </>
                            );
                        }}
                    </Form.Item>
                    {/* Trang admin: hiện thẳng key (Input thường) để xem/sửa, không che. */}
                    <Form.Item name="api_key" label="API key" extra={editing ? 'Key hiện tại đang hiển thị — sửa rồi lưu để đổi; để trống = giữ nguyên.' : undefined}>
                        <Input placeholder="sk-..." autoComplete="off" />
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
