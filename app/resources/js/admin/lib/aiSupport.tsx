// Hooks cho trang cấu hình AI Support (/admin/ai-support). KHÔNG đẻ endpoint mới —
// dùng lại /api/v1/admin/system-settings (4 key help_assistant.*) + /admin/ai-providers
// để liệt kê provider. Tách riêng khỏi SystemSettingsPage cho gọn, dễ mở rộng.

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

// 4 key cấu hình trợ lý Hỏi AI (Support) — đúng tên trong SystemSettingsCatalog.
export const SUPPORT_KEYS = {
    chatProvider: 'help_assistant.provider_code',
    embeddingProvider: 'help_assistant.embedding_provider_code',
    embeddingModel: 'help_assistant.embedding_model',
} as const;

export interface SupportAiConfig {
    chat_provider_code: string;
    embedding_provider_code: string;
    embedding_model: string;
}

interface RawSettingRow { key: string; value: unknown }

/** Đọc 4 setting help_assistant.* (lọc từ group 'ai'). */
export function useSupportAiConfig() {
    return useQuery({
        queryKey: ['support-ai-config'],
        queryFn: async (): Promise<SupportAiConfig> => {
            const rows = (await api.get<{ data: RawSettingRow[] }>('/admin/system-settings', { params: { group: 'ai' } })).data.data;
            const val = (k: string) => {
                const r = rows.find((x) => x.key === k);
                return r && r.value != null ? String(r.value) : '';
            };
            return {
                chat_provider_code: val(SUPPORT_KEYS.chatProvider),
                embedding_provider_code: val(SUPPORT_KEYS.embeddingProvider),
                embedding_model: val(SUPPORT_KEYS.embeddingModel),
            };
        },
    });
}

/** Lưu 1 key system-setting. Giá trị rỗng ⇒ DELETE (khôi phục về env/mặc định). */
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
