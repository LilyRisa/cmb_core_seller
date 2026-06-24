import { Tabs } from 'antd';
import { AppstoreOutlined, CloseOutlined } from '@ant-design/icons';
import { APP_CATALOG, appColor } from '@/lib/desktop/appCatalog';
import { useDesktopShell, DESKTOP_KEY } from '@/lib/desktop/desktopShellStore';

export function TabStrip() {
    const { tabs, activeKey, setActive, closeTab } = useDesktopShell();
    const items = [
        { key: DESKTOP_KEY, label: <span><AppstoreOutlined style={{ color: '#2563EB' }} /> Desktop</span>, closable: false },
        ...tabs.map((t) => {
            const app = APP_CATALOG.find((a) => a.key === t.appKey);
            return {
                key: t.appKey,
                label: <span><span style={{ color: appColor(t.appKey).color }}>{app?.icon}</span> {app?.label ?? t.appKey}</span>,
                closable: true,
            };
        }),
    ];
    return (
        <Tabs
            type="editable-card" hideAdd size="small"
            activeKey={activeKey}
            onChange={setActive}
            onEdit={(key, action) => { if (action === 'remove' && typeof key === 'string') closeTab(key); }}
            items={items}
            style={{ padding: '4px 8px 0', background: '#fff', borderBottom: '1px solid #f0f0f0' }}
            removeIcon={<CloseOutlined />}
        />
    );
}
