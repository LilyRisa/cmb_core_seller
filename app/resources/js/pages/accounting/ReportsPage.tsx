import { useEffect, useMemo, useState } from 'react';
import { App, Button, Card, Popconfirm, Space, Statistic, Table, Tabs, Tag, Tooltip, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import { CheckCircleFilled, DownloadOutlined, ReloadOutlined, WarningFilled } from '@ant-design/icons';
import dayjs from 'dayjs';
import { ACCOUNT_TYPE_COLOR, ACCOUNT_TYPE_LABEL, AccountType, formatAmount, useCreateTaxFiling, useFiscalPeriods, useVatReport } from '@/lib/accounting';
import { useAuth, getCurrentTenantId } from '@/lib/auth';
import { tenantApi } from '@/lib/api';
import { useQuery } from '@tanstack/react-query';
import { AccountingSetupBanner } from './AccountingSetupBanner';
import { AccountTreeSelect } from '@/components/accounting/AccountTreeSelect';
import { PeriodSelect } from '@/components/accounting/PeriodSelect';
import { useCan } from '@/lib/tenant';
import { errorMessage } from '@/lib/api';

function useScopedApi() {
    const { data: user } = useAuth();
    const tenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

interface TrialRow { account_code: string; account_name: string; type: AccountType; opening: number; debit: number; credit: number; closing: number }
interface TrialResp { data: TrialRow[]; meta: { period: string; total_debit: number; total_credit: number; balanced: boolean } }
interface PnlResp { data: { revenue: number; deductions: number; net_revenue: number; cogs: number; gross_profit: number; opex: number; fin_income: number; fin_expense: number; other_income: number; other_expense: number; ebit: number; ebt: number; tax: number; net_income: number; lines: Array<{ section: string; code: string; name: string; amount: number }> }; meta: { period: string } }
interface BsResp { data: { assets: number; liabilities: number; equity: number; balanced: boolean; retained_earnings_net: number; lines: Array<{ section: string; code: string; name: string; amount: number }> }; meta: { period: string; as_of: string } }
interface LedgerResp { data: { account_code: string; account_name: string; opening: number; total_debit: number; total_credit: number; closing: number; lines: Array<{ posted_at: string; entry_code: string; narration: string | null; dr: number; cr: number; running: number }> }; meta: { period: string } }

export function AccountingReportsPage() {
    const { data: periods = [] } = useFiscalPeriods({ kind: 'month' });
    const defaultPeriod = useMemo(() => periods.find((p) => p.code === dayjs().format('YYYY-MM'))?.code ?? periods[0]?.code, [periods]);
    const [period, setPeriod] = useState<string | undefined>();
    useEffect(() => { if (!period && defaultPeriod) setPeriod(defaultPeriod); }, [defaultPeriod, period]);

    const [tab, setTab] = useState<'trial' | 'pnl' | 'bs' | 'ledger' | 'vat'>('trial');
    const canExport = useCan('accounting.export');

    return (
        <div style={{ padding: '8px 0' }}>
            <AccountingSetupBanner />
            <Card
                title={<Typography.Title level={5} style={{ margin: 0 }}>Báo cáo tài chính</Typography.Title>}
                extra={
                    <Space>
                        <span style={{ color: 'rgba(0,0,0,0.55)' }}>Kỳ</span>
                        <PeriodSelect value={period} onChange={setPeriod} style={{ minWidth: 160 }} />
                        {canExport && period && <ExportMisaButton period={period} />}
                    </Space>
                }
                styles={{ body: { padding: 0 } }}
            >
                <Tabs
                    activeKey={tab}
                    onChange={(k) => setTab(k as typeof tab)}
                    style={{ padding: '0 16px' }}
                    items={[
                        { key: 'trial', label: 'Cân đối số phát sinh', children: <TrialBalanceTab period={period} /> },
                        { key: 'pnl', label: 'Kết quả kinh doanh (B02)', children: <PnlTab period={period} /> },
                        { key: 'bs', label: 'Cân đối kế toán (B01)', children: <BsTab period={period} /> },
                        { key: 'ledger', label: 'Sổ chi tiết TK', children: <LedgerTab period={period} /> },
                        { key: 'vat', label: 'Thuế GTGT (01/GTGT)', children: <VatTab period={period} canExport={canExport} /> },
                    ]}
                />
            </Card>
        </div>
    );
}

function ExportMisaButton({ period }: { period: string }) {
    const api = useScopedApi();
    const { message } = App.useApp();
    const onExport = async () => {
        if (!api) return;
        try {
            const r = await api.get(`/accounting/reports/export-misa`, { params: { period }, responseType: 'blob' });
            const blob = new Blob([r.data], { type: r.headers['content-type'] as string });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `accounting-export-misa-${period}.zip`;
            a.click();
            URL.revokeObjectURL(url);
            message.success('Đã tải xuống file export.');
        } catch (e) {
            message.error(errorMessage(e));
        }
    };

    return <Button type="primary" icon={<DownloadOutlined />} onClick={onExport}>Export MISA</Button>;
}

function TrialBalanceTab({ period }: { period: string | undefined }) {
    const api = useScopedApi();
    const { data, isFetching, refetch } = useQuery({
        queryKey: ['accounting', 'reports', 'trial-balance', period],
        enabled: api != null && !!period,
        queryFn: async () => {
            const { data } = await api!.get<TrialResp>('/accounting/reports/trial-balance', { params: { period } });
            return data;
        },
    });

    const columns: ColumnsType<TrialRow> = [
        { title: 'Mã TK', dataIndex: 'account_code', width: 110, render: (c: string) => <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
        { title: 'Tên TK', dataIndex: 'account_name', ellipsis: true },
        { title: 'Loại', dataIndex: 'type', width: 140, render: (t: AccountType) => <Tag color={ACCOUNT_TYPE_COLOR[t]} style={{ marginInlineEnd: 0 }}>{ACCOUNT_TYPE_LABEL[t]}</Tag> },
        { title: 'Dư đầu', dataIndex: 'opening', width: 140, align: 'right', render: (v: number) => v === 0 ? <Typography.Text type="secondary">0</Typography.Text> : formatAmount(v) },
        { title: 'PS Nợ', dataIndex: 'debit', width: 140, align: 'right', render: (v: number) => v === 0 ? <Typography.Text type="secondary">0</Typography.Text> : <Typography.Text strong>{formatAmount(v)}</Typography.Text> },
        { title: 'PS Có', dataIndex: 'credit', width: 140, align: 'right', render: (v: number) => v === 0 ? <Typography.Text type="secondary">0</Typography.Text> : <Typography.Text strong>{formatAmount(v)}</Typography.Text> },
        { title: 'Dư cuối', dataIndex: 'closing', width: 150, align: 'right', render: (v: number) => <Typography.Text strong style={{ color: v < 0 ? '#cf1322' : undefined }}>{formatAmount(v)}</Typography.Text> },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 12 }}>
                <Tooltip title="Tải lại"><Button icon={<ReloadOutlined />} onClick={() => refetch()} /></Tooltip>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16, marginBottom: 16 }}>
                <Statistic title="Tổng PS Nợ" value={data?.meta.total_debit ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                <Statistic title="Tổng PS Có" value={data?.meta.total_credit ?? 0} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                <Statistic title="Kiểm cân bằng" value={data?.meta.balanced ? 'Cân khớp' : 'Lệch'} prefix={data?.meta.balanced ? <CheckCircleFilled /> : <WarningFilled />} valueStyle={{ color: data?.meta.balanced ? '#3f8600' : '#cf1322' }} />
            </div>
            <Table<TrialRow>
                rowKey={(r) => r.account_code}
                dataSource={data?.data ?? []}
                columns={columns}
                loading={isFetching}
                pagination={{ pageSize: 50, showSizeChanger: true, pageSizeOptions: [25, 50, 100] }}
                size="middle"
                scroll={{ x: 1100 }}
            />
        </div>
    );
}

function PnlTab({ period }: { period: string | undefined }) {
    const api = useScopedApi();
    const { data, isFetching } = useQuery({
        queryKey: ['accounting', 'reports', 'pnl', period],
        enabled: api != null && !!period,
        queryFn: async () => {
            const { data } = await api!.get<PnlResp>('/accounting/reports/profit-loss', { params: { period } });
            return data;
        },
    });
    const r = data?.data;
    if (isFetching || !r) return <Typography.Text type="secondary">Đang tải…</Typography.Text>;

    const rows: Array<[string, number, number | null]> = [
        ['Doanh thu BH&CCDV', r.revenue, 1],
        ['Các khoản giảm trừ DT', r.deductions, 2],
        ['Doanh thu thuần', r.net_revenue, 3],
        ['Giá vốn hàng bán', r.cogs, 4],
        ['Lợi nhuận gộp', r.gross_profit, 5],
        ['DT hoạt động tài chính', r.fin_income, 6],
        ['Chi phí tài chính', r.fin_expense, 7],
        ['Chi phí QLKD (642)', r.opex, 8],
        ['Lợi nhuận thuần từ HĐKD', r.ebit, 9],
        ['Thu nhập khác (711)', r.other_income, null],
        ['Chi phí khác (811)', r.other_expense, 10],
        ['Lợi nhuận trước thuế', r.ebt, 11],
        ['Chi phí thuế TNDN', r.tax, 12],
        ['Lợi nhuận sau thuế', r.net_income, 13],
    ];
    return (
        <Card type="inner" title={<Typography.Text strong>BÁO CÁO KẾT QUẢ HOẠT ĐỘNG KINH DOANH — Mẫu B02-DNN</Typography.Text>} style={{ marginTop: 8 }}>
            <Table
                dataSource={rows.map((row, i) => ({ key: i, name: row[0], amount: row[1], idx: row[2] }))}
                pagination={false}
                size="small"
                bordered
                columns={[
                    { title: 'Mã chỉ tiêu', dataIndex: 'idx', width: 110, align: 'center', render: (v: number | null) => v ?? '' },
                    { title: 'Chỉ tiêu', dataIndex: 'name', render: (n: string, row) => (row.idx === 5 || row.idx === 11 || row.idx === 13) ? <Typography.Text strong>{n}</Typography.Text> : n },
                    { title: 'Số tiền (₫)', dataIndex: 'amount', width: 200, align: 'right', render: (v: number, row) => {
                        const strong = row.idx === 5 || row.idx === 11 || row.idx === 13;
                        return <Typography.Text strong={strong} style={{ color: row.idx === 13 ? (v >= 0 ? '#3f8600' : '#cf1322') : undefined }}>{formatAmount(v)}</Typography.Text>;
                    } },
                ]}
            />
        </Card>
    );
}

function BsTab({ period }: { period: string | undefined }) {
    const api = useScopedApi();
    const { data, isFetching } = useQuery({
        queryKey: ['accounting', 'reports', 'bs', period],
        enabled: api != null && !!period,
        queryFn: async () => {
            const { data } = await api!.get<BsResp>('/accounting/reports/balance-sheet', { params: { period } });
            return data;
        },
    });
    const r = data?.data;
    if (isFetching || !r) return <Typography.Text type="secondary">Đang tải…</Typography.Text>;

    const assets = r.lines.filter((l) => l.section === 'asset' || l.section === 'contra_asset');
    const liab = r.lines.filter((l) => l.section === 'liability');
    const eq = r.lines.filter((l) => l.section === 'equity');

    return (
        <div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 16, marginBottom: 16 }}>
                <Statistic title="Tổng tài sản" value={r.assets} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#2563EB' }} />
                <Statistic title="Tổng nợ phải trả" value={r.liabilities} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#fa541c' }} />
                <Statistic title="Vốn chủ sở hữu" value={r.equity} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#722ed1' }} />
                <Statistic title="Cân đối" value={r.balanced ? 'Cân khớp' : 'Lệch'} prefix={r.balanced ? <CheckCircleFilled /> : <WarningFilled />} valueStyle={{ color: r.balanced ? '#3f8600' : '#cf1322' }} />
                <Statistic title="LNST năm nay" value={r.retained_earnings_net} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: r.retained_earnings_net >= 0 ? '#3f8600' : '#cf1322' }} />
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                <Card type="inner" title={<Typography.Text strong>TÀI SẢN</Typography.Text>}>
                    <BsTable rows={assets} total={r.assets} />
                </Card>
                <Card type="inner" title={<Typography.Text strong>NGUỒN VỐN</Typography.Text>}>
                    <Card type="inner" title="Nợ phải trả" size="small" style={{ marginBottom: 8 }}>
                        <BsTable rows={liab} total={r.liabilities} />
                    </Card>
                    <Card type="inner" title="Vốn chủ sở hữu" size="small">
                        <BsTable rows={eq} total={r.equity} />
                    </Card>
                </Card>
            </div>
        </div>
    );
}

function BsTable({ rows, total }: { rows: Array<{ code: string; name: string; amount: number }>; total: number }) {
    return (
        <Table
            dataSource={rows.map((r, i) => ({ ...r, key: i }))}
            pagination={false}
            size="small"
            columns={[
                { title: 'Mã TK', dataIndex: 'code', width: 90, render: (c: string) => <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
                { title: 'Tên', dataIndex: 'name', ellipsis: true },
                { title: 'Số tiền (₫)', dataIndex: 'amount', width: 150, align: 'right', render: (v: number) => formatAmount(v) },
            ]}
            summary={() => (
                <Table.Summary.Row>
                    <Table.Summary.Cell index={0} colSpan={2}><Typography.Text strong>Tổng</Typography.Text></Table.Summary.Cell>
                    <Table.Summary.Cell index={1} align="right"><Typography.Text strong>{formatAmount(total)}</Typography.Text></Table.Summary.Cell>
                </Table.Summary.Row>
            )}
        />
    );
}

function LedgerTab({ period }: { period: string | undefined }) {
    const [account, setAccount] = useState<string | undefined>('131');
    const api = useScopedApi();
    const { data, isFetching } = useQuery({
        queryKey: ['accounting', 'reports', 'ledger', period, account],
        enabled: api != null && !!period && !!account,
        queryFn: async () => {
            const { data } = await api!.get<LedgerResp>('/accounting/reports/ledger', { params: { period, account_code: account } });
            return data;
        },
    });
    const r = data?.data;

    return (
        <div>
            <div style={{ marginBottom: 16, display: 'flex', gap: 12, alignItems: 'center' }}>
                <span>Tài khoản:</span>
                <div style={{ width: 320 }}>
                    <AccountTreeSelect value={account} onChange={(v) => setAccount(v)} onlyPostable />
                </div>
            </div>
            {!r && <Typography.Text type="secondary">Chọn tài khoản để xem sổ chi tiết.</Typography.Text>}
            {r && (
                <>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 16, marginBottom: 16 }}>
                        <Statistic title="Dư đầu kỳ" value={r.opening} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                        <Statistic title="PS Nợ" value={r.total_debit} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                        <Statistic title="PS Có" value={r.total_credit} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                        <Statistic title="Dư cuối kỳ" value={r.closing} suffix="₫" formatter={(v) => formatAmount(Number(v))} />
                    </div>
                    <Table
                        rowKey={(_, i) => String(i)}
                        dataSource={r.lines}
                        loading={isFetching}
                        pagination={{ pageSize: 50, showSizeChanger: true }}
                        size="small"
                        bordered
                        columns={[
                            { title: 'Ngày', dataIndex: 'posted_at', width: 110, render: (d: string) => dayjs(d).format('DD/MM/YYYY') },
                            { title: 'Mã JE', dataIndex: 'entry_code', width: 150, render: (c: string) => <Typography.Text style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
                            { title: 'Diễn giải', dataIndex: 'narration', ellipsis: true, render: (n: string | null) => n ?? '—' },
                            { title: 'Nợ', dataIndex: 'dr', width: 140, align: 'right', render: (v: number) => v > 0 ? <Typography.Text strong>{formatAmount(v)}</Typography.Text> : <Typography.Text type="secondary">—</Typography.Text> },
                            { title: 'Có', dataIndex: 'cr', width: 140, align: 'right', render: (v: number) => v > 0 ? <Typography.Text strong>{formatAmount(v)}</Typography.Text> : <Typography.Text type="secondary">—</Typography.Text> },
                            { title: 'Số dư sau GD', dataIndex: 'running', width: 160, align: 'right', render: (v: number) => <Typography.Text strong style={{ color: v < 0 ? '#cf1322' : '#2563EB' }}>{formatAmount(v)}</Typography.Text> },
                        ]}
                    />
                </>
            )}
        </div>
    );
}

function VatTab({ period, canExport }: { period: string | undefined; canExport: boolean }) {
    const { data, isFetching, error } = useVatReport(period);
    const createFiling = useCreateTaxFiling();
    const canPost = useCan('accounting.post');
    const { message } = App.useApp();

    const status = (error as { response?: { status?: number } })?.response?.status;
    if (status === 402) {
        return <Typography.Paragraph type="warning">Báo cáo thuế GTGT thuộc gói nâng cao (accounting_advanced). Vui lòng nâng gói để sử dụng.</Typography.Paragraph>;
    }
    if (isFetching || !data) return <Typography.Text type="secondary">Đang tải…</Typography.Text>;

    const rows: Array<{ key: string; code: string; name: string; amount: number; strong?: boolean }> = [
        { key: 'out', code: '[33311]', name: 'Thuế GTGT đầu ra (của hàng hoá, dịch vụ bán ra)', amount: data.output_vat },
        { key: 'in', code: '[1331]', name: 'Thuế GTGT đầu vào được khấu trừ (mua vào)', amount: data.input_vat },
        { key: 'net', code: '[40]', name: 'Thuế GTGT còn phải nộp trong kỳ', amount: data.net_payable, strong: true },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16, flexWrap: 'wrap', gap: 12 }}>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 16, flex: 1 }}>
                    <Statistic title="VAT đầu ra (33311)" value={data.output_vat} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#fa541c' }} />
                    <Statistic title="VAT đầu vào (1331)" value={data.input_vat} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: '#2563EB' }} />
                    <Statistic title="Còn phải nộp" value={data.net_payable} suffix="₫" formatter={(v) => formatAmount(Number(v))} valueStyle={{ color: data.net_payable > 0 ? '#cf1322' : '#3f8600' }} />
                </div>
                {(canPost || canExport) && period && (
                    <Popconfirm
                        title="Lập tờ khai 01/GTGT?"
                        description={`Lưu tờ khai thuế GTGT kỳ ${period} để theo dõi & đối chiếu. Có thể lập lại để cập nhật số liệu.`}
                        okText="Lập tờ khai"
                        cancelText="Huỷ"
                        onConfirm={async () => {
                            try { await createFiling.mutateAsync(period); message.success(`Đã lập tờ khai 01/GTGT-${period}.`); }
                            catch (e) { message.error(errorMessage(e)); }
                        }}
                    >
                        <Button type="primary" loading={createFiling.isPending}>Lập tờ khai 01/GTGT</Button>
                    </Popconfirm>
                )}
            </div>
            <Card type="inner" title={<Typography.Text strong>TỜ KHAI THUẾ GTGT — Mẫu 01/GTGT (kỳ {period})</Typography.Text>}>
                <Table
                    dataSource={rows}
                    pagination={false}
                    size="small"
                    bordered
                    columns={[
                        { title: 'Chỉ tiêu', dataIndex: 'code', width: 110, render: (c: string) => <Typography.Text strong style={{ fontFamily: 'ui-monospace, monospace' }}>{c}</Typography.Text> },
                        { title: 'Nội dung', dataIndex: 'name', render: (n: string, r) => r.strong ? <Typography.Text strong>{n}</Typography.Text> : n },
                        { title: 'Số tiền (₫)', dataIndex: 'amount', width: 200, align: 'right', render: (v: number, r) => <Typography.Text strong={r.strong} style={{ color: r.strong ? (v > 0 ? '#cf1322' : '#3f8600') : undefined }}>{formatAmount(v)}</Typography.Text> },
                    ]}
                />
                <Typography.Paragraph type="secondary" style={{ marginTop: 12, marginBottom: 0, fontSize: 12 }}>
                    Số liệu tổng hợp tự động từ các bút toán TK 33311 (đầu ra) và TK 1331 (đầu vào) trong kỳ. Đây là số liệu tham chiếu;
                    cần đối chiếu với hoá đơn thực tế trước khi nộp tờ khai chính thức trên HTKK/eTax.
                </Typography.Paragraph>
            </Card>
        </div>
    );
}
