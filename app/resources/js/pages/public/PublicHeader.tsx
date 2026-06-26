import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Button, Menu, Space } from 'antd';
import type { MenuProps } from 'antd';
import { AppstoreOutlined, ChromeOutlined, LoginOutlined, MobileOutlined } from '@ant-design/icons';
import { useAuth } from '@/lib/auth';

/** Header public: logo + menu (Trang chủ, Bảng giá, Tài liệu API, Phần mềm phụ trợ↓) + nút Đăng nhập / Vào ứng dụng. */
export function PublicHeader() {
    const { pathname } = useLocation();
    const navigate = useNavigate();
    const { data: user } = useAuth();
    const loggedIn = !!user;

    const items: MenuProps['items'] = [
        { key: '/', label: <Link to="/">Trang chủ</Link> },
        { key: '/pricing', label: <Link to="/pricing">Bảng giá</Link> },
        { key: '/api-docs', label: <Link to="/api-docs">Tài liệu API</Link> },
        {
            key: 'tools', icon: <AppstoreOutlined />, label: 'Phần mềm phụ trợ',
            children: [
                { key: '/tools-ext', icon: <ChromeOutlined />, label: <Link to="/tools#extension">Chrome extension</Link> },
                { key: '/download', icon: <MobileOutlined />, label: <Link to="/download">App mobile</Link> },
            ],
        },
    ];
    const selected = ['/', '/pricing', '/api-docs', '/tools'].find((k) => (k === '/' ? pathname === '/' : pathname.startsWith(k))) ?? '';

    return (
        <header style={{ display: 'flex', alignItems: 'center', gap: 24, padding: '0 24px', height: 64, borderBottom: '1px solid #f0f0f0', position: 'sticky', top: 0, zIndex: 10, background: '#fff' }}>
            <Link to="/" style={{ fontWeight: 700, fontSize: 18, color: '#1677ff', flexShrink: 0 }}>CMBcoreSeller</Link>
            <Menu mode="horizontal" selectedKeys={[selected]} items={items} style={{ flex: 1, borderBottom: 'none', minWidth: 0 }} />
            <Space>
                {loggedIn
                    ? <Button type="primary" icon={<LoginOutlined />} onClick={() => navigate('/dashboard')}>Truy cập</Button>
                    : (<>
                        <Button type="text" onClick={() => navigate('/register')}>Dùng thử</Button>
                        {/* "Truy cập" → vào dashboard; chưa đăng nhập sẽ qua login rồi vào thẳng. */}
                        <Button type="primary" icon={<LoginOutlined />} onClick={() => navigate('/dashboard')}>Truy cập</Button>
                    </>)}
            </Space>
        </header>
    );
}
