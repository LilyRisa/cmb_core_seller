import { Link, Outlet, useLocation } from 'react-router-dom';
import { Card, Menu } from 'antd';
import {
    AppstoreOutlined, AuditOutlined, CarOutlined, CreditCardOutlined, FileTextOutlined, HistoryOutlined,
    PrinterOutlined, ShopOutlined, TeamOutlined, UserOutlined,
} from '@ant-design/icons';
import type { MenuProps } from 'antd';
import { PageHeader } from '@/components/PageHeader';

/**
 * Settings shell (SPEC 0011 / 0007 §2) — left submenu + content area. Each group is its own
 * page; sections not built yet show a "Sắp có" placeholder. Sidebar item "Cài đặt" points here.
 */
const SECTIONS: MenuProps['items'] = [
    { type: 'group', label: 'Tài khoản', children: [
        { key: '/settings/profile', icon: <UserOutlined />, label: <Link to="/settings/profile">Hồ sơ cá nhân</Link> },
        { key: '/settings/workspace', icon: <ShopOutlined />, label: <Link to="/settings/workspace">Thông tin gian hàng</Link> },
        { key: '/settings/plan', icon: <CreditCardOutlined />, label: <Link to="/settings/plan">Gói & nâng cấp</Link> },
    ] },
    { type: 'group', label: 'Nhân sự & phân quyền', children: [
        { key: '/settings/members', icon: <TeamOutlined />, label: <Link to="/settings/members">Nhân viên & vai trò</Link> },
    ] },
    { type: 'group', label: 'Kết nối', children: [
        { key: '/settings/carriers', icon: <CarOutlined />, label: <Link to="/settings/carriers">Đơn vị vận chuyển</Link> },
        { key: '/settings/channels', icon: <AppstoreOutlined />, label: <Link to="/settings/channels">Gian hàng & module phụ trợ</Link> },
    ] },
    { type: 'group', label: 'Vận hành', children: [
        { key: '/settings/orders', icon: <FileTextOutlined />, label: <Link to="/settings/orders">Cài đặt đơn hàng</Link> },
        { key: '/settings/print', icon: <PrinterOutlined />, label: <Link to="/settings/print">Mẫu in</Link> },
        { key: '/settings/accounting/post-rules', icon: <AuditOutlined />, label: <Link to="/settings/accounting/post-rules">Quy tắc hạch toán</Link> },
        { key: '/settings/audit', icon: <HistoryOutlined />, label: <Link to="/settings/audit">Nhật ký thao tác</Link> },
    ] },
];

const KEYS = ['/settings/profile', '/settings/workspace', '/settings/plan', '/settings/members', '/settings/carriers', '/settings/channels', '/settings/orders', '/settings/print', '/settings/accounting/post-rules', '/settings/audit'];

export function SettingsLayout() {
    const { pathname } = useLocation();
    const selected = KEYS.find((k) => pathname.startsWith(k)) ?? '/settings/profile';
    return (
        <div>
            <PageHeader title="Cài đặt" subtitle="Tài khoản, gian hàng, nhân viên & phân quyền, kết nối, vận hành" />
            <div style={{ display: 'flex', gap: 16, alignItems: 'flex-start' }}>
                <Card size="small" styles={{ body: { padding: 8 } }} style={{ width: 248, flexShrink: 0 }}>
                    <Menu mode="inline" selectedKeys={[selected]} items={SECTIONS} style={{ borderInlineEnd: 'none' }} />
                </Card>
                <div style={{ flex: 1, minWidth: 0 }}>
                    <Outlet />
                </div>
            </div>
        </div>
    );
}
