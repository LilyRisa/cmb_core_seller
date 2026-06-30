import { Link, Outlet, useLocation } from 'react-router-dom';
import { Card, Menu } from 'antd';
import {
    ApiOutlined, AppstoreOutlined, AuditOutlined, CarOutlined, CreditCardOutlined, FileTextOutlined, HistoryOutlined,
    LayoutOutlined, PrinterOutlined, ShopOutlined, TeamOutlined, UserOutlined,
} from '@ant-design/icons';
import type { MenuProps } from 'antd';
import { PageHeader } from '@/components/PageHeader';
import { useCan } from '@/lib/tenant';

/**
 * Settings shell (SPEC 0011 / 0007 §2) — left submenu + content area. Each group is its own
 * page; sections not built yet show a "Sắp có" placeholder. Sidebar item "Cài đặt" points here.
 * `canApiKeys` (owner-only) ⇒ hiện mục "API & Tích hợp" (SPEC 2026-06-26).
 */
function buildSections(canApiKeys: boolean, canEInvoice: boolean): MenuProps['items'] {
    return [
    { type: 'group', label: 'Tài khoản', children: [
        { key: '/settings/profile', icon: <UserOutlined />, label: <Link to="/settings/profile">Hồ sơ cá nhân</Link> },
        { key: '/settings/appearance', icon: <LayoutOutlined />, label: <Link to="/settings/appearance">Giao diện</Link> },
        { key: '/settings/workspace', icon: <ShopOutlined />, label: <Link to="/settings/workspace">Thông tin gian hàng</Link> },
        { key: '/settings/plan', icon: <CreditCardOutlined />, label: <Link to="/settings/plan">Gói & nâng cấp</Link> },
    ] },
    { type: 'group', label: 'Nhân sự & phân quyền', children: [
        { key: '/settings/members', icon: <TeamOutlined />, label: <Link to="/settings/members">Nhân viên & vai trò</Link> },
    ] },
    { type: 'group', label: 'Kết nối', children: [
        { key: '/settings/carriers', icon: <CarOutlined />, label: <Link to="/settings/carriers">Đơn vị vận chuyển</Link> },
        { key: '/settings/channels', icon: <AppstoreOutlined />, label: <Link to="/settings/channels">Gian hàng & module phụ trợ</Link> },
        ...(canApiKeys ? [{ key: '/settings/api-keys', icon: <ApiOutlined />, label: <Link to="/settings/api-keys">API & Tích hợp</Link> }] : []),
    ] },
    { type: 'group', label: 'Vận hành', children: [
        { key: '/settings/orders', icon: <FileTextOutlined />, label: <Link to="/settings/orders">Cài đặt đơn hàng</Link> },
        { key: '/settings/print', icon: <PrinterOutlined />, label: <Link to="/settings/print">Mẫu in</Link> },
        { key: '/settings/shipping-labels', icon: <PrinterOutlined />, label: <Link to="/settings/shipping-labels">Mẫu phiếu giao hàng</Link> },
        { key: '/settings/accounting/post-rules', icon: <AuditOutlined />, label: <Link to="/settings/accounting/post-rules">Quy tắc hạch toán</Link> },
        ...(canEInvoice ? [{ key: '/settings/einvoice', icon: <FileTextOutlined />, label: <Link to="/settings/einvoice">Hóa đơn điện tử</Link> }] : []),
        { key: '/settings/audit', icon: <HistoryOutlined />, label: <Link to="/settings/audit">Nhật ký thao tác</Link> },
    ] },
    ];
}

const KEYS = ['/settings/profile', '/settings/appearance', '/settings/workspace', '/settings/plan', '/settings/members', '/settings/carriers', '/settings/channels', '/settings/api-keys', '/settings/orders', '/settings/print', '/settings/shipping-labels', '/settings/accounting/post-rules', '/settings/einvoice', '/settings/audit'];

export function SettingsLayout() {
    const { pathname } = useLocation();
    const canApiKeys = useCan('api_keys.manage');
    const canEInvoice = useCan('einvoice.config');
    const selected = KEYS.find((k) => pathname.startsWith(k)) ?? '/settings/profile';
    return (
        <div>
            <PageHeader title="Cài đặt" subtitle="Tài khoản, gian hàng, nhân viên & phân quyền, kết nối, vận hành" />
            <div style={{ display: 'flex', gap: 16, alignItems: 'flex-start' }}>
                <Card size="small" styles={{ body: { padding: 8 } }} style={{ width: 248, flexShrink: 0 }}>
                    <Menu mode="inline" selectedKeys={[selected]} items={buildSections(canApiKeys, canEInvoice)} style={{ borderInlineEnd: 'none' }} />
                </Card>
                <div style={{ flex: 1, minWidth: 0 }}>
                    <Outlet />
                </div>
            </div>
        </div>
    );
}
