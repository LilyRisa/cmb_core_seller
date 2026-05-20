import { useEffect } from 'react';
import { App as AntApp, Alert, Button, Card, Form, Radio, Space, Spin, Switch, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useMessagingSettings, useSaveMessagingSettings } from '@/lib/messagingConfig';
import { MessagingNav } from '@/components/MessagingNav';

const { Text } = Typography;

/** /settings/messaging — chọn AI provider + bật AI / auto-mode (SPEC-0024 §6.2).
 *  Kết nối kênh đã chuyển sang /messaging/channels. */
export function MessagingSettingsPage() {
    const { message } = AntApp.useApp();
    const canConfig = useCan('messaging.ai.config');
    const { data, isLoading } = useMessagingSettings();
    const save = useSaveMessagingSettings();
    const [form] = Form.useForm();

    useEffect(() => {
        if (data) {
            form.setFieldsValue({ ai_provider_code: data.ai_provider_code ?? '', ai_enabled: data.ai_enabled, auto_mode: data.auto_mode });
        }
    }, [data, form]);

    if (isLoading) return <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>;

    const providers = data?.available_providers ?? [];
    const submit = () => form.validateFields().then((v) => {
        save.mutate({ ...v, ai_provider_code: v.ai_provider_code === '' ? null : v.ai_provider_code }, {
            onSuccess: () => message.success('Đã lưu cài đặt tin nhắn'),
            onError: (e) => message.error(errorMessage(e)),
        });
    });

    return (
        <div>
            <MessagingNav />
            <Card title="Cài đặt AI tin nhắn" style={{ maxWidth: 640 }}>
                {providers.length === 0 && (
                    <Alert type="warning" showIcon style={{ marginBottom: 16 }}
                        message="Chưa có AI provider khả dụng"
                        description="Quản trị viên hệ thống cần thêm & bật provider (Claude/OpenAI) trong /admin/ai-providers trước." />
                )}
                <Form form={form} layout="vertical" disabled={!canConfig}>
                    <Form.Item name="ai_provider_code" label="AI provider" extra="Chọn 1 trong các provider quản trị viên đã bật.">
                        <Radio.Group>
                            <Space direction="vertical">
                                <Radio value="">Không dùng AI</Radio>
                                {providers.map((p) => <Radio key={p.code} value={p.code}>{p.name}</Radio>)}
                            </Space>
                        </Radio.Group>
                    </Form.Item>
                    <Form.Item name="ai_enabled" label="Bật AI gợi ý trả lời" valuePropName="checked"><Switch /></Form.Item>
                    <Form.Item name="auto_mode" label="Tự động trả lời (auto-mode)" valuePropName="checked"
                        extra={<Text type="secondary">AI tự gửi với tin an toàn; tin nhạy cảm (khiếu nại/hoàn tiền/khẩn) sẽ chuyển NV. Cần gói Business.</Text>}>
                        <Switch />
                    </Form.Item>
                    {canConfig && <Button type="primary" loading={save.isPending} onClick={submit}>Lưu</Button>}
                </Form>
            </Card>
        </div>
    );
}
