import { useMemo } from 'react';
import { Select } from 'antd';
import dayjs from 'dayjs';
import { PeriodKind, useFiscalPeriods } from '@/lib/accounting';

/**
 * Bộ chọn kỳ kế toán đầy đủ — nhóm theo năm, không giới hạn 6/12 kỳ như Segmented cũ.
 * Mặc định trỏ tới kỳ tháng hiện tại nếu chưa chọn.
 */
export function PeriodSelect({
    value,
    onChange,
    kind = 'month',
    style,
    allowClear = false,
}: {
    value?: string;
    onChange: (code: string) => void;
    kind?: PeriodKind;
    style?: React.CSSProperties;
    allowClear?: boolean;
}) {
    const { data: periods = [], isFetching } = useFiscalPeriods({ kind });

    const groups = useMemo(() => {
        const byYear = new Map<string, { value: string; label: string }[]>();
        // Sắp xếp kỳ mới nhất lên đầu.
        const sorted = [...periods].sort((a, b) => (a.code < b.code ? 1 : -1));
        sorted.forEach((p) => {
            const year = p.code.slice(0, 4);
            if (!byYear.has(year)) byYear.set(year, []);
            byYear.get(year)!.push({ value: p.code, label: p.code });
        });
        return Array.from(byYear.entries()).map(([year, options]) => ({ label: `Năm ${year}`, options }));
    }, [periods]);

    return (
        <Select
            value={value}
            onChange={onChange}
            loading={isFetching}
            allowClear={allowClear}
            placeholder="Chọn kỳ"
            style={{ minWidth: 150, ...style }}
            options={groups}
            showSearch
            optionFilterProp="label"
            notFoundContent={dayjs().format('YYYY-MM')}
        />
    );
}
