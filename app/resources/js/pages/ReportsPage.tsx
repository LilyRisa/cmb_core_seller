import { useMemo, useState } from 'react';
import { Button, Card, DatePicker, Empty, Progress, Radio, Space, Statistic, Table, Tabs, Tag, Typography } from 'antd';
import { BarChartOutlined, DollarOutlined, DownloadOutlined, FundOutlined, RiseOutlined, ShoppingOutlined, TrophyOutlined } from '@ant-design/icons';
import dayjs, { type Dayjs } from 'dayjs';
import type { ColumnsType } from 'antd/es/table';
import { PageHeader } from '@/components/PageHeader';
import { SkuLine } from '@/components/SkuPicker';
import { MoneyText } from '@/components/MoneyText';
import { FilterChipRow, type ChipItem } from '@/components/FilterChipRow';
import { useCan, useCurrentTenantId } from '@/lib/tenant';
import { CHANNEL_META } from '@/lib/format';
import {
    type Granularity, type ReportFilters, exportReportUrl,
    useProfitReport, useRevenueReport, useTopProductsReport,
} from '@/lib/reports';

const { RangePicker } = DatePicker;

/** Bộ lọc thời gian thân thiện — chip tắt + RangePicker. */
const PRESETS: Array<{ key: string; label: string; range: () => [string, string] }> = [
    { key: '7d', label: '7 ngày', range: () => [dayjs().subtract(6, 'day').format('YYYY-MM-DD'), dayjs().format('YYYY-MM-DD')] },
    { key: '30d', label: '30 ngày', range: () => [dayjs().subtract(29, 'day').format('YYYY-MM-DD'), dayjs().format('YYYY-MM-DD')] },
    { key: 'mtd', label: 'Tháng này', range: () => [dayjs().startOf('month').format('YYYY-MM-DD'), dayjs().format('YYYY-MM-DD')] },
    { key: 'qtd', label: 'Quý này', range: () => {
        const m = dayjs().month();
        const startMonth = Math.floor(m / 3) * 3;

        return [dayjs().month(startMonth).startOf('month').format('YYYY-MM-DD'), dayjs().format('YYYY-MM-DD')];
    } },
    { key: 'ytd', label: 'Năm nay', range: () => [dayjs().startOf('year').format('YYYY-MM-DD'), dayjs().format('YYYY-MM-DD')] },
];

const SOURCE_CHIPS: ChipItem[] = Object.keys(CHANNEL_META).map((k) => ({ value: k, label: CHANNEL_META[k].name }));

export function ReportsPage() {
    const canExport = useCan('reports.export');
    const tenantId = useCurrentTenantId();
    const [tab, setTab] = useState<'revenue' | 'profit' | 'top'>('revenue');
    const [presetKey, setPresetKey] = useState<string | undefined>('30d');
    const initial = PRESETS.find((p) => p.key === presetKey)?.range() ?? PRESETS[1].range();
    const [range, setRange] = useState<[Dayjs, Dayjs]>([dayjs(initial[0]), dayjs(initial[1])]);
    const [granularity, setGranularity] = useState<Granularity>('day');
    const [source, setSource] = useState<string | undefined>();
    const [topSortBy, setTopSortBy] = useState<'revenue' | 'profit' | 'qty'>('revenue');

    const filters: ReportFilters = useMemo(() => ({
        from: range[0].format('YYYY-MM-DD'), to: range[1].format('YYYY-MM-DD'),
        granularity, source,
    }), [range, granularity, source]);

    const onPreset = (k: string | undefined) => {
        setPresetKey(k);
        if (!k) return;
        const p = PRESETS.find((x) => x.key === k);
        if (p) {
            const [f, t] = p.range();
            setRange([dayjs(f), dayjs(t)]);
        }
    };

    return (
        <div>
            <PageHeader title="Báo cáo" subtitle="Doanh thu, lợi nhuận thực (FIFO COGS), top sản phẩm — lọc theo sàn / thời gian; export CSV."
                extra={canExport && tenantId != null && (
                    <a href={exportReportUrl(tenantId, tab === 'top' ? 'top-products' : tab, { ...filters, ...(tab === 'top' ? { sort_by: topSortBy } : {}) })} target="_blank" rel="noreferrer">
                        <Button icon={<DownloadOutlined />}>Xuất CSV</Button>
                    </a>
                )}
            />

            <Card style={{ marginBottom: 12 }} size="small">
                <Space direction="vertical" size={4} style={{ width: '100%' }}>
                    <FilterChipRow label="Khoảng thời gian" value={presetKey}
                        items={PRESETS.map((p) => ({ value: p.key, label: p.label }))}
                        onChange={onPreset}
                        extra={(
                            <RangePicker size="small" value={range as [Dayjs, Dayjs]} onChange={(v) => { if (v?.[0] && v?.[1]) { setRange([v[0], v[1]]); setPresetKey(undefined); } }} format="DD/MM/YYYY" allowClear={false} />
                        )} />
                    <FilterChipRow label="Sàn TMĐT" value={source} items={SOURCE_CHIPS} onChange={(v) => setSource(v)} />
                    <Space style={{ paddingTop: 4 }}>
                        <Typography.Text type="secondary" style={{ fontSize: 13 }}>Đơn vị thời gian:</Typography.Text>
                        <Radio.Group size="small" value={granularity} onChange={(e) => setGranularity(e.target.value)} optionType="button"
                            options={[{ value: 'day', label: 'Ngày' }, { value: 'week', label: 'Tuần' }, { value: 'month', label: 'Tháng' }]} />
                    </Space>
                </Space>
            </Card>

            <Tabs activeKey={tab} onChange={(k) => setTab(k as 'revenue' | 'profit' | 'top')}
                items={[
                    { key: 'revenue', label: <span><BarChartOutlined /> Doanh thu</span>, children: <RevenueTab filters={filters} /> },
                    { key: 'profit', label: <span><RiseOutlined /> Lợi nhuận thực (FIFO)</span>, children: <ProfitTab filters={filters} /> },
                    { key: 'top', label: <span><TrophyOutlined /> Top sản phẩm</span>, children: <TopProductsTab filters={filters} sortBy={topSortBy} setSortBy={setTopSortBy} /> },
                ]} />
        </div>
    );
}

function RevenueTab({ filters }: { filters: ReportFilters }) {
    const { data, isFetching } = useRevenueReport(filters);
    const t = data?.totals;

    return (
        <Card loading={isFetching}>
            {!data ? <Empty /> : (
                <>
                    <Space wrap size={32} style={{ marginBottom: 16 }}>
                        <Statistic title="Số đơn (đã xác nhận)" value={t!.orders} prefix={<ShoppingOutlined />} />
                        <Statistic title="Doanh thu" value={t!.revenue} suffix="₫" formatter={(v) => Number(v).toLocaleString('vi-VN')} prefix={<DollarOutlined />} />
                        <Statistic title="GMV trung bình/đơn" value={t!.avg_order_value} suffix="₫" formatter={(v) => Number(v).toLocaleString('vi-VN')} />
                        <Statistic title="Phí vận chuyển" value={t!.shipping_fee} suffix="₫" formatter={(v) => Number(v).toLocaleString('vi-VN')} />
                    </Space>
                    <Typography.Title level={5}>Theo {data.granularity === 'day' ? 'ngày' : data.granularity === 'week' ? 'tuần' : 'tháng'}</Typography.Title>
                    <Table size="small" rowKey="date" pagination={false} dataSource={data.series}
                        columns={[
                            { title: 'Mốc', dataIndex: 'date', key: 'date' },
                            { title: 'Số đơn', dataIndex: 'orders', key: 'o', align: 'right' },
                            { title: 'Doanh thu', dataIndex: 'revenue', key: 'r', align: 'right', render: (v) => <MoneyText value={v} strong /> },
                            { title: 'Phí ship', dataIndex: 'shipping_fee', key: 's', align: 'right', render: (v) => <MoneyText value={v} /> },
                        ]} />

                    <Typography.Title level={5} style={{ marginTop: 16 }}>Theo sàn</Typography.Title>
                    {data.by_source.length === 0 ? <Empty description="Chưa có dữ liệu" /> : (
                        <Table size="small" rowKey="source" pagination={false} dataSource={data.by_source}
                            columns={[
                                { title: 'Sàn', dataIndex: 'source', key: 's', render: (v) => <Tag color={CHANNEL_META[v]?.color}>{CHANNEL_META[v]?.name ?? v}</Tag> },
                                { title: 'Số đơn', dataIndex: 'orders', key: 'o', align: 'right' },
                                { title: 'Doanh thu', dataIndex: 'revenue', key: 'r', align: 'right', render: (v) => <MoneyText value={v} strong /> },
                                { title: 'Tỉ trọng', key: 'share', align: 'right', render: (_, r) => {
                                    const share = (data.totals.revenue || 1) === 0 ? 0 : Math.round((r.revenue / data.totals.revenue) * 100);

                                    return <Progress percent={share} size="small" style={{ width: 120 }} />;
                                } },
                            ]} />
                    )}
                </>
            )}
        </Card>
    );
}

function ProfitTab({ filters }: { filters: ReportFilters }) {
    const { data, isFetching } = useProfitReport(filters);
    const t = data?.totals;

    return (
        <Card loading={isFetching}>
            {!data ? <Empty /> : (
                <>
                    <Space wrap size={32} style={{ marginBottom: 16 }}>
                        <Statistic title="Số đơn (đã ship)" value={t!.orders} prefix={<FundOutlined />} />
                        <Statistic title="Doanh thu thực" value={t!.revenue} suffix="₫" formatter={(v) => Number(v).toLocaleString('vi-VN')} />
                        <Statistic title="Giá vốn (COGS)" value={t!.cogs} suffix="₫" formatter={(v) => Number(v).toLocaleString('vi-VN')} />
                        <Statistic title="Lợi nhuận gộp" value={t!.gross_profit} suffix="₫"
                            formatter={(v) => Number(v).toLocaleString('vi-VN')}
                            valueStyle={{ color: t!.gross_profit >= 0 ? '#389e0d' : '#cf1322' }} />
                        <Statistic title="Biên LN" value={t!.margin_pct} suffix="%" precision={2} valueStyle={{ color: t!.margin_pct >= 0 ? '#389e0d' : '#cf1322' }} />
                    </Space>
                    <Typography.Title level={5}>Diễn biến theo {data.granularity === 'day' ? 'ngày' : data.granularity === 'week' ? 'tuần' : 'tháng'}</Typography.Title>
                    <Table size="small" rowKey="date" pagination={false} dataSource={data.series}
                        columns={[
                            { title: 'Mốc', dataIndex: 'date', key: 'date' },
                            { title: 'Doanh thu', dataIndex: 'revenue', key: 'r', align: 'right', render: (v) => <MoneyText value={v} /> },
                            { title: 'COGS', dataIndex: 'cogs', key: 'c', align: 'right', render: (v) => <MoneyText value={v} /> },
                            { title: 'LN gộp', dataIndex: 'gross_profit', key: 'g', align: 'right', render: (v) => <Typography.Text strong style={{ color: v >= 0 ? '#389e0d' : '#cf1322' }}><MoneyText value={v} strong /></Typography.Text> },
                            { title: 'Biên', dataIndex: 'margin_pct', key: 'm', align: 'right', width: 100, render: (v) => `${v}%` },
                        ]} />
                </>
            )}
        </Card>
    );
}

function TopProductsTab({ filters, sortBy, setSortBy }: { filters: ReportFilters; sortBy: 'revenue' | 'profit' | 'qty'; setSortBy: (v: 'revenue' | 'profit' | 'qty') => void }) {
    const { data, isFetching } = useTopProductsReport({ ...filters, sort_by: sortBy, limit: 20 });
    const columns: ColumnsType<NonNullable<typeof data>['items'][number]> = [
        { title: '#', key: 'rank', width: 50, render: (_, __, i) => <Typography.Text strong>{i + 1}</Typography.Text> },
        { title: 'SKU', key: 'sku', render: (_, r) => r.sku ? <SkuLine sku={r.sku} avatarSize={36} maxTextWidth={300} /> : `#${r.sku_id}` },
        { title: 'SL bán', dataIndex: 'qty', key: 'qty', width: 100, align: 'right' },
        { title: 'Doanh thu', dataIndex: 'revenue', key: 'r', width: 140, align: 'right', render: (v) => <MoneyText value={v} strong /> },
        { title: 'COGS', dataIndex: 'cogs', key: 'c', width: 130, align: 'right', render: (v) => <MoneyText value={v} /> },
        { title: 'LN gộp', dataIndex: 'gross_profit', key: 'g', width: 140, align: 'right', render: (v) => <Typography.Text strong style={{ color: v >= 0 ? '#389e0d' : '#cf1322' }}><MoneyText value={v} strong /></Typography.Text> },
        { title: 'Biên', dataIndex: 'margin_pct', key: 'm', width: 90, align: 'right', render: (v) => `${v}%` },
    ];
    return (
        <Card loading={isFetching}>
            <Space style={{ marginBottom: 12 }}>
                <Typography.Text type="secondary">Sắp xếp theo:</Typography.Text>
                <Radio.Group size="small" value={sortBy} onChange={(e) => setSortBy(e.target.value)} optionType="button"
                    options={[{ value: 'revenue', label: 'Doanh thu' }, { value: 'profit', label: 'Lợi nhuận' }, { value: 'qty', label: 'Số lượng' }]} />
            </Space>
            <Table rowKey="sku_id" size="middle" pagination={false} dataSource={data?.items ?? []} columns={columns}
                locale={{ emptyText: <Empty description="Chưa có dữ liệu trong khoảng thời gian này" /> }} />
        </Card>
    );
}
