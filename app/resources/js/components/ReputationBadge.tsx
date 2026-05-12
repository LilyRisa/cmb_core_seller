import { Tag, Tooltip } from 'antd';
import { REPUTATION_META, type ReputationLabel } from '@/lib/customers';

/**
 * Buyer reputation chip. By design `ok` renders nothing (keeps the order list
 * uncluttered) unless `showOk` is set. See SPEC 0002 §4.4.
 */
export function ReputationBadge({ label, score, showOk = false, size = 'default' }: { label: ReputationLabel; score?: number; showOk?: boolean; size?: 'small' | 'default' }) {
    if (label === 'ok' && !showOk) return null;
    const meta = REPUTATION_META[label] ?? { label, color: 'default' };
    const tag = (
        <Tag color={meta.color} style={{ marginInlineEnd: 0, ...(size === 'small' ? { fontSize: 11, lineHeight: '16px', padding: '0 5px' } : {}) }}>
            {meta.label}{score != null ? ` ${score}` : ''}
        </Tag>
    );
    return score != null ? <Tooltip title={`Điểm uy tín: ${score}/100`}>{tag}</Tooltip> : tag;
}
