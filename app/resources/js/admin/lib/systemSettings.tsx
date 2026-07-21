// Spec 2026-05-17 — hooks cho `/api/v1/admin/system-settings/*`.

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export type SettingGroup = 'branding' | 'mail' | 'marketplace' | 'fulfillment' | 'sync' | 'push' | 'ai' | 'growth';

export type SettingType = 'string' | 'int' | 'bool' | 'float' | 'json';

export interface SettingRow {
    key: string;
    group: SettingGroup;
    type: SettingType;
    is_secret: boolean;
    label: string;
    description: string | null;
    env_fallback: string | null;
    value: unknown;
    updated_at: string | null;
    updated_by_admin_id: number | null;
}

export function useSystemSettings(group?: SettingGroup) {
    return useQuery({
        queryKey: ['system-settings', group ?? 'all'],
        queryFn: async () =>
            (await api.get<{ data: SettingRow[] }>('/admin/system-settings', { params: { group } })).data.data,
    });
}

export function useUpdateSetting() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ key, value }: { key: string; value: unknown }) =>
            (await api.patch<{ data: { key: string; updated_at: string | null } }>(
                `/admin/system-settings/${encodeURIComponent(key)}`,
                { value },
            )).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['system-settings'] }),
    });
}

export function useDeleteSetting() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (key: string) =>
            (await api.delete<{ data: { ok: boolean } }>(`/admin/system-settings/${encodeURIComponent(key)}`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['system-settings'] }),
    });
}

export async function revealSetting(key: string): Promise<string | null> {
    const r = await api.get<{ data: { key: string; value: string | null } }>(
        `/admin/system-settings/${encodeURIComponent(key)}/reveal`,
    );
    return r.data.data.value;
}

export function useSyncFromEnv() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async () =>
            (await api.post<{ data: { created: number } }>('/admin/system-settings/sync-from-env')).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['system-settings'] }),
    });
}
