import { useMemo } from 'react';
import { useMutation, useQueries, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';
import {
    createPromotion,
    deletePromotion,
    endPromotion,
    getBusyPromos,
    getPromotion,
    getPromotionCapabilities,
    listPromotions,
    pushPromotion,
    setPromotionSkus,
    syncPromotions,
    updatePromotion,
    type CreatePromotionPayload,
    type Promotion,
    type PromotionSku,
    type UpdatePromotionPayload,
} from './api';

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function usePromotions(channelAccountId: number | null, tab: 'pushed' | 'draft') {
    const client = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['promotions', tenantId, channelAccountId, tab],
        enabled: client != null && channelAccountId != null,
        queryFn: () => listPromotions(client!, channelAccountId!, tab),
    });
}

export function usePromotion(id: number | null) {
    const client = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['promotion', tenantId, id],
        enabled: client != null && id != null,
        queryFn: () => getPromotion(client!, id!),
        staleTime: 0,
    });
}

export function usePromotionCapabilities(provider: string | null) {
    const client = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['promotion-caps', tenantId, provider],
        enabled: client != null && !!provider,
        queryFn: () => getPromotionCapabilities(client!, provider!),
        staleTime: 60 * 60 * 1000,
    });
}

/**
 * Tập provider hỗ trợ "chiến dịch giảm giá" riêng (có đối tượng chương trình trên sàn). Sàn giảm giá
 * trực tiếp trên SKU (Lazada: has_program_object=false) bị loại — KHÔNG hardcode tên sàn, lọc theo năng lực.
 * Trong lúc tải caps coi như chưa hỗ trợ (ẩn) để không bao giờ lỡ hiện sàn không hợp lệ.
 */
export function useCampaignProviders(providers: string[]) {
    const client = useScopedApi();
    const tenantId = useCurrentTenantId();
    const distinct = useMemo(() => Array.from(new Set(providers)).sort(), [providers]);
    const results = useQueries({
        queries: distinct.map((p) => ({
            queryKey: ['promotion-caps', tenantId, p],
            enabled: client != null,
            queryFn: () => getPromotionCapabilities(client!, p),
            staleTime: 60 * 60 * 1000,
        })),
    });
    return useMemo(() => {
        const capable = new Set<string>();
        distinct.forEach((p, i) => {
            if (results[i]?.data?.has_program_object) capable.add(p);
        });
        return { capable, isLoading: results.some((r) => r.isLoading) };
    }, [distinct, results]);
}

export function useBusyPromos(channelAccountId: number | null, exceptPromotionId?: number) {
    const client = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['promotion-busy', tenantId, channelAccountId, exceptPromotionId],
        enabled: client != null && channelAccountId != null,
        queryFn: () => getBusyPromos(client!, channelAccountId!, exceptPromotionId),
    });
}

export function useCreatePromotion() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (payload: CreatePromotionPayload) => createPromotion(client!, payload),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['promotions'] }),
    });
}

export function useUpdatePromotion() {
    const client = useScopedApi();
    return useMutation({
        mutationFn: (v: { id: number; payload: UpdatePromotionPayload }) => updatePromotion(client!, v.id, v.payload),
    });
}

export function useSetPromotionSkus() {
    const client = useScopedApi();
    return useMutation({
        mutationFn: (v: { id: number; skus: PromotionSku[] }) => setPromotionSkus(client!, v.id, v.skus),
    });
}

export function usePushPromotion() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => pushPromotion(client!, id),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['promotions'] }),
    });
}

export function useEndPromotion() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => endPromotion(client!, id),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['promotions'] }),
    });
}

export function useDeletePromotion() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => deletePromotion(client!, id),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['promotions'] }),
    });
}

export function useSyncPromotions() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (channelAccountId: number) => syncPromotions(client!, channelAccountId),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['promotions'] }),
    });
}

export type { Promotion };
