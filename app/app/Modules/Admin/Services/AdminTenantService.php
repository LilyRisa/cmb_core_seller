<?php

namespace CMBcoreSeller\Modules\Admin\Services;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\OverQuotaCheckService;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Billing\Services\UsageService;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Services\ChannelConnectionService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service hợp lý hoá các hành động super-admin can thiệp vào tenant. SPEC 0020.
 *
 * Mọi method ghi audit_logs với `tenant_id` = target tenant + `user_id` = admin.
 * Bypass TenantScope tường minh (admin không có tenant context).
 */
class AdminTenantService
{
    public function __construct(
        protected ChannelConnectionService $channels,
        protected SubscriptionService $subscriptions,
        protected UsageService $usage,
        protected OverQuotaCheckService $overQuota,
    ) {}

    /**
     * Xoá kết nối kênh hộ khách hàng (force, không cần xác nhận tên — reason bắt buộc).
     * Reuse `ChannelConnectionService::deleteWithOrders` (xoá kết nối + đơn + sku_mappings + nhả tồn).
     *
     * @return array{deleted_orders:int, unlinked_skus:int}
     */
    public function forceDeleteChannelAccount(Tenant $tenant, int $channelAccountId, string $reason, int $adminUserId): array
    {
        $this->requireReason($reason);
        $account = ChannelAccount::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->findOrFail($channelAccountId);

        $result = $this->channels->deleteWithOrders($account, $adminUserId);

        AuditLog::query()->create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $adminUserId,
            'action' => 'admin.channel_account.delete',
            'auditable_type' => $account->getMorphClass(),
            'auditable_id' => $account->getKey(),
            'changes' => [
                'reason' => $reason,
                'provider' => $account->provider,
                'shop_name' => $account->effectiveName(),
                'deleted_orders' => $result['deleted_orders'],
                'unlinked_skus' => $result['unlinked_skus'],
            ],
            'ip' => request()->ip(),
        ]);

        // Trigger ngay over-quota recompute (không đợi scheduler) — UX banner cập nhật sớm.
        $sub = $this->subscriptions->currentFor((int) $tenant->getKey());
        if ($sub !== null) {
            $this->overQuota->apply($sub);
        }

        return $result;
    }

    /**
     * Đổi gói tenant ngay lập tức (bypass DOWNGRADE_NOT_ALLOWED của BillingService).
     * Subscription cũ ⇒ cancelled `cancelled_at=now`; subscription mới ⇒ active, period_end = now + cycle.
     * KHÔNG tạo invoice (admin tự xử lý thanh toán bên ngoài).
     */
    public function changePlan(Tenant $tenant, string $planCode, string $cycle, string $reason, int $adminUserId): Subscription
    {
        $this->requireReason($reason);
        if (! in_array($cycle, [Subscription::CYCLE_MONTHLY, Subscription::CYCLE_YEARLY, Subscription::CYCLE_TRIAL], true)) {
            throw ValidationException::withMessages(['cycle' => 'Chu kỳ chỉ chấp nhận monthly/yearly/trial.']);
        }
        $plan = Plan::query()->where('code', $planCode)->where('is_active', true)->first();
        if ($plan === null) {
            throw ValidationException::withMessages(['plan_code' => "Plan {$planCode} không tồn tại hoặc đã ngừng."]);
        }

        $tenantId = (int) $tenant->getKey();
        $days = match ($cycle) {
            Subscription::CYCLE_YEARLY => 365,
            Subscription::CYCLE_MONTHLY => 30,
            default => max($plan->trial_days, 14),
        };

        return DB::transaction(function () use ($tenantId, $plan, $cycle, $days, $reason, $adminUserId) {
            $current = $this->subscriptions->currentFor($tenantId);
            $fromCode = $current?->plan?->code;

            if ($current !== null) {
                $current->forceFill([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancel_at' => now(),
                    'ended_at' => now(),
                ])->save();
            }

            $now = now();
            $new = Subscription::query()->create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->getKey(),
                'status' => Subscription::STATUS_ACTIVE,
                'billing_cycle' => $cycle,
                'current_period_start' => $now,
                'current_period_end' => $now->copy()->addDays($days),
                'meta' => ['admin_set_by' => $adminUserId, 'reason' => $reason],
            ]);

            AuditLog::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $adminUserId,
                'action' => 'admin.subscription.change',
                'auditable_type' => $new->getMorphClass(),
                'auditable_id' => $new->getKey(),
                'changes' => [
                    'from_plan' => $fromCode,
                    'to_plan' => $plan->code,
                    'cycle' => $cycle,
                    'reason' => $reason,
                ],
                'ip' => request()->ip(),
            ]);

            // Recompute over-quota cho subscription mới (gói thấp hơn có thể trigger banner).
            $new->load('plan');
            $this->overQuota->apply($new);

            return $new->fresh(['plan']) ?? $new;
        });
    }

    public function suspend(Tenant $tenant, string $reason, int $adminUserId): Tenant
    {
        $this->requireReason($reason);
        $tenant->forceFill(['status' => 'suspended'])->save();
        AuditLog::query()->create([
            'tenant_id' => (int) $tenant->getKey(),
            'user_id' => $adminUserId,
            'action' => 'admin.tenant.suspend',
            'auditable_type' => $tenant->getMorphClass(),
            'auditable_id' => $tenant->getKey(),
            'changes' => ['reason' => $reason],
            'ip' => request()->ip(),
        ]);

        return $tenant;
    }

    public function reactivate(Tenant $tenant, int $adminUserId): Tenant
    {
        $tenant->forceFill(['status' => 'active'])->save();
        AuditLog::query()->create([
            'tenant_id' => (int) $tenant->getKey(),
            'user_id' => $adminUserId,
            'action' => 'admin.tenant.reactivate',
            'auditable_type' => $tenant->getMorphClass(),
            'auditable_id' => $tenant->getKey(),
            'ip' => request()->ip(),
        ]);

        return $tenant;
    }

    private function requireReason(string $reason): void
    {
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Lý do phải có tối thiểu 10 ký tự.']);
        }
    }
}
