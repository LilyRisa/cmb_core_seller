import { useEffect, useMemo, useState } from 'react';
import { Select, Space, Typography } from 'antd';
import { Party, PartyType, useParties, usePartiesByIds } from '@/lib/accounting';

/**
 * Bộ chọn khách hàng / nhà cung cấp có tìm kiếm (thay cho ô nhập ID số).
 *
 *  - Gõ để tìm theo tên / mã / SĐT (debounce 300ms, gọi /accounting/parties).
 *  - Khi `value` được set sẵn (preset) mà chưa nằm trong kết quả tìm kiếm,
 *    component tự resolve nhãn qua `usePartiesByIds` để hiển thị đúng.
 *  - Trả về `id` (number) qua `onChange`.
 */
export function PartyPicker({
    type,
    value,
    onChange,
    placeholder,
    allowClear = true,
    disabled,
    style,
}: {
    type: PartyType;
    value?: number;
    onChange?: (id: number | undefined, party?: Party) => void;
    placeholder?: string;
    allowClear?: boolean;
    disabled?: boolean;
    style?: React.CSSProperties;
}) {
    const [term, setTerm] = useState('');
    const [debounced, setDebounced] = useState('');
    useEffect(() => {
        const t = setTimeout(() => setDebounced(term.trim()), 300);
        return () => clearTimeout(t);
    }, [term]);

    const { data: results = [], isFetching } = useParties(type, debounced);
    // Resolve nhãn cho value đã chọn (nếu không có trong results).
    const presetIds = useMemo(() => (value != null ? [value] : []), [value]);
    const { data: preset = [] } = usePartiesByIds(type, presetIds);

    const options = useMemo(() => {
        const map = new Map<number, Party>();
        [...preset, ...results].forEach((p) => map.set(p.id, p));
        return Array.from(map.values()).map((p) => ({
            value: p.id,
            label: (
                <Space size={6}>
                    <Typography.Text>{p.label}</Typography.Text>
                    {p.secondary && <Typography.Text type="secondary" style={{ fontSize: 12 }}>· {p.secondary}</Typography.Text>}
                </Space>
            ),
            // text dùng cho hiển thị giá trị đã chọn (Select hiển thị label node nên cũng ổn).
            party: p,
        }));
    }, [preset, results]);

    const defaultPlaceholder = type === 'customer' ? 'Tìm khách hàng theo tên / SĐT…' : 'Tìm NCC theo tên / mã…';

    return (
        <Select
            showSearch
            allowClear={allowClear}
            disabled={disabled}
            value={value}
            placeholder={placeholder ?? defaultPlaceholder}
            style={{ width: '100%', ...style }}
            filterOption={false}
            onSearch={setTerm}
            onChange={(v) => {
                const party = options.find((o) => o.value === v)?.party;
                onChange?.(v == null ? undefined : (v as number), party);
            }}
            notFoundContent={isFetching ? 'Đang tìm…' : (debounced ? 'Không tìm thấy' : 'Gõ để tìm…')}
            options={options}
            optionLabelProp="label"
        />
    );
}
