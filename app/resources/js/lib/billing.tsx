import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

/**
 * Billing hooks — Phase 6.4 / SPEC 0018.
 * Đọc gói + subscription + usage qua /api/v1/billing/*; checkout/cancel/profile sửa qua mutation.
 */

export type PlanCode = 'trial' | 'starter' | 'pro' | 'business';
export type SubscriptionStatus = 'trialing' | 'active' | 'past_due' | 'cancelled' | 'expired';
export type BillingCycle = 'monthly' | 'yearly' | 'trial';

export interface PlanLimits { max_channel_accounts: number }

export interface PlanFeatures {
    procurement: boolean;
    fifo_cogs: boolean;
    profit_reports: boolean;
    finance_settlements: boolean;
    demand_planning: boolean;
    mass_listing: boolean;
    automation_rules: boolean;
    priority_support: boolean;
}

export interface Plan {
    id: number;
    code: PlanCode;
    name: string;
    description: string | null;
    price_monthly: number;
    price_yearly: number;
    currency: string;
    trial_days: number;
    limits: PlanLimits;
    features: Partial<PlanFeatures>;
}

export interface Subscription {
    id: number;
    plan: Plan | null;
    plan_code: PlanCode | null;
    status: SubscriptionStatus;
    billing_cycle: BillingCycle;
    trial_ends_at: string | null;
    current_period_start: string | null;
    current_period_end: string | null;
    cancel_at: string | null;
    cancelled_at: string | null;
    days_left: number;
    is_trialing: boolean;
    is_past_due: boolean;
    /** SPEC 0020 — mốc phát hiện tenant vượt hạn mức; null = đang ổn. */
    over_quota_warned_at?: string | null;
    /** SPEC 0020 — đã quá 48h ân hạn ⇒ middleware đang chặn write. */
    over_quota_locked?: boolean;
    over_quota_grace_hours?: number;
}

export interface BillingUsage {
    channel_accounts: { used: number; limit: number };
    features?: Partial<PlanFeatures>;
}

export interface InvoiceLine {
    id: number;
    kind: 'plan' | 'addon' | 'discount';
    description: string;
    quantity: number;
    unit_price: number;
    amount: number;
}

export interface Invoice {
    id: number;
    code: string;
    status: 'draft' | 'pending' | 'paid' | 'void' | 'refunded';
    subscription_id: number;
    period_start: string | null;
    period_end: string | null;
    subtotal: number;
    tax: number;
    total: number;
    currency: string;
    due_at: string | null;
    paid_at: string | null;
    voided_at: string | null;
    created_at: string | null;
    lines?: InvoiceLine[];
}

export interface CheckoutSession { method: string; message?: string; redirect_url?: string; qr_url?: string }

export interface CheckoutResult { invoice: Invoice; gateway: string; checkout: CheckoutSession }

export interface BillingProfile {
    id?: number;
    company_name: string | null;
    tax_code: string | null;
    billing_address: string | null;
    contact_email: string | null;
    contact_phone: string | null;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

export function usePlans() {
    const api = useScopedApi();
    return useQuery({
        queryKey: ['billing', 'plans'],
        enabled: api != null,
        staleTime: 5 * 60_000,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Plan[] }>('/billing/plans');
            return data.data;
        },
    });
}

export function useSubscription() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['billing', tenantId, 'subscription'],
        enabled: api != null,
        staleTime: 60_000,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Subscription | null; meta?: { usage?: BillingUsage } }>('/billing/subscription');
            return { subscription: data.data, usage: data.meta?.usage ?? null };
        },
    });
}

export function useInvoices() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['billing', tenantId, 'invoices'],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Invoice[] }>('/billing/invoices');
            return data.data;
        },
    });
}

export function useCheckout() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: { plan_code: PlanCode; cycle: 'monthly' | 'yearly'; gateway: 'sepay' | 'vnpay' | 'momo' }) => {
            const { data } = await api!.post<{ data: CheckoutResult }>('/billing/checkout', vars);
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['billing', tenantId] });
        },
    });
}

export function useCancelSubscription() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async () => {
            const { data } = await api!.post<{ data: Subscription }>('/billing/subscription/cancel');
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['billing', tenantId, 'subscription'] }),
    });
}

export function useBillingProfile() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['billing', tenantId, 'profile'],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: BillingProfile }>('/billing/billing-profile');
            return data.data;
        },
    });
}

export function useUpdateBillingProfile() {
    const api = useScopedApi();
    const qc = useQueryClient();
    const tenantId = useCurrentTenantId();
    return useMutation({
        mutationFn: async (vars: Partial<BillingProfile>) => {
            const { data } = await api!.patch<{ data: BillingProfile }>('/billing/billing-profile', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['billing', tenantId, 'profile'] }),
    });
}

/** "trial-ish" — gói trial hoặc fallback. UI dùng để hiển thị banner "Đang dùng thử". */
export function isTrialLike(sub: Subscription | null | undefined): boolean {
    if (!sub) return false;
    return sub.is_trialing || sub.plan_code === 'trial';
}
