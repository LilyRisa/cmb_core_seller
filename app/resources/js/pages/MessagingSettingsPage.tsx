import { useEffect } from 'react';
import { App as AntApp, Alert, Button, Card, Form, Input, Radio, Space, Spin, Switch, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useMessagingSettings, useSaveMessagingSettings } from '@/lib/messagingConfig';
import { MessagingNav } from '@/components/MessagingNav';

const { Text, Paragraph } = Typography;

interface SettingsForm {
    ai_provider_code: string;
    ai_enabled: boolean;
    auto_mode_marketplace: boolean;
    auto_mode_facebook: boolean;
    sales_closing_style: string;
    sales_closing_note: string;
}

/** 5 phong cách chốt sale AI có thể áp dụng khi trả lời khách. */
const SALES_CLOSING_STYLES = [
    { value: 'default', label: 'Mặc định' },
    { value: 'consultative', label: 'Tư vấn nhẹ nhàng' },
    { value: 'fast_close', label: 'Chốt nhanh' },
    { value: 'scarcity', label: 'Khan hiếm - ưu đãi' },
    { value: 'attentive', label: 'Chăm sóc kỹ' },
];

/** /settings/messaging — chọn AI provider + bật AI / auto-mode (SPEC-0024 §6.2, ADR-0022).
 *  AI tự động trả lời tách theo nhóm kênh (Sàn vs Facebook). Kết nối kênh ở /messaging/channels. */
export function MessagingSettingsPage() {
    const { message } = AntApp.useApp();
    const canConfig = useCan('messaging.ai.config');
    const { data, isLoading } = useMessagingSettings();
    const save = useSaveMessagingSettings();
    const [form] = Form.useForm<SettingsForm>();

    useEffect(() => {
        if (data) {
            form.setFieldsValue({
                ai_provider_code: data.ai_provider_code ?? '',
                ai_enabled: data.ai_enabled,
                auto_mode_marketplace: data.auto_mode_marketplace,
                auto_mode_facebook: data.auto_mode_facebook,
                sales_closing_style: data.sales_closing_style ?? 'default',
                sales_closing_note: data.sales_closing_note ?? '',
            });
        }
    }, [data, form]);

    if (isLoading) return <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>;

    const providers = data?.available_providers ?? [];

    const submit = () => form.validateFields().then((v) => {
        save.mutate(
            { ...v, ai_provider_code: v.ai_provider_code === '' ? null : v.ai_provider_code },
            {
                onSuccess: () => message.success('Đã lưu cài đặt tin nhắn'),
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    });

    return (
        <div>
            <MessagingNav />
            <Card title="Cài đặt AI tin nhắn" style={{ maxWidth: 680 }}>
                {providers.length === 0 && (
                    <Alert type="warning" showIcon style={{ marginBottom: 16 }}
                        message="Chưa có AI provider khả dụng"
                        description="Quản trị viên hệ thống cần thêm & bật provider (Claude/OpenAI) trong /admin/ai-providers trước." />
                )}

                <Alert type="info" showIcon style={{ marginBottom: 16 }}
                    message="AI tự động hoạt động thế nào?"
                    description={
                        <Paragraph style={{ marginBottom: 0 }}>
                            Ưu tiên xử lý <b>tin nhắn đầu tiên</b> và <b>tin chứa từ khoá</b> (kịch bản hoặc quy tắc) trước.
                            Chỉ khi tin <b>không khớp</b> các mục đó, AI mới tự trả lời. Công tắc <b>"AI tự trả lời"</b> nay
                            bật/tắt <b>theo từng trang</b> ở <b>Kết nối kênh</b> (không còn ở đây). Riêng Facebook: một trang đã
                            bật luồng <b>"Mọi tin nhắn"</b> sẽ tự tắt AI tự trả lời của trang đó.
                        </Paragraph>
                    } />

                <Form form={form} layout="vertical" disabled={!canConfig}>
                    <Form.Item name="ai_provider_code" label="AI provider" extra="Chọn 1 trong các provider quản trị viên đã bật.">
                        <Radio.Group>
                            <Space direction="vertical">
                                <Radio value="">Không dùng AI</Radio>
                                {providers.map((p) => <Radio key={p.code} value={p.code}>{p.name}</Radio>)}
                            </Space>
                        </Radio.Group>
                    </Form.Item>
                    <Form.Item name="ai_enabled" label="Bật AI gợi ý trả lời" valuePropName="checked"
                        extra={<Text type="secondary">Bật để dùng AI (gợi ý cho NV duyệt + nền cho "AI tự trả lời"). Bật/tắt AI tự trả lời theo từng trang ở <b>Kết nối kênh</b>.</Text>}>
                        <Switch />
                    </Form.Item>

                    <Form.Item name="sales_closing_style" label="Phong cách chốt sale"
                        extra="AI sẽ áp dụng phong cách này khi tư vấn và chốt đơn với khách.">
                        <Radio.Group>
                            <Space direction="vertical">
                                {SALES_CLOSING_STYLES.map((s) => <Radio key={s.value} value={s.value}>{s.label}</Radio>)}
                            </Space>
                        </Radio.Group>
                    </Form.Item>
                    <Form.Item name="sales_closing_note" label="Ghi chú chốt sale (tùy chọn)"
                        extra="Ghi chú thêm để AI hiểu rõ hơn cách chốt sale mong muốn (vd: ưu tiên nhắc combo, tránh ép giá).">
                        <Input.TextArea rows={3} maxLength={500} showCount placeholder="Ví dụ: Luôn nhắc chương trình freeship khi khách còn phân vân..." />
                    </Form.Item>

                    {canConfig && <Button type="primary" loading={save.isPending} onClick={submit}>Lưu</Button>}
                </Form>
            </Card>
        </div>
    );
}
