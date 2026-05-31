import { useMutation, useQuery } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/**
 * Data layer cho widget Trợ giúp (module Support): tab "Hỏi AI" (RAG hỏi-đáp cách
 * dùng hệ thống) + tab "Hỏi CSKH" (gửi câu hỏi vào hàng đợi chờ phản hồi).
 */

export interface HelpSource {
    title: string;
    module: string | null;
    screen: string | null;
    score: number;
}

export interface HelpAnswer {
    answer: string;
    sources: HelpSource[];
    /** rag | keyword | *_no_llm | no_docs | empty — để FE biết nguồn câu trả lời. */
    mode: string;
}

export interface ChatTurn {
    role: 'user' | 'assistant';
    content: string;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

/** Hỏi trợ lý AI (RAG). Gửi kèm vài lượt hội thoại gần nhất làm ngữ cảnh. */
export function useAskAssistant() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (input: { question: string; history?: ChatTurn[] }) => {
            const { data } = await api!.post<{ data: HelpAnswer }>('/support/assistant/ask', {
                question: input.question,
                history: input.history ?? [],
            });
            return data.data;
        },
    });
}

/** Gửi câu hỏi tới CSKH (tab "Hỏi CSKH"). */
export function useCreateSupportRequest() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (input: { question: string }) => {
            const { data } = await api!.post<{ data: { id: number; status: string; message: string } }>(
                '/support/requests', { question: input.question },
            );
            return data.data;
        },
    });
}

export interface SupportRequestItem {
    id: number;
    question: string;
    status: 'pending' | 'answered' | 'closed';
    answer: string | null;
    answered_at: string | null;
    created_at: string | null;
}

/**
 * Lịch sử yêu cầu CSKH của tenant hiện tại.
 *
 * Realtime = polling KHI `enabled` (codebase chưa có Reverb/Echo client; messaging cũng
 * dùng polling fallback). `intervalMs` cho phép caller chọn nhịp: widget luôn-mount poll
 * nhẹ TOÀN CỤC (để báo trả lời mới ngay cả khi đóng), tab CSKH đang mở poll nhanh hơn.
 * Cùng queryKey ⇒ React Query gộp observer, lấy interval nhỏ nhất. `enabled=false` ⇒ ngừng
 * poll. Lỗi (vd 402/403) ⇒ không retry & ngừng poll để khỏi spam request.
 */
export function useSupportRequests(enabled: boolean, intervalMs = 8_000) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['support', 'requests', tenantId],
        enabled: enabled && api != null,
        retry: false,
        queryFn: async () => (await api!.get<{ data: SupportRequestItem[] }>('/support/requests')).data.data,
        refetchInterval: (query) => (!enabled || query.state.status === 'error' ? false : intervalMs),
    });
}
