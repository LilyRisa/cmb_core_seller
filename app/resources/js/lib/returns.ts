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
    // --- Khác ---
    OTHERS: 'Lý do khác', OTHER: 'Lý do khác', NONE: 'Không rõ lý do',
};

/** Hiển thị lý do hoàn/hủy: tra map (chuẩn hoá UPPER_SNAKE) → humanize mã lạ → giữ nguyên text. */
export function formatReturnReason(raw: string | null | undefined): string {
    const v = (raw ?? '').trim();
    if (v === '') return '—';
    const key = v.toUpperCase().replace(/[\s-]+/g, '_');
    if (RETURN_REASON_LABEL[key]) return RETURN_REASON_LABEL[key];
    // Mã dạng CODE (chỉ chữ/số/gạch dưới, không khoảng trắng) ⇒ humanize; còn lại là text ⇒ giữ nguyên.
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
