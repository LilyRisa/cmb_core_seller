// /admin/marketing-ai-providers — provider AI RIÊNG cho phân tích marketing (tách AI messaging).
//
// api_key dùng chung SecretInput (hiển thị plaintext, "Đặt giá trị" để đổi — xem
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.3), thay Input
// "để trống = giữ nguyên" trước đây (mơ hồ, dễ nhầm với xoá key). "Lưu" trong modal khoá
// tới khi Test kết nối PASS với đúng (adapter, base_url, model, api_key) đang có trên form
// — chỉ áp dụng adapter anthropic/openai_compatible; manual (stub, không có backend thật)
// giữ hành vi Lưu ngay như trước (§5.4). Icon Card title đổi ApiOutlined → RiseOutlined để
// khớp icon sidebar "AI Marketing" đã cố định ở Phase 0 (tránh trùng icon với trang
// "Nhà cung cấp AI" — spec §4).

import { useState } from 'react';
import { App as AntApp, Button, Card, Form, Input, Modal, Popconfirm, Segmented, Space, Switch, Table, Tag, Typography } from 'antd';
import { DeleteOutlined, PlusOutlined, RiseOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    type MarketingAiAdapter, type MarketingAiProviderInput, type MarketingAiProviderRow,
    useDeleteMarketingAiProvider, useMarketingAiProviders, useSaveMarketingAiProvider, useTestMarketingAiProviderDraft,
} from '../../lib/marketingAiProviders';
import { SecretInput } from '../../components/SecretInput';

const { Text, Paragraph } = Typography;

// Chỉ 2 adapter có request/response shape cố định mới test "nháp" được (khớp
// AdminAiProvidersPage.tsx); 'manual' là stub, không có backend thật để test.
const PROBEABLE_ADAPTERS: MarketingAiAdapter[] = ['anthropic', 'openai_compatible'];

/** /admin/marketing-ai-providers — provider AI RIÊNG cho phân tích marketing (tách AI messaging). */
export function AdminMarketingAiProvidersPage() {
    const { message } = AntApp.useApp();
    const { data: rows, isLoading } = useMarketingAiProviders();
    const save = useSaveMarketingAiProvider();
    const del = useDeleteMarketingAiProvider();
    const testDraft = useTestMarketingAiProviderDraft();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<MarketingAiProviderRow | null>(null);
    const [form] = Form.useForm<MarketingAiProviderInput>();
    const [apiKeyDraft, setApiKeyDraft] = useState<string | null>(null);
    // Chữ ký (adapter|base_url|default_model|api_key) đã Test PASS gần nhất.
    const [verifiedSignature, setVerifiedSignature] = useState<string | null>(null);

    const watchedAdapter = Form.useWatch('adapter', form);
    const watchedBaseUrl = Form.useWatch('base_url', form);
    const watchedModel = Form.useWatch('default_model', form);

    const probeSupported = !!watchedAdapter && PROBEABLE_ADAPTERS.includes(watchedAdapter);
    const currentSignature = JSON.stringify({
        adapter: watchedAdapter ?? '',
        base_url: watchedBaseUrl ?? '',
        default_model: watchedModel ?? '',
        api_key: apiKeyDraft ?? '',
    });
    const needsTest = probeSupported && currentSignature !== verifiedSignature;

    const openNew = () => {
        setEditing(null);
        form.resetFields();
        form.setFieldsValue({ adapter: 'openai_compatible', is_active: true });
        setApiKeyDraft(null);
        setVerifiedSignature(null);
        setOpen(true);
    };
    const openEdit = (r: MarketingAiProviderRow) => {
        setEditing(r);
        form.setFieldsValue({
            code: r.code, display_name: r.display_name ?? undefined, adapter: r.adapter,
            base_url: r.base_url ?? undefined, default_model: r.default_model ?? undefined, is_active: r.is_active,
        });
        setApiKeyDraft(r.api_key ?? null);
        setVerifiedSignature(null);
        setOpen(true);
    };

    const runDraftTest = async () => {
        if (!watchedAdapter) return;
        const r = await testDraft.mutateAsync({
            adapter: watchedAdapter, base_url: watchedBaseUrl ?? null, api_key: apiKeyDraft, default_model: watchedModel ?? null,
        });
        if (r.ok) { message.success(r.message ?? 'Kết nối OK.'); setVerifiedSignature(currentSignature); }
        else message.error(r.message ?? 'Kết nối thất bại.');
    };

    const submit = async () => {
        const input = await form.validateFields();
        input.api_key = apiKeyDraft;
        save.mutate({ input, isNew: editing === null }, {
            onSuccess: () => { setOpen(false); message.success('Đã lưu provider.'); },
            onError: (e) => message.error(errorMessage(e, 'Không lưu được.')),
        });
    };

    return (
        <div>
            <Card
                title={<><RiseOutlined /> Provider AI Marketing (phân tích quảng cáo)</>}
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

            <Modal
                open={open}
                title={editing ? `Sửa ${editing.code}` : 'Thêm provider AI marketing'}
                onCancel={() => setOpen(false)}
                onOk={submit}
                confirmLoading={save.isPending}
                okButtonProps={{ disabled: needsTest }}
                okText="Lưu" cancelText="Huỷ"
            >
                <Form form={form} layout="vertical">
                    <Form.Item name="code" label="Code" rules={[{ required: true, pattern: /^[a-z0-9][a-z0-9_-]*$/, message: 'chữ thường/số/-/_' }]}>
                        <Input disabled={editing !== null} placeholder="forecast-openai" />
                    </Form.Item>
                    <Form.Item name="display_name" label="Tên hiển thị"><Input placeholder="Forecast GPT" /></Form.Item>
                    <Form.Item name="adapter" label="Adapter" rules={[{ required: true }]}>
                        <Segmented options={[{ label: 'OpenAI-compatible', value: 'openai_compatible' }, { label: 'Anthropic', value: 'anthropic' }, { label: 'Manual (stub)', value: 'manual' }]} />
                    </Form.Item>
                    {/* Trang admin: hiện thẳng key qua SecretInput dùng chung (spec §5.3) — thay
                        Input "để trống = giữ nguyên" cũ, vốn dễ nhầm với xoá key. */}
                    <Form.Item label="API key">
                        <SecretInput value={apiKeyDraft} onSave={(v) => setApiKeyDraft(v)} />
                    </Form.Item>
                    <Form.Item name="base_url" label="Base URL (tuỳ chọn)"><Input placeholder="https://api.openai.com/v1" /></Form.Item>
                    <Form.Item name="default_model" label="Model"><Input placeholder="gpt-4o-mini" /></Form.Item>
                    {probeSupported && (
                        <Form.Item label="Xác minh kết nối">
                            <Space>
                                <Button icon={<ThunderboltOutlined />} loading={testDraft.isPending} onClick={runDraftTest}>
                                    Test kết nối
                                </Button>
                                {currentSignature === verifiedSignature
                                    ? <Tag color="green">Đã xác minh</Tag>
                                    : <Tag color="orange">Chưa xác minh — Lưu bị khoá tới khi Test pass</Tag>}
                            </Space>
                        </Form.Item>
                    )}
                    <Form.Item name="is_active" label="Đang dùng" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
