import { getCurrentTenantId, useAuth } from './auth';

/** Tên gian hàng hiện tại — dùng làm tiêu đề tổ chức trên chứng từ in. */
export function useTenantName(): string | undefined {
    const { data: user } = useAuth();
    const tenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
    const t = user?.tenants.find((x) => x.id === tenantId) ?? user?.tenants[0];
    return t?.name;
}
