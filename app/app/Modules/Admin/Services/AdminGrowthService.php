<?php

namespace CMBcoreSeller\Modules\Admin\Services;

use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;

/**
 * Báo cáo tăng trưởng theo nguồn UTM — dùng ở `/admin/growth`
 * (SPEC 2026-07-22-facebook-pixel-capi-growth-attribution-design.md §5).
 *
 * Gom nhóm bằng PHP (không `selectRaw` JSON path) vì cú pháp trích JSON khác nhau giữa
 * SQLite (dev/test, `json_extract`) và Postgres (prod, `->>`) — Laravel không có API JSON
 * aggregate cross-driver. Quy mô tenant của SaaS này đủ nhỏ để nhóm an toàn trong bộ nhớ.
 */
class AdminGrowthService
{
    /**
     * @return list<array{source:string, signups:int, paid:int, conversion_rate:float, revenue_vnd:int}>
     */
    public function attribution(string $groupBy, ?Carbon $from, ?Carbon $to): array
    {
        $tenants = Tenant::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->get(['id', 'acquisition', 'created_at']);

        if ($tenants->isEmpty()) {
            return [];
        }

        $ids = $tenants->pluck('id')->all();

        $revenueByTenant = Invoice::query()->withoutGlobalScope(TenantScope::class)
            ->where('status', Invoice::STATUS_PAID)
            ->whereIn('tenant_id', $ids)
            ->selectRaw('tenant_id, SUM(total) as total')
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $paidByActiveSubscription = Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->whereIn('tenant_id', $ids)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('billing_cycle', '!=', Subscription::CYCLE_TRIAL)
            ->pluck('tenant_id');

        $paidTenantIds = collect($revenueByTenant->keys())->merge($paidByActiveSubscription)->unique();

        $groups = $tenants->groupBy(function (Tenant $t) use ($groupBy) {
            $value = (string) (($t->acquisition ?? [])[$groupBy] ?? '');

            return $value !== '' ? $value : 'Không xác định';
        });

        return $groups->map(function ($group, string $source) use ($paidTenantIds, $revenueByTenant) {
            $tenantIds = $group->pluck('id');
            $signups = $tenantIds->count();
            $paid = $tenantIds->intersect($paidTenantIds)->count();
            $revenue = (int) $tenantIds->map(fn ($id) => (int) ($revenueByTenant[$id] ?? 0))->sum();

            return [
                'source' => $source,
                'signups' => $signups,
                'paid' => $paid,
                'conversion_rate' => $signups > 0 ? round($paid / $signups * 100, 1) : 0.0,
                'revenue_vnd' => $revenue,
            ];
        })->values()->sortByDesc('signups')->values()->all();
    }
}
