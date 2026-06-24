import { create } from 'zustand';
import type { OpenTab } from '@/lib/preferences';

export const DESKTOP_KEY = 'desktop';

interface DesktopShellState {
    tabs: OpenTab[];
    activeKey: string;
    openApp: (appKey: string, path: string) => void;
    setActive: (key: string) => void;
    closeTab: (appKey: string) => void;
    setTabPath: (appKey: string, path: string) => void;
    hydrate: (tabs: OpenTab[], active: string | null) => void;
}

export const useDesktopShell = create<DesktopShellState>((set) => ({
    tabs: [],
    activeKey: DESKTOP_KEY,
    openApp: (appKey, path) => set((s) => {
        const existing = s.tabs.find((t) => t.appKey === appKey);
        if (existing) {
            return { activeKey: appKey, tabs: s.tabs.map((t) => (t.appKey === appKey ? { ...t, path } : t)) };
        }
        return { tabs: [...s.tabs, { appKey, path }], activeKey: appKey };
    }),
    setActive: (key) => set({ activeKey: key }),
    closeTab: (appKey) => set((s) => {
        const idx = s.tabs.findIndex((t) => t.appKey === appKey);
        const tabs = s.tabs.filter((t) => t.appKey !== appKey);
        let activeKey = s.activeKey;
        if (s.activeKey === appKey) {
            const left = idx > 0 ? tabs[idx - 1] : tabs[0];
            activeKey = left ? left.appKey : DESKTOP_KEY;
        }
        return { tabs, activeKey };
    }),
    setTabPath: (appKey, path) => set((s) => ({
        tabs: s.tabs.map((t) => (t.appKey === appKey ? { ...t, path } : t)),
    })),
    hydrate: (tabs, active) => set({ tabs, activeKey: active && tabs.some((t) => t.appKey === active) ? active : DESKTOP_KEY }),
}));
