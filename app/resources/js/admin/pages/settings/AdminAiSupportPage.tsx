// /admin/ai-support — trang RIÊNG cấu hình trợ lý "Hỏi AI" (module Support).
// Tách hẳn khỏi cấu hình provider trả lời tin nhắn (messaging) cho gọn, dễ mở rộng.
//
// 3 phần: (1) Provider CHAT — sinh câu trả lời; (2) Provider EMBEDDING — tạo vector
// RAG (tách riêng để chat dùng OpenRouter còn embedding dùng nguồn có embeddings);
// (3) Embedding model. Dùng lại API system-settings + danh sách /admin/ai-providers.
// Radio.Group theo memory ui-avoid-select-prefer-radio.

import { useEffect, useState } from 'react';
import { App, Alert, Button, Card, Input, Radio, Space, Spin, Tag, Typography } from 'antd';
import { CustomerServiceOutlined, ThunderboltOutlined, SaveOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useAiProviders, useTestAiProvider, type AiProviderRow } from '../../lib/aiProviders';
import { useSupportAiConfig, useSaveSupportSetting, SUPPORT_KEYS } from '../../lib/aiSupport';

const { Text, Paragraph } = Typography;

/** Radio chọn provider; option "" = mặc định/không đặt. `embeddingOnly` ⇒ nhãn cảnh báo provider thiếu embedding. */
function ProviderRadio({
    providers, value, onChange, emptyLabel, requireEmbedding,
}: {
    providers: AiProviderRow[];
    value: string;
    onChange: (v: string) => void;
    emptyLabel: React.ReactNode;
    requireEmbedding?: boolean;
}) {
    const known = new Set(providers.map((p) => p.code));
    return (
        <Radio.Group value={value} onChange={(e) => onChange(e.target.value)}>
            <Space direction="vertical" size={6}>
                <Radio value="">{emptyLabel}</Radio>
                {providers.map((p) => {
                    const hasEmb = !!p.capabilities?.embedding;
                    return (
                        <Radio key={p.code} value={p.code}>
                            <Space size={6} wrap>
                                <Text>{p.display_name ?? p.code}</Text>
                                <Text code style={{ fontSize: 11 }}>{p.code}</Text>
                                {requireEmbedding && (hasEmb
                                    ? <Tag color="green">có embedding</Tag>
                                    : <Tag color="red">không embedding</Tag>)}
                                {!p.is_active && <Tag>đang tắt</Tag>}
                            </Space>
                        </Radio>
                    );
                })}
                {value !== '' && !known.has(value) && (
                    <Radio value={value}>
                        <Space size={6}><Text code style={{ fontSize: 11 }}>{value}</Text><Tag color="red">không tìm thấy provider</Tag></Space>
                    </Radio>
                )}
            </Space>
        </Radio.Group>
    );
}

export function AdminAiSupportPage() {
    const { message } = App.useApp();
    const { data: providersData, isLoading: loadingProviders } = useAiProviders();
    const { data: cfg, isLoading: loadingCfg } = useSupportAiConfig();
    const save = useSaveSupportSetting();
    const test = useTestAiProvider();

    const providers = providersData?.data ?? [];

    // Draft cục bộ — chỉ ghi khi bấm Lưu (tránh gọi API mỗi lần đổi radio).
    const [chat, setChat] = useState('');
    const [embProvider, setEmbProvider] = useState('');
    const [embModel, setEmbModel] = useState('');
    const [testing, setTesting] = useState<string | null>(null);

    useEffect(() => {
        if (cfg) {
            setChat(cfg.chat_provider_code);
            setEmbProvider(cfg.embedding_provider_code);
            setEmbModel(cfg.embedding_model);
        }
    }, [cfg]);

    // Provider thực sự dùng cho embedding = riêng nếu đặt, không thì = chat.
    const effectiveEmbCode = embProvider !== '' ? embProvider : chat;
    const effectiveEmbProvider = providers.find((p) => p.code === effectiveEmbCode);
    const embMissing = effectiveEmbCode !== '' && effectiveEmbProvider && !effectiveEmbProvider.capabilities?.embedding;

    const saveOne = (key: string, value: string, label: string) =>
        save.mutate({ key, value }, {
            onSuccess: () => message.success(`Đã lưu: ${label}`),
            onError: (e) => message.error(errorMessage(e)),
        });

    const runTest = (code: string) => {
        if (!code) return;
        setTesting(code);
        test.mutate(code, {
            onSuccess: (r) => {
                const parts: string[] = [];
                if (r.results?.chat) parts.push(`Chat: ${r.results.chat.ok ? 'OK' : `LỖI (${r.results.chat.reason ?? ''}${r.results.chat.message ? ' — ' + r.results.chat.message : ''})`}`);
                if (r.results?.embedding) parts.push(`Embedding: ${r.results.embedding.ok ? `OK (dim ${r.results.embedding.dimension ?? '?'})` : `LỖI (${r.results.embedding.reason ?? ''}${r.results.embedding.message ? ' — ' + r.results.embedding.message : ''})`}`);
                const detail = parts.join(' · ') || (r.message ?? '');
                if (r.ok) message.success(`[${code}] OK — ${detail}`);
                else message.warning(`[${code}] Chưa OK — ${detail}`);
            },
            onError: (e) => message.error(`[${code}] ${errorMessage(e)}`),
            onSettled: () => setTesting(null),
        });
    };

    if (loadingProviders || loadingCfg) {
        return <Card><Spin /></Card>;
    }

    return (
        <Space direction="vertical" size={16} style={{ width: '100%' }}>
            <Card title={<Space><CustomerServiceOutlined /> Cấu hình AI cho Trợ giúp (Hỏi AI)</Space>}>
                <Paragraph type="secondary" style={{ marginBottom: 0 }}>
                    Trợ lý "Hỏi AI" dùng nhà cung cấp RIÊNG, KHÔNG liên quan provider trả lời tin nhắn của tenant.
                    Quản lý danh sách provider ở mục <Text strong>Nhà cung cấp AI</Text>. Đổi provider/model embedding
                    rồi chạy lại <Text code>php artisan help:index --fresh</Text> để tạo lại vector.
                </Paragraph>
            </Card>

            {/* 1. Provider CHAT */}
            <Card
                size="small"
                title="1. Provider trả lời (chat)"
                extra={chat && (
                    <Button size="small" icon={<ThunderboltOutlined />} loading={testing === chat} onClick={() => runTest(chat)}>Test</Button>
                )}
            >
                <Paragraph type="secondary" style={{ fontSize: 12 }}>
                    Sinh câu trả lời cho người dùng. Có thể dùng provider không có embedding (vd OpenRouter) — phần vector cấu hình riêng ở mục 2.
                </Paragraph>
                <ProviderRadio
                    providers={providers}
                    value={chat}
                    onChange={setChat}
                    emptyLabel={<Text>Tắt — <Text type="secondary">trợ lý chỉ tìm tài liệu, không sinh câu trả lời AI</Text></Text>}
                />
                <div style={{ marginTop: 12 }}>
                    <Button type="primary" icon={<SaveOutlined />} loading={save.isPending}
                        onClick={() => saveOne(SUPPORT_KEYS.chatProvider, chat, 'Provider chat')}>Lưu provider chat</Button>
                </div>
            </Card>

            {/* 2. Provider EMBEDDING */}
            <Card
                size="small"
                title="2. Provider tạo vector (embedding) — cho tìm kiếm ngữ nghĩa (RAG)"
                extra={effectiveEmbCode && (
                    <Button size="small" icon={<ThunderboltOutlined />} loading={testing === effectiveEmbCode} onClick={() => runTest(effectiveEmbCode)}>Test</Button>
                )}
            >
                <Paragraph type="secondary" style={{ fontSize: 12 }}>
                    Biến tài liệu + câu hỏi thành vector để tìm theo ngữ nghĩa. Bắt buộc provider CÓ embedding.
                    Để trống ⇒ dùng chung provider chat ở mục 1.
                </Paragraph>
                <ProviderRadio
                    providers={providers}
                    value={embProvider}
                    onChange={setEmbProvider}
                    requireEmbedding
                    emptyLabel={<Text>Dùng chung provider chat {chat && <Text code style={{ fontSize: 11 }}>{chat}</Text>}</Text>}
                />
                {embMissing && (
                    <Alert
                        type="warning" showIcon style={{ marginTop: 10 }}
                        message="Provider embedding hiện tại không hỗ trợ embedding"
                        description="Provider đang dùng cho vector không có khả năng embedding (vd OpenRouter không có endpoint /v1/embeddings). RAG vector sẽ KHÔNG hoạt động — trợ lý rớt về tìm kiếm từ khoá. Hãy chọn một provider có nhãn 'có embedding' (vd OpenAI text-embedding-3-small)."
                    />
                )}

                <div style={{ marginTop: 16 }}>
                    <Text strong>Embedding model</Text>
                    <Paragraph type="secondary" style={{ fontSize: 12, margin: '4px 0 8px' }}>
                        Tên model embedding của provider trên (vd <Text code>text-embedding-3-small</Text>).
                    </Paragraph>
                    <Space.Compact style={{ width: '100%', maxWidth: 480 }}>
                        <Input value={embModel} onChange={(e) => setEmbModel(e.target.value)} placeholder="text-embedding-3-small" />
                        <Button type="primary" loading={save.isPending}
                            onClick={() => saveOne(SUPPORT_KEYS.embeddingModel, embModel, 'Embedding model')}>Lưu model</Button>
                    </Space.Compact>
                </div>

                <div style={{ marginTop: 12 }}>
                    <Button type="primary" icon={<SaveOutlined />} loading={save.isPending}
                        onClick={() => saveOne(SUPPORT_KEYS.embeddingProvider, embProvider, 'Provider embedding')}>Lưu provider embedding</Button>
                </div>
            </Card>
        </Space>
    );
}
