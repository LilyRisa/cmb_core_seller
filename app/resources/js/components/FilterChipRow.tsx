import { ReactNode } from 'react';
import { Tag } from 'antd';

const { CheckableTag } = Tag;

export interface ChipItem {
    value: string;
    label: ReactNode;
    count?: number | null;
    icon?: ReactNode;
}

/**
 * One row of the "Lọc" panel (BigSeller-style): a left-aligned label + a wrapping
 * list of clickable chips with counts (not input boxes). Single-select; clicking
 * the active chip (or "Tất cả") clears it. See docs/06-frontend/orders-filter-panel.md.
 */
export function FilterChipRow({
    label, items, value, onChange, allLabel = 'Tất cả', extra,
}: {
    label: string;
    items: ChipItem[];
    value: string | undefined;
    onChange: (next: string | undefined) => void;
    allLabel?: string;
    extra?: ReactNode;            // e.g. an expand toggle / extra control on the right
}) {
    return (
        <div style={{ display: 'flex', alignItems: 'baseline', gap: 12, padding: '5px 0', borderBottom: '1px dashed #f0f0f0' }}>
            <div style={{ width: 92, flexShrink: 0, color: '#8c8c8c', fontSize: 13, lineHeight: '22px' }}>{label}</div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4, alignItems: 'center', flex: 1 }}>
                <CheckableTag checked={!value} onChange={() => onChange(undefined)}>{allLabel}</CheckableTag>
                {items.map((it) => (
                    <CheckableTag key={it.value} checked={value === it.value} onChange={() => onChange(value === it.value ? undefined : it.value)}>
                        {it.icon ? <span style={{ marginRight: 4 }}>{it.icon}</span> : null}
                        {it.label}{it.count != null ? <span style={{ opacity: 0.7 }}> ({it.count})</span> : null}
                    </CheckableTag>
                ))}
                {items.length === 0 && <span style={{ color: '#bfbfbf', fontSize: 12 }}>—</span>}
            </div>
            {extra && <div style={{ flexShrink: 0 }}>{extra}</div>}
        </div>
    );
}
