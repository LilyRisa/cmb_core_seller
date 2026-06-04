// Hooks cho /api/v1/admin/marketing-ai-providers/* — provider AI RIÊNG cho phân tích
// marketing (dự báo quảng cáo), tách hoàn toàn với AI messaging. SPEC 2026-06-04.

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export type MarketingAiAdapter = 'anthropic' | 'openai_compatible' | 'manual';

export interface MarketingAiProviderRow {
    code: string;
    display_name: string | null;
    adapter: MarketingAiAdapter;
    base_url: string | null;
    default_model: string | null;
    is_active: boolean;
    has_key: boolean;
}

export interface MarketingAiProviderInput {
    code: string;
    display_name?: string | null;
    adapter: MarketingAiAdapter;
    api_key?: string | null;
    base_url?: string | null;
    default_model?: string | null;
    is_active?: boolean;
}

export function useMarketingAiProviders() {
    return useQuery({
        queryKey: ['admin', 'marketing-ai-providers'],
        queryFn: async () => (await api.get<{ data: MarketingAiProviderRow[] }>('/admin/marketing-ai-providers')).data.data,
    });
}

export function useSaveMarketingAiProvider() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ input, isNew }: { input: MarketingAiProviderInput; isNew: boolean }) => {
            if (isNew) return (await api.post('/admin/marketing-ai-providers', input)).data;
            return (await api.patch(`/admin/marketing-ai-providers/${input.code}`, input)).data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'marketing-ai-providers'] }),
    });
}

export function useDeleteMarketingAiProvider() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (code: string) => { await api.delete(`/admin/marketing-ai-providers/${code}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'marketing-ai-providers'] }),
    });
}
