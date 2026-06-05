import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { App as AntApp, Button, Card, Checkbox, Collapse, DatePicker, Dropdown, Empty, Input, InputNumber, List, Popconfirm, Result, Segmented, Select, Space, Spin, Statistic, Table, Tag, Tooltip, Typography } from 'antd';
import { BulbOutlined, CheckOutlined, CloseOutlined, DisconnectOutlined, EditOutlined, FacebookFilled, FundOutlined, PauseCircleOutlined, PlayCircleOutlined, PlusOutlined, QuestionCircleOutlined, RobotOutlined, SettingOutlined, SyncOutlined } from '@ant-design/icons';
import { useAdDrafts, useDeleteDraft } from '@/lib/adWizard';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { CampaignAiInsightDrawer } from '@/pages/marketing/CampaignAiInsightDrawer';
import { AbComparisonPanel } from '@/pages/marketing/AbComparisonPanel';
import { errorMessage } from '@/lib/api';
import { openOAuthPopup } from '@/lib/oauthPopup';
import { useCan } from '@/lib/tenant';
import {
    type ForecastStrategy, type ReconRow, type ReportLevel, type ReportRow,
    useAdAccounts, useAdForecast, useAdReconciliation, useAdReport, useBulkDisconnectAccounts,
    useConnectFacebookAds, useDisconnectAdAccount, useGenerateForecast, useRefreshAdInsights,
    useUpdateAdEntity,
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
    external_id: 'ID', status: 'Trạng thái', objective: 'Mục tiêu', daily_budget: 'NS/ngày', lifetime_budget: 'NS trọn đời',
    spend: 'Chi tiêu', impressions: 'Hiển thị', reach: 'Tiếp cận', clicks: 'Click', ctr: 'CTR', cpc: 'CPC', cpm: 'CPM',
    frequency: 'Tần suất', purchase_roas: 'ROAS', messaging_conversations: 'Hội thoại', leads: 'Leads',
};

// Giải thích chỉ số (tooltip khi di chuột vào icon "?").
const COL_HELP: Record<string, string> = {
    objective: 'Mục tiêu tối ưu của chiến dịch — Facebook phân phối theo mục tiêu này.',
    status: 'Trạng thái phân phối hiện tại của quảng cáo.',
    daily_budget: 'Số tiền tối đa chi cho mỗi ngày.',
    lifetime_budget: 'Số tiền tối đa cho toàn bộ thời gian chạy.',
    spend: 'Tổng số tiền đã chi cho quảng cáo.',
    impressions: 'Số lần quảng cáo được hiển thị (tính cả lặp lại).',
    reach: 'Số người dùng (không trùng) đã nhìn thấy quảng cáo.',
    clicks: 'Số lượt nhấp vào quảng cáo.',
    ctr: 'Tỷ lệ nhấp = Click ÷ Hiển thị (%). Cao nghĩa là nội dung hấp dẫn.',
    cpc: 'Chi phí trung bình mỗi lượt nhấp = Chi tiêu ÷ Click.',
    cpm: 'Chi phí cho mỗi 1.000 lần hiển thị.',
    frequency: 'Số lần trung bình một người nhìn thấy quảng cáo.',
    purchase_roas: 'Lợi nhuận trên chi tiêu quảng cáo = Doanh thu ÷ Chi tiêu.',
    messaging_conversations: 'Số cuộc hội thoại Messenger bắt đầu từ quảng cáo.',
    leads: 'Số khách hàng tiềm năng (lead) thu được.',
};

// Chuẩn hoá mục tiêu Facebook (raw → tiếng Việt). Gồm cả mục tiêu ODAX mới lẫn mục tiêu cũ.
const OBJECTIVE_VI: Record<string, string> = {
    OUTCOME_SALES: 'Bán hàng', OUTCOME_LEADS: 'Khách hàng tiềm năng', OUTCOME_ENGAGEMENT: 'Tương tác',
    OUTCOME_AWARENESS: 'Nhận diện thương hiệu', OUTCOME_TRAFFIC: 'Truy cập web', OUTCOME_APP_PROMOTION: 'Quảng bá ứng dụng',
    LINK_CLICKS: 'Lượt truy cập', CONVERSIONS: 'Chuyển đổi', POST_ENGAGEMENT: 'Tương tác bài viết',
    PAGE_LIKES: 'Thích Trang', MESSAGES: 'Tin nhắn', LEAD_GENERATION: 'Thu thập KH tiềm năng',
    REACH: 'Tiếp cận', BRAND_AWARENESS: 'Nhận diện thương hiệu', VIDEO_VIEWS: 'Lượt xem video',
    PRODUCT_CATALOG_SALES: 'Bán theo danh mục', STORE_VISITS: 'Ghé cửa hàng', APP_INSTALLS: 'Cài đặt ứng dụng',
};
const objectiveVi = (v: string | null) => (v ? OBJECTIVE_VI[v] ?? v : '—');

// Chuẩn hoá trạng thái (raw → tiếng Việt + màu Tag).
const STATUS_VI: Record<string, { label: string; color: string }> = {
    ACTIVE: { label: 'Đang chạy', color: 'green' },
    PAUSED: { label: 'Tạm dừng', color: 'default' },
    CAMPAIGN_PAUSED: { label: 'Chiến dịch tạm dừng', color: 'default' },
    ADSET_PAUSED: { label: 'Nhóm tạm dừng', color: 'default' },
    DELETED: { label: 'Đã xoá', color: 'red' },
    ARCHIVED: { label: 'Đã lưu trữ', color: 'default' },
    PENDING_REVIEW: { label: 'Chờ duyệt', color: 'gold' },
    IN_PROCESS: { label: 'Đang xử lý', color: 'blue' },
    PREAPPROVED: { label: 'Đã duyệt sơ bộ', color: 'blue' },
    DISAPPROVED: { label: 'Bị từ chối', color: 'red' },
    WITH_ISSUES: { label: 'Có vấn đề', color: 'orange' },
    PENDING_BILLING_INFO: { label: 'Chờ thông tin thanh toán', color: 'gold' },
};
const statusVi = (v: string | null) => (v ? STATUS_VI[v] ?? { label: v, color: 'default' } : { label: '—', color: 'default' });

/** /marketing — báo cáo quảng cáo Facebook kiểu Ads Manager (BM, 3 tab, cột tuỳ chỉnh, drill-down). */
export function MarketingDashboardPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const [params, setParams] = useSearchParams();
    const canConnect = useCan('marketing.connect');
    const { data: drafts } = useAdDrafts();
    const deleteDraft = useDeleteDraft();
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

    // Quick date ranges (computed each render so "hôm nay" is always current).
    const rangePresets: { label: string; value: [Dayjs, Dayjs] }[] = useMemo(() => [
        { label: 'Hôm nay', value: [dayjs(), dayjs()] },
        { label: 'Hôm qua', value: [dayjs().subtract(1, 'day'), dayjs().subtract(1, 'day')] },
        { label: '7 ngày qua', value: [dayjs().subtract(6, 'day'), dayjs()] },
        { label: '30 ngày qua', value: [dayjs().subtract(29, 'day'), dayjs()] },
        { label: '90 ngày qua', value: [dayjs().subtract(89, 'day'), dayjs()] },
    ], []);
    const filters = useMemo(() => ({
        // Ad tab inherits the campaign scope (all ads of the campaign's adsets) and
        // narrows further when specific adsets are ticked — see AdsReportService.
        campaign_ids: level === 'adset' || level === 'ad' ? selCampaigns : undefined,
        adset_ids: level === 'ad' ? selAdsets : undefined,
        q: q || undefined, objective, ad_id: adId || undefined,
    }), [level, selCampaigns, selAdsets, q, objective, adId]);
    const { data: report, isFetching } = useAdReport(selectedId, level, since, until, filters);
    const [aiCampaign, setAiCampaign] = useState<{ id: string; name: string | null } | null>(null);
    const updateEntity = useUpdateAdEntity();
    const bulkDisconnect = useBulkDisconnectAccounts();
    // Inline edit: which cell is being edited + its draft value.
    const [editing, setEditing] = useState<{ key: string; field: 'name' | 'budget'; value: string } | null>(null);

    function saveEdit(r: ReportRow) {
        if (selectedId == null || editing == null) return;
        const patch = editing.field === 'name'
            ? { name: editing.value.trim() }
            : { daily_budget_major: Number(editing.value) || 0 };
        if (editing.field === 'name' && patch.name === '') { setEditing(null); return; }
        updateEntity.mutate(
            { accountId: selectedId, externalId: r.external_id, level, ...patch },
            {
                onSuccess: () => { message.success('Đã cập nhật.'); setEditing(null); },
                onError: (e) => message.error(errorMessage(e, 'Cập nhật thất bại.')),
            },
        );
    }

    function toggleStatus(r: ReportRow) {
        if (selectedId == null) return;
        const isActive = (r.effective_status ?? r.status) === 'ACTIVE';
        updateEntity.mutate(
            { accountId: selectedId, externalId: r.external_id, level, status: isActive ? 'PAUSED' : 'ACTIVE' },
            {
                onSuccess: () => message.success(isActive ? 'Đã tạm dừng.' : 'Đã chạy lại.'),
                onError: (e) => message.error(errorMessage(e, 'Đổi trạng thái thất bại.')),
            },
        );
    }

    const { data: recon } = useAdReconciliation(selectedId);
    const { data: forecast } = useAdForecast(selectedId);
    const genForecast = useGenerateForecast();

    const qc = useQueryClient();
    const applyResult = (p: URLSearchParams) => {
        if (p.get('connected') === 'facebook_ads') {
            message.success('Đã kết nối Facebook Ads!');
            params.delete('connected'); setParams(params, { replace: true });
            qc.invalidateQueries({ queryKey: ['marketing'] }); // refetch accounts/report ngay, không cần reload tay
        } else { const e = p.get('error'); if (e?.startsWith('facebook_ads')) { message.error({ content: ADS_ERRORS[e] ?? 'Kết nối thất bại.', duration: 12 }); params.delete('error'); setParams(params, { replace: true }); } }
    };
    useEffect(() => {
        applyResult(params);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleConnect = () => connect.mutate(undefined, {
        onSuccess: async (d) => { const r = await openOAuthPopup(d.authorize_url); if (r.status === 'done' && r.redirect) applyResult(new URL(r.redirect, window.location.origin).searchParams); },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật INTEGRATIONS_ADS + cấu hình app.')),
    });
    const handleForecast = () => {
        if (selectedId == null) return;
        genForecast.mutate(selectedId, {
            onSuccess: (res) => res.queued
                ? message.info('Đang tạo báo cáo — hệ thống sẽ gửi email cho Quản trị khi xong.')
                : message.success('Đã có báo cáo.'),
            onError: (e) => message.error(errorMessage(e, 'Không tạo được dự báo (cooldown / chưa cấu hình provider AI marketing).')),
        });
    };

    // Table columns (filter by visible set; name always first).
    const fmtCol: Record<string, (r: ReportRow) => React.ReactNode> = {
        external_id: (r) => <Text type="secondary" copyable={{ text: r.external_id }} style={{ fontSize: 12 }}>{r.external_id}</Text>,
        status: (r) => {
            const s = statusVi(r.effective_status ?? r.status ?? null);
            const isActive = (r.effective_status ?? r.status) === 'ACTIVE';
            return (
                <Space size={4}>
                    <Tag color={s.color}>{s.label}</Tag>
                    {canConnect && (
                        <Tooltip title={isActive ? 'Tạm dừng' : 'Chạy lại'}>
                            <Button
                                type="text"
                                size="small"
                                icon={isActive ? <PauseCircleOutlined /> : <PlayCircleOutlined />}
                                onClick={() => toggleStatus(r)}
                            />
                        </Tooltip>
                    )}
                </Space>
            );
        },
        objective: (r) => objectiveVi(r.objective),
        daily_budget: (r) => {
            const isEditing = editing?.key === r.external_id && editing.field === 'budget';
            if (isEditing) {
                return (
                    <Space size={2}>
                        <InputNumber
                            size="small"
                            min={1000}
                            step={10000}
                            autoFocus
                            style={{ width: 120 }}
                            value={Number(editing.value) || undefined}
                            onChange={(v) => setEditing({ key: r.external_id, field: 'budget', value: String(v ?? '') })}
                            onPressEnter={() => saveEdit(r)}
                            formatter={(v) => (v != null ? Number(v).toLocaleString('vi-VN') : '')}
                            parser={(v) => (v != null ? Number(v.replace(/\./g, '')) : 0)}
                        />
                        <Button type="text" size="small" icon={<CheckOutlined />} loading={updateEntity.isPending} onClick={() => saveEdit(r)} />
                        <Button type="text" size="small" icon={<CloseOutlined />} onClick={() => setEditing(null)} />
                    </Space>
                );
            }
            return (
                <Space size={2}>
                    {money(r.daily_budget, currency)}
                    {canConnect && r.daily_budget != null && (
                        <Tooltip title="Sửa ngân sách">
                            <Button
                                type="text"
                                size="small"
                                icon={<EditOutlined />}
                                onClick={() => setEditing({ key: r.external_id, field: 'budget', value: String(r.daily_budget ?? '') })}
                            />
                        </Tooltip>
                    )}
                </Space>
            );
        },
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
        {
            title: 'Tên', dataIndex: 'name', key: 'name', fixed: 'left' as const, width: 240,
            render: (v: string | null, r: ReportRow) => {
                const isEditing = editing?.key === r.external_id && editing.field === 'name';
                if (isEditing) {
                    return (
                        <Space size={2}>
                            <Input
                                size="small"
                                autoFocus
                                style={{ width: 150 }}
                                value={editing.value}
                                onChange={(e) => setEditing({ key: r.external_id, field: 'name', value: e.target.value })}
                                onPressEnter={() => saveEdit(r)}
                            />
                            <Button type="text" size="small" icon={<CheckOutlined />} loading={updateEntity.isPending} onClick={() => saveEdit(r)} />
                            <Button type="text" size="small" icon={<CloseOutlined />} onClick={() => setEditing(null)} />
                        </Space>
                    );
                }
                return (
                    <Space size={2}>
                        <span>{v ?? r.external_id}</span>
                        {canConnect && (
                            <Tooltip title="Đổi tên">
                                <Button
                                    type="text"
                                    size="small"
                                    icon={<EditOutlined />}
                                    onClick={() => setEditing({ key: r.external_id, field: 'name', value: v ?? '' })}
                                />
                            </Tooltip>
                        )}
                    </Space>
                );
            },
        },
        ...ALL_COLUMNS.filter((c) => cols.includes(c)).map((c) => ({
            title: COL_HELP[c]
                ? <Space size={4}>{COL_TITLE[c]}<Tooltip title={COL_HELP[c]}><QuestionCircleOutlined style={{ color: '#aaa', fontSize: 12, cursor: 'help' }} /></Tooltip></Space>
                : COL_TITLE[c],
            key: c,
            render: (_: unknown, r: ReportRow) => fmtCol[c](r),
        })),
        ...(level === 'campaign' ? [{
            title: 'AI', key: 'ai_insight', fixed: 'right' as const, width: 110,
            render: (_: unknown, r: ReportRow) => (
                <Tooltip title="Phân tích AI cho riêng chiến dịch này">
                    <Button size="small" icon={<RobotOutlined />} onClick={() => setAiCampaign({ id: r.external_id, name: r.name })}>
                        Phân tích
                    </Button>
                </Tooltip>
            ),
        }] : []),
    ];

    const rowSelection = level !== 'ad' ? {
        selectedRowKeys: level === 'campaign' ? selCampaigns : selAdsets,
        onChange: (keys: React.Key[]) => (level === 'campaign' ? setSelCampaigns : setSelAdsets)(keys.map(String)),
    } : undefined;

    const objectiveOptions = useMemo(() => {
        const s = new Set((report?.rows ?? []).map((r) => r.objective).filter(Boolean) as string[]);
        return [...s].map((o) => ({ label: objectiveVi(o), value: o }));
    }, [report]);

    // "Đang chạy" = effective_status (ưu tiên) hoặc status === ACTIVE.
    const isActiveRow = (r: ReportRow) => (r.effective_status ?? r.status) === 'ACTIVE';
    // Đẩy hàng đang chạy lên đầu, giữ nguyên thứ tự cũ trong từng nhóm (sort ổn định).
    const sortedRows = useMemo(
        () => [...(report?.rows ?? [])].sort((a, b) => (isActiveRow(a) ? 0 : 1) - (isActiveRow(b) ? 0 : 1)),
        [report],
    );

    return (
        <div>
            <PageHeader title="Quảng cáo Facebook" subtitle="Báo cáo kiểu Ads Manager — lọc theo BM, ngày, 3 cấp; cột tuỳ chỉnh; drill-down." />

            <Card style={{ marginBottom: 16 }}>
                <Space wrap size={12}>
                    <Button type="primary" icon={<FacebookFilled />} loading={connect.isPending} onClick={handleConnect} disabled={!canConnect}>Kết nối Facebook Ads</Button>
                    <Button type="primary" icon={<PlusOutlined />} disabled={selectedId == null} onClick={() => navigate('/marketing/ads/new?accountId=' + selectedId)}>Tạo quảng cáo</Button>
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
                    {canConnect && (bm ?? bmGroups[0]?.id) != null && (bm ?? bmGroups[0]?.id) !== '_' && bmAccounts.length > 0 && (
                        <Popconfirm
                            title={`Ngắt toàn bộ ${bmAccounts.length} tài khoản trong BM này?`}
                            okText="Ngắt cả BM" okButtonProps={{ danger: true }} cancelText="Huỷ"
                            onConfirm={() => {
                                const businessId = (bm ?? bmGroups[0]?.id) as string;
                                bulkDisconnect.mutate({ business_id: businessId }, {
                                    onSuccess: (d) => { setAccountId(null); setBm(null); message.success(`Đã ngắt ${d.deleted} tài khoản.`); },
                                    onError: (e) => message.error(errorMessage(e)),
                                });
                            }}
                        >
                            <Button danger size="small" loading={bulkDisconnect.isPending} icon={<DisconnectOutlined />}>Ngắt cả BM</Button>
                        </Popconfirm>
                    )}
                </Space>
            </Card>

            {(drafts?.length ?? 0) > 0 && (
                <Card title="Bản nháp của tôi" size="small" style={{ marginBottom: 16 }}>
                    <List
                        size="small"
                        dataSource={drafts ?? []}
                        renderItem={(d) => (
                            <List.Item
                                actions={[
                                    <Button
                                        key="edit"
                                        type="link"
                                        icon={<EditOutlined />}
                                        size="small"
                                        onClick={() => navigate('/marketing/ads/' + d.id + '/edit')}
                                    >
                                        Sửa
                                    </Button>,
                                    <Popconfirm
                                        key="delete"
                                        title="Xoá bản nháp?"
                                        okText="Xoá"
                                        okButtonProps={{ danger: true }}
                                        cancelText="Huỷ"
                                        onConfirm={() => deleteDraft.mutate(d.id, { onError: (e) => message.error(errorMessage(e)) })}
                                    >
                                        <Button type="link" danger size="small">Xoá</Button>
                                    </Popconfirm>,
                                ]}
                            >
                                <Space>
                                    <Text>{d.name ?? 'Chưa đặt tên'}</Text>
                                    <Tag color={d.status === 'published' ? 'green' : d.status === 'failed' ? 'red' : d.status === 'publishing' ? 'processing' : 'default'}>
                                        {d.status === 'published' ? 'Đã xuất bản' : d.status === 'failed' ? 'Lỗi' : d.status === 'publishing' ? 'Đang xuất bản' : 'Nháp'}
                                    </Tag>
                                </Space>
                            </List.Item>
                        )}
                    />
                </Card>
            )}

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
                            <DatePicker.RangePicker value={range} onChange={(v) => v && v[0] && v[1] && setRange([v[0], v[1]])} allowClear={false} format="DD/MM/YYYY" presets={rangePresets} />
                            <Input.Search placeholder="Tên chiến dịch/nhóm/QC" allowClear value={q} onChange={(e) => setQ(e.target.value)} style={{ width: 220 }} />
                            <Input placeholder="ID" allowClear value={adId} onChange={(e) => setAdId(e.target.value)} style={{ width: 160 }} />
                            <Select placeholder="Loại (objective)" allowClear value={objective} onChange={setObjective} options={objectiveOptions} style={{ minWidth: 180 }} />
                            <Button icon={<SyncOutlined spin={isFetching} />} onClick={() => selectedId != null && refresh.mutate(selectedId)}>Làm mới</Button>
                            {level === 'campaign' && selCampaigns.length > 0 && (
                                <Tag color="blue" closable onClose={() => setSelCampaigns([])}>Đã chọn {selCampaigns.length} chiến dịch</Tag>
                            )}
                            {level === 'adset' && selAdsets.length > 0 && (
                                <Tag color="blue" closable onClose={() => setSelAdsets([])}>Đã chọn {selAdsets.length} nhóm quảng cáo</Tag>
                            )}
                            {level === 'adset' && selCampaigns.length > 0 && (
                                <Text type="secondary">Đang lọc theo {selCampaigns.length} chiến dịch đã tích.</Text>
                            )}
                            {level === 'adset' && selCampaigns.length === 0 && <Text type="secondary">Tích chiến dịch ở tab Chiến dịch để lọc nhóm.</Text>}
                        </Space>
                        <Table<ReportRow>
                            rowKey="external_id" size="small" scroll={{ x: 'max-content' }}
                            rowClassName={(r) => (isActiveRow(r) ? 'marketing-row-active' : '')}
                            loading={isFetching} dataSource={sortedRows} columns={columns} rowSelection={rowSelection}
                            pagination={{ defaultPageSize: 50, showSizeChanger: true, pageSizeOptions: ['20', '50', '100', '200'] }}
                            locale={{ emptyText: <Empty description="Không có dữ liệu cho bộ lọc/khoảng ngày này." /> }}
                        />
                    </Card>

                    {level === 'adset' && (
                        <Collapse
                            style={{ marginBottom: 16 }}
                            items={[{
                                key: 'ab', label: 'So sánh A/B (biến thể nhóm quảng cáo)',
                                children: <AbComparisonPanel rows={sortedRows} currency={currency} />,
                            }]}
                        />
                    )}

                    <Collapse items={[{
                        key: 'extra', label: 'Đối soát đơn thủ công & Dự báo AI',
                        children: (
                            <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                                <div>
                                    <Space style={{ marginBottom: 8 }}><Text strong>Dự báo & chiến lược (AI)</Text>
                                        {canConnect && <Button size="small" type="primary" loading={genForecast.isPending} onClick={handleForecast}>Tạo dự báo</Button>}</Space>
                                    {forecast ? (
                                        <Space direction="vertical" size={12} style={{ display: 'flex' }}>
                                            <Space size={32} wrap>
                                                <Statistic title="Đơn (7 ngày tới)" value={forecast.payload.forecast?.next_7d?.orders ?? '—'} />
                                                <Statistic title="Chi tiêu (7 ngày tới)" value={money(forecast.payload.forecast?.next_7d?.spend ?? null, currency)} />
                                                <Statistic title="Cost/đơn dự báo" value={money(forecast.payload.forecast?.next_7d?.projected_cost_per_order ?? null, currency)} />
                                                <div style={{ maxWidth: 480 }}>{(forecast.payload.strategy ?? []).map((s: ForecastStrategy, i: number) => <div key={i}><Tag>{s.action}</Tag>{s.rationale}</div>)}</div>
                                            </Space>
                                            {(forecast?.payload.creative_review?.length ?? 0) > 0 && (
                                                <div>
                                                    <Text strong>Đánh giá nội dung quảng cáo</Text>
                                                    {forecast!.payload.creative_review!.map((cr, i) => (
                                                        <div key={i} style={{ marginTop: 6 }}>
                                                            <Tag color={cr.verdict === 'tốt' ? 'green' : 'orange'}>{cr.verdict}</Tag>
                                                            <Text>{cr.name ?? cr.ref}</Text>
                                                            {cr.suggestions.map((s, j) => (
                                                                <div key={j} style={{ marginLeft: 12, color: '#888', fontSize: 12 }}><BulbOutlined /> {s}</div>
                                                            ))}
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
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

            <CampaignAiInsightDrawer
                open={aiCampaign != null}
                accountId={selectedId}
                campaign={aiCampaign}
                onClose={() => setAiCampaign(null)}
            />
        </div>
    );
}
