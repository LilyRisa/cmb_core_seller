import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
// Spec 2026-05-17 — admin SPA dùng cùng cookie session với user, nhưng login bằng
// guard `admin_web`. Hooks dưới đây gọi `/api/v1/admin/*` qua axios chung (cùng
// xsrf flow); auth resolve về `admin_web` ở backend.
import { api } from '@/lib/api';

/**
 * SPEC 0020 — hooks gọi /api/v1/admin/* cho trang super-admin.
 *
 * KHÔNG dùng `tenantApi(...)` (admin global, không cần `X-Tenant-Id`).
 * Mọi mutation invalidate `['admin', 'tenants']` để list/detail tự refetch.
 */

export interface AdminOwner { id: number; name: string; email: string; email_verified_at?: string | null }

export interface AdminSubscription {
    id: number;
    plan_code: string | null;
    plan_name?: string | null;
    status: string;
    billing_cycle: string;
    current_period_start: string | null;
    current_period_end: string | null;
    over_quota_warned_at: string | null;
    over_quota_locked: boolean;
}

export interface AdminTenantSummary {
    id: number;
    name: string;
    slug: string;
    status: string;
    created_at: string | null;
    owner: AdminOwner | null;
    subscription: AdminSubscription | null;
    usage: { channel_accounts: { used: number; limit: number; over: boolean } };
}

export interface AdminChannelAccount {
    id: number;
    provider: string;
    name: string;
    shop_name: string | null;
    display_name: string | null;
    external_shop_id: string;
    status: string;
    last_synced_at: string | null;
    created_at: string | null;
}

export interface AdminMember {
    user_id: number;
    role: string;
    name: string | null;
    email: string | null;
    email_verified_at?: string | null;
    is_super_admin: boolean;
}

export interface AdminAdAccount {
    id: number;
    provider: string; // facebook | tiktok
    name: string | null;
    external_account_id: string;
    currency: string | null;
    status: string;
    business_name: string | null;
    last_synced_at: string | null;
    created_at: string | null;
}

export interface AdminAuditEntry {
    id: number;
    action: string;
    user_id: number | null;
    changes: Record<string, unknown> | null;
    ip: string | null;
    created_at: string | null;
}

export interface AdminInvoice {
    id: number; code: string; status: string;
    subtotal: number; total: number; currency: string;
    due_at: string | null; paid_at: string | null;
    period_start: string; period_end: string;
    meta: Record<string, unknown> | null;
    created_at: string | null;
}

export interface AdminPayment {
    id: number; invoice_id: number; gateway: string;
    amount: number; status: string;
    occurred_at: string | null; refunded_at: string | null;
}

export interface AdminVoucherRedemptionRow {
    id: number; voucher_code: string | null; voucher_name: string | null; voucher_kind: string | null;
    discount_amount: number; granted_days: number; invoice_id: number | null;
    created_at: string | null;
}

export interface AdminAiCreditSummary {
    enabled: boolean; unlimited: boolean; monthly_allowance: number;
    period_used: number; purchased_balance: number; available: number | null;
}

export interface AdminAiUsageHistory {
    all_time: number;
    by_month: Array<{ period_ym: number; count: number }>;
    by_feature: Array<{ feature: string; count: number }>;
}

export interface AdminTenantDetail extends AdminTenantSummary {
    channel_accounts: AdminChannelAccount[];
    ad_accounts: AdminAdAccount[];
    members: AdminMember[];
    recent_admin_actions: AdminAuditEntry[];
    invoices: AdminInvoice[];
    payments: AdminPayment[];
    vouchers_redeemed: AdminVoucherRedemptionRow[];
    sku_count: number;
    ai_credit: AdminAiCreditSummary;
    ai_usage_history: AdminAiUsageHistory;
}

export type VoucherKind = 'percent' | 'fixed' | 'free_days' | 'plan_upgrade' | 'ai_credits';

export interface AdminVoucher {
    id: number; code: string; name: string; description: string | null;
    kind: VoucherKind; value: number;
    valid_plans: string[];
    max_redemptions: number; redemption_count: number;
    starts_at: string | null; expires_at: string | null;
    is_active: boolean; is_in_window: boolean; is_exhausted: boolean;
    created_at: string | null;
}

export interface AdminVoucherDetail extends AdminVoucher {
    recent_redemptions: Array<{
        id: number; tenant_id: number; user_id: number | null;
        invoice_id: number | null; subscription_id: number | null;
        discount_amount: number; granted_days: number;
        created_at: string | null;
    }>;
}

export interface AdminPlan {
    id: number; code: string; name: string; description: string | null;
    is_active: boolean; sort_order: number;
    price_monthly: number; price_yearly: number; currency: string;
    trial_days: number;
    limits: Record<string, number>;
    features: Record<string, boolean>;
}

export interface AdminAuditLogRow {
    id: number;
    tenant_id: number | null; tenant: { id: number; name: string; slug: string } | null;
    user_id: number | null; user: { id: number; name: string; email: string } | null;
    action: string;
    auditable_type: string | null; auditable_id: number | null;
    changes: Record<string, unknown> | null;
    ip: string | null;
    created_at: string | null;
}

export interface AdminBroadcastRow {
    id: number; subject: string; body_markdown: string;
    audience: { kind: string; tenant_ids?: number[] };
    recipient_count: number; sent_count: number; skipped_count: number;
    sent_at: string | null;
    created_by_user_id: number;
    created_at: string | null;
}

export interface AdminUserRow {
    id: number;
    name: string;
    email: string;
    is_super_admin: boolean;
    tenants: Array<{ id: number; name: string; slug: string; role: string }>;
    created_at: string | null;
}

interface Paginated<T> { data: T[]; meta: { pagination: { page: number; per_page: number; total: number; total_pages: number } } }

export interface AdminTenantsFilters { q?: string; over_quota?: boolean; suspended?: boolean; page?: number; per_page?: number }

export function useAdminTenants(filters: AdminTenantsFilters = {}) {
    return useQuery({
        queryKey: ['admin', 'tenants', filters],
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            if (filters.q) params.q = filters.q;
            if (filters.over_quota) params.over_quota = 1;
            if (filters.suspended) params.suspended = 1;
            if (filters.page) params.page = filters.page;
            if (filters.per_page) params.per_page = filters.per_page;
            const { data } = await api.get<Paginated<AdminTenantSummary>>('/admin/tenants', { params });
            return data;
        },
        placeholderData: (p) => p,
        staleTime: 30_000,
    });
}

export function useAdminTenant(id: number | null) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', id],
        enabled: id != null,
        queryFn: async () => {
            const { data } = await api.get<{ data: AdminTenantDetail }>(`/admin/tenants/${id}`);
            return data.data;
        },
    });
}

export function useAdminUsers(filters: { q?: string; is_super_admin?: boolean; page?: number; per_page?: number } = {}) {
    return useQuery({
        queryKey: ['admin', 'users', filters],
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            if (filters.q) params.q = filters.q;
            if (filters.is_super_admin) params.is_super_admin = 1;
            if (filters.page) params.page = filters.page;
            if (filters.per_page) params.per_page = filters.per_page;
            const { data } = await api.get<Paginated<AdminUserRow>>('/admin/users', { params });
            return data;
        },
        placeholderData: (p) => p,
        staleTime: 60_000,
    });
}

export function useAdminDeleteChannel() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; channelAccountId: number; reason: string }) => {
            const { data } = await api.delete<{ data: { deleted_orders: number; unlinked_skus: number } }>(
                `/admin/tenants/${vars.tenantId}/channel-accounts/${vars.channelAccountId}`,
                { data: { reason: vars.reason } },
            );
            return data.data;
        },
        onSuccess: (_d, vars) => {
            qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
            qc.invalidateQueries({ queryKey: ['admin', 'tenants'] });
        },
    });
}

export function useAdminChangePlan() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; plan_code: string; cycle: 'monthly' | 'yearly' | 'trial'; reason: string }) => {
            const { data } = await api.post<{ data: AdminSubscription }>(
                `/admin/tenants/${vars.tenantId}/subscription`,
                { plan_code: vars.plan_code, cycle: vars.cycle, reason: vars.reason },
            );
            return data.data;
        },
        onSuccess: (_d, vars) => {
            qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
            qc.invalidateQueries({ queryKey: ['admin', 'tenants'] });
        },
    });
}

export function useAdminTenantAiCreditAdjust() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; amount: number; reason: string }) => {
            const { data } = await api.post<{ data: AdminAiCreditSummary & { applied: number } }>(
                `/admin/tenants/${vars.tenantId}/ai-credit/adjust`,
                { amount: vars.amount, reason: vars.reason },
            );
            return data.data;
        },
        onSuccess: (_d, vars) => qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] }),
    });
}

export function useAdminSuspendTenant() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; reason: string }) => {
            const { data } = await api.post<{ data: AdminTenantSummary }>(
                `/admin/tenants/${vars.tenantId}/suspend`,
                { reason: vars.reason },
            );
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
    });
}

export function useAdminReactivateTenant() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number }) => {
            const { data } = await api.post<{ data: AdminTenantSummary }>(`/admin/tenants/${vars.tenantId}/reactivate`);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
    });
}

export function useAdminTenantDailyOrderStats(tenantId: number | null, days = 30) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', tenantId, 'daily-stats', days],
        enabled: tenantId != null,
        queryFn: async () => {
            const { data } = await api.get<{ data: Array<{ date: string; count: number; grand_total_sum: number }> }>(
                `/admin/tenants/${tenantId}/orders/daily-stats`, { params: { days } },
            );
            return data.data;
        },
    });
}

export interface AdminOrderStatusHistoryRow {
    order_id: number; order_number: string | null;
    from_status: string | null; to_status: string; raw_status: string | null;
    source: string; changed_at: string | null;
}

export function useAdminTenantOrderStatusHistory(tenantId: number | null, page = 1) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', tenantId, 'order-status-history', page],
        enabled: tenantId != null,
        queryFn: async () => {
            const { data } = await api.get<Paginated<AdminOrderStatusHistoryRow>>(
                `/admin/tenants/${tenantId}/order-status-history`, { params: { page } },
            );
            return data;
        },
        placeholderData: (p) => p,
    });
}

export interface AdminFullAuditEntry extends AdminAuditEntry { admin_user_id: number | null }

export function useAdminTenantAuditLogs(tenantId: number | null, page = 1) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', tenantId, 'audit-logs', page],
        enabled: tenantId != null,
        queryFn: async () => {
            const { data } = await api.get<Paginated<AdminFullAuditEntry>>(
                `/admin/tenants/${tenantId}/audit-logs`, { params: { page } },
            );
            return data;
        },
        placeholderData: (p) => p,
    });
}

export interface AdminLoginHistoryRow {
    user_id: number; name: string | null; email: string | null;
    ip_address: string | null; user_agent: string | null; logged_in_at: string;
}

export function useAdminTenantLoginHistory(tenantId: number | null, page = 1) {
    return useQuery({
        queryKey: ['admin', 'tenants', 'detail', tenantId, 'login-history', page],
        enabled: tenantId != null,
        queryFn: async () => {
            const { data } = await api.get<Paginated<AdminLoginHistoryRow>>(
                `/admin/tenants/${tenantId}/login-history`, { params: { page } },
            );
            return data;
        },
        placeholderData: (p) => p,
    });
}

// SPEC 0023 — vouchers
export function useAdminVouchers(filters: { q?: string; kind?: VoucherKind; active?: boolean; page?: number; per_page?: number } = {}) {
    return useQuery({
        queryKey: ['admin', 'vouchers', filters],
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            if (filters.q) params.q = filters.q;
            if (filters.kind) params.kind = filters.kind;
            if (filters.active) params.active = 1;
            if (filters.page) params.page = filters.page;
            if (filters.per_page) params.per_page = filters.per_page;
            const { data } = await api.get<Paginated<AdminVoucher>>('/admin/vouchers', { params });
            return data;
        },
        placeholderData: (p) => p, staleTime: 30_000,
    });
}

export function useAdminVoucher(id: number | null) {
    return useQuery({
        queryKey: ['admin', 'vouchers', 'detail', id],
        enabled: id != null,
        queryFn: async () => {
            const { data } = await api.get<{ data: AdminVoucherDetail }>(`/admin/vouchers/${id}`);
            return data.data;
        },
    });
}

export function useAdminCreateVoucher() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: {
            code: string; name: string; description?: string;
            kind: VoucherKind; value: number;
            valid_plans?: string[]; max_redemptions?: number;
            starts_at?: string; expires_at?: string;
            meta?: Record<string, unknown>;
        }) => {
            const { data } = await api.post<{ data: AdminVoucher }>('/admin/vouchers', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'vouchers'] }),
    });
}

export function useAdminUpdateVoucher() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: {
            id: number;
            name?: string; description?: string;
            valid_plans?: string[]; max_redemptions?: number;
            starts_at?: string | null; expires_at?: string | null;
            is_active?: boolean;
        }) => {
            const { id, ...rest } = vars;
            const { data } = await api.patch<{ data: AdminVoucher }>(`/admin/vouchers/${id}`, rest);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'vouchers'] }),
    });
}

export function useAdminDisableVoucher() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api.delete<{ data: AdminVoucher }>(`/admin/vouchers/${id}`);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'vouchers'] }),
    });
}

export function useAdminGrantVoucher() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { voucherId: number; tenantId: number; reason: string }) => {
            const { data } = await api.post<{ data: { voucher: AdminVoucher; applied: Record<string, unknown>; redemption_id: number } }>(
                `/admin/vouchers/${vars.voucherId}/grant`,
                { tenant_id: vars.tenantId, reason: vars.reason },
            );
            return data.data;
        },
        onSuccess: (_d, vars) => {
            qc.invalidateQueries({ queryKey: ['admin', 'vouchers'] });
            qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
        },
    });
}

// SPEC 0023 — custom trial
export function useAdminExtendTrial() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; days: number; plan_code?: string; reason: string }) => {
            const { data } = await api.post<{ data: AdminSubscription }>(
                `/admin/tenants/${vars.tenantId}/extend-trial`,
                { days: vars.days, plan_code: vars.plan_code, reason: vars.reason },
            );
            return data.data;
        },
        onSuccess: (_d, vars) => {
            qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
            qc.invalidateQueries({ queryKey: ['admin', 'tenants'] });
        },
    });
}

// SPEC 0023 — plan editor
export function useAdminPlans() {
    return useQuery({
        queryKey: ['admin', 'plans'],
        queryFn: async () => {
            const { data } = await api.get<{ data: AdminPlan[] }>('/admin/plans');
            return data.data;
        },
        staleTime: 60_000,
    });
}

export function useAdminUpdatePlan() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { id: number } & Partial<AdminPlan>) => {
            const { id, ...rest } = vars;
            const { data } = await api.patch<{ data: AdminPlan }>(`/admin/plans/${id}`, rest);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'plans'] }),
    });
}

export function useAdminCreatePlan() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: Partial<AdminPlan> & { code: string; name: string }) => {
            const { data } = await api.post<{ data: AdminPlan }>('/admin/plans', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'plans'] }),
    });
}

// SPEC 0023 — feature override
export function useAdminFeatureOverrides() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; features: Record<string, boolean | null>; reason: string }) => {
            const { data } = await api.post<{ data: AdminSubscription }>(
                `/admin/tenants/${vars.tenantId}/feature-overrides`,
                { features: vars.features, reason: vars.reason },
            );
            return data.data;
        },
        onSuccess: (_d, vars) => {
            qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
        },
    });
}

// SPEC 0023 — manual invoice + mark paid + refund
export function useAdminCreateInvoice() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { tenantId: number; plan_code: string; cycle: 'monthly' | 'yearly' | 'trial'; amount?: number; period_days?: number; note?: string }) => {
            const { tenantId, ...body } = vars;
            const { data } = await api.post<{ data: AdminInvoice }>(`/admin/tenants/${tenantId}/invoices`, body);
            return data.data;
        },
        onSuccess: (_d, vars) => {
            qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
        },
    });
}

export function useAdminMarkInvoicePaid() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { id: number; tenantId?: number; payment_method?: string; reference?: string; paid_at?: string }) => {
            const { id, tenantId, ...body } = vars;
            void tenantId;
            const { data } = await api.post<{ data: AdminInvoice }>(`/admin/invoices/${id}/mark-paid`, body);
            return data.data;
        },
        onSuccess: (_d, vars) => {
            if (vars.tenantId) qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
        },
    });
}

export function useAdminRefundPayment() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { id: number; tenantId?: number; reason: string; rollback_subscription?: boolean }) => {
            const { id, tenantId, ...body } = vars;
            void tenantId;
            const { data } = await api.post<{ data: AdminPayment }>(`/admin/payments/${id}/refund`, body);
            return data.data;
        },
        onSuccess: (_d, vars) => {
            if (vars.tenantId) qc.invalidateQueries({ queryKey: ['admin', 'tenants', 'detail', vars.tenantId] });
        },
    });
}

// SPEC 0023 — audit log search
export interface AdminAuditFilters { action?: string; user_id?: number; tenant_id?: number; from?: string; to?: string; q?: string; page?: number; per_page?: number }
export function useAdminAuditLogs(filters: AdminAuditFilters = {}) {
    return useQuery({
        queryKey: ['admin', 'audit-logs', filters],
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            (Object.keys(filters) as Array<keyof AdminAuditFilters>).forEach((k) => {
                const v = filters[k];
                if (v != null && v !== '') params[k] = v as string | number;
            });
            const { data } = await api.get<Paginated<AdminAuditLogRow>>('/admin/audit-logs', { params });
            return data;
        },
        placeholderData: (p) => p, staleTime: 15_000,
    });
}

// SPEC 0023 — broadcasts
export function useAdminBroadcasts(filters: { page?: number; per_page?: number } = {}) {
    return useQuery({
        queryKey: ['admin', 'broadcasts', filters],
        queryFn: async () => {
            const { data } = await api.get<Paginated<AdminBroadcastRow>>('/admin/broadcasts', { params: filters });
            return data;
        },
        placeholderData: (p) => p, staleTime: 30_000,
    });
}

export function useAdminCreateBroadcast() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { subject: string; body_markdown: string; audience: { kind: string; tenant_ids?: number[] } }) => {
            const { data } = await api.post<{ data: AdminBroadcastRow }>('/admin/broadcasts', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'broadcasts'] }),
    });
}

// Pro trial mode — cấu hình bật/tắt + thời hạn + khung ngày chiến dịch
export interface AdminProTrialSettings {
    enabled: boolean;
    duration_days: number;
    window_start: string | null;
    window_end: string | null;
}

export function useAdminProTrialSettings() {
    return useQuery({
        queryKey: ['admin', 'pro-trial-settings'],
        queryFn: async () => {
            const { data } = await api.get<{ data: AdminProTrialSettings }>('/admin/pro-trial-settings');
            return data.data;
        },
    });
}

export function useAdminUpdateProTrialSettings() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (payload: AdminProTrialSettings) => {
            const { data } = await api.put<{ data: AdminProTrialSettings }>('/admin/pro-trial-settings', payload);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin', 'pro-trial-settings'] }),
    });
}

// SPEC 0023 — user-side voucher preview (used in /settings/plan checkout)
export function useValidateVoucher() {
    return useMutation({
        mutationFn: async (vars: { code: string; plan_code: string; cycle: 'monthly' | 'yearly' }) => {
            const { data } = await api.post<{ data: { valid: boolean; code: string; name: string; kind: string; discount: number; subtotal: number; total_after: number } }>(
                '/billing/vouchers/validate', vars,
            );
            return data.data;
        },
    });
}
