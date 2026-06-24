import { useEffect, useRef } from 'react';
import { MemoryRouter, useLocation, UNSAFE_LocationContext as LocationContext } from 'react-router-dom';
import { Layout } from 'antd';
import { AppHeader } from '@/components/AppHeader';
import { TabStrip } from '@/components/desktop/TabStrip';
import { DesktopHome } from '@/components/desktop/DesktopHome';
import { AppFrame } from '@/components/desktop/AppFrame';
import { APP_CATALOG, appForPath, usePermittedApps } from '@/lib/desktop/appCatalog';
import { useDesktopShell, DESKTOP_KEY } from '@/lib/desktop/desktopShellStore';
import { useUserPreferences, useUpdatePreferences } from '@/lib/preferences';
import { useAuth } from '@/lib/auth';
import { useCan } from '@/lib/tenant';
import { useGlobalMessageNotifications } from '@/lib/useMessageNotifications';
import { useNotificationsRealtime } from '@/lib/notifications';
import { OverQuotaBanner } from '@/components/OverQuotaBanner';
import { HelpChatWidget } from '@/components/support/HelpChatWidget';
import { AnnouncementPopup } from '@/components/AnnouncementPopup';

/** Cầu nối: tab active mirror path nội bộ ra URL trình duyệt + báo path cho store (persist). */
function TabBridge({ appKey, active }: { appKey: string; active: boolean }) {
    const location = useLocation();
    const setTabPath = useDesktopShell((s) => s.setTabPath);
    useEffect(() => {
        const full = location.pathname + location.search;
        setTabPath(appKey, full);
        if (active) window.history.replaceState(null, '', full);
    }, [appKey, active, location.pathname, location.search, setTabPath]);
    return null;
}

export function DesktopShell() {
    const { isFetched: meFetched } = useAuth();
    const prefs = useUserPreferences();
    const update = useUpdatePreferences();
    const { tabs, activeKey, hydrate, openApp, setActive } = useDesktopShell();
    const hydrated = useRef(false);

    // Fix 1 — lọc tab theo quyền khi hydrate (SPEC §3).
    const permitted = usePermittedApps();
    // Fix 3 — thông báo tin nhắn mới toàn cục + realtime chuông (parity v1).
    useGlobalMessageNotifications(useCan('messaging.view'));
    useNotificationsRealtime();

    // Hydrate một lần từ preference; nếu rỗng, seed từ URL hiện tại.
    // Guard: chỉ chạy sau khi query `me` đã resolve để tránh ghi đè tab đã lưu bằng giá trị mặc định [].
    useEffect(() => {
        if (hydrated.current) return;
        if (!meFetched) return;
        hydrated.current = true;
        if (prefs.ui_open_tabs.length) {
            const allowed = new Set(permitted.map((a) => a.key));
            const restorable = prefs.ui_open_tabs.filter((t) => allowed.has(t.appKey));
            hydrate(restorable, prefs.ui_active_tab);
        } else {
            const app = appForPath(window.location.pathname);
            if (app) openApp(app.key, window.location.pathname + window.location.search);
            else setActive(DESKTOP_KEY);
        }
    }, [meFetched, permitted, prefs.ui_open_tabs, prefs.ui_active_tab, hydrate, openApp, setActive]);

    // Persist (debounce) khi tabs/active đổi.
    useEffect(() => {
        if (!hydrated.current) return;
        const id = setTimeout(() => {
            update.mutate({ ui_open_tabs: tabs, ui_active_tab: activeKey === DESKTOP_KEY ? null : activeKey });
        }, 800);
        return () => clearTimeout(id);
    }, [tabs, activeKey]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <AppHeader
                left={<img src="/images/logocmb.png" alt="CMB Core" style={{ width: 28, height: 28, objectFit: 'contain' }} />}
                onOpenSettings={() => openApp('settings', '/settings/profile')}
            />
            <TabStrip />
            {/* Fix 2 — SPEC 0020: banner over-quota hiện trên mọi tab. */}
            <OverQuotaBanner />
            <div style={{ position: 'relative', flex: 1, minHeight: 0 }}>
                {/* Desktop home */}
                <div style={{ position: 'absolute', inset: 0, overflow: 'auto', display: activeKey === DESKTOP_KEY ? 'block' : 'none' }}>
                    <DesktopHome />
                </div>
                {/* Mỗi tab = MemoryRouter độc lập, keep-alive bằng display. */}
                {tabs.map((t) => {
                    const app = APP_CATALOG.find((a) => a.key === t.appKey);
                    if (!app) return null;
                    return (
                        <div key={t.appKey} className="desk-app-panel" style={{ position: 'absolute', inset: 0, display: activeKey === t.appKey ? 'block' : 'none' }}>
                            {/* Reset LocationContext về null ngay trên MemoryRouter của tab: React Router cấm
                                lồng <Router> trong <Router> (invariant đọc useContext(LocationContext) != null).
                                Mỗi tab cần history riêng nên dùng MemoryRouter độc lập — đặt context = null để
                                qua được invariant mà vẫn giữ keep-alive. Xem react-router useInRouterContext(). */}
                            <LocationContext.Provider value={null as never}>
                                <MemoryRouter initialEntries={[t.path]}>
                                    <TabBridge appKey={t.appKey} active={activeKey === t.appKey} />
                                    <AppFrame app={app} />
                                </MemoryRouter>
                            </LocationContext.Provider>
                        </div>
                    );
                })}
            </div>
            {/* Fix 2 — widget trợ giúp nổi + popup thông báo admin (parity v1 AppLayout). */}
            <HelpChatWidget />
            <AnnouncementPopup />
        </Layout>
    );
}
