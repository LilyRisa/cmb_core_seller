import { useMemo, useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { Avatar, Badge, Button, Dropdown, Layout, Menu, Select, Space, Tooltip, Typography } from 'antd';
import {
    AppstoreOutlined,
    BarChartOutlined,
    BellOutlined,
    DashboardOutlined,
    FundOutlined,
    InboxOutlined,
    LogoutOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
    SettingOutlined,
    ShopOutlined,
    ShoppingCartOutlined,
    ShoppingOutlined,
    SwapOutlined,
    TeamOutlined,
    UserOutlined,
} from '@ant-design/icons';
import type { MenuProps } from 'antd';
import { getCurrentTenantId, setCurrentTenantId, useAuth, useLogout } from '@/lib/auth';

const { Header, Sider, Content } = Layout;

const NAV: MenuProps['items'] = [
    { type: 'group', label: 'Tổng quan', children: [
        { key: '/', icon: <DashboardOutlined />, label: <Link to="/">Bảng điều khiển</Link> },
    ] },
    { type: 'group', label: 'Bán hàng', children: [
        { key: '/orders', icon: <ShoppingOutlined />, label: <Link to="/orders">Đơn hàng</Link> },
        { key: '/customers', icon: <TeamOutlined />, label: <Link to="/customers">Khách hàng</Link> },
        { key: '/channels', icon: <ShopOutlined />, label: <Link to="/channels">Gian hàng</Link> },
        { key: '/products', icon: <AppstoreOutlined />, label: <Link to="/products">Sản phẩm & SKU</Link> },
    ] },
    { type: 'group', label: 'Kho & Mua hàng', children: [
        { key: '/inventory', icon: <InboxOutlined />, label: <Link to="/inventory">Tồn kho</Link> },
        { key: '/procurement/suppliers', icon: <ShopOutlined />, label: <Link to="/procurement/suppliers">Nhà cung cấp</Link> },
        { key: '/procurement/purchase-orders', icon: <ShoppingCartOutlined />, label: <Link to="/procurement/purchase-orders">Đơn mua hàng</Link> },
    ] },
    { type: 'group', label: 'Báo cáo & Kế toán', children: [
        { key: '/reports', icon: <BarChartOutlined />, label: <Link to="/reports">Báo cáo</Link> },
        { key: '/finance/settlements', icon: <FundOutlined />, label: <Link to="/finance/settlements">Đối soát sàn</Link> },
    ] },
    { type: 'group', label: 'Hệ thống', children: [
        { key: '/sync-logs', icon: <SwapOutlined />, label: <Link to="/sync-logs">Nhật ký đồng bộ</Link> },
        { key: '/settings', icon: <SettingOutlined />, label: <Link to="/settings">Cài đặt</Link> },
    ] },
];

// Flat key list for selected-key matching.
const KEYS = ['/', '/orders', '/customers', '/channels', '/products', '/inventory', '/procurement/suppliers', '/procurement/purchase-orders', '/reports', '/finance/settlements', '/sync-logs', '/settings'];

export function AppLayout() {
    const { data: user } = useAuth();
    const logout = useLogout();
    const navigate = useNavigate();
    const location = useLocation();
    const [collapsed, setCollapsed] = useState(false);

    const currentTenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
    const currentTenant = user?.tenants.find((t) => t.id === currentTenantId) ?? user?.tenants[0];

    const selectedKey = useMemo(() => {
        const match = KEYS.filter((k) => (k === '/' ? location.pathname === '/' : location.pathname.startsWith(k)))
            .sort((a, b) => b.length - a.length)[0];
        return match ?? '/';
    }, [location.pathname]);

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider theme="light" width={236} collapsedWidth={64} collapsible collapsed={collapsed} trigger={null} style={{ borderRight: '1px solid #f0f0f0' }}>
                <div style={{ height: 56, display: 'flex', alignItems: 'center', gap: 10, padding: '0 18px', fontWeight: 700, fontSize: 16, color: '#1668dc', whiteSpace: 'nowrap', overflow: 'hidden' }}>
                    <span style={{ fontSize: 20 }}>🛒</span> {!collapsed && 'CMBcoreSeller'}
                </div>
                <Menu mode="inline" selectedKeys={[selectedKey]} items={NAV} style={{ borderInlineEnd: 'none' }} />
            </Sider>
            <Layout>
                <Header style={{ background: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0 16px 0 8px', borderBottom: '1px solid #f0f0f0', height: 56, lineHeight: 'normal' }}>
                    <Space>
                        <Button type="text" icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />} onClick={() => setCollapsed((c) => !c)} />
                        <ShopOutlined style={{ color: '#8c8c8c' }} />
                        <Select
                            size="middle" variant="borderless" style={{ minWidth: 200, fontWeight: 500 }}
                            value={currentTenantId ?? undefined}
                            options={(user?.tenants ?? []).map((t) => ({ value: t.id, label: `${t.name} · ${t.role}` }))}
                            onChange={(v) => { setCurrentTenantId(v); navigate(0); }}
                        />
                    </Space>
                    <Space size="middle">
                        <Tooltip title="Thông báo (sắp có)"><Badge dot><Button type="text" icon={<BellOutlined />} /></Badge></Tooltip>
                        <Dropdown
                            menu={{ items: [
                                { key: 'who', disabled: true, label: <span>{user?.name}<br /><Typography.Text type="secondary" style={{ fontSize: 12 }}>{user?.email}</Typography.Text></span> },
                                { type: 'divider' },
                                { key: 'settings', icon: <SettingOutlined />, label: <Link to="/settings/members">Cài đặt</Link> },
                                { key: 'logout', icon: <LogoutOutlined />, label: 'Đăng xuất', onClick: () => logout.mutate(undefined, { onSuccess: () => navigate('/login') }) },
                            ] }}
                        >
                            <Space style={{ cursor: 'pointer' }}>
                                <Avatar size="small" style={{ background: '#1668dc' }} icon={<UserOutlined />} />
                                <span style={{ fontWeight: 500 }}>{user?.name}</span>
                            </Space>
                        </Dropdown>
                    </Space>
                </Header>
                <Content style={{ margin: 16, minHeight: 0 }}>
                    <Outlet context={{ tenantName: currentTenant?.name }} />
                </Content>
            </Layout>
        </Layout>
    );
}
