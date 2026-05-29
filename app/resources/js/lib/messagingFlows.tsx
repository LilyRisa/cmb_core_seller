import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';
import type { Paginated } from './orders';

/**
 * Hooks cho Kịch bản tự động (Flow Builder S3). Gọi `/api/v1/messaging/automation-flows`.
 * `graph` do canvas (reactflow) sinh; node mang thêm `position` cho layout — engine
 * bỏ qua field này, chỉ đọc id/type/data.
 */

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export type FlowStatus = 'draft' | 'active' | 'paused' | 'archived';

export type FlowTriggerType =
    | 'comment_on_post'
    | 'comment_any'
    | 'inbox_first_message'
    | 'inbox_keyword'
    | 'inbox_any';

export type FlowNodeType = 'trigger' | 'send_message' | 'send_buttons' | 'wait_reply' | 'condition' | 'ai_reply' | 'end';

export interface FlowButton {
    id: string;
    label: string;
    type: 'postback' | 'url';
    url?: string;
}

export type FlowMediaKind = 'image' | 'video' | 'audio' | 'file';

export interface FlowAttachment {
    kind: FlowMediaKind;
    storage_path: string;
    mime?: string;
    filename?: string | null;
    size_bytes?: number | null;
}

export interface FlowNodeData {
    text?: string;
    attachments?: FlowAttachment[];
    buttons?: FlowButton[];
    keywords?: string[];
    match?: 'any' | 'all';
    [k: string]: unknown;
}

export interface FlowNode {
    id: string;
    type: FlowNodeType;
    position: { x: number; y: number };
    data: FlowNodeData;
}

export interface FlowEdge {
    id: string;
    source: string;
    target: string;
    sourceHandle?: string | null;
}

export interface FlowGraph {
    nodes: FlowNode[];
    edges: FlowEdge[];
}

export interface AutomationFlow {
    id: number;
    name: string;
    provider: string;
    status: FlowStatus;
    trigger_type: FlowTriggerType;
    trigger_config: Record<string, unknown>;
    graph: FlowGraph;
    version: number;
    enabled: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface FlowValidationError {
    node_id?: string;
    code: string;
    message: string;
}

export type FlowSavePayload = Partial<Omit<AutomationFlow, 'id'>> & { id?: number };

const KEY = 'flows';

export function useFlows() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', KEY, tenantId],
        enabled: api != null,
        queryFn: async () => (await api!.get<Paginated<AutomationFlow>>('/messaging/automation-flows')).data,
    });
}

export function useFlow(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['messaging', 'flow', id, tenantId],
        enabled: api != null && id != null,
        queryFn: async () => (await api!.get<{ data: AutomationFlow }>(`/messaging/automation-flows/${id}`)).data.data,
    });
}

export function useSaveFlow() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (f: FlowSavePayload) => {
            const res = f.id
                ? await api!.patch<{ data: AutomationFlow }>(`/messaging/automation-flows/${f.id}`, f)
                : await api!.post<{ data: AutomationFlow }>('/messaging/automation-flows', f);
            return res.data.data;
        },
        onSuccess: (flow) => {
            qc.invalidateQueries({ queryKey: ['messaging', KEY] });
            qc.invalidateQueries({ queryKey: ['messaging', 'flow', flow.id] });
        },
    });
}

export function useDeleteFlow() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => { await api!.delete(`/messaging/automation-flows/${id}`); },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', KEY] }),
    });
}

export function useDuplicateFlow() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: AutomationFlow }>(`/messaging/automation-flows/${id}/duplicate`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['messaging', KEY] }),
    });
}

export function usePublishFlow() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: AutomationFlow }>(`/messaging/automation-flows/${id}/publish`)).data.data,
        onSuccess: (flow) => {
            qc.invalidateQueries({ queryKey: ['messaging', KEY] });
            qc.invalidateQueries({ queryKey: ['messaging', 'flow', flow.id] });
        },
    });
}

export function usePauseFlow() {
    const api = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: AutomationFlow }>(`/messaging/automation-flows/${id}/pause`)).data.data,
        onSuccess: (flow) => {
            qc.invalidateQueries({ queryKey: ['messaging', KEY] });
            qc.invalidateQueries({ queryKey: ['messaging', 'flow', flow.id] });
        },
    });
}

export function useValidateFlow() {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api!.post<{ data: { valid: boolean; errors: FlowValidationError[] } }>(
                `/messaging/automation-flows/${id}/validate`,
            )).data.data,
    });
}

/** Upload media (ảnh/video/âm thanh/file) cho 1 node Gửi tin; trả descriptor nhúng vào node.data. */
export function useUploadFlowMedia(flowId: number) {
    const api = useScopedApi();
    return useMutation({
        mutationFn: async (input: { file: File; kind: FlowMediaKind }) => {
            const form = new FormData();
            form.append('file', input.file);
            form.append('kind', input.kind);
            return (await api!.post<{ data: FlowAttachment }>(`/messaging/automation-flows/${flowId}/media`, form)).data.data;
        },
    });
}

/** Suy ra loại media từ MIME của file (cho uploader). */
export function mediaKindFromMime(mime: string): FlowMediaKind {
    if (mime.startsWith('image/')) return 'image';
    if (mime.startsWith('video/')) return 'video';
    if (mime.startsWith('audio/')) return 'audio';
    return 'file';
}

/** Nhãn tiếng Việt cho loại trigger (hiển thị danh sách + topbar). */
export const TRIGGER_LABELS: Record<FlowTriggerType, string> = {
    inbox_first_message: 'Tin nhắn đầu tiên',
    inbox_keyword: 'Tin nhắn chứa từ khoá',
    inbox_any: 'Mọi tin nhắn',
    comment_any: 'Mọi bình luận',
    comment_on_post: 'Bình luận trên bài viết',
};

export const STATUS_LABELS: Record<FlowStatus, string> = {
    draft: 'Nháp',
    active: 'Đang chạy',
    paused: 'Tạm dừng',
    archived: 'Lưu trữ',
};
