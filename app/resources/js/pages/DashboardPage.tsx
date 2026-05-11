import { Alert, Card, Col, List, Row, Skeleton, Statistic, Tag, Typography } from 'antd';
import {
    AppstoreOutlined,
    CarOutlined,
    DollarOutlined,
    ExclamationCircleOutlined,
    ShopOutlined,
    ShoppingOutlined,
} from '@ant-design/icons';
import { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/PageHeader';
import { MoneyText } from '@/components/MoneyText';
import { useDashboardSummary } from '@/lib/dashboard';
import { useAuth } from '@/lib/auth';

function StatCard({ title, value, icon, color, to, loading }: { title: string; value: ReactNode; icon: ReactNode; color: string; to?: string; loading?: boolean }) {
    const inner = (
        <Card hoverable={!!to} styles={{ body: { padding: 16 } }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
                <div style={{ width: 44, height: 44, borderRadius: 10, display: 'grid', placeItems: 'center', background: `${color}15`, color, fontSize: 20 }}>{icon}</div>
                <div>
                    <Typography.Text type="secondary" style={{ fontSize: 13 }}>{title}</Typography.Text>
                    <div style={{ fontSize: 22, fontWeight: 700, lineHeight: 1.2 }}>{loading ? <Skeleton.Button active size="small" /> : value}</div>
                </div>
            </div>
        </Card>
    );
    return to ? <Link to={to}>{inner}</Link> : inner;
}

export function DashboardPage() {
    const { data: user } = useAuth();
    const { data, isLoading } = useDashboardSummary();
    const o = data?.orders;

    const todo = [
        { label: 'Đơn chờ xử lý', count: o?.to_process ?? 0, to: '/orders?status=pending,unpaid,processing', color: '#faad14' },
        { label: 'Đơn chờ bàn giao ĐVVC', count: o?.ready_to_ship ?? 0, to: '/orders?status=ready_to_ship', color: '#2f54eb' },
        { label: 'Đơn đang vận chuyển', count: o?.shipped ?? 0, to: '/orders?status=shipped', color: '#13c2c2' },
        { label: 'Đơn có vấn đề (cần kiểm tra)', count: o?.has_issue ?? 0, to: '/orders?has_issue=1', color: '#ff4d4f' },
        { label: 'Gian hàng cần kết nối lại', count: data?.channel_accounts.needs_reconnect ?? 0, to: '/channels', color: '#fa8c16' },
    ];

    return (
        <div>
            <PageHeader title="Bảng điều khiển" subtitle={`Xin chào, ${user?.name ?? ''} 👋`} />

            {!isLoading && (data?.channel_accounts.total ?? 0) === 0 && (
                <Alert
                    type="info" showIcon style={{ marginBottom: 16 }}
                    message="Chưa có gian hàng nào được kết nối"
                    description={<>Kết nối gian hàng TikTok Shop để đơn hàng tự đồng bộ về. <Link to="/channels">Kết nối ngay →</Link></>}
                />
            )}

            <Row gutter={[16, 16]}>
                <Col xs={12} md={6}><StatCard title="Gian hàng hoạt động" value={data?.channel_accounts.active ?? 0} icon={<ShopOutlined />} color="#1668dc" to="/channels" loading={isLoading} /></Col>
                <Col xs={12} md={6}><StatCard title="Đơn hôm nay" value={o?.today ?? 0} icon={<ShoppingOutlined />} color="#52c41a" to="/orders" loading={isLoading} /></Col>
                <Col xs={12} md={6}><StatCard title="Tổng đơn" value={o?.total ?? 0} icon={<AppstoreOutlined />} color="#722ed1" to="/orders" loading={isLoading} /></Col>
                <Col xs={12} md={6}><StatCard title="Doanh thu hôm nay" value={<MoneyText value={data?.revenue_today ?? 0} />} icon={<DollarOutlined />} color="#fa8c16" loading={isLoading} /></Col>
            </Row>

            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col xs={24} md={14}>
                    <Card title={<><ExclamationCircleOutlined /> Việc cần làm</>} styles={{ body: { padding: 0 } }}>
                        <List
                            dataSource={todo}
                            renderItem={(item) => (
                                <List.Item style={{ padding: '12px 20px' }} actions={[<Link key="go" to={item.to}>Xem →</Link>]}>
                                    <List.Item.Meta
                                        avatar={<div style={{ width: 8, height: 8, borderRadius: 4, background: item.color, marginTop: 6 }} />}
                                        title={<Link to={item.to}>{item.label}</Link>}
                                    />
                                    <Tag color={item.count > 0 ? 'blue' : 'default'} style={{ fontWeight: 600, minWidth: 28, textAlign: 'center' }}>{item.count}</Tag>
                                </List.Item>
                            )}
                        />
                    </Card>
                </Col>
                <Col xs={24} md={10}>
                    <Card title={<><CarOutlined /> Trạng thái hệ thống</>}>
                        <Statistic title="Gian hàng đã kết nối" value={data?.channel_accounts.total ?? 0} suffix={`(${data?.channel_accounts.active ?? 0} hoạt động)`} loading={isLoading} />
                        <div style={{ marginTop: 16 }}>
                            <Typography.Text type="secondary">
                                Sàn đang hỗ trợ: <Tag>TikTok Shop (sandbox)</Tag>. Shopee & Lazada sẽ thêm ở Phase 4.
                                Đơn về qua webhook + polling (~10 phút) — xem <Link to="/sync-logs">nhật ký đồng bộ</Link>.
                            </Typography.Text>
                        </div>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
