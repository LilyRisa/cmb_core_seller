import { useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { App as AntApp, Button, Card, Empty, Popconfirm, Space, Spin, Tag, Typography } from 'antd';
import { DisconnectOutlined, FacebookFilled, KeyOutlined } from '@ant-design/icons';
import { MessagingNav } from '@/components/MessagingNav';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { useCan } from '@/lib/tenant';
import { useConnectFacebook, useDisconnectFacebookPage, useMessagingChannels } from '@/lib/messagingConfig';

const { Text } = Typography;

/** Thông điệp cho mã `?error=` từ callback Facebook (FacebookOAuthController). */
const FB_ERRORS: Record<string, string> = {
    facebook_no_pages: 'Tài khoản chưa quản lý Page nào hoặc bạn chưa cấp quyền Page khi đăng nhập.',
    facebook_oauth_state: 'Phiên kết nối đã hết hạn. Vui lòng thử lại.',
    facebook_oauth_failed: 'Kết nối Facebook thất bại. Vui lòng thử lại sau.',
};

/** /messaging/channels — kết nối & quản lý Facebook Page (design 2026-05-20). */
export function MessagingChannelsPage() {
    const { message } = AntApp.useApp();
    const [params, setParams] = useSearchParams();
    const canConnect = useCan('messaging.connect');
    const connectFb = useConnectFacebook();
    const { data: channels, isLoading } = useMessagingChannels();
    const disconnect = useDisconnectFacebookPage();

    useEffect(() => {
        const connected = params.get('connected');
        const err = params.get('error');
        if (connected === 'facebook_page') {
            message.success('Đã kết nối Facebook Page!');
            params.delete('connected'); setParams(params, { replace: true });
        } else if (err) {
            message.error({ content: FB_ERRORS[err] ?? 'Bạn đã huỷ hoặc Facebook từ chối cấp quyền.', duration: 12 });
            params.delete('error'); setParams(params, { replace: true });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleConnect = () => connectFb.mutate(undefined, {
        onSuccess: (d) => { window.location.href = d.authorize_url; },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật facebook_page.')),
    });

    const pages = channels ?? [];

    return (
        <div>
            <MessagingNav />
            <PageHeader title="Kết nối kênh" subtitle="Kết nối Facebook Page để nhận & trả lời tin nhắn Messenger ngay trong hộp thư." />

            <Card title={<><FacebookFilled style={{ color: '#1877F2' }} /> Facebook Page</>} style={{ marginBottom: 16 }}>
                <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                    <Button type="primary" icon={<FacebookFilled />} loading={connectFb.isPending} onClick={handleConnect} disabled={!canConnect}>
                        Kết nối Facebook Page
                    </Button>
                    {isLoading ? (
                        <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
                    ) : pages.length === 0 ? (
                        <Empty description="Chưa kết nối Page nào" />
                    ) : pages.map((p) => (
                        <Card key={p.id} size="small" styles={{ body: { padding: 12 } }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}>
                                <Space direction="vertical" size={2}>
                                    <Space size={6}>
                                        <Text strong>{p.name}</Text>
                                        <Tag color={p.token_expired ? 'red' : 'green'}>{p.token_expired ? 'Hết hạn token' : 'Đang hoạt động'}</Tag>
                                    </Space>
                                    <Text type="secondary" style={{ fontSize: 12 }}>Page ID: {p.external_shop_id}</Text>
                                </Space>
                                {canConnect && (
                                    <Space>
                                        {p.token_expired && (
                                            <Button size="small" type="primary" icon={<KeyOutlined />} loading={connectFb.isPending} onClick={handleConnect}>Kết nối lại</Button>
                                        )}
                                        <Popconfirm
                                            title="Ngắt kết nối Page?"
                                            description="Sẽ gỡ Page và xoá toàn bộ hội thoại liên quan, không khôi phục được."
                                            okText="Ngắt kết nối" okButtonProps={{ danger: true }} cancelText="Huỷ"
                                            onConfirm={() => disconnect.mutate(p.id, {
                                                onSuccess: () => message.success('Đã ngắt kết nối Page.'),
                                                onError: (e) => message.error(errorMessage(e)),
                                            })}
                                        >
                                            <Button size="small" danger icon={<DisconnectOutlined />}>Ngắt kết nối</Button>
                                        </Popconfirm>
                                    </Space>
                                )}
                            </div>
                        </Card>
                    ))}
                </Space>
            </Card>

            <Card title="Lazada / TikTok">
                <Text type="secondary">Lazada/TikTok dùng chung kết nối với Gian hàng. Bật nhắn tin tại <Link to="/channels">trang Gian hàng</Link>.</Text>
            </Card>
        </div>
    );
}
