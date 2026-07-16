<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\AiCreditWallet;
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
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected VoucherService $vouchers,
        protected AiCreditService $aiCredits,
    ) {}

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
     *
     * @param  ?string  $termsVersion  Version điều khoản hoàn tiền đã chấp nhận — LUÔN truyền giá trị từ
     *                                 `config('billing.refund_terms_version')` phía server, không dùng input client.
     * @param  ?string  $termsAcceptedAt  Thời điểm chấp nhận điều khoản (ISO-8601), lưu vào `invoices.meta`.
     */
    public function createUpgradeInvoice(
        int $tenantId,
        string $planCode,
        string $cycle,
        ?string $voucherCode = null,
        ?int $userId = null,
        ?string $termsVersion = null,
        ?string $termsAcceptedAt = null,
    ): Invoice {
        return DB::transaction(function () use ($tenantId, $planCode, $cycle, $voucherCode, $userId, $termsVersion, $termsAcceptedAt) {
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
                'meta' => array_filter([
                    'plan_code' => $plan->code,
                    'cycle' => $cycle,
                    'terms_version' => $termsVersion,
                    'terms_accepted_at' => $termsAcceptedAt,
                ], fn ($v) => $v !== null),
            ]);

            InvoiceLine::query()->create([
                'invoice_id' => $invoice->getKey(),
                'kind' => InvoiceLine::KIND_PLAN,
                'description' => $totals['description'],
                'quantity' => 1,
                'unit_price' => $totals['subtotal'],
                'amount' => $totals['subtotal'],
            ]);

            // SPEC 0023 — áp voucher nếu có (trong transaction). Hỏng ⇒ rollback toàn bộ invoice.
            if ($voucherCode !== null && $voucherCode !== '') {
                $voucher = $this->vouchers->validate($voucherCode, $plan->code, $tenantId);
                $this->vouchers->redeemAtCheckout($voucher, $invoice, $userId);
                $invoice->refresh();
            }

            return $invoice->fresh(['lines']);
        });
    }

    /**
     * Hoá đơn MUA thêm lượt gọi AI (SPEC 0032): tối thiểu 500, bước 100, tổng đã mua ≤ 5000,
     * 100đ/lượt. Cần đang dùng gói có AI. Khi paid ⇒ {@see ActivateSubscriptionService} cộng credit.
     */
    public function createAiCreditInvoice(int $tenantId, int $amount): Invoice
    {
        return DB::transaction(function () use ($tenantId, $amount): Invoice {
            if (! $this->aiCredits->aiEnabled($tenantId)) {
                throw ValidationException::withMessages(['amount' => 'Cần đang dùng gói trả phí có AI để mua thêm lượt.']);
            }
            if ($amount < AiCreditWallet::PURCHASE_MIN || $amount % AiCreditWallet::PURCHASE_STEP !== 0) {
                throw ValidationException::withMessages(['amount' => 'Số lượt mua tối thiểu '.AiCreditWallet::PURCHASE_MIN.', bước '.AiCreditWallet::PURCHASE_STEP.'.']);
            }
            $balance = $this->aiCredits->wallet($tenantId)->purchased_balance;
            if ($balance + $amount > AiCreditWallet::PURCHASE_MAX_BALANCE) {
                throw ValidationException::withMessages(['amount' => 'Tổng lượt đã mua tối đa '.AiCreditWallet::PURCHASE_MAX_BALANCE.' (đang có '.$balance.').']);
            }

            $pricePerCredit = 100;
            $subtotal = $amount * $pricePerCredit;
            $now = now();

            $invoice = Invoice::query()->create([
                'tenant_id' => $tenantId,
                'subscription_id' => $this->subscriptions->currentFor($tenantId)?->getKey() ?? 0,
                'code' => Invoice::nextCode($tenantId),
                'status' => Invoice::STATUS_PENDING,
                'period_start' => $now->format('Y-m-d'),
                'period_end' => $now->format('Y-m-d'),
                'subtotal' => $subtotal,
                'tax' => 0,
                'total' => $subtotal,
                'currency' => 'VND',
                'due_at' => $now->copy()->addDays(7),
                'meta' => ['ai_credits' => $amount],
            ]);

            InvoiceLine::query()->create([
                'invoice_id' => $invoice->getKey(),
                'kind' => InvoiceLine::KIND_ADDON,
                'description' => "Mua {$amount} lượt gọi AI",
                'quantity' => $amount,
                'unit_price' => $pricePerCredit,
                'amount' => $subtotal,
            ]);

            return $invoice->fresh(['lines']);
        });
    }
}
