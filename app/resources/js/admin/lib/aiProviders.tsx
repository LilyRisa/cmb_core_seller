// Hooks cho /api/v1/admin/ai-providers/* — quản lý đa nhà cung cấp AI (adapter động).
// code = slug instance tự do; adapter = loại API (anthropic | openai_compatible | manual).

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export type AiAdapter = 'anthropic' | 'openai_compatible' | 'manual';

export interface AiPreset {
    name: string;
    base_url: string | null;
    default_model: string | null;
}

export interface AiProviderRow {
    code: string;
    adapter: AiAdapter;
    display_name: string | null;
    has_api_key: boolean;
    base_url: string | null;
    default_model: string | null;
    pricing: Array<{ kind: string; unit: number; micro_vnd: number }>;
    is_active: boolean;
    sort_order?: number;
    notes?: string | null;
    capabilities: Record<string, boolean>;
    updated_at: string | null;
}

export interface AiProviderPayload {
    code?: string;
    adapter?: AiAdapter;
    display_name?: string | null;
    api_key?: string | null;
    base_url?: string | null;
    default_model?: string | null;
    pricing?: Array<{ kind: string; unit: number; micro_vnd: number }>;
    is_active?: boolean;
    sort_order?: number;
    notes?: string | null;
}

export interface AiProviderTestResult {
    ok: boolean;
    reason?: string;
    sample?: string;
    message?: string;
}

export function useAiProviders() {
    return useQuery({
        queryKey: ['ai-providers'],
        queryFn: async () =>
            (await api.get<{ data: AiProviderRow[]; adapters: { adapter: AiAdapter; presets: AiPreset[] }[] }>(
                '/admin/ai-providers',
            )).data,
    });
}

export function useCreateAiProvider() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (payload: AiProviderPayload) =>
            (await api.post<{ data: AiProviderRow }>('/admin/ai-providers', payload)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-providers'] }),
    });
}

export function useUpdateAiProvider() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ code, payload }: { code: string; payload: AiProviderPayload }) =>
            (await api.patch<{ data: AiProviderRow }>(`/admin/ai-providers/${encodeURIComponent(code)}`, payload)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-providers'] }),
    });
}

export function useDisableAiProvider() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (code: string) =>
            (await api.delete<{ data: { ok: boolean } }>(`/admin/ai-providers/${encodeURIComponent(code)}`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-providers'] }),
    });
}

export function useTestAiProvider() {
    return useMutation({
        mutationFn: async (code: string) =>
            (await api.post<{ data: AiProviderTestResult }>(`/admin/ai-providers/${encodeURIComponent(code)}/test`)).data.data,
    });
}
