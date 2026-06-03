import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { App as AntApp, Avatar, Button, Card, Checkbox, Empty, Popconfirm, Progress, Result, Space, Spin, Tag, Tooltip, Typography } from 'antd';
import { DisconnectOutlined, FacebookFilled, KeyOutlined, SyncOutlined } from '@ant-design/icons';
import { useQueryClient } from '@tanstack/react-query';
import dayjs from 'dayjs';
import { MessagingNav } from '@/components/MessagingNav';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { openOAuthPopup } from '@/lib/oauthPopup';
import { useCan } from '@/lib/tenant';
import { useBulkDisconnectChannels, useBulkSyncChannels, useConnectFacebook, useDisconnectFacebookPage, useMessagingChannels, useSyncChannel } from '@/lib/messagingConfig';

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
    const { data: channels, isLoading, isError, error } = useMessagingChannels();
    const [reconnectingId, setReconnectingId] = useState<number | null>(null);
    const [disconnectingId, setDisconnectingId] = useState<number | null>(null);
    const disconnect = useDisconnectFacebookPage();
    const syncChannel = useSyncChannel();
    const [syncingId, setSyncingId] = useState<number | null>(null);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const bulkSync = useBulkSyncChannels();
    const bulkDisconnect = useBulkDisconnectChannels();
    const qc = useQueryClient();

    const handleSync = (id: number) => {
        setSyncingId(id);
        syncChannel.mutate(id, {
            onSuccess: () => { setSyncingId(null); message.success('Đã bắt đầu đồng bộ tin nhắn.'); },
            onError: (e) => { setSyncingId(null); message.error(errorMessage(e, 'Không bắt đầu được đồng bộ.')); },
        });
    };

    const applyFbResult = (p: URLSearchParams) => {
        const connected = p.get('connected');
        const err = p.get('error');
        if (connected === 'facebook_page') {
            message.success('Đã kết nối Facebook Page!');
            params.delete('connected'); setParams(params, { replace: true });
            qc.invalidateQueries({ queryKey: ['messaging', 'channels'] });
        } else if (err) {
            message.error({ content: FB_ERRORS[err] ?? 'Bạn đã huỷ hoặc Facebook từ chối cấp quyền.', duration: 12 });
            params.delete('error'); setParams(params, { replace: true });
        }
    };

    useEffect(() => {
        applyFbResult(params);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleConnect = () => connectFb.mutate(undefined, {
        onSuccess: async (d) => {
            const res = await openOAuthPopup(d.authorize_url);
            if (res.status === 'done' && res.redirect) {
                applyFbResult(new URL(res.redirect, window.location.origin).searchParams);
            }
        },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật facebook_page.')),
    });

    const handleReconnect = (id: number) => {
        setReconnectingId(id);
        connectFb.mutate(undefined, {
            onSuccess: async (d) => {
                const res = await openOAuthPopup(d.authorize_url);
                setReconnectingId(null);
                if (res.status === 'done' && res.redirect) {
                    applyFbResult(new URL(res.redirect, window.location.origin).searchParams);
                }
            },
            onError: (e) => { setReconnectingId(null); message.error(errorMessage(e, 'Không khởi tạo được kết nối.')); },
        });
    };

    const pages = channels ?? [];
    // Chỉ giữ id còn tồn tại trong danh sách (page bị ngắt sẽ tự rụng khỏi selection).
    const selectedCount = pages.reduce((n, p) => (selectedIds.has(p.id) ? n + 1 : n), 0);
    const allSelected = pages.length > 0 && selectedCount === pages.length;
    const bulkBusy = bulkSync.isPending || bulkDisconnect.isPending;

    const toggleOne = (id: number, checked: boolean) => setSelectedIds((prev) => {
        const next = new Set(prev);
        if (checked) next.add(id); else next.delete(id);
        return next;
    });
    const toggleAll = (checked: boolean) => setSelectedIds(checked ? new Set(pages.map((p) => p.id)) : new Set());

    const handleBulkSync = () => bulkSync.mutate([...selectedIds], {
        onSuccess: (d) => { setSelectedIds(new Set()); message.success(`Đã bắt đầu đồng bộ ${d.processed} Page.`); },
        onError: (e) => message.error(errorMessage(e, 'Không bắt đầu được đồng bộ hàng loạt.')),
    });
    const handleBulkDisconnect = () => bulkDisconnect.mutate([...selectedIds], {
        onSuccess: (d) => { setSelectedIds(new Set()); message.success(`Đã ngắt kết nối ${d.processed} Page.`); },
        onError: (e) => message.error(errorMessage(e, 'Không ngắt kết nối được hàng loạt.')),
    });

    if (isError) return <Result status="error" title="Không tải được danh sách kênh" subTitle={errorMessage(error)} />;

    return (
        <div>
            <MessagingNav />
            <PageHeader title="Kết nối kênh" subtitle="Kết nối Facebook Page để nhận & trả lời tin nhắn Messenger ngay trong hộp thư." />

            <Card title={<><FacebookFilled style={{ color: '#1877F2' }} /> Facebook Page</>} style={{ marginBottom: 16 }}>
                <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                    <Button type="primary" icon={<FacebookFilled />} loading={connectFb.isPending} onClick={handleConnect} disabled={!canConnect}>
                        Kết nối Facebook Page
                    </Button>
                    {canConnect && pages.length > 0 && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
                            <Checkbox checked={allSelected} indeterminate={selectedCount > 0 && !allSelected} onChange={(e) => toggleAll(e.target.checked)}>
                                Chọn tất cả
                            </Checkbox>
                            <Button size="small" icon={<SyncOutlined />} disabled={selectedCount === 0 || bulkBusy} loading={bulkSync.isPending} onClick={handleBulkSync}>
                                Đồng bộ{selectedCount > 0 ? ` (${selectedCount})` : ''}
                            </Button>
                            <Popconfirm
                                title="Ngắt kết nối các Page đã chọn?"
                                description="Sẽ gỡ các Page và xoá toàn bộ hội thoại liên quan, không khôi phục được."
                                okText="Ngắt kết nối" okButtonProps={{ danger: true, loading: bulkDisconnect.isPending }} cancelText="Huỷ"
                                disabled={selectedCount === 0 || bulkBusy}
                                onConfirm={handleBulkDisconnect}
                            >
                                <Button size="small" danger icon={<DisconnectOutlined />} disabled={selectedCount === 0 || bulkBusy} loading={bulkDisconnect.isPending}>
                                    Ngắt kết nối{selectedCount > 0 ? ` (${selectedCount})` : ''}
                                </Button>
                            </Popconfirm>
                        </div>
                    )}
                    {isLoading ? (
                        <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
                    ) : pages.length === 0 ? (
                        <Empty description="Chưa kết nối Page nào" />
                    ) : pages.map((p) => {
                        const syncing = p.sync.status === 'queued' || p.sync.status === 'running';
                        return (
                        <Card key={p.id} size="small" styles={{ body: { padding: 12 } }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}>
                                <Space size={12} align="start">
                                    {canConnect && (
                                        <Checkbox checked={selectedIds.has(p.id)} onChange={(e) => toggleOne(p.id, e.target.checked)} style={{ marginTop: 12 }} />
                                    )}
                                    <Avatar src={p.avatar_url ?? undefined} icon={<FacebookFilled />} size={40} style={{ background: p.avatar_url ? undefined : '#1877F2' }} />
                                    <Space direction="vertical" size={2}>
                                        <Space size={6}>
                                            <Text strong>{p.name}</Text>
                                            <Tag color={p.token_expired ? 'red' : 'green'}>{p.token_expired ? 'Hết hạn token' : 'Đang hoạt động'}</Tag>
                                            {p.sync.status === 'failed' && (
                                                <Tooltip title={p.sync.error ?? 'Đồng bộ lỗi'}><Tag color="red">Đồng bộ lỗi</Tag></Tooltip>
                                            )}
                                            {p.comment_sync?.status === 'failed' && (
                                                <Tooltip title={p.comment_sync.error ?? 'Comment đồng bộ lỗi'}><Tag color="orange">Comment: cần cấp quyền</Tag></Tooltip>
                                            )}
                                        </Space>
                                        <Text type="secondary" style={{ fontSize: 12 }}>Page ID: {p.external_shop_id}</Text>
                                        {syncing ? (
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 8, maxWidth: 280, width: '100%' }}>
                                                <Progress percent={100} status="active" showInfo={false} size="small" style={{ flex: 1, margin: 0 }} />
                                                <Text type="secondary" style={{ fontSize: 12, whiteSpace: 'nowrap' }}>Đang đồng bộ… {p.sync.done} hội thoại</Text>
                                            </div>
                                        ) : p.sync.status === 'done' ? (
                                            <Text type="secondary" style={{ fontSize: 12 }}>
                                                Đã đồng bộ • {p.message_count} tin nhắn
                                                {p.sync.last_synced_at ? ` • ${dayjs(p.sync.last_synced_at).format('DD/MM HH:mm')}` : ''}
                                            </Text>
                                        ) : (
                                            <Text type="secondary" style={{ fontSize: 12 }}>{p.message_count} tin nhắn</Text>
                                        )}
                                    </Space>
                                </Space>
                                {canConnect && (
                                    <Space>
                                        <Button size="small" icon={<SyncOutlined spin={syncing} />} loading={syncingId === p.id} disabled={syncing} onClick={() => handleSync(p.id)}>
                                            Đồng bộ lại
                                        </Button>
                                        {p.token_expired && (
                                            <Button size="small" type="primary" icon={<KeyOutlined />} loading={reconnectingId === p.id} onClick={() => handleReconnect(p.id)}>Kết nối lại</Button>
                                        )}
                                        <Popconfirm
                                            title="Ngắt kết nối Page?"
                                            description="Sẽ gỡ Page và xoá toàn bộ hội thoại liên quan, không khôi phục được."
                                            okText="Ngắt kết nối" okButtonProps={{ danger: true, loading: disconnectingId === p.id }} cancelText="Huỷ"
                                            onConfirm={() => {
                                                setDisconnectingId(p.id);
                                                disconnect.mutate(p.id, {
                                                    onSuccess: () => { setDisconnectingId(null); message.success('Đã ngắt kết nối Page.'); },
                                                    onError: (e) => { setDisconnectingId(null); message.error(errorMessage(e)); },
                                                });
                                            }}
                                        >
                                            <Button size="small" danger icon={<DisconnectOutlined />} loading={disconnectingId === p.id}>Ngắt kết nối</Button>
                                        </Popconfirm>
                                    </Space>
                                )}
                            </div>
                        </Card>
                        );
                    })}
                </Space>
            </Card>

            <Card title="Lazada / TikTok">
                <Text type="secondary">Lazada/TikTok dùng chung kết nối với Gian hàng. Bật nhắn tin tại <Link to="/channels">trang Gian hàng</Link>.</Text>
            </Card>
        </div>
    );
}
