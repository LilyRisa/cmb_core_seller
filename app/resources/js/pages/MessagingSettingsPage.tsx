import { useEffect } from 'react';
import { App as AntApp, Alert, Button, Card, Form, Modal, Radio, Space, Spin, Switch, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useMessagingSettings, useSaveMessagingSettings } from '@/lib/messagingConfig';
import { useFlows } from '@/lib/messagingFlows';
import { MessagingNav } from '@/components/MessagingNav';

const { Text, Paragraph } = Typography;

interface SettingsForm {
    ai_provider_code: string;
    ai_enabled: boolean;
    auto_mode_marketplace: boolean;
    auto_mode_facebook: boolean;
}

/** /settings/messaging — chọn AI provider + bật AI / auto-mode (SPEC-0024 §6.2, ADR-0022).
 *  AI tự động trả lời tách theo nhóm kênh (Sàn vs Facebook). Kết nối kênh ở /messaging/channels. */
export function MessagingSettingsPage() {
    const { message } = AntApp.useApp();
    const canConfig = useCan('messaging.ai.config');
    const { data, isLoading } = useMessagingSettings();
    const { data: flows } = useFlows();
    const save = useSaveMessagingSettings();
    const [form] = Form.useForm<SettingsForm>();

    useEffect(() => {
        if (data) {
            form.setFieldsValue({
                ai_provider_code: data.ai_provider_code ?? '',
                ai_enabled: data.ai_enabled,
                auto_mode_marketplace: data.auto_mode_marketplace,
                auto_mode_facebook: data.auto_mode_facebook,
            });
        }
    }, [data, form]);

    if (isLoading) return <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>;

    const providers = data?.available_providers ?? [];
    // Số luồng "Mọi tin nhắn" (inbox_any) Facebook đang chạy — sẽ bị tạm dừng nếu bật AI FB.
    const activeCatchAll = (flows?.data ?? []).filter(
        (f) => f.provider === 'facebook_page' && f.trigger_type === 'inbox_any' && f.status === 'active',
    ).length;

    const persist = (v: SettingsForm) => {
        save.mutate(
            { ...v, ai_provider_code: v.ai_provider_code === '' ? null : v.ai_provider_code },
            {
                onSuccess: (res) => {
                    const paused = res.meta?.paused_catch_all_flows ?? 0;
                    message.success(
                        paused > 0
                            ? `Đã lưu. Đã tạm dừng ${paused} luồng "Mọi tin nhắn" của Facebook.`
                            : 'Đã lưu cài đặt tin nhắn',
                    );
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    const submit = () => form.validateFields().then((v) => {
        const turningOnFacebook = v.auto_mode_facebook && !data?.auto_mode_facebook;
        // Cảnh báo loại trừ Tầng 2: bật AI FB sẽ tạm dừng luồng "Mọi tin nhắn" đang chạy.
        if (turningOnFacebook && activeCatchAll > 0) {
            Modal.confirm({
                title: 'Bật AI tự động cho Facebook?',
                content: `Bạn đang có ${activeCatchAll} luồng "Mọi tin nhắn" đang chạy. AI tự động và luồng "Mọi tin nhắn" không thể cùng hoạt động — tiếp tục sẽ tạm dừng các luồng đó.`,
                okText: 'Bật AI & tạm dừng luồng',
                cancelText: 'Huỷ',
                onOk: () => persist(v),
            });
            return;
        }
        persist(v);
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
                            Chỉ khi tin <b>không khớp</b> các mục đó, AI mới tự trả lời. Riêng Facebook: AI tự động và
                            luồng <b>"Mọi tin nhắn"</b> không thể cùng bật — bật cái này sẽ tự tạm dừng cái kia.
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
                        extra={<Text type="secondary">Bật để dùng AI (gợi ý cho NV duyệt). Hai công tắc dưới chỉ chạy khi mục này bật.</Text>}>
                        <Switch />
                    </Form.Item>

                    <Typography.Title level={5} style={{ marginTop: 8 }}>Tự động trả lời (auto-mode)</Typography.Title>
                    <Form.Item name="auto_mode_marketplace" label="AI tự động trả lời — Sàn TMĐT" valuePropName="checked"
                        extra={<Text type="secondary">TikTok / Lazada / Shopee. AI tự gửi với tin an toàn; tin nhạy cảm (khiếu nại/hoàn tiền/khẩn) chuyển NV. Cần gói Business.</Text>}>
                        <Switch />
                    </Form.Item>
                    <Form.Item name="auto_mode_facebook" label="AI tự động trả lời — Facebook" valuePropName="checked"
                        extra={<Text type="secondary">Bật sẽ tạm dừng luồng "Mọi tin nhắn" của Facebook (nếu đang chạy). Tin đầu/từ khoá/luồng đang chạy vẫn ưu tiên trước AI.</Text>}>
                        <Switch />
                    </Form.Item>

                    {canConfig && <Button type="primary" loading={save.isPending} onClick={submit}>Lưu</Button>}
                </Form>
            </Card>
        </div>
    );
}
