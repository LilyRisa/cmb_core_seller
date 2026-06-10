import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { Alert, App as AntApp, Avatar, Badge, Button, Card, Checkbox, Collapse, DatePicker, Dropdown, Empty, Input, InputNumber, List, Popconfirm, Result, Segmented, Select, Space, Spin, Table, Tag, Tooltip, Typography } from 'antd';
import { AlertOutlined, ApiOutlined, CheckOutlined, CloseOutlined, DisconnectOutlined, EditOutlined, FacebookFilled, FileTextOutlined, FolderOpenOutlined, FundOutlined, PauseCircleOutlined, PlayCircleOutlined, PlusOutlined, QuestionCircleOutlined, RobotOutlined, SettingOutlined, SyncOutlined } from '@ant-design/icons';
import { useAdDrafts, useDeleteDraft, useDuplicateDraft } from '@/lib/adWizard';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { CampaignAiInsightDrawer } from '@/pages/marketing/CampaignAiInsightDrawer';
import { AbComparisonPanel } from '@/pages/marketing/AbComparisonPanel';
import { PixelManagerDrawer } from '@/pages/marketing/PixelManagerDrawer';
import { SavedReportsDrawer } from '@/pages/marketing/SavedReportsDrawer';
import { ReportTree } from '@/pages/marketing/ReportTree';
import { ConnectionManagerDrawer } from '@/pages/marketing/ConnectionManagerDrawer';
import { MonitorConfigDrawer, type MonitorTarget } from '@/pages/marketing/MonitorConfigDrawer';
import { ForecastTree } from '@/pages/marketing/ForecastTree';
import { cprOf, resultOf } from '@/lib/adReport';
import { ALL_COLUMNS, COL_HELP, COL_TITLE, DEFAULT_COLUMNS, dec, LABELS, money, num, objectiveVi, pct, statusVi } from '@/pages/marketing/format';
import { errorMessage } from '@/lib/api';
import { openOAuthPopup } from '@/lib/oauthPopup';
import { useCan } from '@/lib/tenant';
import {
    type ReconRow, type ReportLevel, type ReportRow,
    useAdAccounts, useAdForecast, useAdMonitors, useAdReconciliation, useAdReport,
    useClaimAutomation, useConnectFacebookAds, useGenerateForecast, useRefreshAccounts, useRefreshAdInsights,
    useSaveReport, useUpdateAdEntity,
} from '@/lib/marketing';

const { Text } = Typography;

const ADS_ERRORS: Record<string, string> = {
    facebook_ads_no_accounts: 'Tài khoản chưa có Ad Account nào, hoặc chưa cấp quyền ads_read.',
    facebook_ads_oauth_state: 'Phiên kết nối đã hết hạn. Vui lòng thử lại.',
    facebook_ads_oauth_failed: 'Kết nối Facebook Ads thất bại.',
};

const COLS_KEY = 'marketing.report.columns';
const RANGE_KEY = 'marketing.report.range';
// Restore the last campaign date filter from localStorage; default to today.
const loadRange = (): [Dayjs, Dayjs] => {
    try {
        const raw = JSON.parse(localStorage.getItem(RANGE_KEY) || '');
        if (Array.isArray(raw) && raw.length === 2) {
            const a = dayjs(raw[0]);
            const b = dayjs(raw[1]);
            if (a.isValid() && b.isValid()) return [a, b];
        }
    } catch { /* ignore malformed value */ }
    return [dayjs(), dayjs()];
};

// Ghi nhớ lựa chọn 2 cấp BM + tài khoản quảng cáo để khỏi phải chọn lại sau khi tải lại trang.
const BM_KEY = 'marketing.report.bm';
const ACCOUNT_KEY = 'marketing.report.account';
const loadBm = (): string | null => localStorage.getItem(BM_KEY) || null;
const loadAccount = (): number | null => {
    const raw = Number(localStorage.getItem(ACCOUNT_KEY));
    return Number.isInteger(raw) && raw > 0 ? raw : null;
};
/** /marketing — báo cáo quảng cáo Facebook kiểu Ads Manager (BM, 3 tab, cột tuỳ chỉnh, drill-down). */
export function MarketingDashboardPage() {
    const { message } = AntApp.useApp();
    const navigate = useNavigate();
    const [params, setParams] = useSearchParams();
    const canConnect = useCan('marketing.connect');
    const { data: drafts } = useAdDrafts();
    const deleteDraft = useDeleteDraft();
    const duplicateDraft = useDuplicateDraft();
    const connect = useConnectFacebookAds();
    const refresh = useRefreshAdInsights();
    const refreshAccounts = useRefreshAccounts();
    const { data: allAccounts, isLoading: loadingAccounts } = useAdAccounts();
    // Trang này CHỈ Facebook — TikTok có màn riêng (/marketing/tiktok).
    const accounts = useMemo(() => (allAccounts ?? []).filter((a) => a.provider === 'facebook'), [allAccounts]);

    const [bm, setBm] = useState<string | null>(loadBm);
    const [accountId, setAccountId] = useState<number | null>(loadAccount);
    const [level, setLevel] = useState<ReportLevel>('campaign');
    const [reportView, setReportView] = useState<'tree' | 'flat'>('flat');
    const [range, setRange] = useState<[Dayjs, Dayjs]>(loadRange);
    const [q, setQ] = useState('');
    const [adId, setAdId] = useState('');
    const [objective, setObjective] = useState<string | undefined>(undefined);
    const [selCampaigns, setSelCampaigns] = useState<string[]>([]);
    const [selAdsets, setSelAdsets] = useState<string[]>([]);
    const [cols, setCols] = useState<string[]>(() => {
        try { return JSON.parse(localStorage.getItem(COLS_KEY) || '') ?? DEFAULT_COLUMNS; } catch { return DEFAULT_COLUMNS; }
    });
    useEffect(() => { localStorage.setItem(COLS_KEY, JSON.stringify(cols)); }, [cols]);
    useEffect(() => {
        localStorage.setItem(RANGE_KEY, JSON.stringify([range[0].format('YYYY-MM-DD'), range[1].format('YYYY-MM-DD')]));
    }, [range]);

    // BM groups → accounts in selected BM.
    const bmGroups = useMemo(() => {
        const m = new Map<string, { name: string; picture: string | null }>();
        (accounts ?? []).forEach((a) => {
            const id = a.business_id ?? '_';
            if (!m.has(id)) m.set(id, { name: a.business_name ?? 'Không thuộc BM', picture: a.business_picture_url ?? null });
        });
        return [...m.entries()].map(([id, info]) => ({ id, name: info.name, picture: info.picture }));
    }, [accounts]);
    // BM đã chọn nếu còn tồn tại (tránh BM cũ đã ngắt kết nối), nếu không thì BM đầu tiên.
    const effectiveBm = (bm != null && bmGroups.some((g) => g.id === bm)) ? bm : (bmGroups[0]?.id ?? null);
    const bmAccounts = useMemo(() => (accounts ?? []).filter((a) => (a.business_id ?? '_') === effectiveBm), [accounts, effectiveBm]);
    // Tài khoản đã chọn nếu còn nằm trong BM hiện tại, nếu không thì tài khoản đầu tiên của BM.
    const selectedId = (accountId != null && bmAccounts.some((a) => a.id === accountId))
        ? accountId
        : (bmAccounts[0]?.id ?? null);
    const currency = bmAccounts.find((a) => a.id === selectedId)?.currency ?? null;

    useEffect(() => {
        if (effectiveBm != null) localStorage.setItem(BM_KEY, effectiveBm);
    }, [effectiveBm]);
    useEffect(() => {
        if (selectedId != null) localStorage.setItem(ACCOUNT_KEY, String(selectedId));
    }, [selectedId]);
    // Accounts with a Facebook health problem (disabled / payment / policy) across all BMs.
    const unhealthyAccounts = useMemo(() => (accounts ?? []).filter((a) => a.health != null && !a.health.ok), [accounts]);
    const selectedAccount = (accounts ?? []).find((a) => a.id === selectedId) ?? null;
    const sharedNotOwner = selectedAccount?.shared_with_other_tenants === true && selectedAccount?.is_automation_owner === false;

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
    const [pixelOpen, setPixelOpen] = useState(false);
    const [savedOpen, setSavedOpen] = useState(false);
    const [connOpen, setConnOpen] = useState(false);
    const saveReport = useSaveReport();
    const updateEntity = useUpdateAdEntity();
    const claimAutomation = useClaimAutomation();
    const [monitorTarget, setMonitorTarget] = useState<MonitorTarget | null>(null);
    const { data: monitors } = useAdMonitors(selectedId);
    // Sets of monitored external ids by level (for indicators + adset override note).
    const monitoredCampaigns = useMemo(() => new Set((monitors ?? []).filter((m) => m.target_level === 'campaign' && m.enabled).map((m) => m.target_external_id)), [monitors]);
    const monitoredAdsets = useMemo(() => new Set((monitors ?? []).filter((m) => m.target_level === 'adset' && m.enabled).map((m) => m.target_external_id)), [monitors]);
    const isMonitored = (r: ReportRow) => monitoredCampaigns.has(r.external_id) || monitoredAdsets.has(r.external_id);
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
        result: (r) => {
            const res = resultOf(r.objective, r.insights);
            return res == null ? '—' : (
                <span>
                    <Text strong style={{ color: res.color }}>{res.value.toLocaleString('vi-VN')}</Text>
                    <Text type="secondary" style={{ fontSize: 11, marginLeft: 4 }}>{res.label}</Text>
                </span>
            );
        },
        purchases: (r) => num(r.insights?.purchases),
        cpr: (r) => money(cprOf(r.objective, r.insights), currency),
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

    // Giá trị dùng để SẮP XẾP từng cột (số → so sánh số, chuỗi → so sánh theo tiếng Việt).
    // null/không có dữ liệu xuống cuối khi tăng dần.
    const sortVal: Record<string, (r: ReportRow) => number | string> = {
        external_id: (r) => r.external_id,
        status: (r) => statusVi(r.effective_status ?? r.status ?? null).label,
        objective: (r) => objectiveVi(r.objective),
        result: (r) => resultOf(r.objective, r.insights)?.value ?? -1,
        cpr: (r) => cprOf(r.objective, r.insights) ?? -1,
        daily_budget: (r) => r.daily_budget ?? -1,
        lifetime_budget: (r) => r.lifetime_budget ?? -1,
        spend: (r) => r.insights?.spend ?? -1,
        impressions: (r) => r.insights?.impressions ?? -1,
        reach: (r) => r.insights?.reach ?? -1,
        clicks: (r) => r.insights?.clicks ?? -1,
        ctr: (r) => r.insights?.ctr ?? -1,
        cpc: (r) => r.insights?.cpc ?? -1,
        cpm: (r) => r.insights?.cpm ?? -1,
        frequency: (r) => r.insights?.frequency ?? -1,
        purchase_roas: (r) => r.insights?.purchase_roas ?? -1,
        messaging_conversations: (r) => r.insights?.messaging_conversations ?? -1,
        leads: (r) => r.insights?.leads ?? -1,
        purchases: (r) => r.insights?.purchases ?? -1,
    };
    const colSorter = (c: string) => (a: ReportRow, b: ReportRow) => {
        const av = sortVal[c](a);
        const bv = sortVal[c](b);
        return typeof av === 'number' && typeof bv === 'number' ? av - bv : String(av).localeCompare(String(bv), 'vi');
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
            sorter: colSorter(c),
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
        ...(level !== 'ad' && canConnect ? [{
            title: 'Giám sát', key: 'monitor', fixed: 'right' as const, width: 130,
            render: (_: unknown, r: ReportRow) => (
                <Tooltip title={r.daily_budget == null && level === 'campaign' ? 'Ngân sách theo nhóm — chỉ cài tạm dừng' : 'Tự tăng ngân sách / tạm dừng theo chi phí'}>
                    <Button
                        size="small"
                        type={isMonitored(r) ? 'primary' : 'default'}
                        icon={<AlertOutlined />}
                        onClick={() => setMonitorTarget({ level: level as 'campaign' | 'adset', externalId: r.external_id, name: r.name, canIncrease: r.daily_budget != null })}
                    >
                        {isMonitored(r) ? 'Đang bật' : 'Giám sát'}
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
            <PageHeader title="Quảng cáo Facebook" subtitle="Báo cáo quảng cáo facebook, giám sát hiệu suất giải pháp tối ưu chiến dịch" />

            <Card style={{ marginBottom: 16 }}>
                <Space wrap size={12}>
                    <Button type="primary" icon={<FacebookFilled />} loading={connect.isPending} onClick={handleConnect} disabled={!canConnect}>Kết nối Facebook Ads</Button>
                    {(accounts?.length ?? 0) > 0 && (
                        <Tooltip title="Lấy trạng thái tài khoản / BM mới nhất từ Facebook và phát hiện tài khoản mới">
                            <Button
                                icon={<SyncOutlined spin={refreshAccounts.isPending} />}
                                loading={refreshAccounts.isPending}
                                onClick={() => refreshAccounts.mutate(undefined, {
                                    onSuccess: (d) => message.success(`Đã cập nhật ${d.updated} tài khoản${d.created > 0 ? `, thêm ${d.created} mới` : ''}.`),
                                    onError: (e) => message.error(errorMessage(e, 'Làm mới tài khoản thất bại.')),
                                })}
                            >
                                Làm mới tài khoản
                            </Button>
                        </Tooltip>
                    )}
                    <Button type="primary" icon={<PlusOutlined />} disabled={selectedId == null} onClick={() => navigate('/marketing/ads/new?accountId=' + selectedId)}>Tạo quảng cáo</Button>
                    <Button icon={<RobotOutlined />} disabled={selectedId == null} onClick={() => navigate('/marketing/ads/ai?accountId=' + selectedId)}>Tạo bằng AI</Button>
                    <Button icon={<ApiOutlined />} disabled={selectedId == null} onClick={() => setPixelOpen(true)}>Quản lý Pixel</Button>
                    {bmGroups.length > 0 && (
                        <Select
                            style={{ minWidth: 260 }}
                            value={effectiveBm ?? undefined}
                            onChange={(v) => { setBm(v); setAccountId(null); }}
                            optionLabelProp="label"
                            options={bmGroups.map((g) => ({
                                value: g.id,
                                label: 'BM: ' + g.name,
                                title: g.id !== '_' ? `ID: ${g.id}` : undefined,
                                element: (
                                    <Space>
                                        {g.picture
                                            ? <Avatar size={18} src={g.picture} shape="square" />
                                            : <FacebookFilled style={{ color: '#1877f2' }} />}
                                        <span>{g.name}</span>
                                        {g.id !== '_' && <Text type="secondary" style={{ fontSize: 11 }}>#{g.id}</Text>}
                                    </Space>
                                ),
                            }))}
                            optionRender={(opt) => opt.data.element}
                        />
                    )}
                    {bmAccounts.length > 0 && (
                        <Select
                            style={{ minWidth: 280 }}
                            value={selectedId ?? undefined}
                            onChange={(v) => setAccountId(Number(v))}
                            optionLabelProp="label"
                            options={bmAccounts.map((a) => ({
                                value: a.id,
                                label: a.name ?? a.external_account_id,
                                element: (
                                    <Space direction="vertical" size={0}>
                                        <Space size={6}>
                                            {a.health != null && !a.health.ok && (
                                                <Tooltip title={a.health.label}>
                                                    <Badge status={a.health.severity === 'error' ? 'error' : 'warning'} />
                                                </Tooltip>
                                            )}
                                            <span>{a.name ?? a.external_account_id}</span>
                                        </Space>
                                        <Text type="secondary" style={{ fontSize: 11 }} copyable={{ text: a.external_account_id }}>{a.external_account_id}</Text>
                                    </Space>
                                ),
                            }))}
                            optionRender={(opt) => opt.data.element}
                        />
                    )}
                    {canConnect && (accounts?.length ?? 0) > 0 && (
                        <Button danger size="small" icon={<DisconnectOutlined />} onClick={() => setConnOpen(true)}>Quản lý kết nối</Button>
                    )}
                </Space>
            </Card>

            {sharedNotOwner && selectedId != null && (
                <Alert
                    type="warning"
                    showIcon
                    style={{ marginBottom: 16 }}
                    message="Tài khoản quảng cáo này cũng được kết nối ở shop khác"
                    description="Để tránh xung đột (giám sát tự động tăng/tạm dừng chồng nhau), chỉ shop SỞ HỮU mới tự động hoá & chỉnh sửa/xuất bản. Ở shop này hiện chỉ XEM được; nếu muốn quản lý từ đây, hãy tiếp quản quyền."
                    action={
                        <Popconfirm
                            title="Tiếp quản quyền tự động hoá?"
                            description="Shop kia sẽ mất quyền tự động hoá/chỉnh sửa tài khoản này."
                            okText="Tiếp quản" cancelText="Huỷ"
                            onConfirm={() => claimAutomation.mutate(selectedId, {
                                onSuccess: () => message.success('Đã tiếp quản quyền cho shop này.'),
                                onError: (e) => message.error(errorMessage(e)),
                            })}
                        >
                            <Button size="small" type="primary" loading={claimAutomation.isPending}>Tiếp quản quyền</Button>
                        </Popconfirm>
                    }
                />
            )}

            {unhealthyAccounts.length > 0 && (
                <Alert
                    type={unhealthyAccounts.some((a) => a.health?.severity === 'error') ? 'error' : 'warning'}
                    showIcon
                    style={{ marginBottom: 16 }}
                    message="Tài khoản quảng cáo có vấn đề"
                    description={
                        <List
                            size="small"
                            dataSource={unhealthyAccounts}
                            renderItem={(a) => (
                                <List.Item style={{ padding: '4px 0' }}>
                                    <Space wrap>
                                        <Tag color={a.health?.severity === 'error' ? 'red' : 'orange'}>{a.health?.label}</Tag>
                                        <Text strong>{a.name ?? a.external_account_id}</Text>
                                        <Text type="secondary" style={{ fontSize: 12 }}>{a.external_account_id}</Text>
                                        {a.business_name && <Text type="secondary" style={{ fontSize: 12 }}>· BM: {a.business_name}</Text>}
                                    </Space>
                                </List.Item>
                            )}
                        />
                    }
                />
            )}

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
                                    <Button
                                        key="duplicate"
                                        type="link"
                                        size="small"
                                        loading={duplicateDraft.isPending}
                                        onClick={() => duplicateDraft.mutate(d.id, {
                                            onSuccess: (copy) => { message.success('Đã nhân bản — mở bản sao để sửa.'); navigate('/marketing/ads/' + copy.id + '/edit'); },
                                            onError: (e) => message.error(errorMessage(e)),
                                        })}
                                    >
                                        Nhân bản
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
                        title={
                            <Space wrap>
                                <Segmented
                                    value={reportView}
                                    onChange={(v) => setReportView(v as 'tree' | 'flat')}
                                    options={[{ label: 'Bảng phẳng', value: 'flat' }, { label: 'Cây phân cấp', value: 'tree' }]}
                                />
                                {reportView === 'flat' && (
                                    <Segmented value={level} onChange={(v) => setLevel(v as ReportLevel)}
                                        options={(['campaign', 'adset', 'ad'] as ReportLevel[]).map((l) => ({ label: LABELS[l], value: l }))} />
                                )}
                            </Space>
                        }
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
                            {reportView === 'flat' && <>
                                <Input.Search placeholder="Tên chiến dịch/nhóm/QC" allowClear value={q} onChange={(e) => setQ(e.target.value)} style={{ width: 220 }} />
                                <Input placeholder="ID" allowClear value={adId} onChange={(e) => setAdId(e.target.value)} style={{ width: 160 }} />
                                <Select placeholder="Loại (objective)" allowClear value={objective} onChange={setObjective} options={objectiveOptions} style={{ minWidth: 180 }} />
                            </>}
                            <Button
                                icon={<SyncOutlined spin={isFetching || refresh.isPending} />}
                                loading={refresh.isPending}
                                disabled={selectedId == null}
                                onClick={() => selectedId != null && refresh.mutate(selectedId, {
                                    onSuccess: () => message.success('Đã làm mới trạng thái & dữ liệu quảng cáo.'),
                                    onError: (e) => message.error(errorMessage(e, 'Làm mới thất bại.')),
                                })}
                            >
                                Làm mới
                            </Button>
                            <Button
                                icon={<FileTextOutlined />}
                                loading={saveReport.isPending}
                                disabled={selectedId == null}
                                onClick={() => selectedId != null && saveReport.mutate(
                                    { accountId: selectedId, level, since, until, filters },
                                    {
                                        onSuccess: (d) => message.success(`Đã lưu "${d.name}" (${d.row_count} dòng).`),
                                        onError: (e) => message.error(errorMessage(e, 'Lưu báo cáo thất bại.')),
                                    },
                                )}
                            >
                                Lưu báo cáo
                            </Button>
                            <Button icon={<FolderOpenOutlined />} disabled={selectedId == null} onClick={() => setSavedOpen(true)}>Báo cáo đã lưu</Button>
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
                        {reportView === 'tree' ? (
                            <ReportTree
                                accountId={selectedId}
                                since={since}
                                until={until}
                                currency={currency}
                                monitoredCampaigns={monitoredCampaigns}
                                monitoredAdsets={monitoredAdsets}
                                canMonitor={canConnect}
                                onMonitor={(t) => setMonitorTarget(t)}
                            />
                        ) : (
                            <Table<ReportRow>
                                rowKey="external_id" size="small" scroll={{ x: 'max-content' }}
                                rowClassName={(r) => (isActiveRow(r) ? 'marketing-row-active' : '')}
                                loading={isFetching} dataSource={sortedRows} columns={columns} rowSelection={rowSelection}
                                pagination={{ defaultPageSize: 50, showSizeChanger: true, pageSizeOptions: ['20', '50', '100', '200'] }}
                                locale={{ emptyText: <Empty description="Không có dữ liệu cho bộ lọc/khoảng ngày này." /> }}
                            />
                        )}
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
                                        <ForecastTree forecast={forecast} currency={currency} />
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

            <PixelManagerDrawer
                open={pixelOpen}
                accountId={selectedId}
                onClose={() => setPixelOpen(false)}
            />

            <SavedReportsDrawer
                open={savedOpen}
                accountId={selectedId}
                onClose={() => setSavedOpen(false)}
            />

            <ConnectionManagerDrawer
                open={connOpen}
                provider="facebook"
                onClose={() => setConnOpen(false)}
                onChanged={() => { setAccountId(null); setBm(null); }}
            />

            <MonitorConfigDrawer
                open={monitorTarget != null}
                accountId={selectedId}
                target={monitorTarget}
                onClose={() => setMonitorTarget(null)}
            />
        </div>
    );
}
