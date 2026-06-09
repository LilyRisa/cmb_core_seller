import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { Alert, App as AntApp, Button, Card, Collapse, DatePicker, Input, Result, Segmented, Select, Space, Table, Tag, Tooltip, Typography } from 'antd';
import { AlertOutlined, DisconnectOutlined, FundOutlined, RobotOutlined, SyncOutlined, TikTokOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { ReportTree } from '@/pages/marketing/ReportTree';
import { ConnectionManagerDrawer } from '@/pages/marketing/ConnectionManagerDrawer';
import { MonitorConfigDrawer, type MonitorTarget } from '@/pages/marketing/MonitorConfigDrawer';
import { CampaignAiInsightDrawer } from '@/pages/marketing/CampaignAiInsightDrawer';
import { ForecastTree } from '@/pages/marketing/ForecastTree';
import { LABELS, dec, money, num, objectiveVi, pct, statusVi } from '@/pages/marketing/format';
import { errorMessage } from '@/lib/api';
import { openOAuthPopup } from '@/lib/oauthPopup';
import { useCan } from '@/lib/tenant';
import {
    type ReconRow, type ReportLevel, type ReportRow,
    useAdAccounts, useAdForecast, useAdMonitors, useAdReconciliation, useAdReport,
    useConnectTikTokAds, useGenerateForecast, useRefreshAdInsights,
} from '@/lib/marketing';

const { Text } = Typography;

const ADS_ERRORS: Record<string, string> = {
    tiktok_marketing_no_accounts: 'Tài khoản chưa có Ad Account TikTok nào, hoặc chưa cấp quyền.',
    tiktok_marketing_oauth_state: 'Phiên kết nối đã hết hạn. Vui lòng thử lại.',
    tiktok_marketing_oauth_failed: 'Kết nối TikTok Ads thất bại.',
};

const RANGE_KEY = 'tiktok-ads.report.range';
const loadRange = (): [Dayjs, Dayjs] => {
    try {
        const raw = JSON.parse(localStorage.getItem(RANGE_KEY) || '');
        if (Array.isArray(raw) && raw.length === 2) {
            const a = dayjs(raw[0]);
            const b = dayjs(raw[1]);
            if (a.isValid() && b.isValid()) return [a, b];
        }
    } catch { /* ignore */ }
    return [dayjs(), dayjs()];
};

/**
 * /marketing/tiktok — báo cáo + giám sát + phân tích AI quảng cáo TikTok. Màn RIÊNG
 * với Facebook (dễ mở rộng). Tái dùng component trung tính (ReportTree, MonitorConfigDrawer,
 * CampaignAiInsightDrawer, ForecastTree) + service dùng chung (AdsReportService, AdMonitor,
 * AI credit). Giám sát tự-động dùng TikTok write tối thiểu (pause/budget) ở evaluator nền.
 */
export function TikTokAdsDashboardPage() {
    const { message } = AntApp.useApp();
    const [params, setParams] = useSearchParams();
    const qc = useQueryClient();
    const canConnect = useCan('marketing.connect');
    const connect = useConnectTikTokAds();
    const refresh = useRefreshAdInsights();
    const { data: allAccounts, isLoading: loadingAccounts } = useAdAccounts();
    const accounts = useMemo(() => (allAccounts ?? []).filter((a) => a.provider === 'tiktok'), [allAccounts]);

    const [accountId, setAccountId] = useState<number | null>(null);
    const [level, setLevel] = useState<ReportLevel>('campaign');
    const [reportView, setReportView] = useState<'tree' | 'flat'>('flat');
    const [range, setRange] = useState<[Dayjs, Dayjs]>(loadRange);
    const [connOpen, setConnOpen] = useState(false);
    const [q, setQ] = useState('');
    const [monitorTarget, setMonitorTarget] = useState<MonitorTarget | null>(null);
    const [aiCampaign, setAiCampaign] = useState<{ id: string; name: string | null } | null>(null);

    useEffect(() => {
        localStorage.setItem(RANGE_KEY, JSON.stringify([range[0].format('YYYY-MM-DD'), range[1].format('YYYY-MM-DD')]));
    }, [range]);

    const selectedId = accountId ?? accounts[0]?.id ?? null;
    const selectedAccount = accounts.find((a) => a.id === selectedId) ?? null;
    const currency = selectedAccount?.currency ?? null;
    // Account chưa từng đồng bộ (last_synced_at null) ⇒ job SyncAdAccountEntities còn
    // đang chạy trong queue → hiện thông báo "đang đồng bộ" + poll tới khi xong.
    const syncing = selectedAccount != null && selectedAccount.last_synced_at == null;
    const since = range[0].format('YYYY-MM-DD');
    const until = range[1].format('YYYY-MM-DD');

    const rangePresets: { label: string; value: [Dayjs, Dayjs] }[] = useMemo(() => [
        { label: 'Hôm nay', value: [dayjs(), dayjs()] },
        { label: 'Hôm qua', value: [dayjs().subtract(1, 'day'), dayjs().subtract(1, 'day')] },
        { label: '7 ngày qua', value: [dayjs().subtract(6, 'day'), dayjs()] },
        { label: '30 ngày qua', value: [dayjs().subtract(29, 'day'), dayjs()] },
        { label: '90 ngày qua', value: [dayjs().subtract(89, 'day'), dayjs()] },
    ], []);

    const filters = useMemo(() => ({ q: q || undefined }), [q]);
    const { data: report, isFetching } = useAdReport(selectedId, level, since, until, filters);
    const { data: recon } = useAdReconciliation(selectedId);
    const { data: forecast } = useAdForecast(selectedId);
    const genForecast = useGenerateForecast();

    // Giám sát: tập id đang được giám sát (để hiện chỉ báo + ưu tiên cảnh báo theo cấp).
    const { data: monitors } = useAdMonitors(selectedId);
    const monitoredCampaigns = useMemo(() => new Set((monitors ?? []).filter((m) => m.target_level === 'campaign' && m.enabled).map((m) => m.target_external_id)), [monitors]);
    const monitoredAdsets = useMemo(() => new Set((monitors ?? []).filter((m) => m.target_level === 'adset' && m.enabled).map((m) => m.target_external_id)), [monitors]);
    const isMonitored = (r: ReportRow) => monitoredCampaigns.has(r.external_id) || monitoredAdsets.has(r.external_id);

    const handleForecast = () => {
        if (selectedId == null) return;
        genForecast.mutate(selectedId, {
            onSuccess: (res) => res.queued
                ? message.info('Đang tạo dự báo — hệ thống sẽ gửi email cho Quản trị khi xong.')
                : message.success('Đã có dự báo.'),
            onError: (e) => message.error(errorMessage(e, 'Không tạo được dự báo (cooldown / chưa cấu hình provider AI marketing).')),
        });
    };

    // Account vừa kết nối: SyncAdAccountEntities chạy BẤT ĐỒNG BỘ (queue marketing-sync)
    // nên campaign vào DB sau vài giây. Trong lúc account chưa đồng bộ xong
    // (last_synced_at null), poll lại accounts + report (~5s, tối đa ~60s) để dữ liệu
    // tự hiện sau khi cấp quyền — KHÔNG cần reload trang thủ công.
    useEffect(() => {
        if (selectedId == null || !syncing) return;
        let n = 0;
        const t = window.setInterval(() => {
            n += 1;
            qc.invalidateQueries({ queryKey: ['marketing', 'ad-accounts'] });
            qc.invalidateQueries({ queryKey: ['marketing', 'report', selectedId] });
            if (n >= 12) window.clearInterval(t);
        }, 5000);

        return () => window.clearInterval(t);
    }, [selectedId, syncing, qc]);

    const applyResult = (p: URLSearchParams) => {
        if (p.get('connected') === 'tiktok_marketing') {
            message.success('Đã kết nối TikTok Ads!');
            params.delete('connected'); setParams(params, { replace: true });
            qc.invalidateQueries({ queryKey: ['marketing'] });
        } else { const e = p.get('error'); if (e?.startsWith('tiktok_marketing')) { message.error({ content: ADS_ERRORS[e] ?? 'Kết nối thất bại.', duration: 12 }); params.delete('error'); setParams(params, { replace: true }); } }
    };
    useEffect(() => {
        applyResult(params);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const handleConnect = () => connect.mutate(undefined, {
        onSuccess: async (d) => { const r = await openOAuthPopup(d.authorize_url); if (r.status === 'done' && r.redirect) applyResult(new URL(r.redirect, window.location.origin).searchParams); },
        onError: (e) => message.error(errorMessage(e, 'Không khởi tạo được kết nối. Quản trị viên cần bật INTEGRATIONS_ADS=tiktok + cấu hình TIKTOK_ADS_*.')),
    });

    const resultValue = (r: ReportRow) => r.insights?.results ?? 0;
    const cprValue = (r: ReportRow) => {
        const res = resultValue(r);
        return res > 0 ? Math.round((r.insights?.spend ?? 0) / res) : null;
    };

    const nameOf = (r: ReportRow) => r.name ?? r.external_id;
    const mnum = (r: ReportRow, k: 'spend' | 'impressions' | 'reach' | 'clicks' | 'ctr' | 'cpc' | 'cpm' | 'frequency') => r.insights?.[k] ?? 0;

    const columns = [
        { title: LABELS[level], dataIndex: 'name', key: 'name', fixed: 'left' as const, width: 240,
            sorter: (a: ReportRow, b: ReportRow) => nameOf(a).localeCompare(nameOf(b)),
            render: (_: unknown, r: ReportRow) => <Text>{nameOf(r)}</Text> },
        { title: 'Trạng thái', key: 'status', width: 130,
            sorter: (a: ReportRow, b: ReportRow) => statusVi(a.effective_status ?? a.status ?? null).label.localeCompare(statusVi(b.effective_status ?? b.status ?? null).label),
            render: (_: unknown, r: ReportRow) => {
                const s = statusVi(r.effective_status ?? r.status ?? null);
                return <Tag color={s.color}>{s.label}</Tag>;
            } },
        { title: 'Mục tiêu', key: 'objective', width: 130,
            sorter: (a: ReportRow, b: ReportRow) => objectiveVi(a.objective ?? null).localeCompare(objectiveVi(b.objective ?? null)),
            render: (_: unknown, r: ReportRow) => objectiveVi(r.objective ?? null) },
        { title: 'Kết quả', key: 'result', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => resultValue(a) - resultValue(b), render: (_: unknown, r: ReportRow) => num(resultValue(r)) },
        { title: 'CP/Kết quả', key: 'cpr', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => (cprValue(a) ?? Infinity) - (cprValue(b) ?? Infinity), render: (_: unknown, r: ReportRow) => money(cprValue(r), currency) },
        { title: 'NS/ngày', key: 'daily_budget', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => (a.daily_budget ?? 0) - (b.daily_budget ?? 0), render: (_: unknown, r: ReportRow) => money(r.daily_budget, currency) },
        { title: 'Chi tiêu', key: 'spend', align: 'right' as const, defaultSortOrder: 'descend' as const, sorter: (a: ReportRow, b: ReportRow) => mnum(a, 'spend') - mnum(b, 'spend'), render: (_: unknown, r: ReportRow) => money(r.insights?.spend, currency) },
        { title: 'Hiển thị', key: 'impressions', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => mnum(a, 'impressions') - mnum(b, 'impressions'), render: (_: unknown, r: ReportRow) => num(r.insights?.impressions) },
        { title: 'Tiếp cận', key: 'reach', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => mnum(a, 'reach') - mnum(b, 'reach'), render: (_: unknown, r: ReportRow) => num(r.insights?.reach) },
        { title: 'Click', key: 'clicks', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => mnum(a, 'clicks') - mnum(b, 'clicks'), render: (_: unknown, r: ReportRow) => num(r.insights?.clicks) },
        { title: 'CTR', key: 'ctr', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => mnum(a, 'ctr') - mnum(b, 'ctr'), render: (_: unknown, r: ReportRow) => pct(r.insights?.ctr) },
        { title: 'CPC', key: 'cpc', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => mnum(a, 'cpc') - mnum(b, 'cpc'), render: (_: unknown, r: ReportRow) => money(r.insights?.cpc, currency) },
        { title: 'CPM', key: 'cpm', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => mnum(a, 'cpm') - mnum(b, 'cpm'), render: (_: unknown, r: ReportRow) => money(r.insights?.cpm, currency) },
        { title: 'Tần suất', key: 'frequency', align: 'right' as const, sorter: (a: ReportRow, b: ReportRow) => mnum(a, 'frequency') - mnum(b, 'frequency'), render: (_: unknown, r: ReportRow) => dec(r.insights?.frequency) },
        ...(canConnect ? [{
            title: 'Thao tác', key: 'actions', fixed: 'right' as const, width: 130,
            render: (_: unknown, r: ReportRow) => (
                <Space size={4}>
                    {level === 'campaign' && (
                        <Tooltip title="Phân tích AI chiến dịch">
                            <Button size="small" icon={<RobotOutlined />} onClick={() => setAiCampaign({ id: r.external_id, name: r.name })} />
                        </Tooltip>
                    )}
                    {level !== 'ad' && (
                        <Tooltip title="Giám sát: tự tạm dừng / tăng ngân sách theo chi phí/kết quả">
                            <Button
                                size="small"
                                type={isMonitored(r) ? 'primary' : 'default'}
                                icon={<AlertOutlined />}
                                onClick={() => setMonitorTarget({ level: level as 'campaign' | 'adset', externalId: r.external_id, name: r.name, canIncrease: r.daily_budget != null })}
                            />
                        </Tooltip>
                    )}
                </Space>
            ),
        }] : []),
    ];

    const reconColumns = [
        { title: 'Ngày', dataIndex: 'date', key: 'date' },
        { title: 'Chi tiêu', key: 'spend', align: 'right' as const, render: (_: unknown, r: ReconRow) => money(r.spend, currency) },
        { title: 'Đơn thủ công', key: 'manual_orders', align: 'right' as const, render: (_: unknown, r: ReconRow) => num(r.manual_orders) },
        { title: 'Doanh thu thủ công', key: 'manual_revenue', align: 'right' as const, render: (_: unknown, r: ReconRow) => money(r.manual_revenue, currency) },
        { title: 'CP/Đơn', key: 'cpo', align: 'right' as const, render: (_: unknown, r: ReconRow) => money(r.cost_per_order, currency) },
    ];

    return (
        <div>
            <PageHeader title="Quảng cáo TikTok" subtitle="Báo cáo hiệu suất quảng cáo TikTok (kết nối, đồng bộ chiến dịch/nhóm/quảng cáo & đối soát)" />

            <Card style={{ marginBottom: 16 }}>
                <Space wrap size={12}>
                    <Button
                        icon={<TikTokOutlined />}
                        loading={connect.isPending}
                        onClick={handleConnect}
                        disabled={!canConnect}
                        style={canConnect ? { background: '#000', borderColor: '#000', color: '#fff' } : undefined}
                    >Kết nối TikTok Ads</Button>
                    {accounts.length > 0 && (
                        <>
                            <Select
                                style={{ minWidth: 240 }}
                                value={selectedId ?? undefined}
                                onChange={(v) => setAccountId(v)}
                                placeholder="Chọn tài khoản quảng cáo"
                                options={accounts.map((a) => ({ value: a.id, label: `${a.name ?? a.external_account_id} · ${a.external_account_id}` }))}
                            />
                            <Tooltip title="Làm mới dữ liệu báo cáo">
                                <Button icon={<SyncOutlined />} loading={refresh.isPending} disabled={selectedId == null}
                                    onClick={() => selectedId != null && refresh.mutate(selectedId, { onSuccess: () => message.success('Đang đồng bộ lại số liệu…') })} />
                            </Tooltip>
                            {canConnect && <Button icon={<DisconnectOutlined />} onClick={() => setConnOpen(true)}>Quản lý kết nối</Button>}
                        </>
                    )}
                </Space>
            </Card>

            {loadingAccounts ? null : accounts.length === 0 ? (
                <Card><Result icon={<FundOutlined />} title="Chưa kết nối tài khoản quảng cáo TikTok" subTitle="Bấm 'Kết nối TikTok Ads' để bắt đầu." /></Card>
            ) : (
                <>
                    {syncing && (
                        <Alert
                            type="info"
                            showIcon
                            icon={<SyncOutlined spin />}
                            style={{ marginBottom: 16 }}
                            message="Đang đồng bộ chiến dịch từ TikTok…"
                            description="Hệ thống đang kéo dữ liệu chiến dịch/nhóm/quảng cáo về (chạy nền qua hàng đợi). Dữ liệu sẽ tự hiển thị khi xong, thường mất vài giây — bạn không cần tải lại trang."
                        />
                    )}
                    <Card
                        style={{ marginBottom: 16 }}
                        title={
                            <Space wrap>
                                <Segmented<ReportLevel>
                                    value={level}
                                    onChange={(v) => setLevel(v)}
                                    options={[
                                        { label: LABELS.campaign, value: 'campaign' },
                                        { label: LABELS.adset, value: 'adset' },
                                        { label: LABELS.ad, value: 'ad' },
                                    ]}
                                />
                                <Segmented
                                    value={reportView}
                                    onChange={(v) => setReportView(v as 'tree' | 'flat')}
                                    options={[{ label: 'Bảng phẳng', value: 'flat' }, { label: 'Cây phân cấp', value: 'tree' }]}
                                />
                                <Input allowClear placeholder="Tìm theo tên…" value={q} onChange={(e) => setQ(e.target.value)} style={{ width: 200 }} />
                            </Space>
                        }
                        extra={
                            <DatePicker.RangePicker
                                value={range}
                                onChange={(v) => v && v[0] && v[1] && setRange([v[0], v[1]])}
                                presets={rangePresets}
                                allowClear={false}
                            />
                        }
                    >
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
                                rowKey="id"
                                size="small"
                                loading={isFetching}
                                columns={columns}
                                dataSource={report?.rows ?? []}
                                scroll={{ x: 'max-content' }}
                                pagination={{ pageSize: 50, hideOnSinglePage: true }}
                            />
                        )}
                    </Card>

                    <Collapse
                        items={[
                            {
                                key: 'forecast',
                                label: 'Dự báo & khuyến nghị AI (toàn tài khoản)',
                                extra: canConnect ? <Button size="small" type="primary" loading={genForecast.isPending} onClick={(e) => { e.stopPropagation(); handleForecast(); }}>Tạo dự báo</Button> : undefined,
                                children: forecast
                                    ? <ForecastTree forecast={forecast} currency={currency} />
                                    : <Text type="secondary">Chưa có dự báo — bấm "Tạo dự báo".</Text>,
                            },
                            {
                                key: 'recon',
                                label: 'Đối soát chi tiêu vs đơn thủ công (14 ngày)',
                                children: (
                                    <Table<ReconRow>
                                        rowKey="date"
                                        size="small"
                                        columns={reconColumns}
                                        dataSource={recon?.rows ?? []}
                                        pagination={false}
                                    />
                                ),
                            },
                        ]}
                    />
                </>
            )}

            <ConnectionManagerDrawer
                open={connOpen}
                provider="tiktok"
                onClose={() => setConnOpen(false)}
                onChanged={() => setAccountId(null)}
            />
            <MonitorConfigDrawer
                open={monitorTarget != null}
                accountId={selectedId}
                target={monitorTarget}
                onClose={() => setMonitorTarget(null)}
            />
            <CampaignAiInsightDrawer
                open={aiCampaign != null}
                accountId={selectedId}
                campaign={aiCampaign}
                onClose={() => setAiCampaign(null)}
            />
        </div>
    );
}
