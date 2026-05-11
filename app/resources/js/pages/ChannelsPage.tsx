import { useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Alert, Avatar, Button, Card, Col, Empty, Popconfirm, Result, Row, Space, Tag, Tooltip, Typography } from 'antd';
import { App as AntApp } from 'antd';
import { CheckCircleOutlined, ClockCircleOutlined, PlusOutlined, ReloadOutlined, ShopOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { DateText } from '@/components/MoneyText';
import { CHANNEL_META, CHANNEL_STATUS_COLOR, CHANNEL_STATUS_LABEL } from '@/lib/format';
import { errorMessage } from '@/lib/api';
import { ChannelAccount, useChannelAccounts, useConnectChannel, useDisconnectChannel, useResyncChannel } from '@/lib/channels';
import { useCan } from '@/lib/tenant';

const CALLBACK_ERRORS: Record<string, string> = {
    oauth_state: 'Phiên kết nối đã hết hạn hoặc không hợp lệ. Vui lòng thử kết nối lại.',
    shop_already_connected: 'Gian hàng này đã được kết nối ở một workspace khác.',
    oauth_failed: 'Kết nối thất bại. Vui lòng thử lại.',
    oauth_missing_params: 'Thiếu tham số từ sàn. Vui lòng thử lại.',
};

function ShopCard({ account, canManage, onResync, onDisconnect }: { account: ChannelAccount; canManage: boolean; onResync: () => void; onDisconnect: () => void }) {
    const meta = CHANNEL_META[account.provider] ?? { name: account.provider, color: '#8c8c8c' };
    return (
        <Card styles={{ body: { padding: 16 } }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 12 }}>
                <Space align="start">
                    <Avatar shape="square" size={40} style={{ background: meta.color, color: '#fff', fontWeight: 700 }}>{meta.name.slice(0, 2)}</Avatar>
                    <Space direction="vertical" size={2}>
                        <Typography.Text strong>{account.shop_name ?? account.external_shop_id}</Typography.Text>
                        <Typography.Text type="secondary" style={{ fontSize: 12 }}>{meta.name} · ID: {account.external_shop_id}</Typography.Text>
                        <Tag color={CHANNEL_STATUS_COLOR[account.status] ?? 'default'}>{CHANNEL_STATUS_LABEL[account.status] ?? account.status}</Tag>
                    </Space>
                </Space>
                {canManage && (
                    <Space>
                        <Tooltip title="Đồng bộ lại đơn ngay"><Button size="small" icon={<ReloadOutlined />} onClick={onResync} disabled={account.status !== 'active'}>Đồng bộ</Button></Tooltip>
                        <Popconfirm title="Ngắt kết nối gian hàng này?" description="Lịch sử đơn vẫn được giữ lại; đồng bộ sẽ dừng." okText="Ngắt" cancelText="Huỷ" onConfirm={onDisconnect}>
                            <Button size="small" danger>Ngắt</Button>
                        </Popconfirm>
                    </Space>
                )}
            </div>
            <div style={{ marginTop: 12, display: 'flex', gap: 24, color: '#8c8c8c', fontSize: 12 }}>
                <span><ClockCircleOutlined /> Đồng bộ gần nhất: <DateText value={account.last_synced_at} /></span>
                <span>Webhook gần nhất: <DateText value={account.last_webhook_at} /></span>
                {account.token_expires_at && <span>Token hết hạn: <DateText value={account.token_expires_at} withTime={false} /></span>}
            </div>
            {account.status === 'expired' && <Alert type="warning" showIcon style={{ marginTop: 12 }} message="Token đã hết hạn — cần kết nối lại để tiếp tục đồng bộ đơn." />}
        </Card>
    );
}

export function ChannelsPage() {
    const { message } = AntApp.useApp();
    const [params, setParams] = useSearchParams();
    const canManage = useCan('channels.manage');
    const { data, isLoading, isError, error, refetch } = useChannelAccounts();
    const connect = useConnectChannel();
    const disconnect = useDisconnectChannel();
    const resync = useResyncChannel();

    useEffect(() => {
        const connected = params.get('connected');
        const err = params.get('error');
        if (connected) {
            message.success(`Đã kết nối gian hàng ${CHANNEL_META[connected]?.name ?? connected}! Đơn 90 ngày gần đây đang được tải về.`);
            params.delete('connected'); setParams(params, { replace: true });
        } else if (err) {
            message.error(CALLBACK_ERRORS[err] ?? 'Có lỗi khi kết nối gian hàng.');
            params.delete('error'); setParams(params, { replace: true });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    if (isError) return <Result status="error" title="Không tải được danh sách gian hàng" subTitle={errorMessage(error)} extra={<Button onClick={() => refetch()}>Thử lại</Button>} />;

    const accounts = data?.data ?? [];
    const connectable = data?.meta.connectable_providers ?? [];

    return (
        <div>
            <PageHeader title="Gian hàng" subtitle="Kết nối các gian hàng sàn TMĐT để đồng bộ đơn hàng tự động" extra={<Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isLoading}>Làm mới</Button>} />

            {canManage && (
                <Card title={<><PlusOutlined /> Kết nối gian hàng mới</>} style={{ marginBottom: 16 }}>
                    {connect.isError && <Alert type="error" showIcon style={{ marginBottom: 12 }} message={errorMessage(connect.error, 'Không bắt đầu được luồng kết nối.')} />}
                    <Space wrap>
                        {connectable.length === 0 && <Typography.Text type="secondary">Chưa có sàn nào sẵn sàng. (TikTok cần cấu hình app key/secret sandbox trong <code>.env</code>.)</Typography.Text>}
                        {connectable.map((p) => {
                            const meta = CHANNEL_META[p.code] ?? { name: p.name, color: '#8c8c8c' };
                            return (
                                <Button key={p.code} type="primary" icon={<ShopOutlined />} loading={connect.isPending && connect.variables === p.code} onClick={() => connect.mutate(p.code)} style={{ background: meta.color, borderColor: meta.color }}>
                                    Kết nối {meta.name}
                                </Button>
                            );
                        })}
                        {/* providers awaiting API approval */}
                        {!connectable.some((p) => p.code === 'shopee') && <Button disabled icon={<ShopOutlined />}>Shopee <Tag style={{ marginLeft: 6 }}>Phase 4</Tag></Button>}
                        {!connectable.some((p) => p.code === 'lazada') && <Button disabled icon={<ShopOutlined />}>Lazada <Tag style={{ marginLeft: 6 }}>Phase 4</Tag></Button>}
                    </Space>
                    <Typography.Paragraph type="secondary" style={{ marginTop: 12, marginBottom: 0, fontSize: 12 }}>
                        Bấm "Kết nối" sẽ chuyển bạn tới trang ủy quyền của sàn; sau khi đồng ý, bạn quay lại đây. Yêu cầu: <code>APP_URL</code> phải là địa chỉ HTTPS công khai (dùng ngrok cho dev) để sàn redirect callback về được.
                    </Typography.Paragraph>
                </Card>
            )}

            <Card title={<><CheckCircleOutlined /> Gian hàng đã kết nối ({accounts.length})</>} loading={isLoading} styles={{ body: { padding: accounts.length ? 16 : undefined } }}>
                {accounts.length === 0 ? (
                    <Empty description="Chưa có gian hàng nào. Kết nối TikTok Shop để bắt đầu." />
                ) : (
                    <Row gutter={[16, 16]}>
                        {accounts.map((a) => (
                            <Col xs={24} xl={12} key={a.id}>
                                <ShopCard
                                    account={a} canManage={canManage}
                                    onResync={() => resync.mutate(a.id, { onSuccess: () => message.success('Đã xếp lịch đồng bộ lại đơn của gian hàng này.') })}
                                    onDisconnect={() => disconnect.mutate(a.id, { onSuccess: () => message.success('Đã ngắt kết nối gian hàng.') })}
                                />
                            </Col>
                        ))}
                    </Row>
                )}
            </Card>
        </div>
    );
}
