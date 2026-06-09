import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';
import 'dayjs/locale/vi';

dayjs.extend(relativeTime);
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.locale('vi');

/**
 * Display timezone (UTC+7). Storage & transport are always UTC; every user-facing
 * date/time renders in Vietnam time regardless of the viewer's browser timezone.
 * Mirrors the backend `app_display_tz()` helper / `config('app.display_timezone')`.
 */
export const DISPLAY_TZ = 'Asia/Ho_Chi_Minh';

/** Money is integer VND đồng from the API — format with a thousands separator. */
export function formatMoney(value: number | null | undefined, currency = 'VND'): string {
    if (value == null) return '—';
    const n = new Intl.NumberFormat('vi-VN').format(value);
    return currency === 'VND' ? `${n} ₫` : `${n} ${currency}`;
}

/** ISO-8601 (instant) -> Asia/Ho_Chi_Minh display. Use for timestamps (created_at, sent_at, …). */
export function formatDate(iso: string | null | undefined, withTime = true): string {
    if (!iso) return '—';
    const d = dayjs(iso).tz(DISPLAY_TZ);
    return withTime ? d.format('DD/MM/YYYY HH:mm') : d.format('DD/MM/YYYY');
}

/** Same as {@link formatDate} but keeps seconds — for audit/log views. */
export function formatDateTimeSeconds(iso: string | null | undefined): string {
    if (!iso) return '—';
    return dayjs(iso).tz(DISPLAY_TZ).format('DD/MM/YYYY HH:mm:ss');
}

/** Compact day + time (no year), Asia/Ho_Chi_Minh — for dense tables/tooltips. */
export function formatDateShort(iso: string | null | undefined): string {
    if (!iso) return '—';
    return dayjs(iso).tz(DISPLAY_TZ).format('DD/MM HH:mm');
}

/**
 * Calendar-date display, NO timezone conversion — for pure date values (bill_date,
 * due_date, accounting periods…) where there is no instant to localize and converting
 * would risk shifting across midnight.
 */
export function formatDateOnly(iso: string | null | undefined): string {
    if (!iso) return '—';
    return dayjs(iso).format('DD/MM/YYYY');
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
 * Ordered status "tabs" for the orders list (curated subset, BigSeller-style). Mỗi tab khớp với một/ vài
 * trạng thái đơn chuẩn (`statuses`) — UI tab luôn nhất quán với cột "Trạng thái". (BE còn filter `?stage=`
 * theo vận đơn cho app/API — SPEC 0013 — nhưng các tab trên web theo trạng thái đơn cho đơn giản, dễ hiểu.)
 */
export const ORDER_STATUS_TABS: Array<{ key: string; label: string; statuses?: string[]; stage?: FulfillmentStage }> = [
    { key: '', label: 'Tất cả', statuses: [] },
    { key: 'pending', label: 'Chờ xử lý', statuses: ['pending', 'unpaid'] },
    { key: 'processing', label: 'Đang xử lý', statuses: ['processing'] },
    { key: 'ready_to_ship', label: 'Chờ bàn giao', statuses: ['ready_to_ship'] },
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
    facebook_page: { name: 'Facebook', color: '#1877F2' },
    facebook: { name: 'Facebook', color: '#1877F2' },
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
