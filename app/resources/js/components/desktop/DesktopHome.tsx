import { usePermittedApps, appColor } from '@/lib/desktop/appCatalog';
import { useDesktopShell } from '@/lib/desktop/desktopShellStore';
import { useUserPreferences } from '@/lib/preferences';

/**
 * Màn hình nền "Web Desktop" — hình nền (preset đã chọn hoặc gradient mặc định) +
 * lưới icon ứng dụng căn giữa kiểu hệ điều hành. Bấm icon ⇒ mở app thành tab.
 */
export function DesktopHome() {
    const openApp = useDesktopShell((s) => s.openApp);
    const apps = usePermittedApps();
    const { ui_desktop_bg } = useUserPreferences();

    // Ảnh preset: phủ lớp tối nhẹ để nhãn icon trắng vẫn đọc rõ. Không có ⇒ dùng gradient mặc định ở .desk-home.
    const bgStyle = ui_desktop_bg
        ? { background: `linear-gradient(rgba(8,18,48,0.28), rgba(8,18,48,0.42)), center/cover no-repeat url(${ui_desktop_bg})` }
        : undefined;

    return (
        <div className="desk-home" style={bgStyle}>
            <div className="desk-grid">
                {apps.map((app) => (
                    <button key={app.key} className="desk-icon" onClick={() => openApp(app.key, app.defaultPath)} title={app.label}>
                        <span className="desk-icon-badge" style={{ background: appColor(app.key).iconBg }}>{app.icon}</span>
                        <span className="desk-icon-label">{app.label}</span>
                    </button>
                ))}
            </div>
        </div>
    );
}
