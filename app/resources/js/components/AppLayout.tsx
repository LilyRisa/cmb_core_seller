import { useMemo } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { Layout, Menu, Select, Space, Typography, Button, Dropdown } from 'antd';
import {
    DashboardOutlined,
    ShopOutlined,
    ShoppingOutlined,
    InboxOutlined,
    AppstoreOutlined,
    CarOutlined,
    LogoutOutlined,
    UserOutlined,
} from '@ant-design/icons';
import { useAuth, useLogout, getCurrentTenantId, setCurrentTenantId } from '@/lib/auth';

const { Header, Sider, Content } = Layout;

const NAV = [
    { key: '/', icon: <DashboardOutlined />, label: <Link to="/">Tổng quan</Link> },
    { key: '/orders', icon: <ShoppingOutlined />, label: <Link to="/orders">Đơn hàng</Link> },
    { key: '/channels', icon: <ShopOutlined />, label: <Link to="/channels">Gian hàng</Link> },
    { key: '/products', icon: <AppstoreOutlined />, label: <Link to="/products">Sản phẩm & SKU</Link> },
    { key: '/inventory', icon: <InboxOutlined />, label: <Link to="/inventory">Tồn kho</Link> },
    { key: '/fulfillment', icon: <CarOutlined />, label: <Link to="/fulfillment">Giao hàng & in</Link> },
];

export function AppLayout() {
    const { data: user } = useAuth();
    const logout = useLogout();
    const navigate = useNavigate();
    const location = useLocation();

    const currentTenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
    const selectedKey = useMemo(() => {
        const match = NAV.map((n) => n.key)
            .filter((k) => k === '/' ? location.pathname === '/' : location.pathname.startsWith(k))
            .sort((a, b) => b.length - a.length)[0];
        return match ?? '/';
    }, [location.pathname]);

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider breakpoint="lg" collapsedWidth="0" theme="dark">
                <div style={{ color: '#fff', fontWeight: 700, fontSize: 16, padding: '16px 20px', whiteSpace: 'nowrap' }}>
                    CMBcoreSeller
                </div>
                <Menu theme="dark" mode="inline" selectedKeys={[selectedKey]} items={NAV} />
            </Sider>
            <Layout>
                <Header style={{ background: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0 16px' }}>
                    <Space>
                        <Typography.Text type="secondary">Gian hàng / workspace:</Typography.Text>
                        <Select
                            size="small"
                            style={{ minWidth: 200 }}
                            value={currentTenantId ?? undefined}
                            options={(user?.tenants ?? []).map((t) => ({ value: t.id, label: `${t.name} (${t.role})` }))}
                            onChange={(v) => {
                                setCurrentTenantId(v);
                                navigate(0);
                            }}
                        />
                    </Space>
                    <Dropdown
                        menu={{
                            items: [
                                { key: 'who', disabled: true, label: `${user?.name} — ${user?.email}` },
                                { type: 'divider' },
                                {
                                    key: 'logout',
                                    icon: <LogoutOutlined />,
                                    label: 'Đăng xuất',
                                    onClick: () => logout.mutate(undefined, { onSuccess: () => navigate('/login') }),
                                },
                            ],
                        }}
                    >
                        <Button type="text" icon={<UserOutlined />}>{user?.name}</Button>
                    </Dropdown>
                </Header>
                <Content style={{ margin: 16 }}>
                    <Outlet />
                </Content>
            </Layout>
        </Layout>
    );
}
