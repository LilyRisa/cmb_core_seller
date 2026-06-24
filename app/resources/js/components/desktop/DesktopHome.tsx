import { Card, Typography } from 'antd';
import { usePermittedApps } from '@/lib/desktop/appCatalog';
import { useDesktopShell } from '@/lib/desktop/desktopShellStore';
import { DashboardPage } from '@/pages/DashboardPage';

export function DesktopHome() {
    const openApp = useDesktopShell((s) => s.openApp);
    const apps = usePermittedApps();
    return (
        <div style={{ padding: 24 }}>
            <Typography.Title level={4} style={{ marginBottom: 16 }}>Ứng dụng</Typography.Title>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(132px, 1fr))', gap: 16, marginBottom: 32 }}>
                {apps.map((app) => (
                    <Card key={app.key} hoverable styles={{ body: { padding: 20, textAlign: 'center' } }}
                        onClick={() => openApp(app.key, app.defaultPath)}>
                        <div style={{ fontSize: 32, color: '#2563EB', marginBottom: 8 }}>{app.icon}</div>
                        <div style={{ fontWeight: 500 }}>{app.label}</div>
                    </Card>
                ))}
            </div>
            <Typography.Title level={4} style={{ marginBottom: 16 }}>Tổng quan</Typography.Title>
            <DashboardPage />
        </div>
    );
}
