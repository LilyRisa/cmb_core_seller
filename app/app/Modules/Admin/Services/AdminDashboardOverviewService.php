<?php

namespace CMBcoreSeller\Modules\Admin\Services;

use CMBcoreSeller\Modules\Billing\Contracts\AiUsageReporter;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;

/**
 * Số liệu tổng hợp cho trang "Tổng quan" admin — docs/superpowers/specs/
 * 2026-07-21-admin-panel-ux-redesign-design.md §6. Đọc trực tiếp model của Billing/Support/Tenancy
 * (giống pattern sẵn có ở AdminTenantController), riêng lượt gọi AI đi qua contract
 * `AiUsageReporter` (Admin không chạm bảng ai_usage_counters trực tiếp, theo doc-comment của
 * contract đó).
 */
class AdminDashboardOverviewService
{
    public function __construct(private AiUsageReporter $aiUsage) {}

    public function overview(): array
    {
        return [
            'tenants' => $this->tenants(),
            'revenue' => $this->revenue(),
            'support' => $this->support(),
            'ai_usage' => $this->aiUsageBlock(),
        ];
    }

    private function tenants(): array
    {
        $tz = app_display_tz();

        $activeTotal = Tenant::query()->where('status', 'active')->count();

        $byPlan = Subscription::withoutGlobalScope(TenantScope::class)
            ->whereIn('subscriptions.status', Subscription::ALIVE_STATUSES)
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->selectRaw('plans.code as plan_code, plans.name as plan_name, COUNT(*) as count')
            ->groupBy('plans.code', 'plans.name')
            ->orderByDesc('count')
            ->toBase()
            ->get()
            ->map(fn ($r) => ['plan_code' => $r->plan_code, 'plan_name' => $r->plan_name, 'count' => (int) $r->count])
            ->all();

        $from = Carbon::now($tz)->subDays(29)->startOfDay();
        $byDay = [];
        for ($i = 0; $i < 30; $i++) {
            $byDay[$from->clone()->addDays($i)->format('Y-m-d')] = 0;
        }
        Tenant::query()
            ->where('created_at', '>=', $from->clone()->setTimezone('UTC'))
            ->get(['created_at'])
            ->each(function ($t) use (&$byDay, $tz) {
                $d = $t->created_at->copy()->setTimezone($tz)->format('Y-m-d');
                if (isset($byDay[$d])) {
                    $byDay[$d]++;
                }
            });
        $newByDay = collect($byDay)->map(fn ($count, $date) => ['date' => $date, 'count' => $count])->values()->all();

        $trialSubs = Subscription::withoutGlobalScope(TenantScope::class)
            ->where('status', Subscription::STATUS_TRIALING)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [Carbon::now(), Carbon::now()->addDays(7)])
            ->orderBy('trial_ends_at')
            ->get(['tenant_id', 'trial_ends_at']);

        $trialTenantNames = Tenant::query()
            ->whereIn('id', $trialSubs->pluck('tenant_id'))
            ->pluck('name', 'id');

        $trialEndingSoon = $trialSubs
            ->map(fn (Subscription $s) => [
                'tenant_id' => $s->tenant_id,
                'tenant_name' => $trialTenantNames[$s->tenant_id] ?? '—',
                'trial_ends_at' => $s->trial_ends_at?->toIso8601String(),
            ])
            ->all();

        return [
            'active_total' => $activeTotal,
            'by_plan' => $byPlan,
            'new_by_day' => $newByDay,
            'trial_ending_soon' => $trialEndingSoon,
        ];
    }

    private function revenue(): array
    {
        $tz = app_display_tz();

        // MRR estimate — spec 2026-07-21 §6.2: sum over Subscription::ALIVE_STATUSES (bao gồm
        // 'trialing'), yearly billing_cycle quy đổi price_yearly/12 (chia nguyên, chấp nhận
        // under-count vì đây là số ước tính).
        $mrr = (int) Subscription::withoutGlobalScope(TenantScope::class)
            ->whereIn('subscriptions.status', Subscription::ALIVE_STATUSES)
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->select(['subscriptions.billing_cycle', 'plans.price_monthly', 'plans.price_yearly'])
            ->toBase()
            ->get()
            ->sum(fn ($r) => $r->billing_cycle === Subscription::CYCLE_YEARLY
                ? intdiv((int) $r->price_yearly, 12)
                : (int) $r->price_monthly);

        $monthStart = Carbon::now($tz)->startOfMonth()->setTimezone('UTC');
        $monthEnd = Carbon::now($tz)->endOfMonth()->setTimezone('UTC');
        $byStatus = Invoice::withoutGlobalScope(TenantScope::class)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(total), 0) as total_sum')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $paid = $byStatus->get(Invoice::STATUS_PAID);
        $pending = $byStatus->get(Invoice::STATUS_PENDING);

        $revenueByMonth = Invoice::withoutGlobalScope(TenantScope::class)
            ->where('status', Invoice::STATUS_PAID)
            ->where('paid_at', '>=', Carbon::now($tz)->subMonths(11)->startOfMonth()->setTimezone('UTC'))
            ->get(['paid_at', 'total'])
            ->groupBy(fn ($inv) => (int) $inv->paid_at->copy()->setTimezone($tz)->format('Ym'))
            ->map(fn ($group, $ym) => ['period_ym' => (int) $ym, 'total' => (int) $group->sum('total')])
            ->sortBy('period_ym')
            ->values()
            ->all();

        $activeVouchers = Voucher::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', Carbon::now()))
            ->count();

        return [
            'mrr_estimate' => $mrr,
            'invoices_this_month' => [
                'paid_count' => (int) ($paid->cnt ?? 0),
                'paid_total' => (int) ($paid->total_sum ?? 0),
                'pending_count' => (int) ($pending->cnt ?? 0),
                'pending_total' => (int) ($pending->total_sum ?? 0),
            ],
            'revenue_by_month' => $revenueByMonth,
            'active_vouchers' => $activeVouchers,
        ];
    }

    private function support(): array
    {
        $openCount = SupportConversation::withoutGlobalScope(TenantScope::class)
            ->where('status', SupportConversation::STATUS_OPEN)
            ->count();

        $avgResolutionHours = (float) (SupportConversation::withoutGlobalScope(TenantScope::class)
            ->where('status', SupportConversation::STATUS_CLOSED)
            ->whereNotNull('closed_at')
            ->get(['created_at', 'closed_at'])
            ->avg(fn ($c) => $c->created_at->diffInMinutes($c->closed_at) / 60) ?? 0);

        $recentAuditLog = AuditLog::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'action', 'admin_user_id', 'user_id', 'created_at'])
            ->map(fn (AuditLog $a) => [
                'action' => $a->action,
                'actor' => $a->admin_user_id ? "Admin #{$a->admin_user_id}" : ($a->user_id ? "User #{$a->user_id}" : '—'),
                'at' => $a->created_at->toIso8601String(),
            ])
            ->all();

        return [
            'open_count' => $openCount,
            'avg_resolution_hours' => round($avgResolutionHours, 1),
            'recent_audit_log' => $recentAuditLog,
        ];
    }

    private function aiUsageBlock(): array
    {
        $totalCalls = $this->aiUsage->totalCallsThisMonth();
        $topTenants = $this->aiUsage->topTenantsByUsageThisMonth(5);
        $tenantNames = Tenant::query()
            ->whereIn('id', array_column($topTenants, 'tenant_id'))
            ->pluck('name', 'id');

        return [
            'calls_this_month' => $totalCalls,
            'top_tenants' => array_map(fn ($r) => [
                'tenant_id' => $r['tenant_id'],
                'tenant_name' => $tenantNames[$r['tenant_id']] ?? '—',
                'calls_this_month' => $r['calls_this_month'],
            ], $topTenants),
        ];
    }
}
