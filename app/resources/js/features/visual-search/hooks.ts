import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useMemo, useState } from 'react';

import { tenantApi } from '@/lib/api';
import { useCurrentTenantId } from '@/lib/tenant';

import {
    createVisualItem,
    deleteVisualImage,
    deleteVisualItem,
    fetchVisualImageBlob,
    getVisualItem,
    listVisualItems,
    lookupVisualImage,
    setVisualPrimary,
    updateVisualItem,
    uploadVisualImages,
    type VisualItemPayload,
    type VisualMatch,
} from './api';

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

const KEY = 'visual-search';

export function useVisualItems() {
    const client = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'items', tenantId],
        enabled: client != null,
        queryFn: () => listVisualItems(client!),
    });
}

export function useVisualItem(id: number | null) {
    const client = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: [KEY, 'item', tenantId, id],
        enabled: client != null && id != null,
        queryFn: () => getVisualItem(client!, id!),
    });
}

export function useCreateVisualItem() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (payload: VisualItemPayload) => createVisualItem(client!, payload),
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
    });
}

export function useUpdateVisualItem() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ id, payload }: { id: number; payload: VisualItemPayload }) => updateVisualItem(client!, id, payload),
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
    });
}

export function useDeleteVisualItem() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => deleteVisualItem(client!, id),
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
    });
}

export function useUploadVisualImages() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ itemId, files }: { itemId: number; files: File[] }) => uploadVisualImages(client!, itemId, files),
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
    });
}

export function useDeleteVisualImage() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ itemId, imageId }: { itemId: number; imageId: number }) => deleteVisualImage(client!, itemId, imageId),
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
    });
}

export function useSetVisualPrimary() {
    const client = useScopedApi();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ itemId, imageId }: { itemId: number; imageId: number }) => setVisualPrimary(client!, itemId, imageId),
        onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
    });
}

export function useVisualLookup() {
    const client = useScopedApi();
    return useMutation<VisualMatch, unknown, { file: File; rerank?: boolean; channelAccountId?: number }>({
        mutationFn: ({ file, rerank, channelAccountId }) => lookupVisualImage(client!, file, { rerank, channelAccountId }),
    });
}

/** Tải thumbnail ảnh training (object URL), tự revoke khi unmount. */
export function useVisualImageBlob(itemId: number | null, imageId: number | null): string | null {
    const client = useScopedApi();
    const [url, setUrl] = useState<string | null>(null);

    useEffect(() => {
        let active = true;
        let made: string | null = null;
        if (client && itemId != null && imageId != null) {
            fetchVisualImageBlob(client, itemId, imageId)
                .then((u) => {
                    if (active) {
                        made = u;
                        setUrl(u);
                    } else {
                        URL.revokeObjectURL(u);
                    }
                })
                .catch(() => undefined);
        }
        return () => {
            active = false;
            if (made) URL.revokeObjectURL(made);
        };
    }, [client, itemId, imageId]);

    return url;
}
