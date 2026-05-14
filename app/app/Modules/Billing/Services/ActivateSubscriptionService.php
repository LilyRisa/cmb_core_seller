<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * Kích hoạt subscription mới sau khi invoice paid. SPEC 0018 §3.3.
 *
 * Luồng:
 *   - Tìm gói trong `invoice.meta.plan_code` + `cycle`.
 *   - Subscription "alive" hiện tại của tenant ⇒ mark cancelled (cancel_at=now) để partial
 *     unique index không xung đột (1 alive sub per tenant).
 *   - Tạo subscription mới `active` với period theo cycle (monthly = 30d, yearly = 365d).
 *   - Update `invoice.subscription_id` trỏ tới subscription mới (để FE/audit truy ngược).
 *
 * Idempotent: nếu invoice đã trỏ tới subscription `active` đúng plan/cycle ⇒ no-op.
 */
class ActivateSubscriptionService
{
    public function activate(Invoice $invoice): ?Subscription
    {
        $planCode = (string) ($invoice->meta['plan_code'] ?? '');
        $cycle = (string) ($invoice->meta['cycle'] ?? Subscription::CYCLE_MONTHLY);
        if ($planCode === '') {
            return null;
        }

        $plan = Plan::query()->where('code', $planCode)->first();
        if ($plan === null) {
            return null;
        }

        return DB::transaction(function () use ($invoice, $plan, $cycle): Subscription {
            // Đóng các subscription "alive" hiện tại của tenant.
            Subscription::query()->withoutGlobalScope(\CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope::class)
                ->where('tenant_id', $invoice->tenant_id)
                ->whereIn('status', Subscription::ALIVE_STATUSES)
                ->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancel_at' => now(),
                    'cancelled_at' => now(),
                    'ended_at' => now(),
                ]);

            $days = $cycle === Subscription::CYCLE_YEARLY ? 365 : 30;
            $now = now();

            $sub = Subscription::query()->create([
                'tenant_id' => $invoice->tenant_id,
                'plan_id' => $plan->getKey(),
                'status' => Subscription::STATUS_ACTIVE,
                'billing_cycle' => $cycle,
                'current_period_start' => $now,
                'current_period_end' => $now->copy()->addDays($days),
                'meta' => ['invoice_id' => $invoice->getKey()],
            ]);

            // Link ngược invoice → subscription mới (cho UI / audit).
            $invoice->forceFill(['subscription_id' => $sub->getKey()])->save();

            return $sub;
        });
    }
}
