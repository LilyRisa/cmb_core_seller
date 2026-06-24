import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api, ensureCsrf } from './api';
import { useAuth, type AuthUser } from './auth';

export type OpenTab = { appKey: string; path: string };
export interface UiPreferences {
    ui_shell: 'v1' | 'v2';
    ui_open_tabs: OpenTab[];
    ui_active_tab: string | null;
    /** SPEC 0039 — URL hình nền Desktop đã chọn; null = gradient mặc định. */
    ui_desktop_bg: string | null;
}

const DEFAULTS: UiPreferences = { ui_shell: 'v1', ui_open_tabs: [], ui_active_tab: null, ui_desktop_bg: null };

/** Đọc preference giao diện từ `me` (đã kèm trong payload auth). */
export function useUserPreferences(): UiPreferences {
    const { data: user } = useAuth();
    return { ...DEFAULTS, ...(user?.preferences ?? {}) };
}

/** Ghi preference (PUT /me/preferences) và cập nhật cache `me` để FE phản ứng ngay. */
export function useUpdatePreferences() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (patch: Partial<UiPreferences>) => {
            await ensureCsrf();
            const { data } = await api.put<{ data: UiPreferences }>('/me/preferences', patch);
            return data.data;
        },
        onSuccess: (prefs) => {
            qc.setQueryData<AuthUser | null>(['me'], (prev) => (prev ? { ...prev, preferences: prefs } : prev));
        },
    });
}
