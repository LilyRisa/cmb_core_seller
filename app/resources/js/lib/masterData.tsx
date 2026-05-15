import { useQuery } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/**
 * SPEC 0021 — master-data Tỉnh / Quận / Phường VN dùng cho AddressPicker khi tạo đơn manual.
 * Backend: /api/v1/master-data/* — proxy GHN master-data, cache 24h shared toàn tenant.
 * Trả về cả `id` (GHN ProvinceID/DistrictID) + `code` (GHN WardCode) để map vào shipping_address
 * khi đẩy đơn lên GHN (createShipment cần `district_id` + `ward_code`).
 */

export interface Province { id: number; name: string; code: string | null }
export interface District { id: number; name: string; province_id: number }
export interface Ward { code: string; name: string; district_id: number }

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function useProvinces() {
    const api = useScopedApi();
    return useQuery({
        queryKey: ['master-data', 'provinces'],
        enabled: api != null,
        staleTime: 24 * 60 * 60 * 1000,
        queryFn: async () => { const { data } = await api!.get<{ data: Province[] }>('/master-data/provinces'); return data.data; },
    });
}

export function useDistricts(provinceId: number | null | undefined) {
    const api = useScopedApi();
    return useQuery({
        queryKey: ['master-data', 'districts', provinceId],
        enabled: api != null && !!provinceId,
        staleTime: 24 * 60 * 60 * 1000,
        queryFn: async () => { const { data } = await api!.get<{ data: District[] }>('/master-data/districts', { params: { province_id: provinceId } }); return data.data; },
    });
}

export function useWards(districtId: number | null | undefined) {
    const api = useScopedApi();
    return useQuery({
        queryKey: ['master-data', 'wards', districtId],
        enabled: api != null && !!districtId,
        staleTime: 24 * 60 * 60 * 1000,
        queryFn: async () => { const { data } = await api!.get<{ data: Ward[] }>('/master-data/wards', { params: { district_id: districtId } }); return data.data; },
    });
}
