// Hooks trang "AI chấm ảnh" (/admin/ai-visual-rerank). Chọn provider AI RIÊNG cho
// bước vision re-rank, tách khỏi model chat. Endpoint: /api/v1/admin/ai-visual-rerank.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface RerankProvider {
    code: string;
    display_name: string | null;
    default_model: string | null;
    is_active: boolean;
    vision: boolean;
}

export interface RerankConfig {
    selected_provider_code: string | null;
    providers: RerankProvider[];
}

export function useVisualRerank() {
    return useQuery({
        queryKey: ['visual-rerank-config'],
        queryFn: async (): Promise<RerankConfig> =>
            (await api.get<{ data: RerankConfig }>('/admin/ai-visual-rerank')).data.data,
    });
}

export function useSaveVisualRerank() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (providerCode: string) => {
            await api.put('/admin/ai-visual-rerank', { provider_code: providerCode });
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['visual-rerank-config'] }),
    });
}

export interface RerankTestResult {
    ok: boolean;
    sample?: string;
    reason?: string;
    message?: string;
}

export function useTestVisualRerank() {
    return useMutation({
        mutationFn: async (providerCode: string): Promise<RerankTestResult> =>
            (await api.post<{ data: RerankTestResult }>('/admin/ai-visual-rerank/test', { provider_code: providerCode })).data.data,
    });
}
