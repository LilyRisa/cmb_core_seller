import { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Alert, Avatar, Badge, Button, Card, Col, Empty, List, Progress, Radio, Row, Skeleton, Space, Tag, Tooltip, Typography } from 'antd';
import {
    ArrowDownOutlined, ArrowUpOutlined, AuditOutlined, BankOutlined, CarOutlined, CreditCardOutlined,
    DollarOutlined, ExclamationCircleOutlined, FallOutlined, FundOutlined, LinkOutlined, PictureOutlined,
    PrinterOutlined, ReloadOutlined, RiseOutlined, ShopOutlined, ShoppingCartOutlined,
    ThunderboltOutlined, TrophyOutlined, WalletOutlined, WarningOutlined,
} from '@ant-design/icons';
import {
    Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, ResponsiveContainer,
    Tooltip as ReTooltip, XAxis, YAxis,
} from 'recharts';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { CHANNEL_META } from '@/lib/format';
import { deltaPct, useDashboardSummary, type DashboardRange, type DashboardSummary, type KpiPair } from '@/lib/dashboard';
import { useAccountingDashboardSummary, type AccountingDashboardSummary } from '@/lib/accounting';
import { useAuth } from '@/lib/auth';

/** AntD palette dùng nhất quán cho cả thẻ KPI lẫn biểu đồ. */
const COLOR = {
    revenue: '#1668dc',          // primary blue
    orders: '#52c41a',
    estProfit: '#722ed1',
    grossProfit: '#13c2c2',
    aov: '#fa8c16',
    warning: '#faad14',
    danger: '#ff4d4f',
    muted: '#8c8c8c',
};

const RANGE_OPTIONS: Array<{ value: DashboardRange; label: string }> = [
    { value: 7, label: '7 ngày' },
    { value: 30, label: '30 ngày' },
    { value: 90, label: '90 ngày' },
];

export function DashboardPage() {
    const { data: user } = useAuth();
    const [range, setRange] = useRange();
    const { data, isLoading, isFetching, refetch } = useDashboardSummary(range);
    const acct = useAccountingDashboardSummary();

    const todoBuckets = useMemo(() => makeTodoBuckets(data), [data]);
    const noChannel = !isLoading && (data?.channel_accounts.total ?? 0) === 0;

    return (
        <div>
            <PageHeader
                title="Bảng điều khiển"
                subtitle={`Xin chào ${user?.name ?? ''} — tổng quan ${range} ngày gần nhất`}
                extra={(
                    <Space>
                        <Radio.Group value={range} onChange={(e) => setRange(e.target.value as DashboardRange)} optionType="button" buttonStyle="solid"
                            options={RANGE_OPTIONS} />
                        <Button icon={<ReloadOutlined />} onClick={() => refetch()} loading={isFetching}>Làm mới</Button>
                    </Space>
                )}
            />

            {noChannel && (
                <Alert
                    type="info" showIcon style={{ marginBottom: 16 }}
                    message="Chưa có gian hàng nào được kết nối"
                    description={<>Kết nối gian hàng để đơn tự đồng bộ về và dashboard có dữ liệu thực. <Link to="/channels">Kết nối ngay →</Link></>}
                />
            )}

            {/* Hàng 1 — KPI cards. Mỗi thẻ: số chính · delta vs kỳ trước · sparkline 7/30/90 ngày. */}
            <Row gutter={[16, 16]}>
                <Col xs={24} sm={12} xl={6}>
                    <KpiCard title="Doanh thu" value={data?.kpis.revenue.current ?? 0} pair={data?.kpis.revenue}
                        unit="₫" color={COLOR.revenue} icon={<DollarOutlined />}
                        spark={data?.series.map((p) => ({ x: p.date, y: p.revenue })) ?? []}
                        loading={isLoading}
                    />
                </Col>
                <Col xs={24} sm={12} xl={6}>
                    <KpiCard title="Số đơn" value={data?.kpis.orders.current ?? 0} pair={data?.kpis.orders}
                        color={COLOR.orders} icon={<ShoppingCartOutlined />}
                        spark={data?.series.map((p) => ({ x: p.date, y: p.orders })) ?? []}
                        loading={isLoading}
                    />
                </Col>
                <Col xs={24} sm={12} xl={6}>
                    <KpiCard title="Lợi nhuận ước tính" hint="Sau phí sàn ước tính (SPEC 0012)" value={data?.kpis.estimated_profit.current ?? 0} pair={data?.kpis.estimated_profit}
                        unit="₫" color={COLOR.estProfit} icon={<RiseOutlined />}
                        spark={data?.series.map((p) => ({ x: p.date, y: p.estimated_profit })) ?? []}
                        loading={isLoading}
                    />
                </Col>
                <Col xs={24} sm={12} xl={6}>
                    <KpiCard title="GMV trung bình/đơn" value={data?.kpis.avg_order_value.current ?? 0} pair={data?.kpis.avg_order_value}
                        unit="₫" color={COLOR.aov} icon={<FundOutlined />}
                        spark={data?.series.map((p) => ({ x: p.date, y: p.orders > 0 ? Math.round(p.revenue / p.orders) : 0 })) ?? []}
                        loading={isLoading}
                    />
                </Col>
            </Row>

            {/* Hàng 2 — biểu đồ chính (doanh thu cột + LN ước tính line) + việc cần làm */}
            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col xs={24} xl={16}>
                    <Card title={<Space><FundOutlined /> Doanh thu & lợi nhuận theo ngày</Space>}
                        extra={<Typography.Text type="secondary" style={{ fontSize: 12 }}>{data ? `${dayjs(data.period.from).format('DD/MM')} – ${dayjs(data.period.to).format('DD/MM/YYYY')}` : '—'}</Typography.Text>}
                        loading={isLoading} styles={{ body: { paddingTop: 8 } }}>
                        <RevenueProfitChart data={data?.series ?? []} />
                    </Card>
                </Col>
                <Col xs={24} xl={8}>
                    <Card title={<Space><ExclamationCircleOutlined /> Việc cần làm</Space>} loading={isLoading} styles={{ body: { padding: 0 } }}
                        extra={<Typography.Text type="secondary" style={{ fontSize: 12 }}>cần xử lý ngay</Typography.Text>}>
                        <List dataSource={todoBuckets} renderItem={(t) => (
                            <List.Item style={{ padding: '12px 16px' }}>
                                <List.Item.Meta
                                    avatar={<div style={{ width: 36, height: 36, borderRadius: 8, display: 'grid', placeItems: 'center', background: `${t.color}15`, color: t.color, fontSize: 16 }}>{t.icon}</div>}
                                    title={<Link to={t.to}>{t.label}</Link>}
                                    description={<Typography.Text type="secondary" style={{ fontSize: 12 }}>{t.hint}</Typography.Text>}
                                />
                                <Badge count={t.count} overflowCount={9999} showZero
                                    style={{ background: t.count > 0 ? t.color : '#f0f0f0', color: t.count > 0 ? '#fff' : '#8c8c8c', boxShadow: 'none', fontWeight: 600 }} />
                            </List.Item>
                        )} />
                    </Card>
                </Col>
            </Row>

            {/* Hàng 3 — top SKU + breakdown theo sàn */}
            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col xs={24} xl={14}>
                    <Card title={<Space><TrophyOutlined /> Top sản phẩm bán chạy</Space>}
                        extra={<Link to="/reports?tab=top">Xem tất cả →</Link>}
                        loading={isLoading}>
                        <TopSkusList items={data?.top_skus ?? []} />
                    </Card>
                </Col>
                <Col xs={24} xl={10}>
                    <Card title={<Space><ShopOutlined /> Doanh thu theo sàn</Space>} loading={isLoading}>
                        <BySourceChart data={data?.by_source ?? []} />
                    </Card>
                </Col>
            </Row>

            {/* Hàng 4 — thống kê nhanh kế toán (chỉ render nếu API trả về OK; ẩn khi 402/403). */}
            {!acct.isError && (acct.isLoading || acct.data) && (
                <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                    <Col xs={24}>
                        <AccountingQuickStats data={acct.data} loading={acct.isLoading} />
                    </Col>
                </Row>
            )}

            {/* Hàng 5 — tình trạng hệ thống */}
            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col xs={24}>
                    <Card title={<Space><ThunderboltOutlined /> Trạng thái hệ thống</Space>} loading={isLoading}>
                        <Row gutter={[16, 12]}>
                            <Col xs={24} sm={8}>
                                <SystemStatItem label="Gian hàng đã kết nối" value={`${data?.channel_accounts.active ?? 0} / ${data?.channel_accounts.total ?? 0}`}
                                    hint={data?.channel_accounts.needs_reconnect ? `${data.channel_accounts.needs_reconnect} cần kết nối lại` : 'Tất cả token còn hiệu lực'}
                                    color={(data?.channel_accounts.needs_reconnect ?? 0) > 0 ? COLOR.warning : COLOR.orders} icon={<ShopOutlined />}
                                    to="/channels" />
                            </Col>
                            <Col xs={24} sm={8}>
                                <SystemStatItem label="Tổng đơn lưu trữ" value={(data?.orders.total ?? 0).toLocaleString('vi-VN')}
                                    hint={`Hôm nay: ${data?.orders.today ?? 0} đơn`} color={COLOR.revenue} icon={<ShoppingCartOutlined />}
                                    to="/orders" />
                            </Col>
                            <Col xs={24} sm={8}>
                                <SystemStatItem label="Lợi nhuận thực (FIFO)" value={(data?.kpis.gross_profit.current ?? 0).toLocaleString('vi-VN') + ' ₫'}
                                    hint={`Biên ${data?.kpis.margin_pct.current ?? 0}% · chỉ đơn đã ship`}
                                    color={(data?.kpis.gross_profit.current ?? 0) >= 0 ? COLOR.grossProfit : COLOR.danger} icon={<RiseOutlined />}
                                    to="/reports?tab=profit" />
                            </Col>
                        </Row>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

// -- Range hook (URL param so reload/share-link giữ nguyên lựa chọn) ----------------------

function parseRange(v: string | null): DashboardRange {
    const n = Number(v);
    return n === 30 ? 30 : n === 90 ? 90 : 7;
}

function useRange(): [DashboardRange, (r: DashboardRange) => void] {
    const [params, setParams] = useSearchParams();
    const [range, setRangeState] = useState<DashboardRange>(parseRange(params.get('range')));
    const set = (v: DashboardRange) => {
        const next = new URLSearchParams(params);
        next.set('range', String(v));
        setParams(next, { replace: true });
        setRangeState(v);
    };
    return [range, set];
}

// -- KPI card ----------------------------------------------------------------

function KpiCard({ title, value, pair, unit, color, icon, spark, loading, hint }: {
    title: string;
    value: number;
    pair?: KpiPair;
    unit?: string;
    color: string;
    icon: React.ReactNode;
    spark: Array<{ x: string; y: number }>;
    loading?: boolean;
    hint?: string;
}) {
    const delta = pair ? deltaPct(pair) : null;
    const up = delta != null && delta >= 0;
    const formatVal = (n: number) => n.toLocaleString('vi-VN');

    return (
        <Card styles={{ body: { padding: 16 } }} hoverable>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 12 }}>
                <div style={{ minWidth: 0, flex: 1 }}>
                    <Space size={6} style={{ color: COLOR.muted, fontSize: 13 }}>
                        <span style={{ width: 22, height: 22, borderRadius: 6, display: 'inline-grid', placeItems: 'center', background: `${color}15`, color }}>{icon}</span>
                        <span>{title}</span>
                        {hint && <Tooltip title={hint}><Typography.Text type="secondary" style={{ fontSize: 11, cursor: 'help' }}>ⓘ</Typography.Text></Tooltip>}
                    </Space>
                    <div style={{ marginTop: 6, fontSize: 22, fontWeight: 700, lineHeight: 1.2 }}>
                        {loading ? <Skeleton.Button active size="small" /> : <>{formatVal(value)}{unit && <span style={{ fontSize: 14, fontWeight: 500, marginLeft: 4, color: COLOR.muted }}>{unit}</span>}</>}
                    </div>
                    {!loading && delta != null && (
                        <Space size={4} style={{ marginTop: 4, fontSize: 12 }}>
                            <span style={{ color: up ? '#389e0d' : '#cf1322', fontWeight: 600 }}>
                                {up ? <ArrowUpOutlined /> : <ArrowDownOutlined />} {Math.abs(delta)}%
                            </span>
                            <Typography.Text type="secondary" style={{ fontSize: 12 }}>so kỳ trước</Typography.Text>
                        </Space>
                    )}
                    {!loading && delta == null && pair && pair.previous === 0 && pair.current > 0 && (
                        <Typography.Text type="secondary" style={{ fontSize: 12, marginTop: 4, display: 'block' }}>Kỳ trước chưa có dữ liệu</Typography.Text>
                    )}
                </div>
                <div style={{ width: 96, height: 48 }}>
                    {loading ? null : spark.length === 0 ? null : (
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={spark} margin={{ top: 4, right: 0, bottom: 0, left: 0 }}>
                                <defs>
                                    <linearGradient id={`grad-${title}`} x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stopColor={color} stopOpacity={0.4} />
                                        <stop offset="100%" stopColor={color} stopOpacity={0} />
                                    </linearGradient>
                                </defs>
                                <Area type="monotone" dataKey="y" stroke={color} strokeWidth={2} fill={`url(#grad-${title})`} dot={false} isAnimationActive={false} />
                            </AreaChart>
                        </ResponsiveContainer>
                    )}
                </div>
            </div>
        </Card>
    );
}

// -- Combined revenue (bar) + estimated profit (line) chart ------------------

const fmtAxisVnd = (n: number) => {
    const v = Math.abs(n);
    if (v >= 1_000_000_000) return `${(n / 1_000_000_000).toFixed(1)}B`;
    if (v >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`;
    if (v >= 1_000) return `${Math.round(n / 1_000)}K`;
    return String(n);
};
const fmtFullVnd = (n: number) => `${n.toLocaleString('vi-VN')} ₫`;

function RevenueProfitChart({ data }: { data: DashboardSummary['series'] }) {
    if (data.length === 0) {
        return <div style={{ padding: 32 }}><Empty description="Chưa có dữ liệu trong khoảng này" /></div>;
    }
    return (
        <ResponsiveContainer width="100%" height={320}>
            <BarChart data={data} margin={{ top: 12, right: 16, bottom: 0, left: 0 }} barCategoryGap="20%">
                <defs>
                    <linearGradient id="bar-rev" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor={COLOR.revenue} stopOpacity={0.95} />
                        <stop offset="100%" stopColor={COLOR.revenue} stopOpacity={0.55} />
                    </linearGradient>
                </defs>
                <CartesianGrid stroke="#f0f0f0" vertical={false} />
                <XAxis dataKey="date" tickFormatter={(v) => dayjs(v).format('DD/MM')} tick={{ fontSize: 11, fill: COLOR.muted }} axisLine={false} tickLine={false} />
                <YAxis yAxisId="left" tickFormatter={fmtAxisVnd} tick={{ fontSize: 11, fill: COLOR.muted }} axisLine={false} tickLine={false} width={50} />
                <YAxis yAxisId="right" orientation="right" tickFormatter={(v) => `${v}`} tick={{ fontSize: 11, fill: COLOR.muted }} axisLine={false} tickLine={false} width={30} />
                <ReTooltip content={<RevTooltip />} cursor={{ fill: 'rgba(22,104,220,0.06)' }} />
                <Bar yAxisId="left" dataKey="revenue" name="Doanh thu" fill="url(#bar-rev)" radius={[4, 4, 0, 0]} />
                {/* Tận dụng <Line> trong <BarChart> qua composition của recharts: render dưới dạng customized series. */}
                <Bar yAxisId="left" dataKey="estimated_profit" name="LN ước tính" fill={COLOR.estProfit} fillOpacity={0.65} radius={[4, 4, 0, 0]} />
            </BarChart>
        </ResponsiveContainer>
    );
}

interface RevTooltipProps {
    active?: boolean;
    label?: string;
    payload?: Array<{ payload: DashboardSummary['series'][number] }>;
}
function RevTooltip({ active, label, payload }: RevTooltipProps) {
    if (!active || !payload || payload.length === 0) return null;
    const p = payload[0].payload;
    return (
        <div style={{ background: '#fff', border: '1px solid #f0f0f0', borderRadius: 8, padding: '8px 12px', boxShadow: '0 4px 12px rgba(0,0,0,0.08)', minWidth: 180 }}>
            <div style={{ fontWeight: 600, marginBottom: 6 }}>{dayjs(label).format('dddd, DD/MM/YYYY')}</div>
            <Row><Col span={14} style={{ color: COLOR.muted }}><span style={{ display: 'inline-block', width: 10, height: 10, background: COLOR.revenue, borderRadius: 2, marginRight: 6 }} />Doanh thu</Col><Col span={10} style={{ textAlign: 'right', fontWeight: 600 }}>{fmtFullVnd(p.revenue)}</Col></Row>
            <Row><Col span={14} style={{ color: COLOR.muted }}><span style={{ display: 'inline-block', width: 10, height: 10, background: COLOR.estProfit, borderRadius: 2, marginRight: 6 }} />LN ước tính</Col><Col span={10} style={{ textAlign: 'right', fontWeight: 600, color: p.estimated_profit >= 0 ? '#389e0d' : '#cf1322' }}>{fmtFullVnd(p.estimated_profit)}</Col></Row>
            <Row><Col span={14} style={{ color: COLOR.muted }}><span style={{ display: 'inline-block', width: 10, height: 10, background: COLOR.grossProfit, borderRadius: 2, marginRight: 6 }} />LN thực (FIFO)</Col><Col span={10} style={{ textAlign: 'right', fontWeight: 600 }}>{fmtFullVnd(p.gross_profit)}</Col></Row>
            <Row><Col span={14} style={{ color: COLOR.muted }}><span style={{ display: 'inline-block', width: 10, height: 10, background: COLOR.orders, borderRadius: 2, marginRight: 6 }} />Số đơn</Col><Col span={10} style={{ textAlign: 'right', fontWeight: 600 }}>{p.orders}</Col></Row>
        </div>
    );
}

// -- Top SKUs list -----------------------------------------------------------

function TopSkusList({ items }: { items: DashboardSummary['top_skus'] }) {
    if (items.length === 0) return <Empty description="Chưa có đơn nào trong khoảng này" />;
    const max = Math.max(...items.map((i) => i.revenue), 1);
    return (
        <Space direction="vertical" size={12} style={{ width: '100%' }}>
            {items.map((s, i) => {
                const pct = Math.round((s.revenue / max) * 100);
                return (
                    <div key={s.sku_id} style={{ display: 'grid', gridTemplateColumns: '28px 44px 1fr auto', columnGap: 10, alignItems: 'center' }}>
                        <Typography.Text strong style={{ color: i < 3 ? COLOR.revenue : COLOR.muted, fontSize: 14 }}>#{i + 1}</Typography.Text>
                        <Avatar shape="square" size={40} src={s.image_url ?? undefined} icon={<PictureOutlined />} style={{ background: '#f5f5f5', color: '#bfbfbf' }} />
                        <div style={{ minWidth: 0 }}>
                            <Typography.Text strong ellipsis={{ tooltip: s.name }} style={{ display: 'block' }}>{s.name || s.sku_code}</Typography.Text>
                            <Typography.Text type="secondary" style={{ fontSize: 12 }}>{s.sku_code} · {s.qty} sp đã bán</Typography.Text>
                            <Progress percent={pct} showInfo={false} strokeColor={COLOR.revenue} size="small" style={{ marginTop: 4, marginBottom: 0 }} />
                        </div>
                        <div style={{ textAlign: 'right' }}>
                            <Typography.Text strong>{s.revenue.toLocaleString('vi-VN')} ₫</Typography.Text>
                        </div>
                    </div>
                );
            })}
        </Space>
    );
}

// -- By source bar (horizontal-ish via vertical BarChart) --------------------

function BySourceChart({ data }: { data: DashboardSummary['by_source'] }) {
    if (data.length === 0) return <Empty description="Chưa có dữ liệu" />;
    const total = data.reduce((s, x) => s + x.revenue, 0);
    const enriched = data.map((d) => ({ ...d, name: CHANNEL_META[d.source]?.name ?? d.source, color: CHANNEL_META[d.source]?.color ?? COLOR.muted, share: total > 0 ? Math.round((d.revenue / total) * 100) : 0 }));
    return (
        <>
            <ResponsiveContainer width="100%" height={Math.max(120, enriched.length * 44 + 16)}>
                <BarChart data={enriched} layout="vertical" margin={{ top: 4, right: 16, bottom: 4, left: 4 }}>
                    <CartesianGrid stroke="#f0f0f0" horizontal={false} />
                    <XAxis type="number" tickFormatter={fmtAxisVnd} tick={{ fontSize: 11, fill: COLOR.muted }} axisLine={false} tickLine={false} />
                    <YAxis type="category" dataKey="name" tick={{ fontSize: 12 }} axisLine={false} tickLine={false} width={100} />
                    <ReTooltip
                        cursor={{ fill: 'rgba(22,104,220,0.06)' }}
                        contentStyle={{ borderRadius: 8, fontSize: 12 }}
                        formatter={(v) => [fmtFullVnd(Number(v)), 'Doanh thu']}
                    />
                    <Bar dataKey="revenue" radius={[0, 4, 4, 0]}>
                        {enriched.map((d, i) => <Cell key={i} fill={d.color} />)}
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
            <Space direction="vertical" size={6} style={{ width: '100%', marginTop: 8 }}>
                {enriched.map((d) => (
                    <Row key={d.source} gutter={8} align="middle">
                        <Col flex="auto"><Tag color={d.color === '#000000' ? 'default' : undefined} style={d.color !== '#000000' ? { background: `${d.color}15`, borderColor: `${d.color}30`, color: d.color } : undefined}>{d.name}</Tag><Typography.Text type="secondary" style={{ fontSize: 12, marginLeft: 6 }}>{d.orders} đơn</Typography.Text></Col>
                        <Col><Typography.Text strong>{d.share}%</Typography.Text></Col>
                    </Row>
                ))}
            </Space>
        </>
    );
}

// -- System status item ------------------------------------------------------

function SystemStatItem({ label, value, hint, color, icon, to }: { label: string; value: React.ReactNode; hint: string; color: string; icon: React.ReactNode; to: string }) {
    return (
        <Link to={to} style={{ color: 'inherit' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '4px 0' }}>
                <div style={{ width: 44, height: 44, borderRadius: 10, display: 'grid', placeItems: 'center', background: `${color}15`, color, fontSize: 18 }}>{icon}</div>
                <div>
                    <Typography.Text type="secondary" style={{ fontSize: 13 }}>{label}</Typography.Text>
                    <div style={{ fontSize: 18, fontWeight: 700, lineHeight: 1.2 }}>{value}</div>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }}>{hint}</Typography.Text>
                </div>
            </div>
        </Link>
    );
}

// -- Accounting quick stats --------------------------------------------------

function AccountingQuickStats({ data, loading }: { data?: AccountingDashboardSummary; loading: boolean }) {
    const title = <Space><AuditOutlined /> Thống kê nhanh kế toán</Space>;

    // Chưa khởi tạo module → CTA dẫn user tới /accounting (banner setup ở trang đó sẽ lo).
    if (data && !data.initialized) {
        return (
            <Card title={title}>
                <Empty
                    description={<>Module kế toán chưa được khởi tạo cho gian hàng này.</>}
                >
                    <Link to="/accounting"><Button type="primary" icon={<ThunderboltOutlined />}>Khởi tạo kế toán</Button></Link>
                </Empty>
            </Card>
        );
    }

    const fmtVnd = (n: number) => n.toLocaleString('vi-VN') + ' ₫';
    const period = data?.current_period;
    const periodLabel = period ? `Kỳ ${dayjs(period.code).format('MM/YYYY')} · ${period.status_label}` : 'Chưa có kỳ tháng hiện tại';
    const periodColor = period?.status === 'open' ? COLOR.orders : period?.status === 'locked' ? COLOR.danger : COLOR.warning;

    const arOverdue = data?.ar.overdue ?? 0;
    const apOverdue = data?.ap.overdue ?? 0;
    const revenue = data?.pl_period?.revenue ?? 0;
    const gross = data?.pl_period?.gross_profit ?? 0;
    const net = data?.pl_period?.net_income ?? 0;

    return (
        <Card
            title={title}
            extra={<Space size={8}>
                <Tag color={periodColor === COLOR.orders ? 'green' : periodColor === COLOR.danger ? 'red' : 'orange'} style={{ marginInlineEnd: 0 }}>{periodLabel}</Tag>
                <Link to="/accounting/reports" style={{ fontSize: 13 }}>Xem báo cáo →</Link>
            </Space>}
            loading={loading}
        >
            <Row gutter={[16, 16]}>
                <Col xs={24} sm={12} xl={8}>
                    <AcctStatCard label="Tiền & ngân hàng" value={fmtVnd(data?.cash.total ?? 0)}
                        hint={`${data?.cash.accounts ?? 0} quỹ/tài khoản đang hoạt động`}
                        color={COLOR.revenue} icon={<WalletOutlined />} to="/accounting/cash" />
                </Col>
                <Col xs={24} sm={12} xl={8}>
                    <AcctStatCard label="Phải thu khách hàng" value={fmtVnd(data?.ar.total ?? 0)}
                        hint={arOverdue > 0 ? `Quá hạn >60 ngày: ${fmtVnd(arOverdue)}` : 'Không có khoản quá hạn'}
                        color={arOverdue > 0 ? COLOR.warning : COLOR.orders} icon={<CreditCardOutlined />} to="/accounting/ar" />
                </Col>
                <Col xs={24} sm={12} xl={8}>
                    <AcctStatCard label="Phải trả NCC" value={fmtVnd(data?.ap.total ?? 0)}
                        hint={apOverdue > 0 ? `Quá hạn >60 ngày: ${fmtVnd(apOverdue)}` : 'Không có khoản quá hạn'}
                        color={apOverdue > 0 ? COLOR.danger : COLOR.orders} icon={<BankOutlined />} to="/accounting/ap" />
                </Col>
                <Col xs={24} sm={12} xl={8}>
                    <AcctStatCard label="Doanh thu thuần kỳ" value={fmtVnd(revenue)}
                        hint={period ? `Tháng ${dayjs(period.code).format('MM/YYYY')}` : '—'}
                        color={COLOR.revenue} icon={<DollarOutlined />} to="/accounting/reports?tab=pnl" />
                </Col>
                <Col xs={24} sm={12} xl={8}>
                    <AcctStatCard label="Lợi nhuận gộp kỳ" value={fmtVnd(gross)}
                        hint={revenue > 0 ? `Biên gộp ${Math.round((gross / revenue) * 100)}%` : 'Chưa có doanh thu'}
                        color={gross >= 0 ? COLOR.grossProfit : COLOR.danger}
                        icon={gross >= 0 ? <RiseOutlined /> : <FallOutlined />} to="/accounting/reports?tab=pnl" />
                </Col>
                <Col xs={24} sm={12} xl={8}>
                    <AcctStatCard label="Lãi/lỗ kỳ (sau thuế)" value={fmtVnd(net)}
                        hint={`Chi phí QLKD: ${fmtVnd(data?.pl_period?.opex ?? 0)}`}
                        color={net >= 0 ? COLOR.estProfit : COLOR.danger}
                        icon={net >= 0 ? <RiseOutlined /> : <FallOutlined />} to="/accounting/reports?tab=pnl" />
                </Col>
            </Row>
        </Card>
    );
}

function AcctStatCard({ label, value, hint, color, icon, to }: { label: string; value: React.ReactNode; hint: string; color: string; icon: React.ReactNode; to: string }) {
    return (
        <Link to={to} style={{ color: 'inherit', display: 'block' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '8px 12px', border: '1px solid #f0f0f0', borderRadius: 8, transition: 'background 0.2s' }}
                onMouseEnter={(e) => { (e.currentTarget as HTMLDivElement).style.background = '#fafafa'; }}
                onMouseLeave={(e) => { (e.currentTarget as HTMLDivElement).style.background = ''; }}>
                <div style={{ width: 44, height: 44, borderRadius: 10, display: 'grid', placeItems: 'center', background: `${color}15`, color, fontSize: 18, flex: '0 0 auto' }}>{icon}</div>
                <div style={{ minWidth: 0, flex: 1 }}>
                    <Typography.Text type="secondary" style={{ fontSize: 13 }}>{label}</Typography.Text>
                    <div style={{ fontSize: 18, fontWeight: 700, lineHeight: 1.25 }}>{value}</div>
                    <Typography.Text type="secondary" style={{ fontSize: 12 }} ellipsis>{hint}</Typography.Text>
                </div>
            </div>
        </Link>
    );
}

// -- Todo bucket builder -----------------------------------------------------

interface TodoBucket { label: string; hint: string; count: number; color: string; icon: React.ReactNode; to: string }

function makeTodoBuckets(data?: DashboardSummary): TodoBucket[] {
    const o = data?.orders;
    return [
        { label: 'Đơn chờ xử lý', hint: 'Cần "Chuẩn bị hàng" để sàn cấp vận đơn', count: o?.to_process ?? 0, color: COLOR.warning, icon: <ShoppingCartOutlined />, to: '/orders?tab=pending' },
        { label: 'Đơn chờ bàn giao ĐVVC', hint: 'Đã đóng gói, chờ giao cho đơn vị vận chuyển', count: o?.ready_to_ship ?? 0, color: COLOR.revenue, icon: <CarOutlined />, to: '/orders?status=ready_to_ship' },
        { label: 'Đơn cần in phiếu', hint: 'Có vận đơn open chưa in phiếu giao hàng', count: o?.shipped ?? 0, color: COLOR.grossProfit, icon: <PrinterOutlined />, to: '/orders?tab=processing&printed=0' },
        { label: 'Đơn chưa liên kết SKU', hint: 'Cần ghép SKU sàn với SKU hàng hoá để trừ tồn', count: o?.unmapped ?? 0, color: COLOR.danger, icon: <LinkOutlined />, to: '/orders?has_issue=1' },
        { label: 'Đơn có vấn đề', hint: 'Đơn lỗi cần kiểm tra lại', count: (o?.has_issue ?? 0) - (o?.unmapped ?? 0), color: COLOR.danger, icon: <WarningOutlined />, to: '/orders?tab=issue' },
        { label: 'Gian hàng cần kết nối lại', hint: 'Token đã hết hạn — đơn không tự về', count: data?.channel_accounts.needs_reconnect ?? 0, color: COLOR.warning, icon: <ShopOutlined />, to: '/channels' },
    ].filter((t) => t.count >= 0);
}
