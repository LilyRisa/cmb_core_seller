import { Card, Col, Row, Statistic, Typography, Alert } from 'antd';
import { useAuth } from '@/lib/auth';

export function DashboardPage() {
    const { data: user } = useAuth();

    return (
        <div>
            <Typography.Title level={3}>Tổng quan</Typography.Title>
            <Alert
                type="info"
                showIcon
                style={{ marginBottom: 16 }}
                message="Phase 0 — khung ứng dụng"
                description="Đăng nhập, đa tenant, phân quyền và khung kết nối sàn/ĐVVC đã sẵn sàng. Các tính năng (đồng bộ đơn TikTok, ghép SKU, tồn kho, in vận đơn...) sẽ được thêm theo roadmap trong docs/."
            />
            <Row gutter={16}>
                <Col span={6}><Card><Statistic title="Gian hàng đã kết nối" value={0} /></Card></Col>
                <Col span={6}><Card><Statistic title="Đơn hôm nay" value={0} /></Card></Col>
                <Col span={6}><Card><Statistic title="Đơn chờ xử lý" value={0} /></Card></Col>
                <Col span={6}><Card><Statistic title="SKU sắp hết hàng" value={0} /></Card></Col>
            </Row>
            <Card style={{ marginTop: 16 }} title="Tài khoản">
                <p><b>{user?.name}</b> — {user?.email}</p>
                <p>Thuộc {user?.tenants.length ?? 0} workspace.</p>
            </Card>
        </div>
    );
}
