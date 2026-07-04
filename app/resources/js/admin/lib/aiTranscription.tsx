// Hooks trang "AI chuyển giọng nói (STT)" (/admin/ai-transcription). Chọn provider AI
// RIÊNG cho bước transcribe voice message, tách khỏi model chat. Endpoint:
// /api/v1/admin/ai-transcription.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface SttProvider {
    code: string;
    display_name: string | null;
    default_model: string | null;
    is_active: boolean;
    transcription_verified: boolean | null;
    transcription_verified_at: string | null;
    transcription_verify_error: string | null;
}

export interface SttConfig {
    selected_provider_code: string | null;
    providers: SttProvider[];
}

export function useTranscriptionConfig() {
    return useQuery({
        queryKey: ['ai-transcription-config'],
        queryFn: async (): Promise<SttConfig> =>
            (await api.get<{ data: SttConfig }>('/admin/ai-transcription')).data.data,
    });
}

export function useSaveTranscription() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (providerCode: string) => {
            await api.put('/admin/ai-transcription', { provider_code: providerCode });
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-transcription-config'] }),
    });
}

export interface SttTestResult {
    ok: boolean;
    text?: string;
    reason?: string;
    message?: string;
}

export function useTestTranscription() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (providerCode: string): Promise<SttTestResult> =>
            (await api.post<{ data: SttTestResult }>('/admin/ai-transcription/test', { provider_code: providerCode })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-transcription-config'] }),
    });
}
