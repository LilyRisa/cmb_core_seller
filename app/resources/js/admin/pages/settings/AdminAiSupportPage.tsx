// /admin/ai-support — trang RIÊNG cấu hình trợ lý "Hỏi AI" (module Support).
// TỰ CHỨA: credentials riêng (base_url + api_key + model), KHÔNG dùng chung bảng
// "Nhà cung cấp AI" của messaging. Tách CHAT và EMBEDDING độc lập → có thể dùng cùng
// provider hay khác provider cho chat và embedding (tuỳ base_url/model).
//
// api_key dùng chung SecretInput (hiển thị plaintext, "Đặt giá trị" để đổi — xem
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.3), thay Input
// hand-rolled trước đây. "Lưu" mỗi khối (chat/embedding) khoá tới khi Test kết nối khối đó
// pass với đúng giá trị đang có trên form (§5.4) — đổi field bất kỳ sau khi test ⇒ phải
// test lại. Khối embedding vẫn giữ lối thoát "để trống Base URL = tắt vector" — không cần
// test khi tắt hẳn.

import { useEffect, useState } from 'react';
import { App, Alert, Button, Card, Input, Space, Spin, Tag, Typography } from 'antd';
import { CustomerServiceOutlined, SaveOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useSupportAiConfig, useSaveSupportSetting, useTestAiSupportDraft, SUPPORT_KEYS } from '../../lib/aiSupport';
import { SecretInput } from '../../components/SecretInput';

const { Text, Paragraph } = Typography;

export function AdminAiSupportPage() {
    const { message } = App.useApp();
    const { data: cfg, isLoading } = useSupportAiConfig();
    const save = useSaveSupportSetting();
    const testDraft = useTestAiSupportDraft();

    const [chatBaseUrl, setChatBaseUrl] = useState('');
    const [chatKey, setChatKey] = useState('');
    const [chatModel, setChatModel] = useState('');
    const [embBaseUrl, setEmbBaseUrl] = useState('');
    const [embKey, setEmbKey] = useState('');
    const [embModel, setEmbModel] = useState('');

    // Chữ ký (base_url|api_key|model) đã Test PASS gần nhất cho từng khối — Lưu chỉ mở
    // khoá khi chữ ký hiện tại khớp; đổi field nào cũng buộc test lại (spec §5.4).
    const [chatVerifiedSig, setChatVerifiedSig] = useState<string | null>(null);
    const [embVerifiedSig, setEmbVerifiedSig] = useState<string | null>(null);

    useEffect(() => {
        if (cfg) {
            setChatBaseUrl(cfg.chat_base_url);
            setChatModel(cfg.chat_model);
            setEmbBaseUrl(cfg.embedding_base_url);
            setEmbModel(cfg.embedding_model);
            setChatKey(cfg.chat_api_key);
            setEmbKey(cfg.embedding_api_key);
        }
    }, [cfg]);

    const chatSig = `${chatBaseUrl}|${chatKey}|${chatModel}`;
    const embSig = `${embBaseUrl}|${embKey}|${embModel}`;

    const saveOne = (key: string, value: string, label: string) =>
        new Promise<void>((resolve) => {
            save.mutate({ key, value }, {
                onSuccess: () => { message.success(`Đã lưu: ${label}`); resolve(); },
                onError: (e) => { message.error(`${label}: ${errorMessage(e)}`); resolve(); },
            });
        });

    const saveChat = async () => {
        await saveOne(SUPPORT_KEYS.chatBaseUrl, chatBaseUrl.trim(), 'Chat Base URL');
        await saveOne(SUPPORT_KEYS.chatModel, chatModel.trim(), 'Chat Model');
        await saveOne(SUPPORT_KEYS.chatApiKey, chatKey.trim(), 'Chat API key');
    };
    const saveEmbedding = async () => {
        await saveOne(SUPPORT_KEYS.embeddingBaseUrl, embBaseUrl.trim(), 'Embedding Base URL');
        await saveOne(SUPPORT_KEYS.embeddingModel, embModel.trim(), 'Embedding Model');
        await saveOne(SUPPORT_KEYS.embeddingApiKey, embKey.trim(), 'Embedding API key');
    };

    const testChat = async () => {
        const r = await testDraft.mutateAsync({ kind: 'chat', base_url: chatBaseUrl.trim(), api_key: chatKey.trim(), model: chatModel.trim() });
        if (r.ok) { message.success(r.message ?? 'Kết nối OK.'); setChatVerifiedSig(chatSig); }
        else message.error(r.message ?? 'Kết nối thất bại.');
    };
    const testEmbedding = async () => {
        const r = await testDraft.mutateAsync({ kind: 'embedding', base_url: embBaseUrl.trim(), api_key: embKey.trim(), model: embModel.trim() });
        if (r.ok) { message.success(r.message ?? 'Kết nối OK.'); setEmbVerifiedSig(embSig); }
        else message.error(r.message ?? 'Kết nối thất bại.');
    };

    if (isLoading) return <Card><Spin /></Card>;

    return (
        <Space direction="vertical" size={16} style={{ width: '100%' }}>
            <Card title={<Space><CustomerServiceOutlined /> Cấu hình AI cho Trợ giúp (Hỏi AI)</Space>}>
                <Paragraph type="secondary" style={{ marginBottom: 0 }}>
                    Trợ lý "Hỏi AI" dùng cấu hình RIÊNG (base URL + API key + model), KHÔNG liên quan
                    mục <Text strong>Nhà cung cấp AI</Text> (trả lời tin nhắn). Đổi base URL/model embedding
                    rồi chạy lại <Text code>php artisan help:index --fresh</Text> để tạo lại vector.
                </Paragraph>
            </Card>

            {/* 1. CHAT */}
            <Card size="small" title="1. Chat — sinh câu trả lời">
                <Paragraph type="secondary" style={{ fontSize: 12 }}>
                    OpenAI-compatible. <Text code>Base URL</Text> = GỐC host, KHÔNG kèm <Text code>/v1</Text>
                    (vd OpenRouter: <Text code>https://openrouter.ai/api</Text>).
                </Paragraph>
                <Space direction="vertical" size={10} style={{ width: '100%', maxWidth: 560 }}>
                    <div>
                        <Text strong>Base URL</Text>
                        <Input value={chatBaseUrl} onChange={(e) => setChatBaseUrl(e.target.value)} placeholder="https://openrouter.ai/api" />
                    </div>
                    <div>
                        <Text strong>API key</Text>
                        <SecretInput value={chatKey || null} onSave={setChatKey} />
                    </div>
                    <div>
                        <Text strong>Model</Text>
                        <Input value={chatModel} onChange={(e) => setChatModel(e.target.value)} placeholder="google/gemini-2.0-flash-lite-001" />
                    </div>
                    <Space>
                        <Button icon={<ThunderboltOutlined />} loading={testDraft.isPending} onClick={testChat}>Test kết nối</Button>
                        {chatSig === chatVerifiedSig
                            ? <Tag color="green">Đã xác minh</Tag>
                            : <Tag color="orange">Chưa xác minh — cần Test trước khi Lưu</Tag>}
                    </Space>
                    <Button type="primary" icon={<SaveOutlined />} loading={save.isPending} disabled={chatSig !== chatVerifiedSig} onClick={saveChat}>
                        Lưu cấu hình chat
                    </Button>
                </Space>
            </Card>

            {/* 2. EMBEDDING */}
            <Card size="small" title="2. Embedding — tạo vector cho tìm kiếm ngữ nghĩa (RAG)">
                <Alert
                    type="info" showIcon style={{ marginBottom: 12 }}
                    message="Phải dùng MODEL embedding hợp lệ của provider"
                    description="Có thể dùng cùng provider với chat hoặc provider khác. Quan trọng: Model phải là model EMBEDDING (vd openai/text-embedding-3-small trên OpenRouter, text-embedding-3-small trên OpenAI) — KHÔNG phải model chat. Để TRỐNG Base URL ⇒ tắt vector, trợ lý chạy tìm kiếm từ khoá."
                />
                <Space direction="vertical" size={10} style={{ width: '100%', maxWidth: 560 }}>
                    <div>
                        <Text strong>Base URL</Text>
                        <Input value={embBaseUrl} onChange={(e) => setEmbBaseUrl(e.target.value)} placeholder="https://api.openai.com (trống = tắt vector)" />
                    </div>
                    <div>
                        <Text strong>API key</Text>
                        <SecretInput value={embKey || null} onSave={setEmbKey} />
                    </div>
                    <div>
                        <Text strong>Model</Text>
                        <Input value={embModel} onChange={(e) => setEmbModel(e.target.value)} placeholder="text-embedding-3-small" />
                    </div>
                    <Space>
                        <Button icon={<ThunderboltOutlined />} loading={testDraft.isPending} onClick={testEmbedding} disabled={embBaseUrl.trim() === ''}>
                            Test kết nối
                        </Button>
                        {embBaseUrl.trim() === ''
                            ? <Tag>Đang tắt vector — không cần test</Tag>
                            : embSig === embVerifiedSig
                                ? <Tag color="green">Đã xác minh</Tag>
                                : <Tag color="orange">Chưa xác minh — cần Test trước khi Lưu</Tag>}
                    </Space>
                    <Button
                        type="primary" icon={<SaveOutlined />} loading={save.isPending}
                        disabled={embBaseUrl.trim() !== '' && embSig !== embVerifiedSig}
                        onClick={saveEmbedding}
                    >
                        Lưu cấu hình embedding
                    </Button>
                </Space>
                <Paragraph type="secondary" style={{ fontSize: 12, marginTop: 12, marginBottom: 0 }}>
                    Sau khi lưu embedding, chạy <Text code>php artisan help:index --fresh</Text> để tạo lại vector tài liệu.
                </Paragraph>
            </Card>
        </Space>
    );
}
