import { useQuery } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/**
 * SPEC 0021 — master-data Tỉnh / Quận / Phường VN dùng cho AddressPicker.
 *
 * Đọc trực tiếp DB qua /api/v1/master-data/* — nguồn nạp bằng `php artisan addresses:sync` từ:
 *  - `addresskit.cas.so` (format='new', 2-cấp, post-2025): Tỉnh → Phường/Xã.
 *  - `provinces.open-api.vn` (format='old', 3-cấp, pre-2025): Tỉnh → Quận → Phường/Xã.
 *
 * Cache 24h cả ở BE (DB) lẫn FE (React Query staleTime). Có thể chuyển format khi tạo đơn để
 * khớp với địa chỉ khách cung cấp (vd khách vẫn dùng "Quận 1, TP HCM" theo chuẩn cũ).
 */

export type AddressFormat = 'new' | 'old';

export interface Province { code: string; name: string; english_name?: string | null; division_type?: string | null; phone_code?: number | null; decree?: string | null }
export interface District { code: string; name: string; codename?: string | null; division_type?: string | null; province_code: string }
export interface Ward {
    code: string;
    name: string;
    english_name?: string | null;
    codename?: string | null;
    division_type?: string | null;
    province_code: string;
    district_code: string | null;
    decree?: string | null;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useProvinces(format: AddressFormat = 'new') {
    const api = useScopedApi();
    return useQuery({
        queryKey: ['master-data', 'provinces', format],
        enabled: api != null,
        staleTime: 24 * 60 * 60 * 1000,
        queryFn: async () => { const { data } = await api!.get<{ data: Province[] }>('/master-data/provinces', { params: { format } }); return data.data; },
    });
}

export function useDistricts(provinceCode: string | null | undefined, format: AddressFormat = 'old') {
    const api = useScopedApi();
    return useQuery({
        queryKey: ['master-data', 'districts', format, provinceCode],
        // Districts chỉ áp dụng cho 'old' (NEW không còn cấp quận).
        enabled: api != null && format === 'old' && !!provinceCode,
        staleTime: 24 * 60 * 60 * 1000,
        queryFn: async () => { const { data } = await api!.get<{ data: District[] }>('/master-data/districts', { params: { format, province_code: provinceCode } }); return data.data; },
    });
}

export function useWards(parentCode: string | null | undefined, format: AddressFormat = 'new') {
    const api = useScopedApi();
    return useQuery({
        queryKey: ['master-data', 'wards', format, parentCode],
        enabled: api != null && !!parentCode,
        staleTime: 24 * 60 * 60 * 1000,
        queryFn: async () => {
            const params: Record<string, string> = { format };
            if (format === 'new') params.province_code = parentCode!;
            else params.district_code = parentCode!;
            const { data } = await api!.get<{ data: Ward[] }>('/master-data/wards', { params });
            return data.data;
        },
    });
}
