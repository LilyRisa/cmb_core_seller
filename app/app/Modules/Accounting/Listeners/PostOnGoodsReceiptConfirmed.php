<?php

namespace CMBcoreSeller\Modules\Accounting\Listeners;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\JournalService;
use CMBcoreSeller\Modules\Accounting\Services\PostRuleResolver;
use CMBcoreSeller\Modules\Inventory\Events\GoodsReceiptConfirmed;
use CMBcoreSeller\Modules\Inventory\Models\GoodsReceiptItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Phase 7.1 — SPEC 0019.
 *
 * Listen `Inventory\GoodsReceiptConfirmed` → post bút toán:
 *   Dr 156 (HTK kho A, dim_warehouse, dim_sku)  /  Cr 331 (Phải trả NCC, party=supplier_id từ PO)
 *
 * Idempotency: `inventory.goods_receipt.{$id}.posted`. Replay = no-op (JournalService trả entry cũ).
 *
 * Skip an toàn khi tenant chưa setup module Accounting (chưa nâng gói Pro/Business).
 */
class PostOnGoodsReceiptConfirmed implements ShouldQueue
{
    public string $queue = 'accounting';

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private readonly JournalService $journals,
        private readonly PostRuleResolver $rules,
        private readonly AccountingSetupService $setup,
    ) {}

    public function handle(GoodsReceiptConfirmed $event): void
    {
        $receipt = $event->receipt;
        $tenantId = (int) $receipt->tenant_id;
        if (! $this->setup->isInitialized($tenantId)) {
            return; // tenant chưa onboard Accounting — skip êm.
        }

        $rule = $this->rules->resolve($tenantId, 'inventory.goods_receipt.confirmed');
        if ($rule === null || ! $rule['enabled']) {
            return;
        }

        // Load items bypass tenant scope — listener có thể chạy trong queue worker chưa set CurrentTenant.
        $items = GoodsReceiptItem::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('goods_receipt_id', $receipt->id)
            ->get();
        // Tổng cộng từng dòng = qty × unit_cost (đã chốt ở confirmGoodsReceipt).
        $totalCost = 0;
        $linePieces = [];
        foreach ($items as $it) {
            $qty = (int) $it->qty;
            $cost = (int) $it->unit_cost;
            if ($qty <= 0 || $cost <= 0) {
                continue;
            }
            $amount = $qty * $cost;
            $totalCost += $amount;
            $linePieces[] = [
                'amount' => $amount,
                'sku_id' => (int) $it->sku_id,
                'warehouse_id' => (int) $receipt->warehouse_id,
                'memo' => sprintf('SKU#%d × %d', (int) $it->sku_id, $qty),
            ];
        }
        if ($totalCost === 0 || empty($linePieces)) {
            return; // phiếu nhập 0 đồng (vd nhập biếu tặng) — không hạch toán; user có thể vào sửa tay sau.
        }

        // Resolve account để chắc tồn tại + postable trước khi build DTO.
        $debitAcc = $this->getPostableAccount($tenantId, $rule['debit']);
        $creditAcc = $this->getPostableAccount($tenantId, $rule['credit']);
        if ($debitAcc === null || $creditAcc === null) {
            Log::warning('Accounting: post-rule maps to missing/non-postable account', ['rule' => $rule, 'tenant_id' => $tenantId, 'gr' => $receipt->code]);

            return;
        }

        $supplierId = (int) ($receipt->supplier_id ?? 0) ?: null;
        $supplierPartyType = $supplierId ? JournalLineDTO::credit('x', 1)->partyType : null; // placeholder unused
        $supplierOpts = $supplierId ? ['party_type' => 'supplier', 'party_id' => $supplierId] : [];

        $lines = [];
        foreach ($linePieces as $i => $p) {
            $lines[] = JournalLineDTO::debit($debitAcc->code, $p['amount'], [
                'dim_warehouse_id' => $p['warehouse_id'],
                'dim_sku_id' => $p['sku_id'],
                'memo' => $p['memo'],
            ]);
        }
        // Một dòng Có gom toàn bộ → cân với tổng Dr.
        $lines[] = JournalLineDTO::credit($creditAcc->code, $totalCost, array_merge($supplierOpts, [
            'memo' => sprintf('Phải trả NCC theo phiếu %s', (string) $receipt->code),
        ]));

        $dto = new JournalEntryDTO(
            tenantId: $tenantId,
            postedAt: $receipt->confirmed_at ?? now(),
            sourceModule: 'inventory',
            sourceType: 'goods_receipt',
            sourceId: (int) $receipt->id,
            idempotencyKey: sprintf('inventory.goods_receipt.%d.posted', (int) $receipt->id),
            lines: $lines,
            narration: sprintf('Nhập kho %s', (string) $receipt->code),
            createdBy: $receipt->confirmed_by ?? null,
        );

        $this->journals->post($dto);
    }

    private function getPostableAccount(int $tenantId, string $code): ?ChartAccount
    {
        return ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('code', $code)
            ->where('is_postable', true)->where('is_active', true)->first();
    }
}
