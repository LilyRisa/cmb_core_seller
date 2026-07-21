// Spec 2026-07-21 (redesign) — trang "Tổng quan" thật, thay placeholder chào mừng cũ.
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §6.

import { Alert, Card, Col, Row, Statistic, Table, Tag, Typography } from 'antd';
import {
    ShopOutlined, DollarOutlined, SolutionOutlined, ApiOutlined,
} from '@ant-design/icons';
import {
    Bar, BarChart, CartesianGrid, Line, LineChart, ResponsiveContainer, Tooltip as ReTooltip, XAxis, YAxis,
} from 'recharts';
import { Link } from 'react-router-dom';
import { useAdminOverview } from '../lib/adminDashboard';
import { useAdminMe } from '../lib/adminAuth';

const fmtVnd = (n: number) => `${n.toLocaleString('vi-VN')} đ`;

export function AdminDashboardPage() {
    const { data: me } = useAdminMe();
    const { data, isLoading, isError } = useAdminOverview();

    return (
        <div>
            <Typography.Title level={4} style={{ marginTop: 0 }}>Xin chào, {me?.name}</Typography.Title>

            {isError && (
                <Alert type="error" showIcon style={{ marginBottom: 16 }} message="Không tải được số liệu tổng quan." />
            )}

            <Row gutter={16} style={{ marginBottom: 16 }}>
                <Col span={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Tenant hoạt động" value={data?.tenants.active_total ?? 0} prefix={<ShopOutlined />} />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card loading={isLoading}>
                        <Statistic title="MRR ước tính" value={data?.revenue.mrr_estimate ?? 0} formatter={(v) => fmtVnd(Number(v))} prefix={<DollarOutlined />} />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Yêu cầu CSKH đang mở" value={data?.support.open_count ?? 0} prefix={<SolutionOutlined />} />
                    </Card>
                </Col>
                <Col span={6}>
                    <Card loading={isLoading}>
                        <Statistic title="Lượt gọi AI tháng này" value={data?.ai_usage.calls_this_month ?? 0} prefix={<ApiOutlined />} />
                    </Card>
                </Col>
            </Row>

            <Row gutter={16} style={{ marginBottom: 16 }}>
                <Col span={12}>
                    <Card title="Tenant mới (30 ngày)" loading={isLoading}>
                        <div style={{ height: 220 }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={data?.tenants.new_by_day ?? []}>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                    <XAxis dataKey="date" tick={{ fontSize: 10 }} interval={4} />
                                    <YAxis allowDecimals={false} width={30} />
                                    <ReTooltip />
                                    <Bar dataKey="count" fill="#2563EB" radius={[4, 4, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </Card>
                </Col>
                <Col span={12}>
                    <Card title="Phân bố theo gói" loading={isLoading}>
                        <Table
                            size="small" rowKey="plan_code" pagination={false}
                            dataSource={data?.tenants.by_plan ?? []}
                            columns={[
                                { title: 'Gói', dataIndex: 'plan_name' },
                                { title: 'Số tenant', dataIndex: 'count', width: 100 },
                            ]}
                        />
                        {(data?.tenants.trial_ending_soon.length ?? 0) > 0 && (
                            <>
                                <Typography.Text strong style={{ display: 'block', marginTop: 16 }}>
                                    Sắp hết trial (7 ngày tới)
                                </Typography.Text>
                                <Table
                                    size="small" rowKey="tenant_id" pagination={false} style={{ marginTop: 8 }}
                                    dataSource={data?.tenants.trial_ending_soon ?? []}
                                    columns={[
                                        {
                                            title: 'Tenant', dataIndex: 'tenant_name',
                                            render: (v: string, r) => <Link to={`/admin/tenants/${r.tenant_id}`}>{v}</Link>,
                                        },
                                        {
                                            title: 'Hết hạn', dataIndex: 'trial_ends_at',
                                            render: (v: string) => new Date(v).toLocaleDateString('vi-VN'),
                                        },
                                    ]}
                                />
                            </>
                        )}
                    </Card>
                </Col>
            </Row>

            <Row gutter={16} style={{ marginBottom: 16 }}>
                <Col span={12}>
                    <Card title="Doanh thu 12 tháng (đã thu)" loading={isLoading}>
                        <div style={{ height: 220 }}>
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={data?.revenue.revenue_by_month ?? []}>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                    <XAxis dataKey="period_ym" tick={{ fontSize: 10 }} />
                                    <YAxis width={50} tickFormatter={(v: number) => `${(v / 1_000_000).toFixed(0)}tr`} />
                                    <ReTooltip formatter={(v: any) => (typeof v === 'number' ? fmtVnd(v) : '')} />
                                    <Line type="monotone" dataKey="total" stroke="#10B981" strokeWidth={2} dot={false} />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                        <Typography.Paragraph type="secondary" style={{ marginTop: 8, marginBottom: 0 }}>
                            Hoá đơn tháng này: {data?.revenue.invoices_this_month.paid_count ?? 0} đã thu
                            ({fmtVnd(data?.revenue.invoices_this_month.paid_total ?? 0)}) ·{' '}
                            {data?.revenue.invoices_this_month.pending_count ?? 0} chờ thu
                            ({fmtVnd(data?.revenue.invoices_this_month.pending_total ?? 0)}) ·{' '}
                            {data?.revenue.active_vouchers ?? 0} voucher đang hoạt động.
                        </Typography.Paragraph>
                    </Card>
                </Col>
                <Col span={12}>
                    <Card title="Hoạt động gần đây (audit log)" loading={isLoading}>
                        <Table
                            size="small" rowKey={(r) => `${r.action}-${r.at}`} pagination={false}
                            dataSource={data?.support.recent_audit_log ?? []}
                            columns={[
                                { title: 'Hành động', dataIndex: 'action', render: (v: string) => <Tag>{v}</Tag> },
                                { title: 'Người thực hiện', dataIndex: 'actor', width: 120 },
                                {
                                    title: 'Lúc', dataIndex: 'at', width: 140,
                                    render: (v: string) => new Date(v).toLocaleString('vi-VN'),
                                },
                            ]}
                        />
                        <Typography.Paragraph type="secondary" style={{ marginTop: 8, marginBottom: 0 }}>
                            Thời gian xử lý CSKH trung bình: {data?.support.avg_resolution_hours ?? 0} giờ.
                        </Typography.Paragraph>
                    </Card>
                </Col>
            </Row>

            <Card title="Top 5 tenant dùng AI nhiều nhất (tháng này)" loading={isLoading}>
                <Table
                    size="small" rowKey="tenant_id" pagination={false}
                    dataSource={data?.ai_usage.top_tenants ?? []}
                    columns={[
                        {
                            title: 'Tenant', dataIndex: 'tenant_name',
                            render: (v: string, r) => <Link to={`/admin/tenants/${r.tenant_id}`}>{v}</Link>,
                        },
                        { title: 'Lượt gọi', dataIndex: 'calls_this_month', width: 120 },
                    ]}
                />
            </Card>
        </div>
    );
}
