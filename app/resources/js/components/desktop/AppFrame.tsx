import { useMemo } from 'react';
import { Link, Routes, useLocation } from 'react-router-dom';
import { Layout, Menu } from 'antd';
import type { MenuProps } from 'antd';
import type { AppDef } from '@/lib/desktop/appCatalog';
import { appRouteElements } from '@/routes/appRoutes';

const { Sider, Content } = Layout;

function toItems(app: AppDef): MenuProps['items'] {
    return app.menu.map((m) => m.children
        ? { key: m.key, label: m.label, children: m.children.map((c) => ({ key: c.key, label: <Link to={c.key}>{c.label}</Link> })) }
        : { key: m.key, label: <Link to={m.key}>{m.label}</Link> });
}

const flatKeys = (app: AppDef): string[] =>
    app.menu.flatMap((m) => (m.children ? m.children.map((c) => c.key) : [m.key]));

export function AppFrame({ app }: { app: AppDef }) {
    const location = useLocation();
    const items = useMemo(() => toItems(app), [app]);
    const selectedKey = useMemo(() => {
        const keys = flatKeys(app);
        return keys.filter((k) => location.pathname === k || location.pathname.startsWith(k + '/'))
            .sort((a, b) => b.length - a.length)[0] ?? keys[0];
    }, [app, location.pathname]);

    return (
        <Layout className="desk-window" style={{ height: '100%' }}>
            <Sider className="desk-window-sider" theme="light" width={220} style={{ overflowY: 'auto' }}>
                <Menu mode="inline" selectedKeys={[selectedKey]} defaultOpenKeys={[]} items={items} style={{ borderInlineEnd: 'none' }} />
            </Sider>
            <Content className="desk-window-content">
                <Routes>{appRouteElements()}</Routes>
            </Content>
        </Layout>
    );
}
