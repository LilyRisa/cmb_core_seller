import { useMemo } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { tenantApi } from './api';
import { getCurrentTenantId, useAuth } from './auth';

/** Local mirror cho `useCurrentTenantId` (lib/tenant) — tránh import vòng. */
function useCurrentTenantId(): number | null {
    const { data: user } = useAuth();
    return getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;
}

function useScopedApi() {
    const tenantId = useCurrentTenantId();
    return useMemo(() => (tenantId == null ? null : tenantApi(tenantId)), [tenantId]);
}

/* ============================================================================
 * Types
 * ========================================================================== */

export type AccountType =
    | 'asset' | 'liability' | 'equity' | 'revenue' | 'expense'
    | 'cogs' | 'contra_revenue' | 'contra_asset' | 'clearing';

export type NormalBalance = 'debit' | 'credit';

export interface ChartAccount {
    id: number;
    code: string;
    name: string;
    type: AccountType;
    normal_balance: NormalBalance;
    parent_id: number | null;
    is_postable: boolean;
    is_active: boolean;
    vas_template: string;
    sort_order: number;
    description: string | null;
}

export type PeriodKind = 'month' | 'quarter' | 'year';

export type PeriodStatus = 'open' | 'closed' | 'locked';

export interface FiscalPeriod {
    id: number;
    code: string;
    kind: PeriodKind;
    start_date: string;
    end_date: string;
    status: PeriodStatus;
    status_label: string;
    closed_at: string | null;
    closed_by: number | null;
    close_note: string | null;
}

export interface JournalLine {
    id: number;
    line_no: number;
    account_id: number;
    account_code: string;
    account_name?: string;
    dr_amount: number;
    cr_amount: number;
    party_type: string | null;
    party_id: number | null;
    dim_warehouse_id: number | null;
    dim_shop_id: number | null;
    dim_sku_id: number | null;
    dim_order_id: number | null;
    dim_tax_code: string | null;
    memo: string | null;
}

export interface JournalEntry {
    id: number;
    code: string;
    posted_at: string;
    period_id: number;
    period_code?: string;
    narration: string | null;
    source_module: string;
    source_type: string;
    source_id: number | null;
    idempotency_key: string;
    is_adjustment: boolean;
    is_reversal_of_id: number | null;
    adjusted_period_id: number | null;
    total_debit: number;
    total_credit: number;
    currency: string;
    created_by: number | null;
    created_at: string | null;
    is_auto: boolean;
    lines?: JournalLine[];
}

export interface AccountBalance {
    id: number;
    account_id: number;
    account_code?: string;
    account_name?: string;
    period_id: number;
    period_code?: string;
    opening: number;
    debit: number;
    credit: number;
    closing: number;
    recomputed_at: string | null;
}

export interface PostRule {
    id: number;
    event_key: string;
    debit_account_code: string;
    credit_account_code: string;
    is_enabled: boolean;
    notes: string | null;
    updated_at: string | null;
}

export interface Paginated<T> {
    data: T[];
    meta: { current_page: number; last_page: number; per_page: number; total: number };
}

/* ============================================================================
 * Setup
 * ========================================================================== */

export function useAccountingSetupStatus() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'setup-status'],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: { initialized: boolean } }>('/accounting/setup/status');
            return data.data;
        },
    });
}

export function useAccountingSetup() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { year?: number }) => {
            const { data } = await api!.post<{ data: { accounts_created: number; periods_created: number; rules_created: number; initialized: boolean } }>('/accounting/setup', vars);
            return data.data;
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['accounting', tenantId] });
        },
    });
}

/* ============================================================================
 * Dashboard summary (thống kê nhanh — dùng ở DashboardPage)
 * ========================================================================== */

export interface AccountingDashboardSummary {
    initialized: boolean;
    current_period: { code: string; status: PeriodStatus; status_label: string } | null;
    cash: { total: number; accounts: number };
    ar: { total: number; overdue: number };
    ap: { total: number; overdue: number };
    pl_period: { revenue: number; cogs: number; gross_profit: number; opex: number; net_income: number } | null;
}

export function useAccountingDashboardSummary() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'dashboard-summary'],
        enabled: api != null,
        // 5 phút — không cần realtime, tránh hit DB liên tục (PL cộng dồn cả kỳ).
        staleTime: 5 * 60_000,
        retry: (failureCount, err) => {
            // 402 = plan không bật accounting → không thử lại, để FE biết ẩn block.
            const status = (err as { response?: { status?: number } })?.response?.status;
            return status !== 402 && status !== 403 && failureCount < 2;
        },
        queryFn: async () => {
            const { data } = await api!.get<{ data: AccountingDashboardSummary }>('/accounting/dashboard-summary');
            return data.data;
        },
    });
}

/* ============================================================================
 * Chart of Accounts
 * ========================================================================== */

export interface AccountFilters {
    type?: AccountType;
    q?: string;
    active_only?: boolean;
}

export function useChartAccounts(filters: AccountFilters = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'accounts', filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const params: Record<string, string | number | boolean> = {};
            if (filters.type) params.type = filters.type;
            if (filters.q) params.q = filters.q;
            if (filters.active_only) params.active_only = 1;
            const { data } = await api!.get<{ data: ChartAccount[] }>('/accounting/accounts', { params });
            return data.data;
        },
    });
}

export function useCreateChartAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { code: string; name: string; type: AccountType; parent_code?: string; normal_balance: NormalBalance; is_postable?: boolean; sort_order?: number; description?: string }) => {
            const { data } = await api!.post<{ data: ChartAccount }>('/accounting/accounts', vars);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'accounts'] }),
    });
}

export function useUpdateChartAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { id: number; name?: string; sort_order?: number; is_active?: boolean; is_postable?: boolean; description?: string }) => {
            const { id, ...payload } = vars;
            const { data } = await api!.patch<{ data: ChartAccount }>(`/accounting/accounts/${id}`, payload);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'accounts'] }),
    });
}

export function useDeleteChartAccount() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            await api!.delete(`/accounting/accounts/${id}`);
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'accounts'] }),
    });
}

/* ============================================================================
 * Fiscal periods
 * ========================================================================== */

export interface PeriodFilters {
    kind?: PeriodKind;
    year?: number;
}

export function useFiscalPeriods(filters: PeriodFilters = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'periods', filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            if (filters.kind) params.kind = filters.kind;
            if (filters.year) params.year = filters.year;
            const { data } = await api!.get<{ data: FiscalPeriod[] }>('/accounting/periods', { params });
            return data.data;
        },
    });
}

export function usePeriodAction() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { code: string; action: 'close' | 'reopen' | 'lock'; note?: string }) => {
            const { data } = await api!.post<{ data: FiscalPeriod }>(
                `/accounting/periods/${vars.code}/${vars.action}`,
                vars.note ? { note: vars.note } : {},
            );
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'periods'] }),
    });
}

export function useEnsureYearPeriods() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (year: number) => {
            const { data } = await api!.post<{ data: { created: number } }>('/accounting/periods/ensure-year', { year });
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'periods'] }),
    });
}

/* ============================================================================
 * Journal entries
 * ========================================================================== */

export interface JournalFilters {
    period?: string;
    source_module?: 'manual' | 'auto' | string;
    from?: string;
    to?: string;
    q?: string;
    account_code?: string;
    page?: number;
    per_page?: number;
}

export function useJournals(filters: JournalFilters = {}) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'journals', filters],
        enabled: api != null,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            (Object.keys(filters) as Array<keyof JournalFilters>).forEach((k) => {
                const v = filters[k];
                if (v !== undefined && v !== null && v !== '') params[k] = v as string | number;
            });
            const { data } = await api!.get<Paginated<JournalEntry>>('/accounting/journals', { params });
            return data;
        },
    });
}

export function useJournalDetail(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'journals', id],
        enabled: api != null && id != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: JournalEntry }>(`/accounting/journals/${id}`);
            return data.data;
        },
    });
}

export interface CreateJournalPayload {
    posted_at: string;
    narration?: string;
    lines: Array<{
        account_code: string;
        dr_amount?: number;
        cr_amount?: number;
        party_type?: string | null;
        party_id?: number | null;
        dim_warehouse_id?: number | null;
        dim_shop_id?: number | null;
        dim_sku_id?: number | null;
        dim_order_id?: number | null;
        memo?: string | null;
    }>;
}

export function useCreateJournal() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (payload: CreateJournalPayload) => {
            const idempotencyKey = `manual-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
            const { data } = await api!.post<{ data: JournalEntry }>('/accounting/journals', payload, {
                headers: { 'Idempotency-Key': idempotencyKey },
            });
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'journals'] }),
    });
}

export function useReverseJournal() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { id: number; reason?: string }) => {
            const { data } = await api!.post<{ data: JournalEntry }>(`/accounting/journals/${vars.id}/reverse`, {
                reason: vars.reason,
            });
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'journals'] }),
    });
}

/* ============================================================================
 * Balances
 * ========================================================================== */

export function useBalances(period: string | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'balances', period],
        enabled: api != null && !!period,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const { data } = await api!.get<{ data: AccountBalance[] }>('/accounting/balances', {
                params: { period },
            });
            return data.data;
        },
    });
}

export function useRecomputeBalances() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (period: string) => {
            const { data } = await api!.post<{ data: { rows: number; period: string } }>(
                '/accounting/balances/recompute',
                { period },
            );
            return data.data;
        },
        onSuccess: (_, period) => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'balances', period] }),
    });
}

/* ============================================================================
 * Post rules
 * ========================================================================== */

export function usePostRules() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'post-rules'],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: PostRule[] }>('/accounting/post-rules');
            return data.data;
        },
    });
}

export function useUpdatePostRule() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { event_key: string; debit_account_code: string; credit_account_code: string; is_enabled?: boolean; notes?: string }) => {
            const { event_key, ...payload } = vars;
            const { data } = await api!.patch<{ data: PostRule }>(`/accounting/post-rules/${event_key}`, payload);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'post-rules'] }),
    });
}

/* ============================================================================
 * Parties (khách hàng / nhà cung cấp) — cho PartyPicker
 * ========================================================================== */

export type PartyType = 'customer' | 'supplier';

export interface Party {
    id: number;
    type: PartyType;
    label: string;
    secondary: string | null;
}

/** Tìm khách/NCC theo từ khoá (debounce ở component). */
export function useParties(type: PartyType, q: string) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'parties', type, q],
        enabled: api != null,
        staleTime: 30_000,
        placeholderData: (prev) => prev,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Party[] }>('/accounting/parties', { params: { type, q: q || undefined } });
            return data.data;
        },
    });
}

/** Resolve nhãn cho các id đã chọn sẵn (preset) — dùng để hiển thị giá trị ban đầu của picker. */
export function usePartiesByIds(type: PartyType, ids: number[]) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const key = ids.slice().sort((a, b) => a - b).join(',');
    return useQuery({
        queryKey: ['accounting', tenantId, 'parties-by-ids', type, key],
        enabled: api != null && ids.length > 0,
        staleTime: 5 * 60_000,
        queryFn: async () => {
            const { data } = await api!.get<{ data: Party[] }>('/accounting/parties', { params: { type, ids: key } });
            return data.data;
        },
    });
}

/* ============================================================================
 * Cash accounts & Bank reconciliation (Phase 7.4)
 * ========================================================================== */

export type CashKind = 'cash' | 'bank' | 'ewallet' | 'cod_intransit';

export interface CashAccount {
    id: number;
    code: string;
    name: string;
    kind: CashKind;
    bank_name: string | null;
    account_no: string | null;
    account_holder: string | null;
    currency: string;
    gl_account_id: number;
    gl_account_code?: string;
    is_active: boolean;
    description: string | null;
    balance: number;
}

export interface BankStatement {
    id: number;
    cash_account_id: number;
    period_start: string;
    period_end: string;
    imported_from: string;
    lines_count: number;
    total_in: number;
    total_out: number;
    status: string;
    created_at: string | null;
}

export type BankLineStatus = 'unmatched' | 'matched' | 'ignored';

export interface BankStatementLine {
    id: number;
    bank_statement_id: number;
    txn_date: string;
    amount: number;
    counter_party: string | null;
    memo: string | null;
    external_ref: string | null;
    status: BankLineStatus;
    matched_ref_type: string | null;
    matched_ref_id: number | null;
    matched_journal_entry_id: number | null;
}

export function useCashAccounts() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'cash-accounts'],
        enabled: api != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: CashAccount[] }>('/accounting/cash-accounts');
            return data.data;
        },
    });
}

export function useBankStatements(cashAccountId?: number) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'bank-statements', cashAccountId ?? null],
        enabled: api != null,
        retry: (n, err) => {
            const s = (err as { response?: { status?: number } })?.response?.status;
            return s !== 402 && s !== 403 && n < 1;
        },
        queryFn: async () => {
            const params: Record<string, number> = {};
            if (cashAccountId) params.cash_account_id = cashAccountId;
            const { data } = await api!.get<{ data: BankStatement[]; meta: unknown }>('/accounting/bank-statements', { params });
            return data.data;
        },
    });
}

export function useBankStatementDetail(id: number | null) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'bank-statement', id],
        enabled: api != null && id != null,
        queryFn: async () => {
            const { data } = await api!.get<{ data: BankStatement & { lines: BankStatementLine[] } }>(`/accounting/bank-statements/${id}`);
            return data.data;
        },
    });
}

export function useMatchBankLine() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (vars: { id: number; ref_type: 'customer_receipt' | 'vendor_payment' | 'journal_entry'; ref_id: number; journal_entry_id?: number }) => {
            const { id, ...payload } = vars;
            const { data } = await api!.post<{ data: BankStatementLine }>(`/accounting/bank-statement-lines/${id}/match`, payload);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId] }),
    });
}

export function useIgnoreBankLine() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) => {
            const { data } = await api!.post<{ data: BankStatementLine }>(`/accounting/bank-statement-lines/${id}/ignore`);
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId] }),
    });
}

/* ============================================================================
 * VAT / Tờ khai thuế (Phase 7.5)
 * ========================================================================== */

export interface VatAggregate {
    output_vat: number;
    input_vat: number;
    net_payable: number;
}

export function useVatReport(period: string | undefined) {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    return useQuery({
        queryKey: ['accounting', tenantId, 'vat', period],
        enabled: api != null && !!period,
        retry: (n, err) => {
            const s = (err as { response?: { status?: number } })?.response?.status;
            return s !== 402 && s !== 403 && n < 1;
        },
        queryFn: async () => {
            const { data } = await api!.get<{ data: VatAggregate; meta: { period: string } }>('/accounting/reports/vat', { params: { period } });
            return data.data;
        },
    });
}

export function useCreateTaxFiling() {
    const api = useScopedApi();
    const tenantId = useCurrentTenantId();
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (period: string) => {
            const { data } = await api!.post<{ data: unknown }>('/accounting/tax-filings', { period });
            return data.data;
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['accounting', tenantId, 'vat'] }),
    });
}

/* ============================================================================
 * Helpers
 * ========================================================================== */

/** Format VND amount: 1.234.567 ₫ (no decimals — VND không có cents). */
export function formatVND(amount: number): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(amount);
}

/** Format số (1.234.567) cho cột Nợ/Có — không kèm ký hiệu ₫ để gọn bảng. */
export function formatAmount(amount: number): string {
    return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(amount);
}

/** Nhãn tiếng Việt cho `event_key` (post rule). */
export const EVENT_KEY_LABEL: Record<string, string> = {
    'inventory.goods_receipt.confirmed': 'Nhập kho từ NCC',
    'inventory.stock_transfer': 'Chuyển kho',
    'inventory.stocktake_adjust.in': 'Kiểm kê — thừa',
    'inventory.stocktake_adjust.out': 'Kiểm kê — thiếu',
    'orders.shipped.revenue': 'Đơn shipped — doanh thu',
    'orders.shipped.vat': 'Đơn shipped — VAT đầu ra',
    'orders.shipped.cogs': 'Đơn shipped — giá vốn',
    'finance.settlement.commission': 'Đối soát — hoa hồng sàn',
    'finance.settlement.payment_fee': 'Đối soát — phí thanh toán',
    'finance.settlement.shipping_fee': 'Đối soát — phí ship',
    'finance.settlement.voucher_seller': 'Đối soát — voucher của shop',
    'finance.settlement.adjustment': 'Đối soát — điều chỉnh',
    'finance.settlement.payout': 'Sàn chuyển tiền về (payout)',
    'procurement.vendor_bill.recorded': 'Hoá đơn NCC',
    'procurement.vendor_bill.vat': 'Hoá đơn NCC — VAT đầu vào',
    'cash.receipt.from_customer': 'Phiếu thu — từ khách',
    'cash.payment.to_supplier': 'Phiếu chi — trả NCC',
    'bank.receipt.from_customer': 'Báo có — từ khách',
    'bank.payment.to_supplier': 'Báo nợ — trả NCC',
};

export const ACCOUNT_TYPE_LABEL: Record<AccountType, string> = {
    asset: 'Tài sản',
    liability: 'Nợ phải trả',
    equity: 'Vốn CSH',
    revenue: 'Doanh thu',
    expense: 'Chi phí',
    cogs: 'Giá vốn',
    contra_revenue: 'Giảm trừ DT',
    contra_asset: 'Hao mòn / Dự phòng',
    clearing: 'Trung gian',
};

export const ACCOUNT_TYPE_COLOR: Record<AccountType, string> = {
    asset: 'blue',
    liability: 'orange',
    equity: 'purple',
    revenue: 'green',
    expense: 'red',
    cogs: 'volcano',
    contra_revenue: 'magenta',
    contra_asset: 'gold',
    clearing: 'default',
};

export const PERIOD_STATUS_COLOR: Record<PeriodStatus, string> = {
    open: 'green',
    closed: 'orange',
    locked: 'red',
};
