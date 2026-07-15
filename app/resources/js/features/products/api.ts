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
    external_item_id?: string | null;
    pushed_at?: string | null;
}

export type ListingStatus = 'draft' | 'ready' | 'pushing' | 'reviewing' | 'live' | 'published' | 'failed';

export interface MasterSkuRef {
    id: number;
    sku_code: string;
    name: string;
}

export interface ListingDraftSku {
    id: number;
    seller_sku: string;
    sale_props: Record<string, string>;
    price: number;
    /** Tồn khởi tạo đẩy lên sàn (KHÔNG phải tồn kho master SKU của app). */
    stock: number;
    package_weight: number | null;
    package_dims: { length?: number; width?: number; height?: number } | null;
    /** TikTok: kho xuất hàng cho từng SKU. */
    warehouse_id?: string | null;
    /** Ảnh riêng của phân loại/SKU (đẩy lên sàn theo biến thể). */
    image_ref?: string | null;
    /** Master SKU đã liên kết thủ công (để đồng bộ tồn kho sau khi đẩy); null = chưa liên kết. */
    master_variant_id?: number | null;
    master_sku?: MasterSkuRef | null;
}

export interface ListingDraft {
    id: number;
    product_id: number;
    channel_account_id: number;
    provider: string;
    status: ListingStatus;
    name?: string;
    description?: string | null;
    video_url?: string | null;
    category_id: string | null;
    brand_id: string | null;
    attributes: Record<string, unknown>;
    media_refs: string[];
    logistics: Record<string, unknown>;
    /** Map field → message, vd {"categoryId": "Phải chọn danh mục lá"}. KHÔNG phải mảng chuỗi. */
    validation_errors: Record<string, string>;
    skus: ListingDraftSku[];
}

export interface UpdateListingPayload {
    name?: string | null;
    description?: string | null;
    video_url?: string | null;
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
    // Backend trả chuỗi message; một số job cũ/cache có thể là object {message}.
    error: string | { message?: string } | null;
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

export interface ListingLimits {
    max_images: number;
    max_videos: number;
}

export async function getListingLimits(client: AxiosInstance, provider: string): Promise<ListingLimits> {
    const { data } = await client.get<{ data: ListingLimits }>(`/channels/${provider}/listing-limits`);
    return data.data;
}

/** Upload video cho nháp đăng sàn → trả URL (đẩy lên sàn ở bước sau). */
export async function uploadListingVideo(client: AxiosInstance, file: File): Promise<{ url: string }> {
    const form = new FormData();
    form.append('video', file);
    const { data } = await client.post<{ data: { url: string } }>('/media/video', form);
    return data.data;
}

/** Tìm master SKU có sẵn để liên kết thủ công với SKU nháp đăng sàn. */
export async function searchMasterSkus(client: AxiosInstance, q: string): Promise<MasterSkuRef[]> {
    const { data } = await client.get<{ data: Array<{ id: number; sku_code: string; name: string }> }>('/skus', {
        params: { q, per_page: 20 },
    });
    return data.data.map((s) => ({ id: s.id, sku_code: s.sku_code, name: s.name }));
}

export async function getListing(client: AxiosInstance, id: number): Promise<ListingDraft> {
    const { data } = await client.get<{ data: ListingDraft }>(`/listings/${id}`);
    return data.data;
}

export async function deleteListing(client: AxiosInstance, id: number): Promise<void> {
    await client.delete(`/listings/${id}`);
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

export async function aiSuggestDescription(client: AxiosInstance, id: number): Promise<{ description: string; provider: string }> {
    const { data } = await client.post<{ data: { description: string; provider: string } }>(`/listings/${id}/ai-description`);
    return data.data;
}

/** AI gợi ý mô tả cho sản phẩm ĐÃ có trên sàn; gửi mô tả đang soạn để AI cải thiện. */
export async function aiSuggestMarketplaceDescription(
    client: AxiosInstance,
    id: number,
    currentDescription?: string,
): Promise<{ description: string; provider: string }> {
    const { data } = await client.post<{ data: { description: string; provider: string } }>(
        `/channel-listings/${id}/ai-description`,
        { description: currentDescription ?? '' },
    );
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

export interface BulkCloneResult { channel_listing_id: number; ok: boolean; results?: CloneToShopsResult[]; error?: string }

export async function bulkCloneChannelListingsToShops(
    client: AxiosInstance,
    channelListingIds: number[],
    channelAccountIds: number[],
): Promise<BulkCloneResult[]> {
    const { data } = await client.post<{ data: BulkCloneResult[] }>('/channel-listings/bulk-clone-to-shops', {
        channel_listing_ids: channelListingIds,
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

export interface CategorySearchHit {
    id: string;
    name: string;
    is_leaf: boolean;
    /** Đường dẫn breadcrumb "A › B › C" để chọn trực tiếp. */
    path: string;
}

export async function searchCategories(
    client: AxiosInstance,
    provider: string,
    channelAccountId: number,
    q: string,
): Promise<CategorySearchHit[]> {
    const { data } = await client.get<{ data: CategorySearchHit[] }>(`/channels/${provider}/categories/search`, {
        params: { channel_account_id: channelAccountId, q },
    });
    return data.data;
}

export async function getCategoryPath(
    client: AxiosInstance,
    provider: string,
    channelAccountId: number,
    categoryId: string,
): Promise<CategorySearchHit> {
    const { data } = await client.get<{ data: CategorySearchHit }>(`/channels/${provider}/category-path`, {
        params: { channel_account_id: channelAccountId, category_id: categoryId },
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
    q?: string,
): Promise<Brand[]> {
    const { data } = await client.get<{ data: Brand[] }>(`/channels/${provider}/brands`, {
        params: { channel_account_id: channelAccountId, category_id: categoryId, q: q || undefined },
    });
    return data.data;
}

/* ============================================================================
 * Chỉnh sửa hàng loạt (SPEC 2026-07-15) — sửa nhiều nháp cùng 1 provider cùng lúc
 * ========================================================================== */

export interface BulkUpdateItem extends UpdateListingPayload {
    id: number;
}

export interface BulkUpdateResult {
    id: number;
    status: ListingStatus | 'error';
    validation_errors: Record<string, string> | null;
}

export async function getListingsBulk(client: AxiosInstance, ids: number[]): Promise<ListingDraft[]> {
    const { data } = await client.get<{ data: ListingDraft[] }>('/listings/bulk', {
        params: { ids: ids.join(',') },
    });
    return data.data;
}

export async function updateListingsBulk(client: AxiosInstance, items: BulkUpdateItem[]): Promise<BulkUpdateResult[]> {
    const { data } = await client.put<{ data: BulkUpdateResult[] }>('/listings/bulk', { items });
    return data.data;
}
