import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import utc from 'dayjs/plugin/utc';
import 'dayjs/locale/vi';

dayjs.extend(relativeTime);
dayjs.extend(utc);
dayjs.locale('vi');

/** Money is integer VND đồng from the API — format with a thousands separator. */
export function formatMoney(value: number | null | undefined, currency = 'VND'): string {
    if (value == null) return '—';
    const n = new Intl.NumberFormat('vi-VN').format(value);
    return currency === 'VND' ? `${n} ₫` : `${n} ${currency}`;
}

/** ISO-8601 UTC -> Asia/Ho_Chi_Minh display. */
export function formatDate(iso: string | null | undefined, withTime = true): string {
    if (!iso) return '—';
    const d = dayjs(iso);
    return withTime ? d.format('DD/MM/YYYY HH:mm') : d.format('DD/MM/YYYY');
}

export function fromNow(iso: string | null | undefined): string {
    return iso ? dayjs(iso).fromNow() : '—';
}

/** AntD Tag colors keyed by canonical order status. */
export const ORDER_STATUS_COLOR: Record<string, string> = {
    unpaid: 'default',
    pending: 'gold',
    processing: 'blue',
    ready_to_ship: 'geekblue',
    shipped: 'cyan',
    delivered: 'green',
    completed: 'success',
    delivery_failed: 'volcano',
    returning: 'orange',
    returned_refunded: 'magenta',
    cancelled: 'red',
};

export const ORDER_STATUS_LABEL: Record<string, string> = {
    unpaid: 'Chờ thanh toán',
    pending: 'Chờ xử lý',
    processing: 'Đang xử lý',
    ready_to_ship: 'Chờ bàn giao',
    shipped: 'Đang vận chuyển',
    delivered: 'Đã giao',
    completed: 'Hoàn tất',
    delivery_failed: 'Giao thất bại',
    returning: 'Đang trả/hoàn',
    returned_refunded: 'Đã trả/hoàn',
    cancelled: 'Đã huỷ',
};

export type FulfillmentStage = 'prepare' | 'pack' | 'handover';

/**
 * Ordered "tabs" for the orders list (BigSeller-style). 3 tab "công việc" đầu (Chờ xử lý / Đang xử lý /
 * Chờ bàn giao) lọc theo **bước xử lý dựa trên vận đơn** (`stage`, SPEC 0013) — đơn chưa có vận đơn = "chưa
 * chuẩn bị hàng" ⇒ Chờ xử lý, áp cho mọi nguồn (sàn & manual); các tab còn lại lọc theo trạng thái đơn.
 */
export const ORDER_STATUS_TABS: Array<{ key: string; label: string; statuses?: string[]; stage?: FulfillmentStage }> = [
    { key: '', label: 'Tất cả', statuses: [] },
    { key: 'prepare', label: 'Chờ xử lý', stage: 'prepare' },
    { key: 'pack', label: 'Đang xử lý', stage: 'pack' },
    { key: 'handover', label: 'Chờ bàn giao', stage: 'handover' },
    { key: 'shipped', label: 'Đang giao', statuses: ['shipped', 'delivery_failed'] },
    { key: 'delivered', label: 'Đã giao', statuses: ['delivered'] },
    { key: 'completed', label: 'Hoàn tất', statuses: ['completed'] },
    { key: 'returning', label: 'Trả/hoàn', statuses: ['returning', 'returned_refunded'] },
    { key: 'cancelled', label: 'Đã huỷ', statuses: ['cancelled'] },
];

export const CHANNEL_META: Record<string, { name: string; color: string }> = {
    tiktok: { name: 'TikTok Shop', color: '#000000' },
    shopee: { name: 'Shopee', color: '#ee4d2d' },
    lazada: { name: 'Lazada', color: '#0f146d' },
    manual: { name: 'Đơn thủ công', color: '#8c8c8c' },
};

export const CHANNEL_STATUS_COLOR: Record<string, string> = {
    active: 'success',
    expired: 'warning',
    revoked: 'default',
    disabled: 'default',
};

export const CHANNEL_STATUS_LABEL: Record<string, string> = {
    active: 'Hoạt động',
    expired: 'Cần kết nối lại',
    revoked: 'Đã ngắt',
    disabled: 'Tạm dừng',
};
