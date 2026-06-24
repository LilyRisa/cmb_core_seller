import { usePermittedApps } from '@/lib/desktop/appCatalog';
import { useDesktopShell } from '@/lib/desktop/desktopShellStore';

/**
 * Màn hình nền "Web Desktop" — hình nền gradient + lưới icon ứng dụng kiểu hệ điều hành.
 * Bấm icon ⇒ mở app thành tab (có animation mở ở DesktopShell). Dashboard nay là 1 app riêng,
 * không còn nhúng ở đây.
 */
export function DesktopHome() {
    const openApp = useDesktopShell((s) => s.openApp);
    const apps = usePermittedApps();
    return (
        <div className="desk-home">
            <div className="desk-grid">
                {apps.map((app) => (
                    <button key={app.key} className="desk-icon" onClick={() => openApp(app.key, app.defaultPath)} title={app.label}>
                        <span className="desk-icon-badge">{app.icon}</span>
                        <span className="desk-icon-label">{app.label}</span>
                    </button>
                ))}
            </div>
        </div>
    );
}
