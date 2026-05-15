import { useMemo, useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { Avatar, Badge, Button, Dropdown, Layout, Menu, Select, Space, Tooltip, Typography } from 'antd';
import {
    AppstoreOutlined,
    BarChartOutlined,
    BellOutlined,
    BookOutlined,
    CalendarOutlined,
    ContainerOutlined,
    DashboardOutlined,
    FundOutlined,
    InboxOutlined,
    LogoutOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
    PartitionOutlined,
    SafetyCertificateOutlined,
    SettingOutlined,
    ShopOutlined,
    ShoppingCartOutlined,
    ShoppingOutlined,
    SwapOutlined,
    TeamOutlined,
    ToolOutlined,
    UserOutlined,
    WalletOutlined,
} from '@ant-design/icons';
import type { MenuProps } from 'antd';
import { getCurrentTenantId, setCurrentTenantId, useAuth, useLogout } from '@/lib/auth';
import { OverQuotaBanner } from '@/components/OverQuotaBanner';

const { Header, Sider, Content } = Layout;

function buildNav(isSuperAdmin: boolean): MenuProps['items'] {
    const items: NonNullable<MenuProps['items']> = [
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
            { key: '/procurement/demand-planning', icon: <FundOutlined />, label: <Link to="/procurement/demand-planning">Đề xuất nhập hàng</Link> },
            { key: '/procurement/suppliers', icon: <ShopOutlined />, label: <Link to="/procurement/suppliers">Nhà cung cấp</Link> },
            { key: '/procurement/purchase-orders', icon: <ShoppingCartOutlined />, label: <Link to="/procurement/purchase-orders">Đơn mua hàng</Link> },
        ] },
        { type: 'group', label: 'Báo cáo & Kế toán', children: [
            { key: '/reports', icon: <BarChartOutlined />, label: <Link to="/reports">Báo cáo</Link> },
            { key: '/finance/settlements', icon: <FundOutlined />, label: <Link to="/finance/settlements">Đối soát sàn</Link> },
            { key: '/accounting/journals', icon: <BookOutlined />, label: <Link to="/accounting/journals">Sổ nhật ký</Link> },
            { key: '/accounting/chart-of-accounts', icon: <PartitionOutlined />, label: <Link to="/accounting/chart-of-accounts">Hệ thống TK</Link> },
            { key: '/accounting/balances', icon: <ContainerOutlined />, label: <Link to="/accounting/balances">Cân đối phát sinh</Link> },
            { key: '/accounting/ar', icon: <TeamOutlined />, label: <Link to="/accounting/ar">Công nợ phải thu</Link> },
            { key: '/accounting/ap', icon: <ShopOutlined />, label: <Link to="/accounting/ap">Công nợ phải trả</Link> },
            { key: '/accounting/cash', icon: <WalletOutlined />, label: <Link to="/accounting/cash">Quỹ & Ngân hàng</Link> },
            { key: '/accounting/reports', icon: <BarChartOutlined />, label: <Link to="/accounting/reports">Báo cáo tài chính</Link> },
            { key: '/accounting/periods', icon: <CalendarOutlined />, label: <Link to="/accounting/periods">Kỳ kế toán</Link> },
        ] },
        { type: 'group', label: 'Hệ thống', children: [
            { key: '/sync-logs', icon: <SwapOutlined />, label: <Link to="/sync-logs">Nhật ký đồng bộ</Link> },
            { key: '/settings', icon: <SettingOutlined />, label: <Link to="/settings">Cài đặt</Link> },
        ] },
    ];

    // SPEC 0020 — chỉ super-admin thấy nhóm này.
    if (isSuperAdmin) {
        items.push({
            type: 'group', label: 'Quản trị hệ thống', children: [
                { key: '/admin/tenants', icon: <ToolOutlined />, label: <Link to="/admin/tenants">Tenant & gian hàng</Link> },
                { key: '/admin/users', icon: <SafetyCertificateOutlined />, label: <Link to="/admin/users">Người dùng hệ thống</Link> },
            ],
        });
    }

    return items;
}

// Flat key list for selected-key matching.
const BASE_KEYS = ['/', '/orders', '/customers', '/channels', '/products', '/inventory',
    '/procurement/demand-planning', '/procurement/suppliers', '/procurement/purchase-orders',
    '/reports', '/finance/settlements',
    '/accounting/journals', '/accounting/chart-of-accounts', '/accounting/balances', '/accounting/ar', '/accounting/ap', '/accounting/cash', '/accounting/reports', '/accounting/periods',
    '/sync-logs', '/settings'];
const ADMIN_KEYS = ['/admin/tenants', '/admin/users'];

export function AppLayout() {
    const { data: user } = useAuth();
    const logout = useLogout();
    const navigate = useNavigate();
    const location = useLocation();
    const [collapsed, setCollapsed] = useState(false);

    const currentTenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
    const currentTenant = user?.tenants.find((t) => t.id === currentTenantId) ?? user?.tenants[0];
    const isSuperAdmin = user?.is_super_admin === true;

    const nav = useMemo(() => buildNav(isSuperAdmin), [isSuperAdmin]);
    const keys = useMemo(() => isSuperAdmin ? [...BASE_KEYS, ...ADMIN_KEYS] : BASE_KEYS, [isSuperAdmin]);

    const selectedKey = useMemo(() => {
        const match = keys.filter((k) => (k === '/' ? location.pathname === '/' : location.pathname.startsWith(k)))
            .sort((a, b) => b.length - a.length)[0];
        return match ?? '/';
    }, [location.pathname, keys]);

    const isAdminRoute = location.pathname.startsWith('/admin/');

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider theme="light" width={236} collapsedWidth={64} collapsible collapsed={collapsed} trigger={null} style={{ borderRight: '1px solid #f0f0f0' }}>
                <div style={{ height: 56, display: 'flex', alignItems: 'center', gap: 10, padding: collapsed ? '0' : '0 18px', justifyContent: collapsed ? 'center' : 'flex-start', fontWeight: 700, fontSize: 16, color: '#fa8c16', whiteSpace: 'nowrap', overflow: 'hidden' }}>
                    <img src="/images/logocmb.png" alt="CMB Core" style={{ width: 32, height: 32, objectFit: 'contain', flex: 'none' }} />
                    {!collapsed && <span>CMB Core</span>}
                </div>
                <Menu mode="inline" selectedKeys={[selectedKey]} items={nav} style={{ borderInlineEnd: 'none' }} />
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
                    {/* SPEC 0020 — banner over-quota cho user thường; trang admin không cần (super admin biết qua list). */}
                    {!isAdminRoute && <OverQuotaBanner />}
                    <Outlet context={{ tenantName: currentTenant?.name }} />
                </Content>
            </Layout>
        </Layout>
    );
}
