import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { App as AntApp, Button, Card, Collapse, DatePicker, Result, Segmented, Select, Space, Table, Tag, Tooltip, Typography } from 'antd';
import { DisconnectOutlined, FundOutlined, SyncOutlined, TikTokOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { ReportTree } from '@/pages/marketing/ReportTree';
import { ConnectionManagerDrawer } from '@/pages/marketing/ConnectionManagerDrawer';
import { LABELS, dec, money, num, objectiveVi, pct, statusVi } from '@/pages/marketing/format';
import { errorMessage } from '@/lib/api';
import { openOAuthPopup } from '@/lib/oauthPopup';
import { useCan } from '@/lib/tenant';
import {
    type ReconRow, type ReportLevel, type ReportRow,
    useAdAccounts, useAdReconciliation, useAdReport, useConnectTikTokAds, useRefreshAdInsights,
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
 * /marketing/tiktok — báo cáo quảng cáo TikTok (read-only). Màn RIÊNG với Facebook
 * (tách theo yêu cầu, dễ mở rộng). Tái dùng component trung tính: ReportTree +
 * formatter dùng chung (pages/marketing/format). Connector TikTok hiện read-only nên
 * KHÔNG có sửa inline / giám sát / drafts.
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

    useEffect(() => {
        localStorage.setItem(RANGE_KEY, JSON.stringify([range[0].format('YYYY-MM-DD'), range[1].format('YYYY-MM-DD')]));
    }, [range]);

    const selectedId = accountId ?? accounts[0]?.id ?? null;
    const currency = accounts.find((a) => a.id === selectedId)?.currency ?? null;
    const since = range[0].format('YYYY-MM-DD');
    const until = range[1].format('YYYY-MM-DD');

    const rangePresets: { label: string; value: [Dayjs, Dayjs] }[] = useMemo(() => [
        { label: 'Hôm nay', value: [dayjs(), dayjs()] },
        { label: 'Hôm qua', value: [dayjs().subtract(1, 'day'), dayjs().subtract(1, 'day')] },
        { label: '7 ngày qua', value: [dayjs().subtract(6, 'day'), dayjs()] },
        { label: '30 ngày qua', value: [dayjs().subtract(29, 'day'), dayjs()] },
        { label: '90 ngày qua', value: [dayjs().subtract(89, 'day'), dayjs()] },
    ], []);

    const { data: report, isFetching } = useAdReport(selectedId, level, since, until, {});
    const { data: recon } = useAdReconciliation(selectedId);

    // Account vừa kết nối: SyncAdAccountEntities chạy BẤT ĐỒNG BỘ (queue marketing-sync)
    // nên campaign vào DB sau vài giây. Poll lại report tới khi có dữ liệu (tối đa ~30s)
    // để campaign tự hiện sau khi cấp quyền, KHÔNG cần reload trang thủ công.
    const reportRowCount = report?.rows?.length ?? 0;
    useEffect(() => {
        if (selectedId == null || reportRowCount > 0) return;
        let n = 0;
        const t = window.setInterval(() => {
            n += 1;
            qc.invalidateQueries({ queryKey: ['marketing', 'report', selectedId] });
            qc.invalidateQueries({ queryKey: ['marketing', 'ad-accounts'] });
            if (n >= 6) window.clearInterval(t);
        }, 5000);

        return () => window.clearInterval(t);
    }, [selectedId, reportRowCount, qc]);

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

    const columns = [
        { title: LABELS[level], dataIndex: 'name', key: 'name', fixed: 'left' as const, width: 240,
            render: (_: unknown, r: ReportRow) => <Text>{r.name ?? r.external_id}</Text> },
        { title: 'Trạng thái', key: 'status', width: 130, render: (_: unknown, r: ReportRow) => {
            const s = statusVi(r.effective_status ?? r.status ?? null);
            return <Tag color={s.color}>{s.label}</Tag>;
        } },
        { title: 'Mục tiêu', key: 'objective', width: 130, render: (_: unknown, r: ReportRow) => objectiveVi(r.objective ?? null) },
        { title: 'Kết quả', key: 'result', align: 'right' as const, render: (_: unknown, r: ReportRow) => num(resultValue(r)) },
        { title: 'CP/Kết quả', key: 'cpr', align: 'right' as const, render: (_: unknown, r: ReportRow) => money(cprValue(r), currency) },
        { title: 'NS/ngày', key: 'daily_budget', align: 'right' as const, render: (_: unknown, r: ReportRow) => money(r.daily_budget, currency) },
        { title: 'Chi tiêu', key: 'spend', align: 'right' as const, render: (_: unknown, r: ReportRow) => money(r.insights?.spend, currency) },
        { title: 'Hiển thị', key: 'impressions', align: 'right' as const, render: (_: unknown, r: ReportRow) => num(r.insights?.impressions) },
        { title: 'Tiếp cận', key: 'reach', align: 'right' as const, render: (_: unknown, r: ReportRow) => num(r.insights?.reach) },
        { title: 'Click', key: 'clicks', align: 'right' as const, render: (_: unknown, r: ReportRow) => num(r.insights?.clicks) },
        { title: 'CTR', key: 'ctr', align: 'right' as const, render: (_: unknown, r: ReportRow) => pct(r.insights?.ctr) },
        { title: 'CPC', key: 'cpc', align: 'right' as const, render: (_: unknown, r: ReportRow) => money(r.insights?.cpc, currency) },
        { title: 'CPM', key: 'cpm', align: 'right' as const, render: (_: unknown, r: ReportRow) => money(r.insights?.cpm, currency) },
        { title: 'Tần suất', key: 'frequency', align: 'right' as const, render: (_: unknown, r: ReportRow) => dec(r.insights?.frequency) },
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
                                monitoredCampaigns={new Set()}
                                monitoredAdsets={new Set()}
                                canMonitor={false}
                                onMonitor={() => { /* read-only */ }}
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
                        items={[{
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
                        }]}
                    />
                </>
            )}

            <ConnectionManagerDrawer
                open={connOpen}
                provider="tiktok"
                onClose={() => setConnOpen(false)}
                onChanged={() => setAccountId(null)}
            />
        </div>
    );
}
