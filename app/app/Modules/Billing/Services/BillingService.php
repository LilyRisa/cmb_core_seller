<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\InvoiceLine;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Đầu mối tạo invoice + tính total + chuyển gói. SPEC 0018 §3.2.
 *
 * Không xử lý gateway (đó là việc của PaymentRegistry — PR2).
 */
class BillingService
{
    public function __construct(protected SubscriptionService $subscriptions) {}

    /**
     * Tính total theo cycle. Cycle `trial` ⇒ 0; `monthly`/`yearly` ⇒ giá DB.
     *
     * @return array{subtotal:int,tax:int,total:int,period_days:int,description:string}
     */
    public function computeInvoice(Plan $plan, string $cycle): array
    {
        $base = match ($cycle) {
            Subscription::CYCLE_MONTHLY => (int) $plan->price_monthly,
            Subscription::CYCLE_YEARLY => (int) $plan->price_yearly,
            Subscription::CYCLE_TRIAL => 0,
            default => throw ValidationException::withMessages([
                'cycle' => "Chu kỳ không hợp lệ: {$cycle}",
            ]),
        };

        $days = match ($cycle) {
            Subscription::CYCLE_YEARLY => 365,
            Subscription::CYCLE_MONTHLY => 30,
            Subscription::CYCLE_TRIAL => $plan->trial_days > 0 ? (int) $plan->trial_days : 14,
            default => 30,
        };

        $cycleLabel = match ($cycle) {
            Subscription::CYCLE_YEARLY => '1 năm',
            Subscription::CYCLE_MONTHLY => '1 tháng',
            Subscription::CYCLE_TRIAL => "Dùng thử {$days} ngày",
            default => $cycle,
        };

        return [
            'subtotal' => $base,
            'tax' => 0,
            'total' => $base,
            'period_days' => $days,
            'description' => "Gói {$plan->name} ({$cycleLabel})",
        ];
    }

    /**
     * Tạo hoá đơn pending cho user nâng cấp gói. Atomic. SPEC 0018 §3.2.
     * Trả invoice + subscription hiện tại (chưa swap — đợi InvoicePaid).
     *
     * Chặn:
     *   - Plan không tồn tại / không active ⇒ ValidationException PLAN_NOT_FOUND.
     *   - Đang ở cùng plan + cùng cycle, status active, chưa quá hạn ⇒ ALREADY_ON_PLAN.
     *   - Downgrade từ gói cao xuống thấp khi đang active ⇒ DOWNGRADE_NOT_ALLOWED (v1).
     */
    public function createUpgradeInvoice(int $tenantId, string $planCode, string $cycle): Invoice
    {
        return DB::transaction(function () use ($tenantId, $planCode, $cycle) {
            $plan = Plan::query()->where('code', $planCode)->where('is_active', true)->first();
            if ($plan === null) {
                throw ValidationException::withMessages(['plan_code' => 'Gói không tồn tại hoặc đã ngừng hoạt động.']);
            }

            if ($plan->code === Plan::CODE_TRIAL) {
                throw ValidationException::withMessages(['plan_code' => 'Không thể đặt mua gói dùng thử.']);
            }

            if (! in_array($cycle, [Subscription::CYCLE_MONTHLY, Subscription::CYCLE_YEARLY], true)) {
                throw ValidationException::withMessages(['cycle' => 'Chu kỳ chỉ chấp nhận monthly/yearly.']);
            }

            $current = $this->subscriptions->currentFor($tenantId);
            if ($current !== null && $current->plan_id === $plan->getKey()
                && $current->status === Subscription::STATUS_ACTIVE
                && $current->billing_cycle === $cycle
            ) {
                throw ValidationException::withMessages(['plan_code' => 'Bạn đã đang ở gói này (cùng chu kỳ).']);
            }

            // V1: chặn downgrade khi đang active gói cao hơn (so theo price_monthly).
            if ($current !== null
                && $current->status === Subscription::STATUS_ACTIVE
                && $current->plan !== null
                && (int) $current->plan->price_monthly > (int) $plan->price_monthly
            ) {
                throw ValidationException::withMessages([
                    'plan_code' => 'Không thể hạ gói khi đang còn hạn — vui lòng đợi hết kỳ.',
                ]);
            }

            $totals = $this->computeInvoice($plan, $cycle);
            $now = now();
            $periodStart = $now->copy();
            $periodEnd = $now->copy()->addDays($totals['period_days']);

            $invoice = Invoice::query()->create([
                'tenant_id' => $tenantId,
                // subscription_id tạm dùng subscription hiện tại; ActivateSubscription sẽ tạo subscription mới
                // khi invoice paid và update field này.
                'subscription_id' => $current?->getKey() ?? 0,
                'code' => Invoice::nextCode($tenantId),
                'status' => Invoice::STATUS_PENDING,
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'currency' => 'VND',
                'due_at' => $now->copy()->addDays(7),
                'meta' => [
                    'plan_code' => $plan->code,
                    'cycle' => $cycle,
                ],
            ]);

            InvoiceLine::query()->create([
                'invoice_id' => $invoice->getKey(),
                'kind' => InvoiceLine::KIND_PLAN,
                'description' => $totals['description'],
                'quantity' => 1,
                'unit_price' => $totals['subtotal'],
                'amount' => $totals['subtotal'],
            ]);

            return $invoice->fresh(['lines']);
        });
    }
}
