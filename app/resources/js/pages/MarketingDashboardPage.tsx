import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { App as AntApp, Button, Card, Checkbox, Collapse, DatePicker, Dropdown, Empty, Input, Popconfirm, Result, Segmented, Select, Space, Spin, Statistic, Table, Tag, Typography } from 'antd';
import { DisconnectOutlined, FacebookFilled, FundOutlined, SettingOutlined, SyncOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { errorMessage } from '@/lib/api';
import { openOAuthPopup } from '@/lib/oauthPopup';
import { useCan } from '@/lib/tenant';
import {
    type ForecastStrategy, type ReconRow, type ReportLevel, type ReportRow,
    useAdAccounts, useAdForecast, useAdReconciliation, useAdReport, useConnectFacebookAds,
    useDisconnectAdAccount, useGenerateForecast, useRefreshAdInsights,
} from '@/lib/marketing';

const { Text } = Typography;

const ADS_ERRORS: Record<string, string> = {
    facebook_ads_no_accounts: 'Tài khoản chưa có Ad Account nào, hoặc chưa cấp quyền ads_read.',
    facebook_ads_oauth_state: 'Phiên kết nối đã hết hạn. Vui lòng thử lại.',
    facebook_ads_oauth_failed: 'Kết nối Facebook Ads thất bại.',
};

function money(v: number | null | undefined, currency: string | null): string {
    if (v == null) return '—';
    return v.toLocaleString('vi-VN') + (currency ? ' ' + currency : '');
}
const num = (v: number | null | undefined) => (v == null ? '—' : v.toLocaleString('vi-VN'));
const pct = (v: number | null | undefined) => (v == null ? '—' : v.toFixed(2) + '%');
const dec = (v: number | null | undefined) => (v == null ? '—' : v.toFixed(2));

const LABELS: Record<ReportLevel, string> = { campaign: 'Chiến dịch', adset: 'Nhóm quảng cáo', ad: 'Quảng cáo' };
const COLS_KEY = 'marketing.report.columns';
// All toggleable columns (name is always shown).
const ALL_COLUMNS = [
    'external_id', 'status', 'objective', 'daily_budget', 'lifetime_budget',
    'spend', 'impressions', 'reach', 'clicks', 'ctr', 'cpc', 'cpm', 'frequency',
    'purchase_roas', 'messaging_conversations', 'leads',
] as const;
const DEFAULT_COLUMNS = ['external_id', 'status', 'objective', 'daily_budget', 'spend', 'impressions', 'clicks', 'ctr', 'cpc', 'cpm', 'purchase_roas'];
const COL_TITLE: Record<string, string> = {
    external_id: 'ID', status: 'Trạng thái', objective: 'Loại (objective)', daily_budget: 'NS/ngày', lifetime_budget: 'NS trọn đời',
    spend: 'Chi tiêu', impressions: 'Hiển thị', reach: 'Tiếp cận', clicks: 'Click', ctr: 'CTR', cpc: 'CPC', cpm: 'CPM',
    frequency: 'Tần suất', purchase_roas: 'ROAS', messaging_conversations: 'Hội thoại', leads: 'Leads',
};

/** /marketing — báo cáo quảng cáo Facebook kiểu Ads Manager (BM, 3 tab, cột tuỳ chỉnh, drill-down). */
export function MarketingDashboardPage() {
    const { message } = AntApp.useApp();
    const [params, setParams] = useSearchParams();
    const canConnect = useCan('marketing.connect');
    const connect = useConnectFacebookAds();
    const disconnect = useDisconnectAdAccount();
    const refresh = useRefreshAdInsights();
    const { data: accounts, isLoading: loadingAccounts } = useAdAccounts();

    const [bm, setBm] = useState<string | null>(null);
    const [accountId, setAccountId] = useState<number | null>(null);
    const [level, setLevel] = useState<ReportLevel>('campaign');
    const [range, setRange] = useState<[Dayjs, Dayjs]>([dayjs().subtract(6, 'day'), dayjs()]);
    const [q, setQ] = useState('');
    const [adId, setAdId] = useState('');
    const [objective, setObjective] = useState<string | undefined>(undefined);
    const [selCampaigns, setSelCampaigns] = useState<string[]>([]);
    const [selAdsets, setSelAdsets] = useState<string[]>([]);
    const [cols, setCols] = useState<string[]>(() => {
        try { return JSON.parse(localStorage.getItem(COLS_KEY) || '') ?? DEFAULT_COLUMNS; } catch { return DEFAULT_COLUMNS; }
    });
    useEffect(() => { localStorage.setItem(COLS_KEY, JSON.stringify(cols)); }, [cols]);

    // BM groups → accounts in selected BM.
    const bmGroups = useMemo(() => {
        const m = new Map<string, string>();
        (accounts ?? []).forEach((a) => m.set(a.business_id ?? '_', a.business_name ?? 'Không thuộc BM'));
        return [...m.entries()].map(([id, name]) => ({ id, name }));
    }, [accounts]);
    const bmAccounts = useMemo(() => (accounts ?? []).filter((a) => (a.business_id ?? '_') === (bm ?? bmGroups[0]?.id)), [accounts, bm, bmGroups]);
    const selectedId = accountId ?? bmAccounts[0]?.id ?? null;
    const currency = bmAccounts.find((a) => a.id === selectedId)?.currency ?? null;

    const since = range[0].format('YYYY-MM-DD');
    const until = range[1].format('YYYY-MM-DD');
    const filters = useMemo(() => ({
        campaign_ids: level === 'adset' ? selCampaigns : undefined,
        adset_ids: level === 'ad' ? selAdsets : undefined,
        q: q || undefined, objective, ad_id: adId || undefined,
    }), [level, selCampaigns, selAdsets, q, objective, adId]);
    const { data: report, isFetching } = useAdReport(selectedId, level, since, until, filters);
    const { data: recon } = useAdReconciliation(selectedId);
    const { data: forecast } = useAdForecast(selectedId);
    const genForecast = useGenerateForecast();

    const applyResult = (p: URLSearchParams) => {
        if (p.get('connected') === 'facebook_ads') { message.success('Đã kết nối Facebook Ads!'); params.delete('connected'); setParams(params, { replace: true }); }
        else { const e = p.get('error'); if (e?.startsWith('facebook_ads')) { message.error({ content: ADS_ERRORS[e] ?? 'Kết nối thất bại.', duration: 12 }); params.delete('error'); setParams(params, { replace: true }); } }
    };
    useEffect(() => {
        applyResult(params);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleConnect = () => connect.mutate(undefined, {
        onSuccess: async (d) => { const r = await openOAuthPopup(d.authorize_url); if (r.status === 'done' && r.redirect) applyResult(new URL(r.redirect, window.location.origin).searchParams); },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật INTEGRATIONS_ADS + cấu hình app.')),
    });
    const handleForecast = () => { if (selectedId != null) genForecast.mutate(selectedId, { onSuccess: () => message.success('Đã tạo dự báo.'), onError: (e) => message.error(errorMessage(e, 'Không tạo được dự báo (cooldown / chưa cấu hình provider AI marketing).')) }); };

    // Table columns (filter by visible set; name always first).
    const fmtCol: Record<string, (r: ReportRow) => React.ReactNode> = {
        external_id: (r) => <Text type="secondary" copyable={{ text: r.external_id }} style={{ fontSize: 12 }}>{r.external_id}</Text>,
        status: (r) => <Tag color={r.status === 'ACTIVE' ? 'green' : 'default'}>{r.effective_status ?? r.status ?? '—'}</Tag>,
        objective: (r) => r.objective ?? '—',
        daily_budget: (r) => money(r.daily_budget, currency),
        lifetime_budget: (r) => money(r.lifetime_budget, currency),
        spend: (r) => money(r.insights?.spend, currency),
        impressions: (r) => num(r.insights?.impressions),
        reach: (r) => num(r.insights?.reach),
        clicks: (r) => num(r.insights?.clicks),
        ctr: (r) => pct(r.insights?.ctr),
        cpc: (r) => money(r.insights?.cpc, currency),
        cpm: (r) => money(r.insights?.cpm, currency),
        frequency: (r) => dec(r.insights?.frequency),
        purchase_roas: (r) => dec(r.insights?.purchase_roas),
        messaging_conversations: (r) => num(r.insights?.messaging_conversations),
        leads: (r) => num(r.insights?.leads),
    };
    const columns = [
        { title: 'Tên', dataIndex: 'name', key: 'name', fixed: 'left' as const, width: 220, render: (v: string | null, r: ReportRow) => v ?? r.external_id },
        ...ALL_COLUMNS.filter((c) => cols.includes(c)).map((c) => ({ title: COL_TITLE[c], key: c, render: (_: unknown, r: ReportRow) => fmtCol[c](r) })),
    ];

    const rowSelection = level !== 'ad' ? {
        selectedRowKeys: level === 'campaign' ? selCampaigns : selAdsets,
        onChange: (keys: React.Key[]) => (level === 'campaign' ? setSelCampaigns : setSelAdsets)(keys.map(String)),
    } : undefined;

    const objectiveOptions = useMemo(() => {
        const s = new Set((report?.rows ?? []).map((r) => r.objective).filter(Boolean) as string[]);
        return [...s].map((o) => ({ label: o, value: o }));
    }, [report]);

    return (
        <div>
            <PageHeader title="Quảng cáo Facebook" subtitle="Báo cáo kiểu Ads Manager — lọc theo BM, ngày, 3 cấp; cột tuỳ chỉnh; drill-down." />

            <Card style={{ marginBottom: 16 }}>
                <Space wrap size={12}>
                    <Button type="primary" icon={<FacebookFilled />} loading={connect.isPending} onClick={handleConnect} disabled={!canConnect}>Kết nối Facebook Ads</Button>
                    {bmGroups.length > 0 && (
                        <Select style={{ minWidth: 200 }} value={bm ?? bmGroups[0]?.id} onChange={(v) => { setBm(v); setAccountId(null); }}
                            options={bmGroups.map((g) => ({ label: 'BM: ' + g.name, value: g.id }))} />
                    )}
                    {bmAccounts.length > 0 && (
                        <Select style={{ minWidth: 220 }} value={selectedId ?? undefined} onChange={(v) => setAccountId(Number(v))}
                            options={bmAccounts.map((a) => ({ label: a.name ?? a.external_account_id, value: a.id }))} />
                    )}
                    {selectedId != null && canConnect && (
                        <Popconfirm title="Ngắt kết nối Ad Account?" okText="Ngắt" okButtonProps={{ danger: true }} cancelText="Huỷ"
                            onConfirm={() => disconnect.mutate(selectedId, { onSuccess: () => { setAccountId(null); message.success('Đã ngắt.'); }, onError: (e) => message.error(errorMessage(e)) })}>
                            <Button danger size="small" icon={<DisconnectOutlined />}>Ngắt</Button>
                        </Popconfirm>
                    )}
                </Space>
            </Card>

            {loadingAccounts ? (
                <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>
            ) : (accounts?.length ?? 0) === 0 ? (
                <Card><Result icon={<FundOutlined />} title="Chưa kết nối tài khoản quảng cáo" subTitle="Bấm 'Kết nối Facebook Ads' để bắt đầu." /></Card>
            ) : (
                <>
                    <Card style={{ marginBottom: 16 }}
                        title={<Segmented value={level} onChange={(v) => setLevel(v as ReportLevel)}
                            options={(['campaign', 'adset', 'ad'] as ReportLevel[]).map((l) => ({ label: LABELS[l], value: l }))} />}
                        extra={
                            <Dropdown trigger={['click']} dropdownRender={() => (
                                <Card size="small" styles={{ body: { maxHeight: 320, overflow: 'auto' } }}>
                                    <Checkbox.Group value={cols} onChange={(v) => setCols(v as string[])}>
                                        <Space direction="vertical">{ALL_COLUMNS.map((c) => <Checkbox key={c} value={c}>{COL_TITLE[c]}</Checkbox>)}</Space>
                                    </Checkbox.Group>
                                </Card>
                            )}><Button size="small" icon={<SettingOutlined />}>Cột</Button></Dropdown>
                        }>
                        <Space wrap size={8} style={{ marginBottom: 12 }}>
                            <DatePicker.RangePicker value={range} onChange={(v) => v && v[0] && v[1] && setRange([v[0], v[1]])} allowClear={false} format="DD/MM/YYYY" />
                            <Input.Search placeholder="Tên chiến dịch/nhóm/QC" allowClear value={q} onChange={(e) => setQ(e.target.value)} style={{ width: 220 }} />
                            <Input placeholder="ID" allowClear value={adId} onChange={(e) => setAdId(e.target.value)} style={{ width: 160 }} />
                            <Select placeholder="Loại (objective)" allowClear value={objective} onChange={setObjective} options={objectiveOptions} style={{ minWidth: 180 }} />
                            <Button icon={<SyncOutlined spin={isFetching} />} onClick={() => selectedId != null && refresh.mutate(selectedId)}>Làm mới</Button>
                            {level !== 'campaign' && selCampaigns.length === 0 && level === 'adset' && <Text type="secondary">Tích chiến dịch ở tab Chiến dịch để lọc nhóm.</Text>}
                        </Space>
                        <Table<ReportRow>
                            rowKey="external_id" size="small" scroll={{ x: 'max-content' }}
                            loading={isFetching} dataSource={report?.rows ?? []} columns={columns} rowSelection={rowSelection}
                            pagination={{ pageSize: 50, showSizeChanger: true }}
                            locale={{ emptyText: <Empty description="Không có dữ liệu cho bộ lọc/khoảng ngày này." /> }}
                        />
                    </Card>

                    <Collapse items={[{
                        key: 'extra', label: 'Đối soát đơn thủ công & Dự báo AI',
                        children: (
                            <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                                <div>
                                    <Space style={{ marginBottom: 8 }}><Text strong>Dự báo & chiến lược (AI)</Text>
                                        {canConnect && <Button size="small" type="primary" loading={genForecast.isPending} onClick={handleForecast}>Tạo dự báo</Button>}</Space>
                                    {forecast ? (
                                        <Space size={32} wrap>
                                            <Statistic title="Đơn (7 ngày tới)" value={forecast.payload.forecast?.next_7d?.orders ?? '—'} />
                                            <Statistic title="Chi tiêu (7 ngày tới)" value={money(forecast.payload.forecast?.next_7d?.spend ?? null, currency)} />
                                            <Statistic title="Cost/đơn dự báo" value={money(forecast.payload.forecast?.next_7d?.projected_cost_per_order ?? null, currency)} />
                                            <div style={{ maxWidth: 480 }}>{(forecast.payload.strategy ?? []).map((s: ForecastStrategy, i: number) => <div key={i}><Tag>{s.action}</Tag>{s.rationale}</div>)}</div>
                                        </Space>
                                    ) : <Text type="secondary">Chưa có dự báo — bấm "Tạo dự báo".</Text>}
                                </div>
                                <Table<ReconRow>
                                    title={() => <Text strong>Đối soát quảng cáo ↔ đơn thủ công (theo ngày)</Text>}
                                    rowKey="date" size="small" pagination={false} dataSource={(recon?.rows ?? []).slice().reverse()}
                                    columns={[
                                        { title: 'Ngày', dataIndex: 'date', key: 'date' },
                                        { title: 'Chi tiêu', dataIndex: 'spend', key: 'spend', render: (v: number) => money(v, currency) },
                                        { title: 'Hội thoại', dataIndex: 'conversations', key: 'c' },
                                        { title: 'Leads', dataIndex: 'leads', key: 'l' },
                                        { title: 'Đơn thủ công', dataIndex: 'manual_orders', key: 'mo' },
                                        { title: 'Cost/đơn', dataIndex: 'cost_per_order', key: 'cpo', render: (v: number | null) => money(v, currency) },
                                        { title: 'Hội thoại→đơn', dataIndex: 'conv_to_order_pct', key: 'cvr', render: (v: number | null) => v != null ? v.toFixed(1) + '%' : '—' },
                                    ]}
                                />
                            </Space>
                        ),
                    }]} />
                </>
            )}
        </div>
    );
}
