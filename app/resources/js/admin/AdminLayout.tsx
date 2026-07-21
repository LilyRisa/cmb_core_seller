// Spec 2026-05-17 (redesign 2026-07-21) — sidebar + header admin. Tách hoàn toàn khỏi
// `AppLayout` của user. Theme navy/đỏ (xem `admin.tsx` ConfigProvider). Icons từ
// `@ant-design/icons` (memory `ui-use-font-icons-not-emoji`). Sidebar gom nhóm theo
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §4.

import type { ReactNode } from 'react';
import { Layout, Menu, Typography, Space, Button } from 'antd';
import type { MenuProps } from 'antd';
import {
    DashboardOutlined,
    ShopOutlined,
    UserOutlined,
    SettingOutlined,
    AuditOutlined,
    TransactionOutlined,
    LogoutOutlined,
    SafetyCertificateOutlined,
    GiftOutlined,
    ProfileOutlined,
    NotificationOutlined,
    SoundOutlined,
    ApiOutlined,
    CustomerServiceOutlined,
    SolutionOutlined,
    PictureOutlined,
    AudioOutlined,
    MailOutlined,
    RiseOutlined,
    EyeOutlined,
    FunnelPlotOutlined,
} from '@ant-design/icons';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAdminLogout, useAdminMe } from './lib/adminAuth';

interface SidebarLeaf { key: string; icon: ReactNode; label: string }
interface SidebarGroup { groupLabel: string; items: SidebarLeaf[] }
type SidebarEntry = SidebarLeaf | SidebarGroup;

function isGroup(e: SidebarEntry): e is SidebarGroup {
    return 'groupLabel' in e;
}

// Cấu trúc nguồn của sidebar — Menu items (AntD) và breadcrumb header đều dẫn xuất từ đây,
// tránh 2 nguồn sự thật lệch nhau. Thêm mục mới: thêm leaf vào group phù hợp (hoặc group mới).
const SIDEBAR: SidebarEntry[] = [
    { key: '/admin', icon: <DashboardOutlined />, label: 'Tổng quan' },
    {
        groupLabel: 'KHÁCH HÀNG',
        items: [
            { key: '/admin/tenants', icon: <ShopOutlined />, label: 'Tenants' },
            { key: '/admin/growth', icon: <FunnelPlotOutlined />, label: 'Tăng trưởng' },
            { key: '/admin/users', icon: <UserOutlined />, label: 'Người dùng' },
            { key: '/admin/vouchers', icon: <GiftOutlined />, label: 'Voucher' },
            { key: '/admin/plans', icon: <ProfileOutlined />, label: 'Gói thuê bao' },
            { key: '/admin/invoices', icon: <TransactionOutlined />, label: 'Lịch sử thanh toán' },
        ],
    },
    {
        groupLabel: 'TRUYỀN THÔNG',
        items: [
            { key: '/admin/broadcasts', icon: <NotificationOutlined />, label: 'Broadcast' },
            { key: '/admin/announcements', icon: <SoundOutlined />, label: 'Popup thông báo' },
            { key: '/admin/desktop-backgrounds', icon: <PictureOutlined />, label: 'Hình nền Desktop' },
        ],
    },
    {
        groupLabel: 'CẤU HÌNH AI',
        items: [
            { key: '/admin/ai-providers', icon: <ApiOutlined />, label: 'Nhà cung cấp AI' },
            { key: '/admin/marketing-ai-providers', icon: <RiseOutlined />, label: 'AI Marketing' },
            { key: '/admin/ai-support', icon: <CustomerServiceOutlined />, label: 'AI Trợ giúp' },
            { key: '/admin/ai-visual-rerank', icon: <EyeOutlined />, label: 'AI chấm ảnh' },
            { key: '/admin/ai-transcription', icon: <AudioOutlined />, label: 'AI chuyển giọng nói' },
        ],
    },
    {
        groupLabel: 'HỆ THỐNG',
        items: [
            { key: '/admin/settings', icon: <SettingOutlined />, label: 'Cài đặt hệ thống' },
            { key: '/admin/notification-emails', icon: <MailOutlined />, label: 'Email thông báo' },
        ],
    },
    {
        groupLabel: 'HỖ TRỢ & GIÁM SÁT',
        items: [
            { key: '/admin/support-requests', icon: <SolutionOutlined />, label: 'Yêu cầu CSKH' },
            { key: '/admin/audit-logs', icon: <AuditOutlined />, label: 'Nhật ký' },
        ],
    },
];

const MENU_ITEMS: MenuProps['items'] = SIDEBAR.map((e) =>
    isGroup(e)
        ? {
            key: `group:${e.groupLabel}`,
            type: 'group' as const,
            label: e.groupLabel,
            children: e.items.map((i) => ({ key: i.key, icon: i.icon, label: i.label })),
        }
        : { key: e.key, icon: e.icon, label: e.label },
);

const ALL_LEAF_KEYS: string[] = SIDEBAR.flatMap((e) => (isGroup(e) ? e.items.map((i) => i.key) : [e.key]));

function findBreadcrumb(pathname: string): { groupLabel?: string; label: string } | null {
    // Longest-prefix-match (giống hệt `selected` bên dưới) — KHÔNG được match theo thứ tự khai
    // báo đầu tiên: entry đứng đầu `/admin` là tiền tố của MỌI route con khác, nên first-match-wins
    // sẽ luôn trả "Tổng quan" cho mọi trang.
    type Candidate = { key: string; groupLabel?: string; label: string };
    const candidates: Candidate[] = [];
    for (const e of SIDEBAR) {
        if (isGroup(e)) {
            for (const i of e.items) {
                candidates.push({ key: i.key, groupLabel: e.groupLabel, label: i.label });
            }
        } else {
            candidates.push({ key: e.key, label: e.label });
        }
    }
    const matches = candidates
        .filter((c) => pathname === c.key || pathname.startsWith(c.key + '/'))
        .sort((a, b) => b.key.length - a.key.length);

    return matches[0] ?? null;
}

export function AdminLayout() {
    const navigate = useNavigate();
    const loc = useLocation();
    const { data: me } = useAdminMe();
    const logout = useAdminLogout();

    // Chọn item match nhất theo prefix: vd /admin/tenants/123 → /admin/tenants.
    const selected = ALL_LEAF_KEYS
        .filter((k) => loc.pathname === k || loc.pathname.startsWith(k + '/'))
        .sort((a, b) => b.length - a.length)
        .slice(0, 1);

    const crumb = findBreadcrumb(loc.pathname);

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
                    items={MENU_ITEMS}
                    onClick={(e) => navigate(e.key)}
                />
            </Layout.Sider>
            <Layout>
                <Layout.Header style={{
                    background: '#fff',
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    padding: '0 24px',
                    borderBottom: '1px solid #E5E7EB',
                }}>
                    <Typography.Text strong style={{ fontSize: 15 }}>
                        {crumb ? (crumb.groupLabel ? `${crumb.groupLabel} / ${crumb.label}` : crumb.label) : ''}
                    </Typography.Text>
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
