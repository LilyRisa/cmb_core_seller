// Spec 2026-05-17 — sidebar + header admin. Tách hoàn toàn khỏi `AppLayout`
// của user. Theme navy/đỏ (xem `admin.tsx` ConfigProvider). Icons từ
// `@ant-design/icons` (memory `ui-use-font-icons-not-emoji`).

import { Layout, Menu, Typography, Space, Button } from 'antd';
import {
    DashboardOutlined,
    ShopOutlined,
    UserOutlined,
    SettingOutlined,
    AuditOutlined,
    LogoutOutlined,
    SafetyCertificateOutlined,
    GiftOutlined,
    ProfileOutlined,
    NotificationOutlined,
    ApiOutlined,
} from '@ant-design/icons';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAdminLogout, useAdminMe } from './lib/adminAuth';

const SIDEBAR_ITEMS = [
    { key: '/admin', icon: <DashboardOutlined />, label: 'Tổng quan' },
    { key: '/admin/tenants', icon: <ShopOutlined />, label: 'Tenants' },
    { key: '/admin/users', icon: <UserOutlined />, label: 'Người dùng' },
    { key: '/admin/vouchers', icon: <GiftOutlined />, label: 'Voucher' },
    { key: '/admin/plans', icon: <ProfileOutlined />, label: 'Gói thuê bao' },
    { key: '/admin/broadcasts', icon: <NotificationOutlined />, label: 'Broadcast' },
    { key: '/admin/settings', icon: <SettingOutlined />, label: 'Hệ thống' },
    { key: '/admin/ai-providers', icon: <ApiOutlined />, label: 'Nhà cung cấp AI' },
    { key: '/admin/audit-logs', icon: <AuditOutlined />, label: 'Nhật ký' },
];

export function AdminLayout() {
    const navigate = useNavigate();
    const loc = useLocation();
    const { data: me } = useAdminMe();
    const logout = useAdminLogout();

    // Chọn item match nhất theo prefix: vd /admin/tenants/123 → /admin/tenants.
    const selected = SIDEBAR_ITEMS
        .map((i) => i.key)
        .filter((k) => loc.pathname === k || loc.pathname.startsWith(k + '/'))
        .sort((a, b) => b.length - a.length)
        .slice(0, 1);

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Layout.Sider width={240} style={{ background: '#0F172A' }}>
                <div style={{ color: '#fff', padding: '20px 24px', borderBottom: '1px solid #1E293B' }}>
                    <Space>
                        <SafetyCertificateOutlined />
                        <Typography.Text strong style={{ color: '#fff' }}>CMBcore Admin</Typography.Text>
                    </Space>
                </div>
                <Menu
                    theme="dark"
                    mode="inline"
                    selectedKeys={selected}
                    style={{ background: '#0F172A', borderRight: 0 }}
                    items={SIDEBAR_ITEMS}
                    onClick={(e) => navigate(e.key)}
                />
            </Layout.Sider>
            <Layout>
                <Layout.Header style={{
                    background: '#fff',
                    display: 'flex',
                    justifyContent: 'flex-end',
                    alignItems: 'center',
                    padding: '0 24px',
                    borderBottom: '1px solid #E5E7EB',
                }}>
                    <Space>
                        <Typography.Text type="secondary">
                            {me?.name} <Typography.Text code>{me?.username}</Typography.Text>
                        </Typography.Text>
                        <Button
                            icon={<LogoutOutlined />}
                            onClick={() => logout.mutate(undefined, {
                                onSuccess: () => navigate('/admin/login', { replace: true }),
                            })}
                        >
                            Đăng xuất
                        </Button>
                    </Space>
                </Layout.Header>
                <Layout.Content style={{ padding: 24, background: '#F1F5F9' }}>
                    <Outlet />
                </Layout.Content>
            </Layout>
        </Layout>
    );
}
