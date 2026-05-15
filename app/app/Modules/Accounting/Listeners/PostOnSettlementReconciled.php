<?php

namespace CMBcoreSeller\Modules\Accounting\Listeners;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\JournalService;
use CMBcoreSeller\Modules\Accounting\Services\PostRuleResolver;
use CMBcoreSeller\Modules\Finance\Models\Settlement;
use CMBcoreSeller\Modules\Finance\Models\SettlementLine;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\Log;

/**
 * Phase 7.2 — SPEC 0019.
 *
 * Listen `Finance\SettlementReconciled` (chưa có event chuyên dụng — sẽ wire qua Service::reconcile sau).
 * Đối với mỗi `settlement_line` đã matched order_id:
 *   - fee âm (commission/payment_fee/shipping_fee/voucher_seller/adjustment khi seller chịu):
 *       Dr 6421/521/etc. / Cr 131 (party=customer của order)
 *   - revenue/voucher_platform/shipping_subsidy (dương):
 *       Dr 131 / Cr 5111 hoặc 511 (đã hạch toán ở OrderShipped) — SKIP để khỏi double-count.
 *   - payout (= NET = revenue − fees ≈ tổng amount): Dr 1121 / Cr 131
 *
 * Vì hiện chưa có dedicated event, listener này được tạo nhưng đăng ký TRIGGER MANUAL qua
 * `posting:settlement {id}` artisan command (Phase 7.5 sẽ wire vào event). Tạm thời module
 * Finance phát event `SettlementReconciled` sau khi reconcile xong — đăng ký event trong Service Provider.
 *
 * Idempotency key per (settlement_id, line_kind).
 */
class PostOnSettlementReconciled
{
    public function __construct(
        private readonly JournalService $journals,
        private readonly PostRuleResolver $rules,
        private readonly AccountingSetupService $setup,
    ) {}

    /**
     * Gọi tay/Listener khi Settlement reconciled. Tạo nhiều entries chia theo (order, fee_type).
     */
    public function postForSettlement(Settlement $settlement): int
    {
        $tenantId = (int) $settlement->tenant_id;
        if (! $this->setup->isInitialized($tenantId)) {
            return 0;
        }

        $lines = SettlementLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('settlement_id', $settlement->getKey())
            ->whereNotNull('order_id')
            ->get();
        if ($lines->isEmpty()) {
            return 0;
        }

        // Pre-load orders để lấy customer_id (party).
        $orderIds = $lines->pluck('order_id')->unique()->values()->all();
        $orders = Order::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereIn('id', $orderIds)
            ->get()->keyBy('id');

        // Group by (order_id, fee_type) — mỗi cặp = 1 entry để dễ debug.
        $posted = 0;
        $groups = $lines->groupBy(fn ($l) => $l->order_id.':'.$l->fee_type);
        foreach ($groups as $gkey => $glines) {
            [$orderId, $feeType] = explode(':', $gkey);
            $orderId = (int) $orderId;
            $order = $orders->get($orderId);
            if (! $order) {
                continue;
            }
            $totalAmount = (int) $glines->sum('amount');
            if ($totalAmount === 0) {
                continue;
            }

            $eventKey = $this->ruleKeyForFeeType($feeType);
            if ($eventKey === null) {
                continue; // revenue/voucher_platform/shipping_subsidy không post ở đây (đã ở OrderShipped).
            }
            $rule = $this->rules->resolve($tenantId, $eventKey);
            if ($rule === null || ! $rule['enabled']) {
                continue;
            }
            $debitAcc = $this->postable($tenantId, $rule['debit']);
            $creditAcc = $this->postable($tenantId, $rule['credit']);
            if (! $debitAcc || ! $creditAcc) {
                Log::warning('Accounting: settlement post-rule missing account', ['rule' => $rule, 'event' => $eventKey, 'tenant' => $tenantId]);
                continue;
            }

            // amount signed: âm = shop trả, dương = shop thu. Dùng abs.
            $absAmount = abs($totalAmount);
            $partyOpts = $order->customer_id ? ['party_type' => 'customer', 'party_id' => (int) $order->customer_id] : [];

            $lines2 = [
                JournalLineDTO::debit($debitAcc->code, $absAmount, array_merge($partyOpts, [
                    'dim_order_id' => (int) $order->id,
                    'dim_shop_id' => $order->channel_account_id ? (int) $order->channel_account_id : null,
                    'memo' => sprintf('Phí sàn — %s', $feeType),
                ])),
                JournalLineDTO::credit($creditAcc->code, $absAmount, array_merge($partyOpts, [
                    'dim_order_id' => (int) $order->id,
                    'dim_shop_id' => $order->channel_account_id ? (int) $order->channel_account_id : null,
                    'memo' => sprintf('Trừ công nợ 131 theo phí %s', $feeType),
                ])),
            ];

            try {
                $this->journals->post(new JournalEntryDTO(
                    tenantId: $tenantId,
                    postedAt: $settlement->paid_at ?? $settlement->reconciled_at ?? now(),
                    sourceModule: 'finance',
                    sourceType: 'settlement_fee',
                    sourceId: (int) $settlement->getKey(),
                    idempotencyKey: sprintf('finance.settlement.%d.order.%d.%s', (int) $settlement->getKey(), $orderId, $feeType),
                    lines: $lines2,
                    narration: sprintf('Đối soát %s — đơn #%d — phí %s', $settlement->external_id ?: 'kỳ', $orderId, $feeType),
                ));
                $posted++;
            } catch (AccountingException $e) {
                Log::warning('Accounting: settlement entry skipped', ['settlement' => $settlement->getKey(), 'order' => $orderId, 'fee' => $feeType, 'reason' => $e->getMessage()]);
            }
        }

        // Payout: 1 entry tổng cho settlement (cấn trừ 131 đa khách → 1 ngân hàng).
        // Để tránh phức tạp, payout = SUM(amount) chia theo customer.
        $payoutByCustomer = [];
        foreach ($lines as $l) {
            $order = $orders->get($l->order_id);
            if (! $order) {
                continue;
            }
            $cid = $order->customer_id ? (int) $order->customer_id : 0;
            $payoutByCustomer[$cid] ??= 0;
            $payoutByCustomer[$cid] += (int) $l->amount;
        }
        $totalPayout = (int) array_sum($payoutByCustomer);
        if ($totalPayout > 0) {
            $payoutRule = $this->rules->resolve($tenantId, 'finance.settlement.payout');
            if ($payoutRule && $payoutRule['enabled']) {
                $bankAcc = $this->postable($tenantId, $payoutRule['debit']);
                $arAcc = $this->postable($tenantId, $payoutRule['credit']);
                if ($bankAcc && $arAcc) {
                    $lines3 = [JournalLineDTO::debit($bankAcc->code, $totalPayout, [
                        'dim_shop_id' => $settlement->channel_account_id ? (int) $settlement->channel_account_id : null,
                        'memo' => sprintf('Sàn chuyển tiền — kỳ %s', $settlement->external_id),
                    ])];
                    foreach ($payoutByCustomer as $cid => $amt) {
                        if ($amt <= 0) {
                            continue;
                        }
                        $lines3[] = JournalLineDTO::credit($arAcc->code, (int) $amt, [
                            'party_type' => $cid > 0 ? 'customer' : null,
                            'party_id' => $cid > 0 ? $cid : null,
                            'dim_shop_id' => $settlement->channel_account_id ? (int) $settlement->channel_account_id : null,
                            'memo' => $cid > 0 ? "Cấn trừ công nợ khách #{$cid}" : 'Cấn trừ công nợ chung',
                        ]);
                    }
                    try {
                        $this->journals->post(new JournalEntryDTO(
                            tenantId: $tenantId,
                            postedAt: $settlement->paid_at ?? $settlement->reconciled_at ?? now(),
                            sourceModule: 'finance',
                            sourceType: 'settlement_payout',
                            sourceId: (int) $settlement->getKey(),
                            idempotencyKey: sprintf('finance.settlement.%d.payout', (int) $settlement->getKey()),
                            lines: $lines3,
                            narration: sprintf('Sàn chuyển tiền về — kỳ %s', $settlement->external_id),
                        ));
                        $posted++;
                    } catch (AccountingException $e) {
                        Log::warning('Accounting: payout entry skipped', ['settlement' => $settlement->getKey(), 'reason' => $e->getMessage()]);
                    }
                }
            }
        }

        return $posted;
    }

    private function ruleKeyForFeeType(string $feeType): ?string
    {
        return match ($feeType) {
            'commission' => 'finance.settlement.commission',
            'payment_fee' => 'finance.settlement.payment_fee',
            'shipping_fee' => 'finance.settlement.shipping_fee',
            'voucher_seller' => 'finance.settlement.voucher_seller',
            'adjustment' => 'finance.settlement.adjustment',
            default => null, // revenue / shipping_subsidy / voucher_platform / refund / other → skip
        };
    }

    private function postable(int $tenantId, string $code): ?ChartAccount
    {
        return ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('code', $code)
            ->where('is_postable', true)->where('is_active', true)->first();
    }
}
