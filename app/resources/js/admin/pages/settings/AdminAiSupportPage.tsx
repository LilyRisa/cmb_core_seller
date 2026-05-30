// /admin/ai-support — trang RIÊNG cấu hình trợ lý "Hỏi AI" (module Support).
// TỰ CHỨA: credentials riêng (base_url + api_key + model), KHÔNG dùng chung bảng
// "Nhà cung cấp AI" của messaging. Tách CHAT và EMBEDDING độc lập → chat dùng
// Có thể dùng cùng provider hay khác provider cho chat và embedding (tuỳ base_url/model).

import { useEffect, useState } from 'react';
import { App, Alert, Button, Card, Input, Space, Spin, Typography } from 'antd';
import { CustomerServiceOutlined, SaveOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useSupportAiConfig, useSaveSupportSetting, SUPPORT_KEYS } from '../../lib/aiSupport';

const { Text, Paragraph } = Typography;

export function AdminAiSupportPage() {
    const { message } = App.useApp();
    const { data: cfg, isLoading } = useSupportAiConfig();
    const save = useSaveSupportSetting();

    // Draft cục bộ. api_key: chuỗi rỗng = GIỮ NGUYÊN (không ghi đè); nhập mới = đổi.
    const [chatBaseUrl, setChatBaseUrl] = useState('');
    const [chatKey, setChatKey] = useState('');
    const [chatModel, setChatModel] = useState('');
    const [embBaseUrl, setEmbBaseUrl] = useState('');
    const [embKey, setEmbKey] = useState('');
    const [embModel, setEmbModel] = useState('');

    useEffect(() => {
        if (cfg) {
            setChatBaseUrl(cfg.chat_base_url);
            setChatModel(cfg.chat_model);
            setEmbBaseUrl(cfg.embedding_base_url);
            setEmbModel(cfg.embedding_model);
            setChatKey('');
            setEmbKey('');
        }
    }, [cfg]);

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
        if (chatKey !== '') await saveOne(SUPPORT_KEYS.chatApiKey, chatKey, 'Chat API key');
    };
    const saveEmbedding = async () => {
        await saveOne(SUPPORT_KEYS.embeddingBaseUrl, embBaseUrl.trim(), 'Embedding Base URL');
        await saveOne(SUPPORT_KEYS.embeddingModel, embModel.trim(), 'Embedding Model');
        if (embKey !== '') await saveOne(SUPPORT_KEYS.embeddingApiKey, embKey, 'Embedding API key');
    };

    if (isLoading) return <Card><Spin /></Card>;

    const keyPlaceholder = (isSet: boolean) => (isSet ? '•••••••• (để trống = giữ nguyên)' : 'Nhập API key');

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
                        <Input.Password value={chatKey} onChange={(e) => setChatKey(e.target.value)} placeholder={keyPlaceholder(cfg!.chat_api_key_set)} />
                    </div>
                    <div>
                        <Text strong>Model</Text>
                        <Input value={chatModel} onChange={(e) => setChatModel(e.target.value)} placeholder="google/gemini-2.0-flash-lite-001" />
                    </div>
                    <Button type="primary" icon={<SaveOutlined />} loading={save.isPending} onClick={saveChat}>Lưu cấu hình chat</Button>
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
                        <Input.Password value={embKey} onChange={(e) => setEmbKey(e.target.value)} placeholder={keyPlaceholder(cfg!.embedding_api_key_set)} />
                    </div>
                    <div>
                        <Text strong>Model</Text>
                        <Input value={embModel} onChange={(e) => setEmbModel(e.target.value)} placeholder="text-embedding-3-small" />
                    </div>
                    <Button type="primary" icon={<SaveOutlined />} loading={save.isPending} onClick={saveEmbedding}>Lưu cấu hình embedding</Button>
                </Space>
                <Paragraph type="secondary" style={{ fontSize: 12, marginTop: 12, marginBottom: 0 }}>
                    Sau khi lưu embedding, chạy <Text code>php artisan help:index --fresh</Text> để tạo lại vector tài liệu.
                </Paragraph>
            </Card>
        </Space>
    );
}
