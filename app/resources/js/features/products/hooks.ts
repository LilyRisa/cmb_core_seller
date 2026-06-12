import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';
import {
    bulkPush,
    cloneListing,
    createListing,
    getAttributes,
    getBrands,
    getCategories,
    getListing,
    getMarketplaceDetail,
    getPushBatch,
    listMasterProducts,
    pushListing,
    updateListing,
    updateMarketplaceListing,
    type ListingDraft,
    type MarketplaceEditPayload,
    type PushBatch,
    type UpdateListingPayload,
} from './api';

/**
 * Tenant-scoped axios client. Dùng `useCurrentTenantId()` (có fallback về tenant
 * đầu của user) — KHÔNG dùng `getCurrentTenantId()` thuần (đọc localStorage, trả
 * null khi user chưa từng bấm đổi gian hàng) ⇒ tránh client null làm danh sách
 * sản phẩm trống và nút "Làm mới" (refetch bỏ qua `enabled`) crash `null.get`.
 */
function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

function useTenantId() {
    return useCurrentTenantId();
}

/* ============================================================================
 * Queries
 * ========================================================================== */

export function useMasterProducts(status?: string) {
    const client = useScopedApi();
    const tenantId = useTenantId();
    return useQuery({
        queryKey: ['products', 'master', tenantId, status ?? null],
        enabled: client != null,
        queryFn: () => listMasterProducts(client!, status),
    });
}

export function useListing(id: number | null) {
    const client = useScopedApi();
    const tenantId = useTenantId();
    return useQuery({
        queryKey: ['listing', tenantId, id],
        enabled: client != null && id != null,
        queryFn: () => getListing(client!, id!),
    });
}

export function usePushBatch(id: number | null) {
    const client = useScopedApi();
    const tenantId = useTenantId();
    return useQuery({
        queryKey: ['push-batch', tenantId, id],
        enabled: client != null && id != null,
        queryFn: () => getPushBatch(client!, id!),
        // Poll cho tới khi batch xong; dừng lại khi status === 'done'.
        refetchInterval: (q) => (q.state.data?.status === 'done' ? false : 1500),
    });
}

export function useCategories(provider: string | null, channelAccountId: number | null, parentId?: string) {
    const client = useScopedApi();
    const tenantId = useTenantId();
    return useQuery({
        queryKey: ['channel-categories', tenantId, provider, channelAccountId, parentId ?? 'root'],
        enabled: client != null && !!provider && channelAccountId != null,
        queryFn: () => getCategories(client!, provider!, channelAccountId!, parentId),
        staleTime: 10 * 60 * 1000,
    });
}

export function useAttributes(provider: string | null, channelAccountId: number | null, categoryId: string | null) {
    const client = useScopedApi();
    const tenantId = useTenantId();
    return useQuery({
        queryKey: ['channel-attributes', tenantId, provider, channelAccountId, categoryId],
        enabled: client != null && !!provider && channelAccountId != null && !!categoryId,
        queryFn: () => getAttributes(client!, provider!, channelAccountId!, categoryId!),
        staleTime: 10 * 60 * 1000,
    });
}

export function useBrands(provider: string | null, channelAccountId: number | null, categoryId: string | null) {
    const client = useScopedApi();
    const tenantId = useTenantId();
    return useQuery({
        queryKey: ['channel-brands', tenantId, provider, channelAccountId, categoryId],
        enabled: client != null && !!provider && channelAccountId != null && !!categoryId,
        queryFn: () => getBrands(client!, provider!, channelAccountId!, categoryId!),
        staleTime: 10 * 60 * 1000,
    });
}

/* ============================================================================
 * Mutations
 * ========================================================================== */

export function useCreateListing() {
    const client = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useTenantId();
    return useMutation({
        mutationFn: (vars: { productId: number; channelAccountId: number; provider: string }) =>
            createListing(client!, vars.productId, vars.channelAccountId, vars.provider),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['products', 'master', tenantId] });
        },
    });
}

export function useUpdateListing() {
    const client = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useTenantId();
    return useMutation({
        mutationFn: (vars: { id: number; payload: UpdateListingPayload }) =>
            updateListing(client!, vars.id, vars.payload),
        onSuccess: (draft: ListingDraft) => {
            qc.setQueryData(['listing', tenantId, draft.id], draft);
            qc.invalidateQueries({ queryKey: ['products', 'master', tenantId] });
        },
    });
}

export function useMarketplaceDetail(id: number | null) {
    const client = useScopedApi();
    const tenantId = useTenantId();
    return useQuery({
        queryKey: ['marketplace-detail', tenantId, id],
        enabled: client != null && id != null,
        queryFn: () => getMarketplaceDetail(client!, id!),
        staleTime: 0,
        // Gọi live API sàn (có thể chậm/lỗi token) — KHÔNG retry để hỏng nhanh,
        // tránh người dùng nhìn loading lâu. Trang vẫn dùng được nhờ dữ liệu listing.
        retry: false,
    });
}

export function useUpdateMarketplaceListing() {
    const client = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useTenantId();
    return useMutation({
        mutationFn: (vars: { id: number; payload: MarketplaceEditPayload }) =>
            updateMarketplaceListing(client!, vars.id, vars.payload),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['channel-listings', tenantId] });
        },
    });
}

export function useCloneListing() {
    const client = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useTenantId();
    return useMutation({
        mutationFn: (vars: { id: number; channelAccountId: number }) =>
            cloneListing(client!, vars.id, vars.channelAccountId),
        onSuccess: (draft: ListingDraft) => {
            qc.setQueryData(['listing', tenantId, draft.id], draft);
            qc.invalidateQueries({ queryKey: ['products', 'master', tenantId] });
        },
    });
}

export function usePushListing() {
    const client = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useTenantId();
    return useMutation({
        mutationFn: (id: number) => pushListing(client!, id),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['products', 'master', tenantId] });
        },
    });
}

export function useBulkPush() {
    const client = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useTenantId();
    return useMutation({
        mutationFn: (ids: number[]) => bulkPush(client!, ids),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['products', 'master', tenantId] });
        },
    });
}

export type { PushBatch };
