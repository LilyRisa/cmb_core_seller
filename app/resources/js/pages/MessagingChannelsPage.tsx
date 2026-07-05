import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Alert, App as AntApp, Avatar, Button, Card, Checkbox, Empty, Popconfirm, Progress, Result, Space, Spin, Switch, Tag, Tooltip, Typography } from 'antd';
import { DisconnectOutlined, FacebookFilled, KeyOutlined, RobotOutlined, ShopOutlined, SyncOutlined, WarningOutlined } from '@ant-design/icons';
import { useQueryClient } from '@tanstack/react-query';
import dayjs from 'dayjs';
import { MessagingNav } from '@/components/MessagingNav';
import { PageHeader } from '@/components/PageHeader';
import { BusinessInfoDrawer } from '@/components/messaging/BusinessInfoDrawer';
import { errorMessage } from '@/lib/api';
import { openOAuthPopup } from '@/lib/oauthPopup';
import { useCan } from '@/lib/tenant';
import { MARKETPLACE_CHAT_ENABLED } from '@/lib/messaging';
import type { MessagingChannel } from '@/lib/messagingConfig';
import { useBulkDisconnectChannels, useBulkSyncChannels, useConnectFacebook, useConnectLazadaIm, useDisconnectFacebookPage, useMessagingChannels, useSetChannelAiMode, useStartZaloConnect, useSyncChannel } from '@/lib/messagingConfig';

const { Text } = Typography;

/** Thông điệp cho mã `?error=` từ callback Facebook (FacebookOAuthController). */
const FB_ERRORS: Record<string, string> = {
    facebook_no_pages: 'Tài khoản chưa quản lý Page nào hoặc bạn chưa cấp quyền Page khi đăng nhập.',
    facebook_oauth_state: 'Phiên kết nối đã hết hạn. Vui lòng thử lại.',
    facebook_oauth_failed: 'Kết nối Facebook thất bại. Vui lòng thử lại sau.',
};

/** Thông điệp cho mã `?error=` từ callback Lazada IM (LazadaImOAuthController). */
const LZ_ERRORS: Record<string, string> = {
    lazada_im_oauth_state: 'Phiên kết nối đã hết hạn. Vui lòng thử lại.',
    lazada_im_no_seller: 'Không lấy được thông tin gian hàng từ Lazada. Kiểm tra app đã được cấp quyền IM chưa.',
    lazada_im_oauth_failed: 'Kết nối Lazada IM thất bại. Vui lòng thử lại sau.',
};

/** Thông điệp cho mã `?error=` từ callback Zalo OA (ZaloOaOAuthController). */
const ZALO_ERRORS: Record<string, string> = {
    zalo_oa_oauth_state: 'Phiên kết nối đã hết hạn. Vui lòng thử lại.',
    zalo_oa_unavailable: 'Tích hợp Zalo OA chưa được bật. Quản trị viên cần kích hoạt zalo_oa.',
    zalo_oa_no_oa_id: 'Không lấy được ID OA từ Zalo. Vui lòng thử lại.',
    zalo_oa_oauth_failed: 'Kết nối Zalo OA thất bại. Vui lòng thử lại sau.',
    zalo_oa_oauth_missing_params: 'Thiếu thông tin kết nối từ Zalo. Vui lòng thử lại.',
};

/** /messaging/channels — kết nối & quản lý Facebook Page (design 2026-05-20). */
export function MessagingChannelsPage() {
    const { message } = AntApp.useApp();
    const [params, setParams] = useSearchParams();
    const platform = params.get('platform') ?? 'facebook_page';
    const canConnect = useCan('messaging.connect');
    const connectFb = useConnectFacebook();
    const connectLazadaIm = useConnectLazadaIm();
    const startZalo = useStartZaloConnect();
    const { data: channels, isLoading, isError, error } = useMessagingChannels(platform !== 'facebook_page' ? platform : undefined);
    const [reconnectingId, setReconnectingId] = useState<number | null>(null);
    const [disconnectingId, setDisconnectingId] = useState<number | null>(null);
    const disconnect = useDisconnectFacebookPage();
    const syncChannel = useSyncChannel();
    const setAiMode = useSetChannelAiMode();
    const canAi = useCan('messaging.ai.config');
    const [syncingId, setSyncingId] = useState<number | null>(null);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const bulkSync = useBulkSyncChannels();
    const bulkDisconnect = useBulkDisconnectChannels();
    const qc = useQueryClient();
    const [bizPage, setBizPage] = useState<MessagingChannel | null>(null);
    const [bulkBizOpen, setBulkBizOpen] = useState(false);

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

    const applyLzResult = (p: URLSearchParams) => {
        const connected = p.get('connected');
        const err = p.get('error');
        if (connected === 'lazada_im') {
            message.success('Đã kết nối Lazada IM Chat!');
            params.delete('connected'); setParams(params, { replace: true });
            qc.invalidateQueries({ queryKey: ['messaging', 'channels'] });
        } else if (err && err.startsWith('lazada_im')) {
            message.error({ content: LZ_ERRORS[err] ?? 'Kết nối Lazada IM thất bại. Vui lòng thử lại.', duration: 12 });
            params.delete('error'); setParams(params, { replace: true });
        }
    };

    const applyZaloResult = (p: URLSearchParams) => {
        const connected = p.get('connected');
        const err = p.get('error');
        if (connected === 'zalo_oa') {
            message.success('Đã kết nối Zalo OA!');
            params.delete('connected'); setParams(params, { replace: true });
            qc.invalidateQueries({ queryKey: ['messaging', 'channels'] });
        } else if (err && err.startsWith('zalo_oa')) {
            message.error({ content: ZALO_ERRORS[err] ?? 'Bạn đã huỷ hoặc Zalo từ chối cấp quyền.', duration: 12 });
            params.delete('error'); setParams(params, { replace: true });
        }
    };

    useEffect(() => {
        applyFbResult(params);
        applyLzResult(params);
        applyZaloResult(params);
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

    const handleConnectLazadaIm = () => connectLazadaIm.mutate(undefined, {
        onSuccess: async (d) => {
            const res = await openOAuthPopup(d.authorize_url);
            if (res.status === 'done' && res.redirect) {
                applyLzResult(new URL(res.redirect, window.location.origin).searchParams);
            }
        },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật lazada_chat.')),
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

    const handleConnectZalo = () => startZalo.mutate(undefined, {
        onSuccess: async (d) => {
            const res = await openOAuthPopup(d.authorize_url);
            if (res.status === 'done' && res.redirect) {
                applyZaloResult(new URL(res.redirect, window.location.origin).searchParams);
            }
        },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật zalo_oa.')),
    });

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
            <PageHeader title="Kết nối kênh" subtitle="Kết nối Facebook Page, Zalo OA và Lazada IM Chat để nhận & trả lời tin nhắn ngay trong hộp thư." />

            {platform === 'zalo_oa' && (
                <Card title={<><img src="/images/zalo.webp" alt="" width={18} height={18} style={{ objectFit: 'contain', verticalAlign: '-4px', marginRight: 6 }} /> Zalo OA</>} style={{ marginBottom: 16 }}>
                    <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                        <Button type="primary" icon={<img src="/images/zalo.webp" alt="" width={16} height={16} style={{ objectFit: 'contain', verticalAlign: '-3px' }} />} loading={startZalo.isPending} onClick={handleConnectZalo} disabled={!canConnect}>
                            Kết nối Zalo OA
                        </Button>
                        {isLoading ? (
                            <div style={{ textAlign: 'center', padding: 24 }}><Spin /></div>
                        ) : pages.length === 0 ? (
                            <Empty description="Chưa kết nối Zalo OA nào" />
                        ) : pages.map((z) => (
                            <Card key={z.id} size="small" styles={{ body: { padding: 12 } }}>
                                {z.zalo_send_blocked && (
                                    <Alert
                                        type="warning"
                                        icon={<WarningOutlined />}
                                        showIcon
                                        message="OA chưa đủ gói để gửi tin nhắn"
                                        description={
                                            <>
                                                Nâng cấp gói OA tại{' '}
                                                <a href="https://zalo.cloud/oa/pricing" target="_blank" rel="noopener noreferrer">
                                                    zalo.cloud/oa/pricing
                                                </a>
                                                {z.zalo_send_blocked_reason && ` — ${z.zalo_send_blocked_reason}`}
                                            </>
                                        }
                                        style={{ marginBottom: 10 }}
                                    />
                                )}
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12 }}>
                                    <Space size={12} align="center">
                                        <Avatar src={z.avatar_url ?? undefined} icon={<img src="/images/zalo.webp" alt="" width={22} height={22} style={{ objectFit: 'contain' }} />} size={40} style={{ background: z.avatar_url ? undefined : '#fff' }} />
                                        <Space direction="vertical" size={2}>
                                            <Text strong>{z.name}</Text>
                                            <Space size={6}>
                                                <Tag color={z.token_expired ? 'red' : 'green'}>{z.token_expired ? 'Hết hạn token' : 'Đang hoạt động'}</Tag>
                                                <Text type="secondary" style={{ fontSize: 12 }}>OA ID: {z.external_shop_id}</Text>
                                            </Space>
                                        </Space>
                                    </Space>
                                    {canConnect && (
                                        <Space>
                                            {z.token_expired && (
                                                <Button size="small" type="primary" icon={<KeyOutlined />} loading={startZalo.isPending} onClick={handleConnectZalo}>Kết nối lại</Button>
                                            )}
                                            <Button size="small" icon={<ShopOutlined />} onClick={() => setBizPage(z)}>Thông tin cửa hàng</Button>
                                            <Popconfirm
                                                title="Ngắt kết nối Zalo OA?"
                                                description="Sẽ gỡ OA và xoá toàn bộ hội thoại liên quan, không khôi phục được."
                                                okText="Ngắt kết nối" okButtonProps={{ danger: true, loading: disconnectingId === z.id }} cancelText="Huỷ"
                                                onConfirm={() => {
                                                    setDisconnectingId(z.id);
                                                    disconnect.mutate(z.id, {
                                                        onSuccess: () => { setDisconnectingId(null); message.success('Đã ngắt kết nối Zalo OA.'); },
                                                        onError: (e) => { setDisconnectingId(null); message.error(errorMessage(e)); },
                                                    });
                                                }}
                                            >
                                                <Button size="small" danger icon={<DisconnectOutlined />} loading={disconnectingId === z.id}>Ngắt kết nối</Button>
                                            </Popconfirm>
                                        </Space>
                                    )}
                                </div>
                            </Card>
                        ))}
                    </Space>
                </Card>
            )}

            {platform !== 'zalo_oa' && <Card title={<><FacebookFilled style={{ color: '#1877F2' }} /> Facebook Page</>} style={{ marginBottom: 16 }}>
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
                            <Button size="small" icon={<ShopOutlined />} disabled={selectedCount === 0 || bulkBusy} onClick={() => setBulkBizOpen(true)}>
                                Thông tin cửa hàng{selectedCount > 0 ? ` (${selectedCount})` : ''}
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
                                <Space size={12} align="center">
                                    {canAi && (
                                        <Tooltip title="AI tự trả lời tin nhắn cho riêng trang này">
                                            <Space size={6} align="center">
                                                <RobotOutlined style={{ color: p.ai_auto_mode ? '#7C3AED' : '#94A3B8' }} />
                                                <Text type="secondary" style={{ fontSize: 12 }}>AI tự trả lời</Text>
                                                <Switch size="small" checked={p.ai_auto_mode} loading={setAiMode.isPending}
                                                    onChange={(v) => setAiMode.mutate({ id: p.id, ai_auto_mode: v }, { onError: (e) => message.error(errorMessage(e)) })} />
                                            </Space>
                                        </Tooltip>
                                    )}
                                    {canConnect && (
                                    <Space>
                                        <Button size="small" icon={<SyncOutlined spin={syncing} />} loading={syncingId === p.id} disabled={syncing} onClick={() => handleSync(p.id)}>
                                            Đồng bộ lại
                                        </Button>
                                        {p.token_expired && (
                                            <Button size="small" type="primary" icon={<KeyOutlined />} loading={reconnectingId === p.id} onClick={() => handleReconnect(p.id)}>Kết nối lại</Button>
                                        )}
                                        <Button size="small" icon={<ShopOutlined />} onClick={() => setBizPage(p)}>Thông tin cửa hàng</Button>
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
                                </Space>
                            </div>
                        </Card>
                        );
                    })}
                </Space>
            </Card>}

            {/* Tin nhắn sàn (Lazada IM / TikTok) tắt tạm — chưa triển khai xong; bật lại qua MARKETPLACE_CHAT_ENABLED. */}
            {platform !== 'zalo_oa' && MARKETPLACE_CHAT_ENABLED && (
                <>
                    <Card title={<><ShopOutlined style={{ color: '#0F146D' }} /> Lazada IM Chat</>} style={{ marginBottom: 16 }}>
                        <Space direction="vertical" size={8} style={{ display: 'flex' }}>
                            <Text type="secondary">
                                Lazada IM dùng <Text strong>app "IM ERP" riêng</Text> (tách khỏi Gian hàng). Kết nối để nhận & trả lời chat Lazada ngay trong hộp thư.
                            </Text>
                            <Button type="primary" icon={<ShopOutlined />} loading={connectLazadaIm.isPending} onClick={handleConnectLazadaIm} disabled={!canConnect}>
                                Kết nối Lazada IM Chat
                            </Button>
                        </Space>
                    </Card>

                    <Card title="TikTok">
                        <Text type="secondary">TikTok dùng chung kết nối với Gian hàng. Bật nhắn tin tại <Link to="/channels">trang Gian hàng</Link>.</Text>
                    </Card>
                </>
            )}

            <BusinessInfoDrawer
                open={bizPage !== null || bulkBizOpen}
                channelId={bizPage?.id ?? null}
                initial={bizPage?.business_info ?? null}
                bulkIds={bulkBizOpen ? [...selectedIds] : undefined}
                onClose={() => { setBizPage(null); setBulkBizOpen(false); }}
                onSaved={() => { if (bulkBizOpen) setSelectedIds(new Set()); }}
            />
        </div>
    );
}
