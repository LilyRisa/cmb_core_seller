<?php

namespace CMBcoreSeller\Modules\Accounting\Listeners;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\JournalService;
use CMBcoreSeller\Modules\Accounting\Services\PostRuleResolver;
use CMBcoreSeller\Modules\Inventory\Models\OrderCost;
use CMBcoreSeller\Modules\Orders\Events\OrderStatusChanged;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Phase 7.2 — SPEC 0019.
 *
 * Listen `Orders\OrderStatusChanged` → khi order chuyển sang `shipped`/`delivered`/`completed`:
 *  - **Doanh thu**: Dr 131 (party=customer) / Cr 5111 (revenue) + (tuỳ) Cr 33311 (VAT đầu ra).
 *  - **GVHB**: Dr 632 / Cr 1561 — dùng `OrderCost.cogs_total` (đã FIFO-ghi sẵn ở Phase 6.1).
 *
 * Khi đảo trạng thái về `cancelled`/`returned` ⇒ JournalService::reverse() qua key chuẩn.
 *
 * Idempotency: `orders.{$orderId}.revenue` / `.cogs`.
 *
 * Lưu ý:
 *  - `grand_total` = số khách trả (đã trừ giảm giá nội bộ + cộng phí ship khách trả).
 *  - VAT chỉ tách nếu `order.tax > 0`. Nếu không thì gộp doanh thu (gross).
 *  - Phí sàn không hạch toán ở đây — chờ Settlement reconcile (PostOnSettlementReconciled).
 */
class PostOnOrderShipped implements ShouldQueue
{
    public string $queue = 'accounting';

    public int $tries = 3;

    public int $backoff = 30;

    /** Trạng thái nào trigger revenue posting (lần đầu). */
    private const REVENUE_STATUSES = [
        StandardOrderStatus::Shipped->value,
        StandardOrderStatus::Delivered->value,
        StandardOrderStatus::Completed->value,
    ];

    private const REVERSAL_STATUSES = [
        StandardOrderStatus::Cancelled->value,
        StandardOrderStatus::ReturnedRefunded->value,
    ];

    public function __construct(
        private readonly JournalService $journals,
        private readonly PostRuleResolver $rules,
        private readonly AccountingSetupService $setup,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $tenantId = (int) $order->tenant_id;
        if (! $this->setup->isInitialized($tenantId)) {
            return;
        }

        $newStatus = $event->to->value;

        // Chỉ post lần "đầu tiên đạt revenue" — idempotency_key chặn replay.
        if (in_array($newStatus, self::REVENUE_STATUSES, true)) {
            $this->postRevenue($tenantId, $order);
            $this->postCogs($tenantId, $order);

            return;
        }

        if (in_array($newStatus, self::REVERSAL_STATUSES, true)) {
            $this->reverseIfPosted($tenantId, $order);
        }
    }

    private function postRevenue(int $tenantId, $order): void
    {
        $rule = $this->rules->resolve($tenantId, 'orders.shipped.revenue');
        if ($rule === null || ! $rule['enabled']) {
            return;
        }
        $grandTotal = (int) $order->grand_total;
        $tax = (int) ($order->tax ?? 0);
        if ($grandTotal <= 0) {
            return;
        }

        $debitAcc = $this->postable($tenantId, $rule['debit']);
        $creditAcc = $this->postable($tenantId, $rule['credit']);
        if (! $debitAcc || ! $creditAcc) {
            Log::warning('Accounting: orders.shipped.revenue rule maps missing account', ['rule' => $rule, 'tenant' => $tenantId]);

            return;
        }

        $vatAmount = $tax > 0 ? $tax : 0;
        $netRevenue = $grandTotal - $vatAmount;

        $lines = [
            JournalLineDTO::debit($debitAcc->code, $grandTotal, [
                'party_type' => 'customer',
                'party_id' => $order->customer_id ? (int) $order->customer_id : null,
                'dim_order_id' => (int) $order->id,
                'dim_shop_id' => $order->channel_account_id ? (int) $order->channel_account_id : null,
                'memo' => sprintf('Đơn %s', (string) ($order->order_number ?: $order->external_order_id ?: $order->id)),
            ]),
            JournalLineDTO::credit($creditAcc->code, $netRevenue, [
                'dim_order_id' => (int) $order->id,
                'dim_shop_id' => $order->channel_account_id ? (int) $order->channel_account_id : null,
                'memo' => 'Doanh thu bán hàng',
            ]),
        ];

        if ($vatAmount > 0) {
            $vatRule = $this->rules->resolve($tenantId, 'orders.shipped.vat');
            if ($vatRule && $vatRule['enabled']) {
                $vatAcc = $this->postable($tenantId, $vatRule['credit']);
                if ($vatAcc) {
                    $lines[] = JournalLineDTO::credit($vatAcc->code, $vatAmount, [
                        'dim_order_id' => (int) $order->id,
                        'dim_tax_code' => $vatAcc->code,
                        'memo' => 'VAT đầu ra',
                    ]);
                } else {
                    // Không có TK VAT → gộp vào doanh thu (gross).
                    $lines[1] = JournalLineDTO::credit($creditAcc->code, $grandTotal, [
                        'dim_order_id' => (int) $order->id,
                        'memo' => 'Doanh thu bán hàng (gross — chưa cấu hình TK VAT)',
                    ]);
                }
            } else {
                $lines[1] = JournalLineDTO::credit($creditAcc->code, $grandTotal, [
                    'dim_order_id' => (int) $order->id,
                    'memo' => 'Doanh thu (gross)',
                ]);
            }
        }

        try {
            $this->journals->post(new JournalEntryDTO(
                tenantId: $tenantId,
                postedAt: $order->shipped_at ?? $order->completed_at ?? now(),
                sourceModule: 'orders',
                sourceType: 'order',
                sourceId: (int) $order->id,
                idempotencyKey: sprintf('orders.%d.revenue', (int) $order->id),
                lines: $lines,
                narration: sprintf('Ghi nhận doanh thu đơn %s', (string) ($order->order_number ?: $order->external_order_id ?: $order->id)),
            ));
        } catch (AccountingException $e) {
            // Kỳ closed / TK không postable — log và bỏ qua, không retry vô hạn.
            Log::warning('Accounting: revenue post skipped', ['order' => $order->id, 'reason' => $e->getMessage()]);
        }
    }

    private function postCogs(int $tenantId, $order): void
    {
        $rule = $this->rules->resolve($tenantId, 'orders.shipped.cogs');
        if ($rule === null || ! $rule['enabled']) {
            return;
        }
        // Đọc COGS thực từ order_costs (Phase 6.1 FIFO đã ghi sẵn).
        $costs = OrderCost::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('order_id', (int) $order->id)
            ->get();
        $total = (int) $costs->sum('cogs_total');
        if ($total <= 0 || $costs->isEmpty()) {
            // Chưa có FIFO → bỏ; sau khi Phase 6.1 ghi `order_costs`, có thể trigger thủ công.
            return;
        }
        $debitAcc = $this->postable($tenantId, $rule['debit']);
        $creditAcc = $this->postable($tenantId, $rule['credit']);
        if (! $debitAcc || ! $creditAcc) {
            return;
        }

        $lines = [];
        foreach ($costs as $c) {
            $lines[] = JournalLineDTO::debit($debitAcc->code, (int) $c->cogs_total, [
                'dim_order_id' => (int) $order->id,
                'dim_sku_id' => (int) $c->sku_id,
                'memo' => sprintf('GVHB SKU#%d × %d', (int) $c->sku_id, (int) $c->qty),
            ]);
            $lines[] = JournalLineDTO::credit($creditAcc->code, (int) $c->cogs_total, [
                'dim_order_id' => (int) $order->id,
                'dim_sku_id' => (int) $c->sku_id,
                'memo' => 'Xuất kho hàng bán',
            ]);
        }

        try {
            $this->journals->post(new JournalEntryDTO(
                tenantId: $tenantId,
                postedAt: $order->shipped_at ?? $order->completed_at ?? now(),
                sourceModule: 'orders',
                sourceType: 'order_cogs',
                sourceId: (int) $order->id,
                idempotencyKey: sprintf('orders.%d.cogs', (int) $order->id),
                lines: $lines,
                narration: sprintf('Giá vốn hàng bán — đơn %s', (string) ($order->order_number ?: $order->external_order_id ?: $order->id)),
            ));
        } catch (AccountingException $e) {
            Log::warning('Accounting: cogs post skipped', ['order' => $order->id, 'reason' => $e->getMessage()]);
        }
    }

    private function reverseIfPosted(int $tenantId, $order): void
    {
        // Đảo cả 2 entry (revenue + cogs) nếu đã post.
        foreach ([
            sprintf('orders.%d.revenue', (int) $order->id),
            sprintf('orders.%d.cogs', (int) $order->id),
        ] as $key) {
            $entry = \CMBcoreSeller\Modules\Accounting\Models\JournalEntry::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('idempotency_key', $key)->first();
            if ($entry === null) {
                continue;
            }
            try {
                $this->journals->reverse($entry, null, 'Đơn huỷ/trả hàng');
            } catch (AccountingException $e) {
                Log::warning('Accounting: order reverse skipped', ['order' => $order->id, 'reason' => $e->getMessage()]);
            }
        }
    }

    private function postable(int $tenantId, string $code): ?ChartAccount
    {
        return ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('code', $code)
            ->where('is_postable', true)->where('is_active', true)->first();
    }
}
