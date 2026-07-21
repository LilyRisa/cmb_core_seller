import { Link, useNavigate } from 'react-router-dom';
import { Avatar, Button, Dropdown, Space, Tooltip, Typography } from 'antd';
import {
    ChromeOutlined, LogoutOutlined, MobileOutlined, SettingOutlined, ShopOutlined, UserOutlined,
} from '@ant-design/icons';
import { Select } from 'antd';
import { getCurrentTenantId, setCurrentTenantId, useAuth, useLogout } from '@/lib/auth';
import { NotificationBell } from '@/components/NotificationBell';
import { HeaderBillingActions } from '@/components/HeaderBillingActions';
import { CHROME_EXTENSION_URL } from '@/lib/extension';

export function AppHeader({ left, onOpenSettings, className }: { left?: React.ReactNode; onOpenSettings?: () => void; className?: string }) {
    const { data: user } = useAuth();
    const logout = useLogout();
    const navigate = useNavigate();
    const currentTenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;

    const settingsItem = onOpenSettings
        ? { key: 'settings', icon: <SettingOutlined />, label: 'Cài đặt', onClick: onOpenSettings }
        : { key: 'settings', icon: <SettingOutlined />, label: <Link to="/settings/members">Cài đặt</Link> };

    return (
        <div className={className} style={{ background: '#fff', display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', rowGap: 8, padding: '8px 16px 8px 8px', borderBottom: '1px solid #f0f0f0', minHeight: 56 }}>
            <Space wrap>
                {left}
                <ShopOutlined style={{ color: '#8c8c8c' }} />
                <Select
                    size="middle" variant="borderless" style={{ minWidth: 200, fontWeight: 500 }}
                    value={currentTenantId ?? undefined}
                    options={(user?.tenants ?? []).map((t) => ({ value: t.id, label: `${t.name} · ${t.role}` }))}
                    onChange={(v) => { setCurrentTenantId(v); navigate(0); }}
                />
            </Space>
            <Space size="middle" wrap>
                <HeaderBillingActions />
                <Tooltip title="Cài tiện ích Chrome để sao chép sản phẩm">
                    <Button type="text" href={CHROME_EXTENSION_URL} target="_blank" icon={<ChromeOutlined />} />
                </Tooltip>
                <Tooltip title="Tải ứng dụng di động">
                    <Button type="text" href="/download" target="_blank" icon={<MobileOutlined />} />
                </Tooltip>
                <NotificationBell />
                <Dropdown menu={{ items: [
                    { key: 'who', disabled: true, label: <span>{user?.name}<br /><Typography.Text type="secondary" style={{ fontSize: 12 }}>{user?.email}</Typography.Text></span> },
                    { type: 'divider' },
                    settingsItem,
                    { key: 'logout', icon: <LogoutOutlined />, label: 'Đăng xuất', onClick: () => logout.mutate(undefined, { onSuccess: () => navigate('/login') }) },
                ] }}>
                    <Space style={{ cursor: 'pointer' }}>
                        <Avatar size="small" style={{ background: 'linear-gradient(135deg, #2563EB 0%, #1E40AF 100%)' }} icon={<UserOutlined />} />
                        <span style={{ fontWeight: 500 }}>{user?.name}</span>
                    </Space>
                </Dropdown>
            </Space>
        </div>
    );
}
