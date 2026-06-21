import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/** Đơn Hoàn & Hủy (after-sales) — SPEC 0025. */
export interface ReturnRecord {
    id: number;
    source: string;
    kind: 'cancel' | 'return' | 'refund';
    status: AfterSalesStatus;
    status_label: string;
    raw_status: string | null;
    external_return_id: string;
    external_order_id: string | null;
    order_id: number | null;
    order_number: string | null;
    reason: string | null;
    refund_amount: number;
    currency: string;
    items: Array<{ seller_sku?: string; name?: string; quantity?: number }>;
    requested_at: string | null;
    decided_at: string | null;
    created_at: string | null;
}

export type AfterSalesStatus = 'requested' | 'approved' | 'rejected' | 'processing' | 'completed' | 'cancelled';

export const AFTER_SALES_STATUS_LABEL: Record<AfterSalesStatus, string> = {
    requested: 'Chờ xử lý',
    approved: 'Đã duyệt',
    rejected: 'Đã từ chối',
    processing: 'Đang xử lý',
    completed: 'Hoàn tất',
    cancelled: 'Đã huỷ yêu cầu',
};

export const KIND_LABEL: Record<ReturnRecord['kind'], string> = {
    cancel: 'Hủy đơn',
    return: 'Trả hàng',
    refund: 'Hoàn tiền',
};

/**
 * Lý do hoàn/hủy của sàn thường là mã code (Shopee: DIFFERENT_DESCRIPTION, WRONG_ITEM…;
 * TikTok/Lazada: wrong_size, BUYER_CANCEL…). Map về tiếng Việt — key đã CHUẨN HOÁ UPPER_SNAKE
 * nên áp dùng chung cho cả 3 sàn. Mã lạ ⇒ humanize; nếu vốn là text ⇒ giữ nguyên.
 */
export const RETURN_REASON_LABEL: Record<string, string> = {
    // --- Đổi ý / không còn nhu cầu ---
    CHANGE_MIND: 'Khách đổi ý', CHANGED_MIND: 'Khách đổi ý', DONT_WANT: 'Khách không muốn nữa',
    DONT_WANT_ANYMORE: 'Khách không muốn nữa', NO_LONGER_NEEDED: 'Không còn nhu cầu',
    MUTUAL_AGREE: 'Hai bên thỏa thuận', BUYER_CANCEL: 'Khách yêu cầu hủy',
    BUYER_REQUEST: 'Khách yêu cầu hủy', BUYER_REQUESTED: 'Khách yêu cầu hủy',
    CUSTOMER_REQUEST: 'Khách yêu cầu hủy', CANCEL_BY_BUYER: 'Khách yêu cầu hủy',
    // --- Sai / khác mô tả ---
    WRONG_ITEM: 'Giao sai sản phẩm', SELLER_SENT_WRONG_ITEM: 'Người bán giao sai',
    WRONG_PRODUCT: 'Giao sai sản phẩm', WRONG_SIZE: 'Sai kích cỡ', WRONG_COLOR: 'Sai màu sắc',
    DIFFERENT_DESCRIPTION: 'Hàng khác mô tả', NOT_AS_DESCRIBED: 'Hàng khác mô tả',
    ITEM_WRONGDESCRIPTION: 'Hàng khác mô tả', ITEM_WRONG_DESCRIPTION: 'Hàng khác mô tả',
    WRONG_ORDER_INFO: 'Sai thông tin đơn',
    // --- Hư hỏng / chất lượng / hàng giả ---
    ITEM_DAMAGED: 'Sản phẩm bị hư hỏng', DAMAGED: 'Sản phẩm bị hư hỏng', DAMAGED_ITEM: 'Sản phẩm bị hư hỏng',
    PHYSICAL_DMG: 'Hư hỏng vật lý', FUNCTIONAL_DMG: 'Lỗi chức năng', QUALITY_ISSUE: 'Lỗi chất lượng',
    DEFECTIVE: 'Hàng lỗi', EXPIRED: 'Hàng hết hạn', USED: 'Hàng đã qua sử dụng',
    COUNTERFEIT: 'Hàng giả/nhái', FAKE: 'Hàng giả/nhái', ITEM_FAKE: 'Hàng giả/nhái',
    // --- Thiếu / chưa nhận ---
    ITEM_MISSING: 'Thiếu hàng/phụ kiện', MISSING_ITEM: 'Thiếu hàng/phụ kiện', MISSING_QUANTITY: 'Thiếu số lượng',
    NOT_RECEIPT: 'Chưa nhận được hàng', NOT_RECEIVED: 'Chưa nhận được hàng', ITEM_NOT_RECEIVED: 'Chưa nhận được hàng',
    EXPECTATION_FAILED: 'Không như kỳ vọng',
    // --- Hủy phía vận hành ---
    OUT_OF_STOCK: 'Hết hàng', SELLER_CANCEL: 'Người bán hủy', SYSTEM_CANCEL: 'Hệ thống hủy',
    PRICING_ERROR: 'Lỗi giá', WRONG_ADDRESS: 'Sai địa chỉ', ADDRESS_ISSUE: 'Lỗi địa chỉ',
    UNDELIVERABLE_AREA: 'Khu vực không giao được', COD_NOT_SUPPORTED: 'Không hỗ trợ COD',
    CANNOT_SHIP: 'Không thể vận chuyển', LOGISTICS_NOT_AVAILABLE: 'Không thể vận chuyển',
    CHANGE_OF_MIND: 'Khách đổi ý',
    // --- Lý do dạng CÂU tiếng Anh (TikTok trả reason text, không phải mã) — chuẩn hoá UPPER_SNAKE để khớp ---
    NO_LONGER_NEEDED_ITEM_MUST_BE_IN_SEALED_ORIGINAL_CONDITION: 'Không còn nhu cầu',
    PACKAGE_DELIVERY_FAILED: 'Giao hàng thất bại',
    AUTOMATICALLY_CANCELED_DUE_TO_COLLECTION_TIME_OUT: 'Tự huỷ do quá hạn lấy hàng',
    BETTER_PRICE_AVAILABLE: 'Tìm được giá tốt hơn',
    NEED_TO_CHANGE_PAYMENT_METHOD: 'Cần đổi phương thức thanh toán',
    NEED_TO_CHANGE_SHIPPING_ADDRESS: 'Cần đổi địa chỉ giao hàng',
    HIGH_DELIVERY_COSTS: 'Phí giao hàng quá cao',
    PRODUCT_IS_DEFECTIVE_OR_DOESN_T_WORK: 'Sản phẩm lỗi/không hoạt động',
    PRODUCT_DOESN_T_WORK: 'Sản phẩm không hoạt động',
    CUSTOMER_OVERDUE_TO_PAY: 'Khách quá hạn thanh toán',
    RECEIVED_PARCEL_BUT_SOME_ITEMS_WERE_MISSING: 'Nhận hàng nhưng thiếu món',
    SELLER_NOT_RESPONSIVE_TO_INQUIRIES: 'Người bán không phản hồi',
    SELLER_REQUESTING_ORDER_CANCELLATION: 'Người bán yêu cầu huỷ',
    PRODUCT_DOESN_T_MATCH_DESCRIPTION: 'Hàng không đúng mô tả',
    PAYMENT_PROBLEM: 'Vấn đề thanh toán',
    // --- Khác ---
    OTHERS: 'Lý do khác', OTHER: 'Lý do khác', NONE: 'Không rõ lý do',
};

/**
 * Hiển thị lý do hoàn/hủy bằng TIẾNG VIỆT: chuẩn hoá (UPPER_SNAKE, bỏ dấu câu/nháy) → tra map (gồm cả câu
 * tiếng Anh của TikTok) → humanize mã lạ → giữ nguyên text tự do. Sàn trả mã (Shopee) hoặc câu (TikTok).
 */
export function formatReturnReason(raw: string | null | undefined): string {
    const v = (raw ?? '').trim();
    if (v === '') return '—';
    // Bỏ MỌI ký tự không phải chữ/số (gồm dấu nháy của "doesn't", dấu phẩy) ⇒ '_' để khớp cả câu tiếng Anh.
    const key = v.toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    if (RETURN_REASON_LABEL[key]) return RETURN_REASON_LABEL[key];
    // Mã CODE thuần (1 chuỗi UPPER_SNAKE, không khoảng trắng) ⇒ humanize; câu/text tự do ⇒ giữ nguyên.
    if (/^[A-Za-z0-9_]+$/.test(v)) {
        const t = v.replace(/_/g, ' ').toLowerCase();
        return t.charAt(0).toUpperCase() + t.slice(1);
    }
    return v;
}

export interface ReturnsFilters {
    status?: string;
    kind?: string;
    source?: string;
    open_only?: boolean;
    q?: string;
    page?: number;
    per_page?: number;
}

interface Paginated<T> {
    data: T[];
    meta: { pagination: { page: number; per_page: number; total: number; total_pages: number } };
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useReturns(filters: ReturnsFilters) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['returns', tenantId, filters],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<Paginated<ReturnRecord>>('/returns', { params: filters });
            return data;
        },
    });
}

export interface ReturnStats {
    by_status: Record<string, number>;
    open: number;
    requested: number;
}

export function useReturnStats() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['returns', 'stats', tenantId],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: ReturnStats }>('/returns/stats');
            return data.data;
        },
    });
}

export function useDecideReturn() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { id: number; action: 'approve' | 'reject'; comment?: string }) => {
            const { data } = await api!.post<{ data: ReturnRecord }>(`/returns/${vars.id}/${vars.action}`, { comment: vars.comment });
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['returns', tenantId] });
            qc.invalidateQueries({ queryKey: ['returns', 'stats', tenantId] });
        },
    });
}
