import { useMemo, useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { Avatar, Button, Dropdown, Layout, Menu, Select, Space, Tooltip, Typography } from 'antd';
import {
    AppstoreOutlined,
    BarChartOutlined,
    BookOutlined,
    CalendarOutlined,
    ContainerOutlined,
    DashboardOutlined,
    FundOutlined,
    InboxOutlined,
    LogoutOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
    MessageOutlined,
    MobileOutlined,
    PartitionOutlined,
    PieChartOutlined,
    ReadOutlined,
    SafetyCertificateOutlined,
    SettingOutlined,
    RollbackOutlined,
    ShopOutlined,
    ShoppingCartOutlined,
    ShoppingOutlined,
    SwapOutlined,
    TeamOutlined,
    UserOutlined,
    WalletOutlined,
} from '@ant-design/icons';
import type { MenuProps } from 'antd';
import { getCurrentTenantId, setCurrentTenantId, useAuth, useLogout } from '@/lib/auth';
import { useCan } from '@/lib/tenant';
import { useGlobalMessageNotifications } from '@/lib/useMessageNotifications';
import { useNotificationsRealtime } from '@/lib/notifications';
import { NotificationBell } from '@/components/NotificationBell';
import { OverQuotaBanner } from '@/components/OverQuotaBanner';
import { HeaderBillingActions } from '@/components/HeaderBillingActions';
import { HelpChatWidget } from '@/components/support/HelpChatWidget';

const { Header, Sider, Content } = Layout;

// Spec 2026-05-17 — user SPA không còn menu admin; truy cập tại `/admin` (server-side route).
function buildNav(): MenuProps['items'] {
    const items: NonNullable<MenuProps['items']> = [
        { type: 'group', label: 'Tổng quan', children: [
            { key: '/', icon: <DashboardOutlined />, label: <Link to="/">Bảng điều khiển</Link> },
        ] },
        { type: 'group', label: 'Bán hàng', children: [
            { key: '/orders', icon: <ShoppingOutlined />, label: <Link to="/orders">Đơn hàng</Link> },
            { key: '/returns', icon: <RollbackOutlined />, label: <Link to="/returns">Hoàn & Hủy</Link> },
            { key: '/customers', icon: <TeamOutlined />, label: <Link to="/customers">Khách hàng</Link> },
            { key: 'messaging', icon: <MessageOutlined />, label: 'Tin nhắn', children: [
                { key: '/messaging', label: <Link to="/messaging">Hộp thư</Link> },
                { key: '/messaging/channels', label: <Link to="/messaging/channels">Kết nối kênh</Link> },
                { key: '/messaging/templates', label: <Link to="/messaging/templates">Mẫu tin</Link> },
                { key: '/messaging/utility-templates', label: <Link to="/messaging/utility-templates">Tin tiện ích</Link> },
                { key: '/messaging/auto-rules', label: <Link to="/messaging/auto-rules">Tự động trả lời</Link> },
                { key: '/messaging/flows', label: <Link to="/messaging/flows">Kịch bản tự động</Link> },
                { key: '/messaging/knowledge', label: <Link to="/messaging/knowledge">AI training</Link> },
            ] },
            { key: '/channels', icon: <ShopOutlined />, label: <Link to="/channels">Gian hàng</Link> },
            { key: '/products', icon: <AppstoreOutlined />, label: <Link to="/products">Sản phẩm & SKU</Link> },
        ] },
        { type: 'group', label: 'Kho & Mua hàng', children: [
            { key: '/inventory', icon: <InboxOutlined />, label: <Link to="/inventory">Tồn kho</Link> },
            { key: '/procurement/demand-planning', icon: <FundOutlined />, label: <Link to="/procurement/demand-planning">Đề xuất nhập hàng</Link> },
            { key: '/procurement/suppliers', icon: <ShopOutlined />, label: <Link to="/procurement/suppliers">Nhà cung cấp</Link> },
            { key: '/procurement/purchase-orders', icon: <ShoppingCartOutlined />, label: <Link to="/procurement/purchase-orders">Đơn mua hàng</Link> },
        ] },
        { type: 'group', label: 'Marketing', children: [
            { key: '/marketing', icon: <FundOutlined />, label: <Link to="/marketing">Quảng cáo Facebook</Link> },
        ] },
        { type: 'group', label: 'Báo cáo & Kế toán', children: [
            { key: '/reports/overview', icon: <PieChartOutlined />, label: <Link to="/reports/overview">Báo cáo tổng thể</Link> },
            { key: '/reports', icon: <BarChartOutlined />, label: <Link to="/reports">Báo cáo</Link> },
            { key: '/shop-report', icon: <SafetyCertificateOutlined />, label: <Link to="/shop-report">Báo cáo sàn</Link> },
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
            { key: '/support', icon: <ReadOutlined />, label: <Link to="/support">Trung tâm trợ giúp</Link> },
            { key: '/settings', icon: <SettingOutlined />, label: <Link to="/settings">Cài đặt</Link> },
        ] },
    ];

    return items;
}

// Flat key list for selected-key matching.
const BASE_KEYS = ['/', '/orders', '/customers', '/messaging', '/messaging/channels', '/messaging/templates', '/messaging/utility-templates', '/messaging/auto-rules', '/messaging/knowledge', '/channels', '/products', '/inventory',
    '/procurement/demand-planning', '/procurement/suppliers', '/procurement/purchase-orders',
    '/reports/overview', '/reports', '/shop-report', '/marketing', '/finance/settlements',
    '/accounting/journals', '/accounting/chart-of-accounts', '/accounting/balances', '/accounting/ar', '/accounting/ap', '/accounting/cash', '/accounting/reports', '/accounting/periods',
    '/sync-logs', '/support', '/settings'];

export function AppLayout() {
    const { data: user } = useAuth();
    const logout = useLogout();
    const navigate = useNavigate();
    const location = useLocation();
    const [collapsed, setCollapsed] = useState(false);

    const currentTenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
    const currentTenant = user?.tenants.find((t) => t.id === currentTenantId) ?? user?.tenants[0];
    // Thông báo tin nhắn mới toàn cục (mọi trang) — 1 lần tổng lúc vào, sau đó theo từng tin mới.
    useGlobalMessageNotifications(useCan('messaging.view'));
    // SPEC 0036 — realtime chuông thông báo in-app (no-op khi Reverb tắt; chuông vẫn poll).
    useNotificationsRealtime();
    const nav = useMemo(() => buildNav(), []);
    const keys = BASE_KEYS;

    const selectedKey = useMemo(() => {
        const match = keys.filter((k) => (k === '/' ? location.pathname === '/' : location.pathname.startsWith(k)))
            .sort((a, b) => b.length - a.length)[0];
        return match ?? '/';
    }, [location.pathname, keys]);

    return (
        <Layout style={{ minHeight: '100vh' }}>
            {/* Sider ghim theo viewport (sticky, cao 100vh); menu dài cuộn RIÊNG bên trong — không đẩy/tràn cả layout. */}
            <Sider theme="light" width={236} collapsedWidth={64} collapsible collapsed={collapsed} trigger={null} style={{ borderRight: '1px solid #f0f0f0', height: '100vh', position: 'sticky', top: 0, overflow: 'hidden' }}>
                <div style={{ height: 56, flex: 'none', display: 'flex', alignItems: 'center', gap: 10, padding: collapsed ? '0' : '0 18px', justifyContent: collapsed ? 'center' : 'flex-start', fontWeight: 600, fontSize: 16, color: '#0F172A', whiteSpace: 'nowrap', overflow: 'hidden' }}>
                    <img src="/images/logocmb.png" alt="CMB Core" style={{ width: 32, height: 32, objectFit: 'contain', flex: 'none' }} />
                    {!collapsed && <span>CMB Core</span>}
                </div>
                <div style={{ height: 'calc(100vh - 56px)', overflowY: 'auto', overflowX: 'hidden' }}>
                    <Menu mode="inline" selectedKeys={[selectedKey]} defaultOpenKeys={['messaging']} inlineIndent={16} items={nav} style={{ borderInlineEnd: 'none' }} />
                </div>
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
                        <HeaderBillingActions />
                        <Tooltip title="Tải ứng dụng di động">
                            <Button type="text" href="/download" target="_blank" icon={<MobileOutlined />} />
                        </Tooltip>
                        <NotificationBell />
                        <Dropdown
                            menu={{ items: [
                                { key: 'who', disabled: true, label: <span>{user?.name}<br /><Typography.Text type="secondary" style={{ fontSize: 12 }}>{user?.email}</Typography.Text></span> },
                                { type: 'divider' },
                                { key: 'settings', icon: <SettingOutlined />, label: <Link to="/settings/members">Cài đặt</Link> },
                                { key: 'logout', icon: <LogoutOutlined />, label: 'Đăng xuất', onClick: () => logout.mutate(undefined, { onSuccess: () => navigate('/login') }) },
                            ] }}
                        >
                            <Space style={{ cursor: 'pointer' }}>
                                <Avatar size="small" style={{ background: 'linear-gradient(135deg, #2563EB 0%, #1E40AF 100%)' }} icon={<UserOutlined />} />
                                <span style={{ fontWeight: 500 }}>{user?.name}</span>
                            </Space>
                        </Dropdown>
                    </Space>
                </Header>
                <Content style={{ margin: 16, minHeight: 0 }}>
                    {/* SPEC 0020 — banner over-quota cho user thường; trang admin không cần (super admin biết qua list). */}
                    <OverQuotaBanner />
                    <Outlet context={{ tenantName: currentTenant?.name }} />
                </Content>
            </Layout>
            {/* Widget trợ giúp nổi (kéo–thả) — Hỏi AI (RAG) + Hỏi CSKH. Hiện mọi trang app người dùng. */}
            <HelpChatWidget />
        </Layout>
    );
}
