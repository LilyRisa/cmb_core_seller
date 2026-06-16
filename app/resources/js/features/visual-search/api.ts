import type { AxiosInstance } from 'axios';

export interface VisualImage {
    id: number;
    width: number;
    height: number;
    mime_type: string;
    is_primary: boolean;
}

export interface VisualItem {
    id: number;
    name: string;
    description: string | null;
    attributes: Record<string, unknown>;
    ref_code: string | null;
    status: string;
    applies_all_pages: boolean;
    primary_image_id: number | null;
    image_count: number;
    created_at: string | null;
    channel_account_ids?: number[];
    images?: VisualImage[];
}

export interface VisualItemPayload {
    name?: string;
    description?: string | null;
    ref_code?: string | null;
    applies_all_pages?: boolean;
    attributes?: Record<string, unknown>;
    channel_account_ids?: number[];
}

export interface VisualCandidate {
    item_id: number;
    name: string;
    description: string | null;
    attributes: Record<string, unknown>;
    confidence: number;
}

export interface VisualMatch {
    status: 'matched' | 'ambiguous' | 'not_found';
    stage: string;
    item: VisualCandidate | null;
    candidates: VisualCandidate[];
}

export async function listVisualItems(client: AxiosInstance, perPage = 50): Promise<VisualItem[]> {
    const { data } = await client.get<{ data: VisualItem[] }>('/visual-search/items', { params: { per_page: perPage } });
    return data.data;
}

export async function getVisualItem(client: AxiosInstance, id: number): Promise<VisualItem> {
    const { data } = await client.get<{ data: VisualItem }>(`/visual-search/items/${id}`);
    return data.data;
}

export async function createVisualItem(client: AxiosInstance, payload: VisualItemPayload): Promise<VisualItem> {
    const { data } = await client.post<{ data: VisualItem }>('/visual-search/items', payload);
    return data.data;
}

export async function updateVisualItem(client: AxiosInstance, id: number, payload: VisualItemPayload): Promise<VisualItem> {
    const { data } = await client.patch<{ data: VisualItem }>(`/visual-search/items/${id}`, payload);
    return data.data;
}

export async function deleteVisualItem(client: AxiosInstance, id: number): Promise<void> {
    await client.delete(`/visual-search/items/${id}`);
}

export async function uploadVisualImages(client: AxiosInstance, itemId: number, files: File[]): Promise<void> {
    const fd = new FormData();
    files.forEach((f) => fd.append('images[]', f));
    await client.post(`/visual-search/items/${itemId}/images`, fd);
}

export async function deleteVisualImage(client: AxiosInstance, itemId: number, imageId: number): Promise<void> {
    await client.delete(`/visual-search/items/${itemId}/images/${imageId}`);
}

export async function setVisualPrimary(client: AxiosInstance, itemId: number, imageId: number): Promise<void> {
    await client.post(`/visual-search/items/${itemId}/images/${imageId}/primary`);
}

/** Lấy bytes ảnh (kèm X-Tenant-Id) → object URL cho thumbnail. */
export async function fetchVisualImageBlob(client: AxiosInstance, itemId: number, imageId: number): Promise<string> {
    const res = await client.get(`/visual-search/items/${itemId}/images/${imageId}/raw`, { responseType: 'blob' });
    return URL.createObjectURL(res.data as Blob);
}

export async function lookupVisualImage(
    client: AxiosInstance,
    file: File,
    opts: { rerank?: boolean; channelAccountId?: number } = {},
): Promise<VisualMatch> {
    const fd = new FormData();
    fd.append('image', file);
    if (opts.rerank) fd.append('rerank', '1');
    if (opts.channelAccountId != null) fd.append('channel_account_id', String(opts.channelAccountId));
    const { data } = await client.post<{ data: VisualMatch }>('/visual-search/lookup', fd);
    return data.data;
}
