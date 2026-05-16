<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\InvoiceLine;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\Voucher;
use CMBcoreSeller\Modules\Billing\Models\VoucherRedemption;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

/**
 * Voucher engine — SPEC 0023.
 *
 * 2 luồng:
 *   - `redeemAtCheckout(Voucher, Invoice, userId)` — user redeem ở checkout (kind=percent|fixed).
 *     Áp discount line vào invoice + tăng counter + ghi redemption.
 *   - `grant(Voucher, tenantId, adminUserId)` — admin grant trực tiếp (kind=free_days|plan_upgrade).
 *     Áp lên subscription (extend / swap plan) + ghi redemption.
 *
 * Mọi method idempotent ở mức "1 voucher cho 1 invoice" (partial unique index ở DB).
 */
class VoucherService
{
    public function __construct(protected SubscriptionService $subscriptions) {}

    /**
     * Tìm + validate voucher theo code. Ném `ValidationException` với code envelope nếu hỏng.
     */
    public function validate(string $code, ?string $planCode = null): Voucher
    {
        $voucher = Voucher::query()->where('code', $code)->first();
        if ($voucher === null || ! $voucher->is_active) {
            $this->fail('INVALID_VOUCHER', 'Mã ưu đãi không hợp lệ.');
        }
        if (! $voucher->isInWindow()) {
            $this->fail('VOUCHER_EXPIRED', 'Mã ưu đãi đã hết hạn hoặc chưa tới ngày áp dụng.');
        }
        if ($voucher->isExhausted()) {
            $this->fail('VOUCHER_EXHAUSTED', 'Mã ưu đãi đã hết lượt sử dụng.');
        }
        if ($planCode !== null && ! $voucher->isValidForPlan($planCode)) {
            $this->fail('VOUCHER_NOT_FOR_PLAN', 'Mã ưu đãi không áp dụng cho gói này.');
        }

        return $voucher;
    }

    /** Tính discount preview cho UI (không ghi DB). */
    public function previewDiscount(Voucher $voucher, int $subtotal): int
    {
        if (! $voucher->isRedeemableAtCheckout()) {
            return 0;
        }
        if ($voucher->kind === Voucher::KIND_PERCENT) {
            $pct = max(0, min(100, $voucher->value));

            return intdiv($subtotal * $pct, 100);
        }

        // fixed
        return min($subtotal, max(0, $voucher->value));
    }

    /**
     * Áp voucher vào invoice ở checkout. Phải gọi TRONG transaction của createUpgradeInvoice.
     * - Lock voucher row, check exhausted (race-safe).
     * - Tạo `invoice_lines` kind=discount với amount âm.
     * - Update invoice.subtotal/total.
     * - Insert voucher_redemption + increment redemption_count.
     */
    public function redeemAtCheckout(Voucher $voucher, Invoice $invoice, ?int $userId): VoucherRedemption
    {
        if (! $voucher->isRedeemableAtCheckout()) {
            $this->fail('VOUCHER_NOT_REDEEMABLE', 'Mã này chỉ admin được áp dụng trực tiếp, không dùng ở checkout.');
        }

        // Lock voucher row để race-safe.
        $locked = Voucher::query()->where('id', $voucher->id)->lockForUpdate()->first();
        if ($locked === null) {
            $this->fail('INVALID_VOUCHER', 'Mã ưu đãi không tồn tại.');
        }
        if (! $locked->is_active || ! $locked->isInWindow()) {
            $this->fail('VOUCHER_EXPIRED', 'Mã ưu đãi đã hết hạn.');
        }
        if ($locked->isExhausted()) {
            $this->fail('VOUCHER_EXHAUSTED', 'Mã ưu đãi đã hết lượt sử dụng.');
        }

        $discount = $this->previewDiscount($locked, (int) $invoice->subtotal);
        if ($discount <= 0) {
            $this->fail('INVALID_VOUCHER', 'Voucher không tạo được discount hợp lệ.');
        }

        // Insert discount line + update invoice totals.
        InvoiceLine::query()->create([
            'invoice_id' => $invoice->getKey(),
            'kind' => InvoiceLine::KIND_DISCOUNT,
            'description' => "Áp mã ưu đãi {$locked->code}",
            'quantity' => 1,
            'unit_price' => -$discount,
            'amount' => -$discount,
        ]);

        $newSubtotal = max(0, (int) $invoice->subtotal - $discount);
        $newTotal = max(0, (int) $invoice->total - $discount);
        $invoice->forceFill([
            'subtotal' => $newSubtotal,
            'total' => $newTotal,
            'meta' => array_merge((array) $invoice->meta, ['voucher_code' => $locked->code, 'voucher_discount' => $discount]),
        ])->save();

        $redemption = VoucherRedemption::query()->create([
            'voucher_id' => $locked->getKey(),
            'tenant_id' => $invoice->tenant_id,
            'user_id' => $userId,
            'invoice_id' => $invoice->getKey(),
            'discount_amount' => $discount,
            'granted_days' => 0,
        ]);

        $locked->increment('redemption_count');

        return $redemption;
    }

    /**
     * Admin grant voucher trực tiếp tới tenant.
     *  - free_days: extend `current_period_end` thêm `value` ngày (giữ status).
     *  - plan_upgrade: swap plan tới `value=plan_id`, period = `value` days (lấy từ meta.duration_days, default 30).
     *
     * @return array{redemption: VoucherRedemption, subscription: Subscription|null, applied: array<string, mixed>}
     */
    public function grant(Voucher $voucher, int $tenantId, int $adminUserId, string $reason): array
    {
        if ($voucher->isRedeemableAtCheckout()) {
            $this->fail('VOUCHER_KIND_FOR_CHECKOUT', 'Voucher này dành cho user redeem ở checkout, không grant được.');
        }

        return DB::transaction(function () use ($voucher, $tenantId, $adminUserId, $reason) {
            $locked = Voucher::query()->where('id', $voucher->id)->lockForUpdate()->first();
            if ($locked === null || ! $locked->is_active) {
                $this->fail('INVALID_VOUCHER', 'Voucher không hợp lệ.');
            }
            if (! $locked->isInWindow()) {
                $this->fail('VOUCHER_EXPIRED', 'Voucher đã hết hạn.');
            }
            if ($locked->isExhausted()) {
                $this->fail('VOUCHER_EXHAUSTED', 'Voucher hết lượt.');
            }

            $sub = $this->subscriptions->currentFor($tenantId);
            $applied = [];

            if ($locked->kind === Voucher::KIND_FREE_DAYS) {
                if ($sub === null) {
                    $this->fail('NO_ACTIVE_SUBSCRIPTION', 'Tenant chưa có subscription để extend.');
                }
                $days = max(1, $locked->value);
                $base = $sub->current_period_end->isFuture()
                    ? $sub->current_period_end : now();
                $sub->forceFill([
                    'current_period_end' => $base->copy()->addDays($days),
                    'meta' => array_merge((array) $sub->meta, [
                        'last_voucher_code' => $locked->code,
                        'last_voucher_grant_by' => $adminUserId,
                    ]),
                ])->save();
                $applied = ['kind' => 'free_days', 'days' => $days, 'new_period_end' => $sub->current_period_end->toIso8601String()];
            } elseif ($locked->kind === Voucher::KIND_PLAN_UPGRADE) {
                $plan = Plan::query()->where('id', $locked->value)->where('is_active', true)->first();
                if ($plan === null) {
                    $this->fail('INVALID_VOUCHER', 'Voucher trỏ tới plan không tồn tại.');
                }
                $days = (int) ($locked->meta['duration_days'] ?? 30);
                $now = now();
                if ($sub !== null) {
                    $sub->forceFill([
                        'status' => Subscription::STATUS_CANCELLED,
                        'cancelled_at' => $now,
                        'cancel_at' => $now,
                        'ended_at' => $now,
                    ])->save();
                }
                $sub = Subscription::query()->withoutGlobalScope(TenantScope::class)->create([
                    'tenant_id' => $tenantId,
                    'plan_id' => $plan->getKey(),
                    'status' => Subscription::STATUS_TRIALING,        // tặng ⇒ trial trên gói cao
                    'billing_cycle' => Subscription::CYCLE_TRIAL,
                    'current_period_start' => $now,
                    'current_period_end' => $now->copy()->addDays($days),
                    'meta' => [
                        'granted_by_voucher' => $locked->code,
                        'granted_by_admin' => $adminUserId,
                        'duration_days' => $days,
                    ],
                ]);
                $applied = ['kind' => 'plan_upgrade', 'plan_code' => $plan->code, 'days' => $days];
            }

            $redemption = VoucherRedemption::query()->create([
                'voucher_id' => $locked->getKey(),
                'tenant_id' => $tenantId,
                'user_id' => null,
                'invoice_id' => null,
                'subscription_id' => $sub?->getKey(),
                'discount_amount' => 0,
                'granted_days' => $locked->kind === Voucher::KIND_FREE_DAYS ? $locked->value : 0,
                'meta' => ['by_admin' => $adminUserId, 'reason' => $reason],
            ]);

            $locked->increment('redemption_count');

            return ['redemption' => $redemption, 'subscription' => $sub, 'applied' => $applied];
        });
    }

    /**
     * Throw 422 JSON envelope với code cụ thể (vd `VOUCHER_EXPIRED`) — KHÔNG dùng
     * ValidationException vì handler chuẩn sẽ map sang `VALIDATION_FAILED`.
     *
     * @return never
     */
    private function fail(string $code, string $message): void
    {
        throw new HttpResponseException(response()->json([
            'error' => ['code' => $code, 'message' => $message],
        ], 422));
    }
}
