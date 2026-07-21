import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { Select, Space, Typography } from 'antd';
import { useAdminTenants } from '../lib/admin';

/**
 * Option tối giản để "mồi" trước danh sách khi đã biết tenant nào được chọn (VD: điều hướng từ
 * AdminTenantsPage kèm `location.state`, xem AdminBroadcastsPage) — tránh Select hiển thị số ID
 * thô vì chưa kịp tìm kiếm ra tên.
 */
export interface TenantPickerOption {
    value: number;
    label: ReactNode;
}

/**
 * Bộ chọn tenant có tìm kiếm theo mã shop / tên / email chủ shop (thay ô nhập tenant ID số
 * — giao diện không hiển thị ID nên trước đây admin không biết gõ số nào).
 * Gõ để tìm (debounce 300ms, gọi GET /admin/tenants?q=...). `mode="multiple"` để chọn nhiều tenant.
 *
 * `initialOptions` (tuỳ chọn): option đã biết trước khi có kết quả tìm kiếm — dùng khi `value`
 * được set từ nơi khác chứ không phải do admin gõ tìm tại chỗ (VD: bulk-select ở
 * AdminTenantsPage → điều hướng sang form Broadcast kèm sẵn danh sách tenant). Được gộp với kết
 * quả tìm kiếm hiện tại; nếu trùng `value`, kết quả tìm kiếm mới hơn sẽ ghi đè.
 */
export function TenantPicker({
    value,
    onChange,
    mode,
    placeholder,
    allowClear = true,
    disabled,
    style,
    initialOptions,
}: {
    value?: number | number[];
    onChange?: (value: any) => void;
    mode?: 'multiple';
    placeholder?: string;
    allowClear?: boolean;
    disabled?: boolean;
    style?: React.CSSProperties;
    initialOptions?: TenantPickerOption[];
}) {
    const [term, setTerm] = useState('');
    const [debounced, setDebounced] = useState('');
    useEffect(() => {
        const t = setTimeout(() => setDebounced(term.trim()), 300);
        return () => clearTimeout(t);
    }, [term]);

    const { data, isFetching } = useAdminTenants({ q: debounced, per_page: 20 });
    const tenants = data?.data ?? [];

    const fetchedOptions: TenantPickerOption[] = tenants.map((t) => ({
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

    // Gộp initialOptions (đã biết trước) với kết quả tìm kiếm hiện tại — fetchedOptions ghi đè
    // nếu trùng value (dữ liệu tìm kiếm mới hơn), nhưng initialOptions vẫn hiển thị cho các value
    // chưa nằm trong kết quả tìm kiếm hiện tại (VD: chưa gõ tìm gì).
    const merged = new Map<number, TenantPickerOption>();
    (initialOptions ?? []).forEach((o) => merged.set(o.value, o));
    fetchedOptions.forEach((o) => merged.set(o.value, o));
    const options = Array.from(merged.values());

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
