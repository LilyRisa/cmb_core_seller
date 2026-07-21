// Hooks cho /api/v1/admin/ai-providers/* — quản lý đa nhà cung cấp AI (adapter động).
// code = slug instance tự do; adapter = loại API (anthropic | openai_compatible | manual).

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export type AiAdapter = 'anthropic' | 'openai_compatible' | 'custom_http' | 'manual';
export type AiRole = 'chat' | 'vision' | 'transcription';

export interface AiPreset {
    name: string;
    base_url: string | null;
    default_model: string | null;
}

/** Cấu hình HTTP tùy chỉnh (adapter custom_http — SPEC-0026). */
export interface CustomHttpConfig {
    method?: 'POST' | 'PUT' | 'GET';
    headers?: Record<string, string>;
    request_template?: string;
    response_path?: string;
    usage?: { prompt_path?: string; completion_path?: string };
}

export interface AiProviderRow {
    code: string;
    adapter: AiAdapter;
    role: AiRole;
    display_name: string | null;
    has_api_key: boolean;
    /** Plaintext key (đã giải mã) — trang admin hiển thị thẳng trong form sửa. */
    api_key: string | null;
    base_url: string | null;
    default_model: string | null;
    pricing: Array<{ kind: string; unit: number; micro_vnd: number }>;
    adapter_config?: CustomHttpConfig | null;
    is_active: boolean;
    sort_order?: number;
    notes?: string | null;
    capabilities: Record<string, boolean>;
    updated_at: string | null;
}

export interface AiProviderPayload {
    code?: string;
    adapter?: AiAdapter;
    role?: AiRole;
    display_name?: string | null;
    api_key?: string | null;
    base_url?: string | null;
    default_model?: string | null;
    pricing?: Array<{ kind: string; unit: number; micro_vnd: number }>;
    adapter_config?: CustomHttpConfig | null;
    is_active?: boolean;
    sort_order?: number;
    notes?: string | null;
}

export interface AiCapabilityResult {
    ok: boolean;
    sample?: string;
    dimension?: number;
    model?: string;
    reason?: string;
    message?: string;
}

export interface AiProviderTestResult {
    ok: boolean;
    reason?: string;
    sample?: string;
    message?: string;
    /** Kết quả từng năng lực đã test (chat = sinh reply, embedding = vector RAG). */
    results?: { chat?: AiCapabilityResult; embedding?: AiCapabilityResult };
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

export interface AiProviderDraftTestResult {
    ok: boolean;
    message?: string;
}

export interface AiProviderDraftTestPayload {
    adapter: AiAdapter;
    base_url: string | null;
    api_key: string | null;
    default_model: string | null;
}

/** Test kết nối bằng credentials ĐANG NHẬP trên form (chưa lưu) — chỉ hỗ trợ adapter
 * anthropic/openai_compatible (custom_http/manual không gate, xem AdminAiProvidersPage). */
export function useTestAiProviderDraft() {
    return useMutation({
        mutationFn: async (payload: AiProviderDraftTestPayload) =>
            (await api.post<{ data: AiProviderDraftTestResult }>('/admin/ai-providers/test-draft', payload)).data.data,
    });
}
