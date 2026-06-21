import { Tag } from 'antd';
import { ORDER_STATUS_COLOR, orderStatusText } from '@/lib/format';

// Luôn hiển thị nhãn tiếng Việt; KHÔNG còn tooltip "raw: ..." (không lộ mã raw sàn). `rawStatus` giữ trong
// type để caller cũ không vỡ nhưng không dùng nữa.
export function StatusTag({ status, label }: { status: string; label?: string; rawStatus?: string | null }) {
    return <Tag color={ORDER_STATUS_COLOR[status] ?? 'default'} style={{ marginInlineEnd: 0 }}>{orderStatusText(status, label)}</Tag>;
}
