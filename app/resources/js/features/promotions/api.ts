import type { AxiosInstance } from 'axios';

/** Năng lực giảm giá của sàn — để render UI khớp sàn. */
export interface PromotionCapabilities {
    max_items_per_call: number;
    supports_percent: boolean;
    has_program_object: boolean;
    supports_time_of_day: boolean;
}

export type DiscountType = 'percent' | 'fixed';
export type PromotionStatus = 'draft' | 'pushing' | 'live' | 'ended' | 'failed';

export interface PromotionSku {
    id?: number;
    channel_listing_id?: number | null;
    external_product_id?: string | null;
    external_sku_id?: string | null;
    seller_sku?: string | null;
    image?: string | null;
    title?: string | null;
    base_price: number;
    discount_value: number;
    sale_price?: number;
    push_status?: 'pending' | 'ok' | 'failed';
    error?: string | null;
}

export interface Promotion {
    id: number;
    channel_account_id: number;
    provider: string;
    external_promotion_id: string | null;
    title: string;
    discount_type: DiscountType;
    starts_at: string | null;
    ends_at: string | null;
    status: PromotionStatus;
    source: 'app' | 'sync';
    last_error: { message?: string } | null;
    pushed_at: string | null;
    synced_at: string | null;
    sku_count?: number;
    skus?: PromotionSku[];
}

export interface CreatePromotionPayload {
    channel_account_id: number;
    title: string;
    discount_type: DiscountType;
    starts_at: string;
    ends_at: string;
}

export type UpdatePromotionPayload = Partial<Omit<CreatePromotionPayload, 'channel_account_id'>>;

export async function listPromotions(client: AxiosInstance, channelAccountId: number, tab: 'pushed' | 'draft'): Promise<Promotion[]> {
    const { data } = await client.get<{ data: Promotion[] }>('/channel-promotions', { params: { channel_account_id: channelAccountId, tab } });
    return data.data;
}

export async function getPromotion(client: AxiosInstance, id: number): Promise<Promotion> {
    const { data } = await client.get<{ data: Promotion }>(`/channel-promotions/${id}`);
    return data.data;
}

export async function createPromotion(client: AxiosInstance, payload: CreatePromotionPayload): Promise<Promotion> {
    const { data } = await client.post<{ data: Promotion }>('/channel-promotions', payload);
    return data.data;
}

export async function updatePromotion(client: AxiosInstance, id: number, payload: UpdatePromotionPayload): Promise<Promotion> {
    const { data } = await client.patch<{ data: Promotion }>(`/channel-promotions/${id}`, payload);
    return data.data;
}

export async function setPromotionSkus(client: AxiosInstance, id: number, skus: PromotionSku[]): Promise<Promotion> {
    const { data } = await client.post<{ data: Promotion }>(`/channel-promotions/${id}/skus`, { skus });
    return data.data;
}

export async function pushPromotion(client: AxiosInstance, id: number): Promise<void> {
    await client.post(`/channel-promotions/${id}/push`);
}

export async function endPromotion(client: AxiosInstance, id: number): Promise<Promotion> {
    const { data } = await client.post<{ data: Promotion }>(`/channel-promotions/${id}/end`);
    return data.data;
}

export async function deletePromotion(client: AxiosInstance, id: number): Promise<void> {
    await client.delete(`/channel-promotions/${id}`);
}

/** Khoá-bận (external_sku_id hoặc external_product_id cho item no-variant) + giá giảm đang chạy trên sàn. */
export interface BusyPromos { ids: string[]; prices: Record<string, number> }

export async function getBusyPromos(client: AxiosInstance, channelAccountId: number, exceptPromotionId?: number): Promise<BusyPromos> {
    const { data } = await client.get<{ data: { external_sku_ids: string[]; prices?: Record<string, number> } }>('/channel-promotions/busy-skus', {
        params: { channel_account_id: channelAccountId, except: exceptPromotionId },
    });
    return { ids: data.data.external_sku_ids ?? [], prices: data.data.prices ?? {} };
}

export async function syncPromotions(client: AxiosInstance, channelAccountId: number): Promise<number> {
    const { data } = await client.post<{ data: { synced: number } }>('/channel-promotions/sync', null, { params: { channel_account_id: channelAccountId } });
    return data.data.synced;
}

export async function getPromotionCapabilities(client: AxiosInstance, provider: string): Promise<PromotionCapabilities | null> {
    const { data } = await client.get<{ data: PromotionCapabilities | null }>('/channel-promotions/capabilities', { params: { provider } });
    return data.data;
}
