import type { AxiosInstance } from 'axios';

/* ============================================================================
 * Types — product publishing (đăng sản phẩm lên sàn)
 * ========================================================================== */

export interface MasterProduct {
    id: number;
    name: string;
    image: string | null;
    brand: string | null;
    category: string | null;
    /** Bản nháp listing đã tạo cho sản phẩm này (nếu BE trả kèm). */
    listings?: ListingDraftSummary[];
}

export interface ListingDraftSummary {
    id: number;
    provider: string;
    channel_account_id: number;
    status: ListingStatus;
}

export type ListingStatus = 'draft' | 'ready' | 'pushing' | 'reviewing' | 'live' | 'published' | 'failed';

export interface ListingDraftSku {
    id: number;
    seller_sku: string;
    sale_props: Record<string, string>;
    price: number;
    stock: number;
    package_weight: number | null;
    package_dims: { length?: number; width?: number; height?: number } | null;
    /** TikTok: kho xuất hàng cho từng SKU. */
    warehouse_id?: string | null;
}

export interface ListingDraft {
    id: number;
    product_id: number;
    channel_account_id: number;
    provider: string;
    status: ListingStatus;
    name?: string;
    description?: string | null;
    category_id: string | null;
    brand_id: string | null;
    attributes: Record<string, unknown>;
    media_refs: string[];
    logistics: Record<string, unknown>;
    validation_errors: string[];
    skus: ListingDraftSku[];
}

export interface UpdateListingPayload {
    description?: string | null;
    category_id?: string | null;
    brand_id?: string | null;
    attributes?: Record<string, unknown>;
    media_refs?: string[];
    logistics?: Record<string, unknown>;
    skus?: Partial<ListingDraftSku>[];
}

export interface PushJob {
    listing_id: number;
    status: 'queued' | 'running' | 'success' | 'failed';
    step_label: string | null;
    progress: number;
    error: string | null;
}

export interface PushBatch {
    id: number;
    total: number;
    succeeded: number;
    failed: number;
    status: 'running' | 'done';
    jobs: PushJob[];
}

export interface CategoryNode {
    id: string;
    parent_id: string | null;
    name: string;
    is_leaf: boolean;
}

export interface ListingAttribute {
    id: string;
    name: string;
    required: boolean;
    is_sale_prop: boolean;
    input_type: 'select' | 'multi_select' | 'text' | 'number';
    values: { id: string; name: string }[];
}

export interface Brand {
    id: string;
    name: string;
    mandatory: boolean;
}

/* ============================================================================
 * Endpoints — all take a tenant-scoped AxiosInstance (baseURL /api/v1)
 * ========================================================================== */

export async function listMasterProducts(client: AxiosInstance, status?: string): Promise<MasterProduct[]> {
    const { data } = await client.get<{ data: MasterProduct[] }>('/products', {
        params: status ? { status } : undefined,
    });
    return data.data;
}

export async function deleteMasterProduct(client: AxiosInstance, id: number): Promise<void> {
    await client.delete(`/products/${id}`);
}

export async function getListing(client: AxiosInstance, id: number): Promise<ListingDraft> {
    const { data } = await client.get<{ data: ListingDraft }>(`/listings/${id}`);
    return data.data;
}

export async function createListing(
    client: AxiosInstance,
    productId: number,
    channelAccountId: number,
    provider: string,
): Promise<ListingDraft> {
    const { data } = await client.post<{ data: ListingDraft }>(`/products/${productId}/listings`, {
        channel_account_id: channelAccountId,
        provider,
    });
    return data.data;
}

export async function updateListing(
    client: AxiosInstance,
    id: number,
    payload: UpdateListingPayload,
): Promise<ListingDraft> {
    const { data } = await client.put<{ data: ListingDraft }>(`/listings/${id}`, payload);
    return data.data;
}

export async function cloneListing(
    client: AxiosInstance,
    id: number,
    channelAccountId: number,
): Promise<ListingDraft> {
    const { data } = await client.post<{ data: ListingDraft }>(`/listings/${id}/clone`, {
        channel_account_id: channelAccountId,
    });
    return data.data;
}

export async function pushListing(client: AxiosInstance, id: number): Promise<{ batch_id: number }> {
    const { data } = await client.post<{ data: { batch_id: number } }>(`/listings/${id}/push`);
    return data.data;
}

export async function bulkPush(client: AxiosInstance, ids: number[]): Promise<{ batch_id: number }> {
    const { data } = await client.post<{ data: { batch_id: number } }>('/listings/bulk-push', {
        listing_ids: ids,
    });
    return data.data;
}

export async function getPushBatch(client: AxiosInstance, id: number): Promise<PushBatch> {
    const { data } = await client.get<{ data: PushBatch }>(`/push-batches/${id}`);
    return data.data;
}

/* ============================================================================
 * Sửa sản phẩm đã có trên sàn (ChannelListing) — đẩy tiêu đề/mô tả/ảnh/giá lên sàn
 * ========================================================================== */

export interface MarketplaceListingDetail {
    external_product_id: string;
    title: string;
    description: string;
    images: string[];
    skus: { external_sku_id: string; seller_sku: string; price: number }[];
}

export interface MarketplaceEditPayload {
    title?: string;
    description?: string;
    images?: string[];
    prices?: { external_sku_id: string; price: number }[];
}

export interface CloneToShopsResult {
    id: number;
    provider: string;
    status: string;
}

/** Sao chép một sản phẩm đã có trên sàn sang nhiều shop → tạo nháp (ready nếu cùng nền tảng). */
export async function cloneChannelListingToShops(
    client: AxiosInstance,
    channelListingId: number,
    channelAccountIds: number[],
): Promise<CloneToShopsResult[]> {
    const { data } = await client.post<{ data: CloneToShopsResult[] }>(`/channel-listings/${channelListingId}/clone-to-shops`, {
        channel_account_ids: channelAccountIds,
    });
    return data.data;
}

export async function getMarketplaceDetail(client: AxiosInstance, id: number): Promise<MarketplaceListingDetail> {
    const { data } = await client.get<{ data: MarketplaceListingDetail }>(`/channel-listings/${id}/marketplace-detail`);
    return data.data;
}

export async function updateMarketplaceListing(
    client: AxiosInstance,
    id: number,
    payload: MarketplaceEditPayload,
): Promise<MarketplaceListingDetail> {
    const { data } = await client.put<{ data: MarketplaceListingDetail }>(`/channel-listings/${id}/marketplace`, payload);
    return data.data;
}

/* ============================================================================
 * Tùy chọn vận chuyển của shop (cho trang soạn nháp đăng sàn)
 * ========================================================================== */

export interface ShippingOptions {
    mode: 'channels' | 'warehouse_delivery' | 'package';
    /** Shopee: kênh vận chuyển bật được. */
    channels?: { id: string; name: string; fee_type: string }[];
    /** TikTok: kho. */
    warehouses?: { id: string; name: string; is_default: boolean }[];
    /** TikTok: phương thức giao hàng. */
    delivery_options?: { id: string; name: string }[];
    /** Lazada: ghi chú (vận chuyển theo kiện). */
    notes?: string;
}

export async function getShippingOptions(
    client: AxiosInstance,
    provider: string,
    channelAccountId: number,
): Promise<ShippingOptions> {
    const { data } = await client.get<{ data: ShippingOptions }>(`/channels/${provider}/shipping-options`, {
        params: { channel_account_id: channelAccountId },
    });
    return data.data;
}

export async function getCategories(
    client: AxiosInstance,
    provider: string,
    channelAccountId: number,
    parentId?: string,
): Promise<CategoryNode[]> {
    const { data } = await client.get<{ data: CategoryNode[] }>(`/channels/${provider}/categories`, {
        params: { channel_account_id: channelAccountId, parent_id: parentId },
    });
    return data.data;
}

export async function getAttributes(
    client: AxiosInstance,
    provider: string,
    channelAccountId: number,
    categoryId: string,
): Promise<ListingAttribute[]> {
    const { data } = await client.get<{ data: ListingAttribute[] }>(`/channels/${provider}/attributes`, {
        params: { channel_account_id: channelAccountId, category_id: categoryId },
    });
    return data.data;
}

export async function getBrands(
    client: AxiosInstance,
    provider: string,
    channelAccountId: number,
    categoryId: string,
): Promise<Brand[]> {
    const { data } = await client.get<{ data: Brand[] }>(`/channels/${provider}/brands`, {
        params: { channel_account_id: channelAccountId, category_id: categoryId },
    });
    return data.data;
}
