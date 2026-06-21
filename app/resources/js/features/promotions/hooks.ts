import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
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
