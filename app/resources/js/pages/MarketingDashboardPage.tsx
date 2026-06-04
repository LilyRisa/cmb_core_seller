import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { App as AntApp, Button, Card, Empty, Popconfirm, Result, Segmented, Space, Spin, Statistic, Table, Tag, Tooltip, Typography } from 'antd';
import { BarChartOutlined, DisconnectOutlined, FacebookFilled, SyncOutlined } from '@ant-design/icons';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { openOAuthPopup } from '@/lib/oauthPopup';
import { useCan } from '@/lib/tenant';
import {
    type AdEntityRow,
    useAdAccounts, useAdInsights, useConnectFacebookAds, useDisconnectAdAccount, useRefreshAdInsights,
} from '@/lib/marketing';

const { Text } = Typography;

/** Mã `?error=` từ callback Facebook Ads (AdsOAuthController). */
const ADS_ERRORS: Record<string, string> = {
    facebook_ads_no_accounts: 'Tài khoản chưa có Ad Account nào, hoặc chưa cấp quyền ads_read.',
    facebook_ads_oauth_state: 'Phiên kết nối đã hết hạn. Vui lòng thử lại.',
    facebook_ads_oauth_failed: 'Kết nối Facebook Ads thất bại. Vui lòng thử lại sau.',
};

function money(v: number | null | undefined, currency: string | null): string {
    if (v == null) return '—';
    return v.toLocaleString('vi-VN') + (currency ? ' ' + currency : '');
}

/** /marketing — dashboard quảng cáo Facebook near-real-time (SPEC 2026-06-04). */
export function MarketingDashboardPage() {
    const { message } = AntApp.useApp();
    const [params, setParams] = useSearchParams();
    const canConnect = useCan('marketing.connect');
    const connect = useConnectFacebookAds();
    const disconnect = useDisconnectAdAccount();
    const refresh = useRefreshAdInsights();
    const { data: accounts, isLoading: loadingAccounts } = useAdAccounts();
    const [accountId, setAccountId] = useState<number | null>(null);

    const selectedId = accountId ?? accounts?.[0]?.id ?? null;
    const selectedAccount = useMemo(() => accounts?.find((a) => a.id === selectedId) ?? null, [accounts, selectedId]);
    const { data: insights, isFetching } = useAdInsights(selectedId);

    const applyResult = (p: URLSearchParams) => {
        const connected = p.get('connected');
        const err = p.get('error');
        if (connected === 'facebook_ads') {
            message.success('Đã kết nối Facebook Ads!');
            params.delete('connected'); setParams(params, { replace: true });
        } else if (err && err.startsWith('facebook_ads')) {
            message.error({ content: ADS_ERRORS[err] ?? 'Kết nối Facebook Ads thất bại.', duration: 12 });
            params.delete('error'); setParams(params, { replace: true });
        }
    };

    useEffect(() => {
        applyResult(params);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleConnect = () => connect.mutate(undefined, {
        onSuccess: async (d) => {
            const res = await openOAuthPopup(d.authorize_url);
            if (res.status === 'done' && res.redirect) {
                applyResult(new URL(res.redirect, window.location.origin).searchParams);
            }
        },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật INTEGRATIONS_ADS=facebook.')),
    });

    const handleRefresh = () => {
        if (selectedId == null) return;
        refresh.mutate(selectedId, {
            onSuccess: () => message.success('Đã yêu cầu cập nhật số liệu (tươi sau ~vài phút).'),
            onError: (e) => message.error(errorMessage(e, 'Không cập nhật được.')),
        });
    };

    const currency = selectedAccount?.currency ?? null;
    const acc = insights?.account.insights ?? null;

    const columns = [
        { title: 'Tên', dataIndex: 'name', key: 'name', render: (v: string | null, r: AdEntityRow) => v ?? r.external_id },
        { title: 'Cấp', dataIndex: 'level', key: 'level', render: (v: string) => <Tag>{v}</Tag> },
        { title: 'Trạng thái', dataIndex: 'effective_status', key: 'status', render: (v: string | null, r: AdEntityRow) => <Tag color={r.status === 'ACTIVE' ? 'green' : 'default'}>{v ?? r.status ?? '—'}</Tag> },
        { title: 'Ngân sách/ngày', dataIndex: 'daily_budget', key: 'budget', render: (v: number | null) => money(v, currency) },
        { title: 'Chi tiêu', key: 'spend', render: (_: unknown, r: AdEntityRow) => money(r.insights?.spend, currency) },
        { title: 'Hiển thị', key: 'impr', render: (_: unknown, r: AdEntityRow) => (r.insights?.impressions ?? 0).toLocaleString('vi-VN') },
        { title: 'Click', key: 'clicks', render: (_: unknown, r: AdEntityRow) => (r.insights?.clicks ?? 0).toLocaleString('vi-VN') },
        { title: 'CTR', key: 'ctr', render: (_: unknown, r: AdEntityRow) => r.insights?.ctr != null ? r.insights.ctr.toFixed(2) + '%' : '—' },
        { title: 'CPC', key: 'cpc', render: (_: unknown, r: AdEntityRow) => money(r.insights?.cpc, currency) },
        { title: 'ROAS', key: 'roas', render: (_: unknown, r: AdEntityRow) => r.insights?.purchase_roas != null ? r.insights.purchase_roas.toFixed(2) : '—' },
        {
            title: '', key: 'flag', render: (_: unknown, r: AdEntityRow) => r.insights?.is_finalizing
                ? <Tooltip title="Số liệu còn dao động trong 28 ngày (Facebook re-attribution)"><Tag color="orange">đang hoàn tất</Tag></Tooltip>
                : null,
        },
    ];

    return (
        <div>
            <PageHeader title="Quảng cáo Facebook" subtitle="Số liệu near-real-time (cập nhật ~15 phút) — AI đánh giá & tối ưu sẽ bổ sung ở giai đoạn sau." />

            <Card style={{ marginBottom: 16 }}>
                <Space wrap>
                    <Button type="primary" icon={<FacebookFilled />} loading={connect.isPending} onClick={handleConnect} disabled={!canConnect}>
                        Kết nối Facebook Ads
                    </Button>
                    {(accounts?.length ?? 0) > 0 && (
                        <Segmented
                            value={selectedId ?? undefined}
                            onChange={(v) => setAccountId(Number(v))}
                            options={(accounts ?? []).map((a) => ({ label: a.name ?? a.external_account_id, value: a.id }))}
                        />
                    )}
                    {selectedId != null && (
                        <>
                            <Button icon={<SyncOutlined spin={isFetching || refresh.isPending} />} onClick={handleRefresh} loading={refresh.isPending}>
                                Làm mới
                            </Button>
                            {canConnect && (
                                <Popconfirm
                                    title="Ngắt kết nối Ad Account?"
                                    description="Gỡ kết nối tài khoản quảng cáo này khỏi hệ thống."
                                    okText="Ngắt kết nối" okButtonProps={{ danger: true }} cancelText="Huỷ"
                                    onConfirm={() => disconnect.mutate(selectedId, {
                                        onSuccess: () => { setAccountId(null); message.success('Đã ngắt kết nối.'); },
                                        onError: (e) => message.error(errorMessage(e)),
                                    })}
                                >
                                    <Button danger icon={<DisconnectOutlined />}>Ngắt kết nối</Button>
                                </Popconfirm>
                            )}
                        </>
                    )}
                </Space>
            </Card>

            {loadingAccounts ? (
                <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>
            ) : (accounts?.length ?? 0) === 0 ? (
                <Card><Result icon={<BarChartOutlined />} title="Chưa kết nối tài khoản quảng cáo" subTitle="Bấm 'Kết nối Facebook Ads' để bắt đầu xem số liệu." /></Card>
            ) : (
                <>
                    <Card title={`Tổng quan${selectedAccount ? ' · ' + (selectedAccount.name ?? selectedAccount.external_account_id) : ''}`} style={{ marginBottom: 16 }}>
                        <Space size={48} wrap>
                            <Statistic title="Chi tiêu" value={money(acc?.spend, currency)} />
                            <Statistic title="Hiển thị" value={(acc?.impressions ?? 0).toLocaleString('vi-VN')} />
                            <Statistic title="Click" value={(acc?.clicks ?? 0).toLocaleString('vi-VN')} />
                            <Statistic title="CTR" value={acc?.ctr != null ? acc.ctr.toFixed(2) + '%' : '—'} />
                            <Statistic title="CPC" value={money(acc?.cpc, currency)} />
                            <Statistic title="ROAS" value={acc?.purchase_roas != null ? acc.purchase_roas.toFixed(2) : '—'} />
                        </Space>
                        {acc == null && <div style={{ marginTop: 12 }}><Text type="secondary">Chưa có số liệu — bấm "Làm mới" hoặc chờ poll tự động.</Text></div>}
                    </Card>

                    <Card title="Chi tiết campaign / ad set / ad">
                        <Table<AdEntityRow>
                            rowKey="id"
                            size="small"
                            dataSource={insights?.entities ?? []}
                            columns={columns}
                            pagination={false}
                            locale={{ emptyText: <Empty description="Chưa có entity — đang đồng bộ." /> }}
                        />
                    </Card>
                </>
            )}
        </div>
    );
}
