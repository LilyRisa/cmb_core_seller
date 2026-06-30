import { useMemo, useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { Button, Layout, Menu } from 'antd';
import {
    AppstoreOutlined,
    BarChartOutlined,
    BookOutlined,
    CloudUploadOutlined,
    CopyOutlined,
    DashboardOutlined,
    FacebookFilled,
    FundOutlined,
    InboxOutlined,
    TikTokOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
    MessageOutlined,
    PercentageOutlined,
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
    WalletOutlined,
} from '@ant-design/icons';
import type { MenuProps } from 'antd';
import { getCurrentTenantId, useAuth } from '@/lib/auth';
import { useCan } from '@/lib/tenant';
import { useGlobalMessageNotifications } from '@/lib/useMessageNotifications';
import { useNotificationsRealtime } from '@/lib/notifications';
import { AnnouncementPopup } from '@/components/AnnouncementPopup';
import { OverQuotaBanner } from '@/components/OverQuotaBanner';
import { HelpChatWidget } from '@/components/support/HelpChatWidget';
import { AppHeader } from '@/components/AppHeader';

const { Sider, Content } = Layout;

// Spec 2026-05-17 — user SPA không còn menu admin; truy cập tại `/admin` (server-side route).
function buildNav(): MenuProps['items'] {
    const items: NonNullable<MenuProps['items']> = [
        { type: 'group', label: 'Tổng quan', children: [
            { key: '/dashboard', icon: <DashboardOutlined />, label: <Link to="/dashboard">Bảng điều khiển</Link> },
        ] },
        { type: 'group', label: 'Bán hàng', children: [
            { key: '/orders', icon: <ShoppingOutlined />, label: <Link to="/orders?tab=pending">Đơn hàng</Link> },
            { key: '/returns', icon: <RollbackOutlined />, label: <Link to="/returns">Hoàn & Hủy</Link> },
            { key: '/customers', icon: <TeamOutlined />, label: <Link to="/customers">Khách hàng</Link> },
            { key: '/channels', icon: <ShopOutlined />, label: <Link to="/channels">Gian hàng</Link> },
            { key: '/products', icon: <AppstoreOutlined />, label: <Link to="/products">Sản phẩm & SKU</Link> },
        ] },
        // Tin nhắn = mục riêng (tách khỏi Bán hàng), chia theo nền tảng — mỗi nền tảng có đủ trang con.
        // Facebook đã có; Zalo OA thêm submenu Phase 1 (SPEC 0039).
        { type: 'group', label: 'Tin nhắn', children: [
            { key: 'messaging-facebook', icon: <FacebookFilled />, label: 'Facebook', children: [
                { key: '/messaging', label: <Link to="/messaging">Hộp thư</Link> },
                { key: '/messaging/channels', label: <Link to="/messaging/channels">Kết nối kênh</Link> },
                { key: '/messaging/templates', label: <Link to="/messaging/templates">Mẫu tin</Link> },
                { key: '/messaging/utility-templates', label: <Link to="/messaging/utility-templates">Tin tiện ích</Link> },
                { key: '/messaging/auto-rules', label: <Link to="/messaging/auto-rules">Tự động trả lời</Link> },
                { key: '/messaging/flows', label: <Link to="/messaging/flows">Kịch bản tự động</Link> },
                { key: '/messaging/knowledge', label: <Link to="/messaging/knowledge">AI training</Link> },
            ] },
            { key: 'messaging-zalo', icon: <MessageOutlined />, label: 'Zalo OA', children: [
                { key: '/messaging?platform=zalo_oa', label: <Link to="/messaging?platform=zalo_oa">Hộp thư</Link> },
                { key: '/messaging/channels?platform=zalo_oa', label: <Link to="/messaging/channels?platform=zalo_oa">Kết nối Zalo OA</Link> },
                { key: '/messaging/auto-rules?platform=zalo_oa', label: <Link to="/messaging/auto-rules?platform=zalo_oa">Tự động trả lời</Link> },
                { key: '/messaging/flows?platform=zalo_oa', label: <Link to="/messaging/flows?platform=zalo_oa">Kịch bản tự động</Link> },
            ] },
        ] },
        { type: 'group', label: 'Đăng bán sàn', children: [
            { key: '/marketplace/products', icon: <CopyOutlined />, label: <Link to="/marketplace/products">Sao chép sản phẩm</Link> },
            { key: '/marketplace/to-push', icon: <CloudUploadOutlined />, label: <Link to="/marketplace/to-push">Chờ đẩy lên sàn</Link> },
            { key: '/marketplace/on-channel', icon: <ShopOutlined />, label: <Link to="/marketplace/on-channel">Đã có trên sàn</Link> },
            { key: '/marketplace/promotions', icon: <PercentageOutlined />, label: <Link to="/marketplace/promotions">Chiến dịch giảm giá</Link> },
        ] },
        { type: 'group', label: 'Kho & Mua hàng', children: [
            { key: '/inventory', icon: <InboxOutlined />, label: <Link to="/inventory">Tồn kho</Link> },
            { key: '/procurement/demand-planning', icon: <FundOutlined />, label: <Link to="/procurement/demand-planning">Đề xuất nhập hàng</Link> },
            { key: '/procurement/suppliers', icon: <ShopOutlined />, label: <Link to="/procurement/suppliers">Nhà cung cấp</Link> },
            { key: '/procurement/purchase-orders', icon: <ShoppingCartOutlined />, label: <Link to="/procurement/purchase-orders">Đơn mua hàng</Link> },
        ] },
        { type: 'group', label: 'Quảng cáo', children: [
            { key: '/marketing', icon: <FacebookFilled />, label: <Link to="/marketing">Quảng cáo Facebook</Link> },
            { key: '/marketing/tiktok', icon: <TikTokOutlined />, label: <Link to="/marketing/tiktok">Quảng cáo TikTok</Link> },
        ] },
        { type: 'group', label: 'Báo cáo', children: [
            { key: '/reports/overview', icon: <PieChartOutlined />, label: <Link to="/reports/overview">Báo cáo tổng thể</Link> },
            { key: '/reports', icon: <BarChartOutlined />, label: <Link to="/reports">Báo cáo bán hàng</Link> },
            { key: '/shop-report', icon: <SafetyCertificateOutlined />, label: <Link to="/shop-report">Báo cáo sàn</Link> },
            { key: '/finance/settlements', icon: <FundOutlined />, label: <Link to="/finance/settlements">Đối soát sàn</Link> },
        ] },
        { type: 'group', label: 'Kế toán', children: [
            { key: '/accounting/dashboard', icon: <DashboardOutlined />, label: <Link to="/accounting/dashboard">Tổng quan kế toán</Link> },
            { key: 'acc-books', icon: <BookOutlined />, label: 'Sổ sách', children: [
                { key: '/accounting/journals', label: <Link to="/accounting/journals">Sổ nhật ký chung</Link> },
                { key: '/accounting/chart-of-accounts', label: <Link to="/accounting/chart-of-accounts">Hệ thống tài khoản</Link> },
                { key: '/accounting/balances', label: <Link to="/accounting/balances">Cân đối phát sinh</Link> },
                { key: '/accounting/periods', label: <Link to="/accounting/periods">Kỳ kế toán</Link> },
            ] },
            { key: 'acc-money', icon: <WalletOutlined />, label: 'Công nợ & Tiền', children: [
                { key: '/accounting/ar', label: <Link to="/accounting/ar">Công nợ phải thu</Link> },
                { key: '/accounting/ap', label: <Link to="/accounting/ap">Công nợ phải trả</Link> },
                { key: '/accounting/cash', label: <Link to="/accounting/cash">Quỹ & Ngân hàng</Link> },
            ] },
            { key: '/accounting/reports', icon: <BarChartOutlined />, label: <Link to="/accounting/reports">Báo cáo tài chính & Thuế</Link> },
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
const BASE_KEYS = ['/', '/orders', '/customers', '/messaging', '/messaging/channels', '/messaging/templates', '/messaging/utility-templates', '/messaging/auto-rules', '/messaging/knowledge', '/channels', '/products', '/marketplace/products', '/marketplace/to-push', '/marketplace/on-channel', '/marketplace/promotions', '/inventory',
    '/procurement/demand-planning', '/procurement/suppliers', '/procurement/purchase-orders',
    '/reports/overview', '/reports', '/shop-report', '/marketing', '/marketing/tiktok', '/finance/settlements',
    '/accounting/dashboard', '/accounting/journals', '/accounting/chart-of-accounts', '/accounting/balances', '/accounting/ar', '/accounting/ap', '/accounting/cash', '/accounting/reports', '/accounting/periods',
    '/sync-logs', '/support', '/settings'];

export function AppLayout() {
    const { data: user } = useAuth();
    const location = useLocation();
    const navigate = useNavigate();
    const [collapsed, setCollapsed] = useState(false);

    const currentTenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
    const currentTenant = user?.tenants.find((t) => t.id === currentTenantId) ?? user?.tenants[0];
    // Thông báo tin nhắn mới toàn cục (mọi trang) — 1 lần tổng lúc vào, sau đó theo từng tin mới.
    useGlobalMessageNotifications(useCan('messaging.view'), (id) => navigate(id ? `/messaging?conversation=${id}` : '/messaging'));
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
                    <Menu mode="inline" selectedKeys={[selectedKey]} defaultOpenKeys={['messaging-facebook', 'messaging-zalo', 'acc-books', 'acc-money']} inlineIndent={16} items={nav} style={{ borderInlineEnd: 'none' }} />
                </div>
            </Sider>
            <Layout>
                <AppHeader left={<Button type="text" icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />} onClick={() => setCollapsed((c) => !c)} />} />
                <Content style={{ margin: 16, minHeight: 0 }}>
                    {/* SPEC 0020 — banner over-quota cho user thường; trang admin không cần (super admin biết qua list). */}
                    <OverQuotaBanner />
                    <Outlet context={{ tenantName: currentTenant?.name }} />
                </Content>
            </Layout>
            {/* Widget trợ giúp nổi (kéo–thả) — Hỏi AI (RAG) + Hỏi CSKH. Hiện mọi trang app người dùng. */}
            <HelpChatWidget />
            {/* SPEC 0037 — popup thông báo admin (giữa màn hình, 1 lần/tab). */}
            <AnnouncementPopup />
        </Layout>
    );
}
