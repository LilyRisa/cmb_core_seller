import { useEffect } from 'react';
import { App as AntApp, Alert, Button, Card, Form, Select, Space, Spin, Switch, Typography } from 'antd';
import { FacebookFilled } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useConnectFacebook, useMessagingSettings, useSaveMessagingSettings } from '@/lib/messagingConfig';

const { Text } = Typography;

/** /settings/messaging — kết nối kênh + chọn AI provider + bật AI / auto-mode (SPEC-0024 §6.2). */
export function MessagingSettingsPage() {
    const { message } = AntApp.useApp();
    const canConfig = useCan('messaging.ai.config');
    const connectFb = useConnectFacebook();

    const handleConnectFacebook = () => {
        connectFb.mutate(undefined, {
            onSuccess: (d) => { window.location.href = d.authorize_url; },
            onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối Facebook. Quản trị viên cần bật facebook_page.')),
        });
    };
    const { data, isLoading } = useMessagingSettings();
    const save = useSaveMessagingSettings();
    const [form] = Form.useForm();

    useEffect(() => {
        if (data) {
            form.setFieldsValue({ ai_provider_code: data.ai_provider_code, ai_enabled: data.ai_enabled, auto_mode: data.auto_mode });
        }
    }, [data, form]);

    if (isLoading) return <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>;

    const providers = data?.available_providers ?? [];
    const submit = () => form.validateFields().then((v) => {
        save.mutate(v, {
            onSuccess: () => message.success('Đã lưu cài đặt tin nhắn'),
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    return (
        <Space direction="vertical" size="large" style={{ display: 'flex', maxWidth: 640 }}>
        <Card title="Kết nối kênh nhắn tin">
            <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                <Text type="secondary">Kết nối Facebook Page để nhận & trả lời tin nhắn Messenger ngay trong hộp thư.</Text>
                <Button icon={<FacebookFilled style={{ color: '#1877F2' }} />} loading={connectFb.isPending}
                    onClick={handleConnectFacebook} disabled={!canConfig}>
                    Kết nối Facebook Page
                </Button>
                <Text type="secondary" style={{ fontSize: 12 }}>TikTok / Lazada chia sẻ kết nối với Gian hàng (Channels). Shopee sắp có.</Text>
            </Space>
        </Card>
        <Card title="Cài đặt AI tin nhắn">
            {providers.length === 0 && (
                <Alert type="warning" showIcon style={{ marginBottom: 16 }}
                    message="Chưa có AI provider khả dụng"
                    description="Quản trị viên hệ thống cần thêm & bật provider (Claude/OpenAI) trong /admin/ai-providers trước." />
            )}
            <Form form={form} layout="vertical" disabled={!canConfig}>
                <Form.Item name="ai_provider_code" label="AI provider" extra="Chọn 1 trong các provider quản trị viên đã bật.">
                    <Select allowClear placeholder="Chưa chọn" options={providers.map((p) => ({ value: p.code, label: p.name }))} />
                </Form.Item>
                <Form.Item name="ai_enabled" label="Bật AI gợi ý trả lời" valuePropName="checked"><Switch /></Form.Item>
                <Form.Item name="auto_mode" label="Tự động trả lời (auto-mode)" valuePropName="checked"
                    extra={<Text type="secondary">AI tự gửi với tin an toàn; tin nhạy cảm (khiếu nại/hoàn tiền/khẩn) sẽ chuyển NV. Cần gói Business.</Text>}>
                    <Switch />
                </Form.Item>
                {canConfig && <Button type="primary" loading={save.isPending} onClick={submit}>Lưu</Button>}
            </Form>
        </Card>
        </Space>
    );
}
