// Hooks cho trang cấu hình AI Support (/admin/ai-support). KHÔNG đẻ endpoint mới —
// dùng lại /api/v1/admin/system-settings (6 key help_assistant.*). Credentials Support
// TỰ CHỨA, KHÔNG liên quan bảng ai_providers/registry của messaging.

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

// 6 key cấu hình trợ lý Hỏi AI (Support) — đúng tên trong SystemSettingsCatalog.
// api_key là secret ⇒ GET trả '****' khi đã đặt, null khi chưa.
export const SUPPORT_KEYS = {
    chatBaseUrl: 'help_assistant.chat_base_url',
    chatApiKey: 'help_assistant.chat_api_key',
    chatModel: 'help_assistant.chat_model',
    embeddingBaseUrl: 'help_assistant.embedding_base_url',
    embeddingApiKey: 'help_assistant.embedding_api_key',
    embeddingModel: 'help_assistant.embedding_model',
} as const;

export interface SupportAiConfig {
    chat_base_url: string;
    chat_api_key_set: boolean;   // secret: chỉ biết đã đặt hay chưa
    chat_model: string;
    embedding_base_url: string;
    embedding_api_key_set: boolean;
    embedding_model: string;
}

interface RawSettingRow { key: string; value: unknown }

export function useSupportAiConfig() {
    return useQuery({
        queryKey: ['support-ai-config'],
        queryFn: async (): Promise<SupportAiConfig> => {
            const rows = (await api.get<{ data: RawSettingRow[] }>('/admin/system-settings', { params: { group: 'ai' } })).data.data;
            const str = (k: string) => {
                const r = rows.find((x) => x.key === k);
                return r && r.value != null ? String(r.value) : '';
            };
            // Secret: backend mask thành '****' khi đã có giá trị.
            const secretSet = (k: string) => str(k) === '****';
            return {
                chat_base_url: str(SUPPORT_KEYS.chatBaseUrl),
                chat_api_key_set: secretSet(SUPPORT_KEYS.chatApiKey),
                chat_model: str(SUPPORT_KEYS.chatModel),
                embedding_base_url: str(SUPPORT_KEYS.embeddingBaseUrl),
                embedding_api_key_set: secretSet(SUPPORT_KEYS.embeddingApiKey),
                embedding_model: str(SUPPORT_KEYS.embeddingModel),
            };
        },
    });
}

/** Lưu 1 key system-setting. Giá trị rỗng ⇒ DELETE (xoá, về env/mặc định). */
export function useSaveSupportSetting() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ key, value }: { key: string; value: string }) => {
            if (value === '') {
                await api.delete(`/admin/system-settings/${encodeURIComponent(key)}`);
            } else {
                await api.patch(`/admin/system-settings/${encodeURIComponent(key)}`, { value });
            }
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['support-ai-config'] }),
    });
}
