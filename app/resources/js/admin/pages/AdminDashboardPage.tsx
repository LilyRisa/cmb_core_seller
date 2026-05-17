// Spec 2026-05-17 — landing page sau khi login. Phase 5 đặt placeholder card;
// stats dashboards có thể bổ sung sau (tenant count, plan distribution…).

import { Card, Typography, Space } from 'antd';
import { SafetyCertificateOutlined } from '@ant-design/icons';
import { useAdminMe } from '../lib/adminAuth';

export function AdminDashboardPage() {
    const { data: me } = useAdminMe();

    return (
        <Space direction="vertical" size={16} style={{ width: '100%' }}>
            <Card>
                <Space direction="vertical" size={4}>
                    <Space>
                        <SafetyCertificateOutlined style={{ fontSize: 20 }} />
                        <Typography.Title level={4} style={{ margin: 0 }}>
                            Xin chào, {me?.name}
                        </Typography.Title>
                    </Space>
                    <Typography.Paragraph type="secondary" style={{ margin: 0 }}>
                        Dùng menu bên trái để quản lý tenants, người dùng, voucher, gói thuê bao,
                        và cấu hình hệ thống. Mọi thao tác ghi audit log.
                    </Typography.Paragraph>
                </Space>
            </Card>
        </Space>
    );
}
