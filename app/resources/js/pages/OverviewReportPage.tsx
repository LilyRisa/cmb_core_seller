import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Alert, Card, Col, Empty, Row, Segmented, Space, Spin, Statistic, Table, Tag, Typography } from 'antd';
import { BarChartOutlined, LockOutlined, SafetyCertificateOutlined, WalletOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { Area, AreaChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip as RTooltip, XAxis, YAxis } from 'recharts';
import { PageHeader } from '@/components/PageHeader';
import { ChannelLogo } from '@/components/ChannelLogo';
import { errorCode } from '@/lib/api';
import { useRevenueReport, useProfitReport } from '@/lib/reports';
import { useSettlementSummary } from '@/lib/finance';
import { useShopReport, PROVIDER_LABEL, RATING_COLOR, type ShopReportEntry } from '@/lib/shopReport';
import { useChannelAccounts } from '@/lib/channels';

const { Text } = Typography;

const RANGE_OPTIONS = [
    { label: '7 ngày', value: 7 },
    { label: '30 ngày', value: 30 },
    { label: '90 ngày', value: 90 },
];

function vnd(n: number | null | undefined): string {
    return `${Math.round(n ?? 0).toLocaleString('vi-VN')} ₫`;
}

/** Một query có bị khoá theo gói (402 PLAN_FEATURE_LOCKED) không. */
function isLocked(err: unknown): boolean {
    return errorCode(err) === 'PLAN_FEATURE_LOCKED';
}

function LockedCard({ title, feature }: { title: string; feature: string }) {
    return (
        <Card title={title} style={{ marginBottom: 16 }}>
            <Empty
                image={Empty.PRESENTED_IMAGE_SIMPLE}
                description={(
                    <span><LockOutlined /> Tính năng <b>{feature}</b> có ở gói cao hơn. <Link to="/plans">Nâng cấp gói</Link> để xem.</span>
                )}
            />
        </Card>
    );
}

export function OverviewReportPage() {
    const [days, setDays] = useState(30);
    const { from, to } = useMemo(() => {
        const end = dayjs();
        return { from: end.subtract(days - 1, 'day').format('YYYY-MM-DD'), to: end.format('YYYY-MM-DD') };
    }, [days]);

    const revenueQ = useRevenueReport({ from, to, granularity: 'day' });
    const profitQ = useProfitReport({ from, to, granularity: 'day' });
    const settleQ = useSettlementSummary({ from, to });
    const shopQ = useShopReport();
    const { data: channels } = useChannelAccounts();
    const shopName = (cid: number) => channels?.data?.find((c) => c.id === cid)?.name ?? `#${cid}`;

    const rev = revenueQ.data;
    const profit = profitQ.data;
    const settle = settleQ.data;

    // Xu hướng theo ngày — doanh thu (luôn có) + lợi nhuận ròng (nếu gói cho phép).
    const trend = useMemo(() => {
        const profitBy = new Map((profit?.series ?? []).map((s) => [s.date, s.net_profit]));
        return (rev?.series ?? []).map((s) => ({ date: s.date, revenue: s.revenue, net_profit: profitBy.get(s.date) ?? 0 }));
    }, [rev, profit]);

    // --- Doanh thu theo sàn ---
    const bySourceCols: ColumnsType<{ source: string; orders: number; revenue: number }> = [
        { title: 'Sàn', dataIndex: 'source', key: 'source', render: (s: string) => (
            <Space size={6}><ChannelLogo provider={s} size={16} /><span>{PROVIDER_LABEL[s] ?? s}</span></Space>
        ) },
        { title: 'Đơn', dataIndex: 'orders', key: 'orders', align: 'right', width: 90 },
        { title: 'Doanh thu', dataIndex: 'revenue', key: 'revenue', align: 'right', render: (v: number) => <Text strong>{vnd(v)}</Text> },
    ];

    // --- Sức khoẻ gian hàng (rút gọn) ---
    const shopRows = (shopQ.data ?? []) as ShopReportEntry[];
    const shopCols: ColumnsType<ShopReportEntry> = [
        { title: 'Gian hàng', key: 'shop', render: (_: unknown, r) => (
            <Space size={6}><ChannelLogo provider={r.provider} size={16} /><span>{r.shop_name}</span></Space>
        ) },
        { title: 'Xếp hạng', key: 'rating', width: 130, render: (_: unknown, r) => {
            if (!r.available) return <Text type="secondary">—</Text>;
            if (r.overall_rating != null) return <Tag color={RATING_COLOR[r.overall_rating] ?? 'default'}>{r.overall_label ?? `Hạng ${r.overall_rating}`}</Tag>;
            return <Tag color="blue">Hiệu suất</Tag>;
        } },
        { title: 'Đạt mục tiêu', key: 'passed', width: 110, align: 'center', render: (_: unknown, r) => (
            r.available && r.total_metrics ? <Text>{r.passed_count ?? 0}/{r.total_metrics}</Text> : <Text type="secondary">—</Text>
        ) },
        { title: 'Điểm phạt', key: 'penalty', width: 100, align: 'center', render: (_: unknown, r) => {
            const total = (r.penalties ?? []).reduce((s, p) => s + (p.points || 0), 0);
            return total > 0 ? <Tag color="orange">{total}</Tag> : (r.supports_penalty ? <Tag color="green">0</Tag> : <Text type="secondary">—</Text>);
        } },
    ];

    return (
        <div>
            <PageHeader
                title={<Space><BarChartOutlined />Báo cáo tổng thể</Space>}
                extra={<Segmented options={RANGE_OPTIONS} value={days} onChange={(v) => setDays(v as number)} />}
            />

            {revenueQ.isLoading ? (
                <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>
            ) : (
                <>
                    {/* KPI doanh thu & lợi nhuận */}
                    <Row gutter={16} style={{ marginBottom: 16 }}>
                        <Col xs={12} md={6}><Card><Statistic title="Doanh thu" value={Math.round(rev?.totals.revenue ?? 0)} formatter={(v) => vnd(Number(v))} /></Card></Col>
                        <Col xs={12} md={6}><Card><Statistic title="Đơn hàng" value={rev?.totals.orders ?? 0} /></Card></Col>
                        <Col xs={12} md={6}><Card><Statistic title="Giá trị TB/đơn" value={Math.round(rev?.totals.avg_order_value ?? 0)} formatter={(v) => vnd(Number(v))} /></Card></Col>
                        <Col xs={12} md={6}>
                            <Card>
                                {isLocked(profitQ.error) ? (
                                    <Statistic title="Lợi nhuận ròng" valueRender={() => <Text type="secondary"><LockOutlined /> Gói cao hơn</Text>} value={0} />
                                ) : (
                                    <Statistic
                                        title={`Lợi nhuận ròng${profit ? ` · biên ${profit.totals.margin_pct}%` : ''}`}
                                        value={Math.round(profit?.totals.net_profit ?? 0)}
                                        formatter={(v) => vnd(Number(v))}
                                        valueStyle={{ color: (profit?.totals.net_profit ?? 0) >= 0 ? '#389e0d' : '#cf1322' }}
                                    />
                                )}
                            </Card>
                        </Col>
                    </Row>

                    {/* Xu hướng doanh thu & lợi nhuận */}
                    {trend.length > 0 ? (
                        <Card title="Xu hướng doanh thu & lợi nhuận" style={{ marginBottom: 16 }}>
                            <ResponsiveContainer width="100%" height={260}>
                                <AreaChart data={trend} margin={{ top: 8, right: 16, left: 0, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f0f0f0" />
                                    <XAxis dataKey="date" tickFormatter={(v) => dayjs(v).format('DD/MM')} tick={{ fontSize: 11 }} axisLine={false} tickLine={false} />
                                    <YAxis tickFormatter={(v) => `${Math.round(Number(v) / 1000)}k`} tick={{ fontSize: 11 }} axisLine={false} tickLine={false} width={48} />
                                    <RTooltip formatter={(v, name) => [vnd(Number(v)), name === 'revenue' ? 'Doanh thu' : 'Lợi nhuận ròng']} labelFormatter={(d) => dayjs(d as string).format('DD/MM/YYYY')} />
                                    <Legend formatter={(v) => (v === 'revenue' ? 'Doanh thu' : 'Lợi nhuận ròng')} />
                                    <Area type="monotone" dataKey="revenue" stroke="#1677ff" fill="#1677ff22" strokeWidth={2} dot={false} />
                                    {!isLocked(profitQ.error) ? <Area type="monotone" dataKey="net_profit" stroke="#389e0d" fill="#389e0d22" strokeWidth={2} dot={false} /> : null}
                                </AreaChart>
                            </ResponsiveContainer>
                        </Card>
                    ) : null}

                    <Row gutter={16}>
                        <Col xs={24} lg={14}>
                            {/* Đối soát thực */}
                            {isLocked(settleQ.error) ? (
                                <LockedCard title="Đối soát thực" feature="Đối soát sàn" />
                            ) : (
                                <Card title={<Space><WalletOutlined />Đối soát thực (tiền sàn đã trả)</Space>} style={{ marginBottom: 16 }}
                                    loading={settleQ.isLoading}
                                    extra={<Link to="/finance/settlements">Chi tiết</Link>}>
                                    <Row gutter={16}>
                                        <Col xs={12} md={8}><Statistic title="Thực nhận (payout)" value={Math.round(settle?.totals.payout ?? 0)} formatter={(v) => vnd(Number(v))} valueStyle={{ color: '#389e0d' }} /></Col>
                                        <Col xs={12} md={8}><Statistic title="Doanh thu đối soát" value={Math.round(settle?.totals.revenue ?? 0)} formatter={(v) => vnd(Number(v))} /></Col>
                                        <Col xs={12} md={8}><Statistic title="Phí sàn" value={Math.round(Math.abs(settle?.totals.fee ?? 0))} formatter={(v) => `-${vnd(Number(v))}`} valueStyle={{ color: '#cf1322' }} /></Col>
                                    </Row>
                                    <div style={{ marginTop: 12 }}>
                                        <Space size={6} wrap>
                                            <Tag>{settle?.totals.settlements ?? 0} kỳ đối soát</Tag>
                                            <Tag color="green">{settle?.totals.reconciled ?? 0} đã khớp</Tag>
                                            {(settle?.totals.pending ?? 0) > 0 ? <Tag color="orange">{settle?.totals.pending} chờ khớp</Tag> : null}
                                            {(settle?.totals.error ?? 0) > 0 ? <Tag color="red">{settle?.totals.error} lỗi</Tag> : null}
                                        </Space>
                                    </div>
                                </Card>
                            )}

                            {/* Doanh thu theo sàn */}
                            <Card title="Doanh thu theo sàn" style={{ marginBottom: 16 }}>
                                <Table
                                    size="small"
                                    rowKey="source"
                                    columns={bySourceCols}
                                    dataSource={rev?.by_source ?? []}
                                    pagination={false}
                                    locale={{ emptyText: 'Chưa có doanh thu trong kỳ.' }}
                                />
                            </Card>
                        </Col>

                        <Col xs={24} lg={10}>
                            {/* Sức khoẻ gian hàng */}
                            {isLocked(shopQ.error) ? (
                                <LockedCard title="Sức khoẻ gian hàng" feature="Báo cáo sàn" />
                            ) : (
                                <Card title={<Space><SafetyCertificateOutlined />Sức khoẻ gian hàng</Space>} style={{ marginBottom: 16 }}
                                    loading={shopQ.isLoading}
                                    extra={<Link to="/shop-report">Chi tiết</Link>}>
                                    <Table
                                        size="small"
                                        rowKey="channel_account_id"
                                        columns={shopCols}
                                        dataSource={shopRows}
                                        pagination={false}
                                        locale={{ emptyText: 'Chưa có gian hàng hỗ trợ báo cáo sức khoẻ.' }}
                                    />
                                </Card>
                            )}
                        </Col>
                    </Row>

                    {/* Đối soát theo gian hàng */}
                    {!isLocked(settleQ.error) && (settle?.by_channel.length ?? 0) > 0 ? (
                        <Card title="Đối soát theo gian hàng" style={{ marginBottom: 16 }}>
                            <Table
                                size="small"
                                rowKey="channel_account_id"
                                pagination={false}
                                dataSource={settle?.by_channel ?? []}
                                columns={[
                                    { title: 'Gian hàng', key: 'shop', render: (_: unknown, r: { channel_account_id: number }) => shopName(r.channel_account_id) },
                                    { title: 'Kỳ', dataIndex: 'settlements', key: 'settlements', align: 'right', width: 80 },
                                    { title: 'Thực nhận', dataIndex: 'payout', key: 'payout', align: 'right', render: (v: number) => <Text strong>{vnd(v)}</Text> },
                                    { title: 'Phí sàn', dataIndex: 'fee', key: 'fee', align: 'right', render: (v: number) => <Text type="danger">-{vnd(Math.abs(v))}</Text> },
                                ]}
                            />
                        </Card>
                    ) : null}

                    <Alert
                        type="info"
                        showIcon
                        message="“Đối soát thực” là số tiền sàn ĐÃ đối soát/thanh toán trong kỳ — có thể lệch với doanh thu ghi nhận do phí sàn, hoàn tiền và độ trễ đối soát."
                    />
                </>
            )}
        </div>
    );
}
