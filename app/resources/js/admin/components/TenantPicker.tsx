import { useEffect, useState } from 'react';
import { Select, Space, Typography } from 'antd';
import { useAdminTenants } from '../lib/admin';

/**
 * Bộ chọn tenant có tìm kiếm theo mã shop / tên / email chủ shop (thay ô nhập tenant ID số
 * — giao diện không hiển thị ID nên trước đây admin không biết gõ số nào).
 * Gõ để tìm (debounce 300ms, gọi GET /admin/tenants?q=...). `mode="multiple"` để chọn nhiều tenant.
 */
export function TenantPicker({
    value,
    onChange,
    mode,
    placeholder,
    allowClear = true,
    disabled,
    style,
}: {
    value?: number | number[];
    onChange?: (value: any) => void;
    mode?: 'multiple';
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

    const { data, isFetching } = useAdminTenants({ q: debounced, per_page: 20 });
    const tenants = data?.data ?? [];

    const options = tenants.map((t) => ({
        value: t.id,
        label: (
            <Space size={6}>
                <Typography.Text>{t.name}</Typography.Text>
                <Typography.Text type="secondary" style={{ fontSize: 12 }}>
                    · {t.code}{t.owner ? ` · ${t.owner.email}` : ''}
                </Typography.Text>
            </Space>
        ),
    }));

    return (
        <Select
            showSearch
            allowClear={allowClear}
            disabled={disabled}
            mode={mode}
            value={value}
            placeholder={placeholder ?? 'Tìm theo mã / tên / email…'}
            style={{ width: '100%', ...style }}
            filterOption={false}
            onSearch={setTerm}
            onChange={onChange}
            notFoundContent={isFetching ? 'Đang tìm…' : (debounced ? 'Không tìm thấy' : 'Gõ để tìm…')}
            options={options}
            optionLabelProp="label"
        />
    );
}
