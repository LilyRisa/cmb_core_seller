import { Tag, Tooltip } from 'antd';
import { ORDER_STATUS_COLOR, ORDER_STATUS_LABEL } from '@/lib/format';

export function StatusTag({ status, label, rawStatus }: { status: string; label?: string; rawStatus?: string | null }) {
    const tag = <Tag color={ORDER_STATUS_COLOR[status] ?? 'default'} style={{ marginInlineEnd: 0 }}>{label ?? ORDER_STATUS_LABEL[status] ?? status}</Tag>;
    return rawStatus ? <Tooltip title={`raw: ${rawStatus}`}>{tag}</Tooltip> : tag;
}
