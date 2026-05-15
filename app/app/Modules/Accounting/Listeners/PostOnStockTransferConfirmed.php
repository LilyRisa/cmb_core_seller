<?php

namespace CMBcoreSeller\Modules\Accounting\Listeners;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\JournalService;
use CMBcoreSeller\Modules\Accounting\Services\PostRuleResolver;
use CMBcoreSeller\Modules\Inventory\Events\StockTransferConfirmed;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\StockTransferItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Phase 7.1 — SPEC 0019.
 *
 * Listen `Inventory\StockTransferConfirmed` → Dr 156 (kho-đến) / Cr 156 (kho-đi).
 *
 * Giá vốn dùng để hạch toán = `inventory_levels.cost_price` của (sku, kho-đi) tại thời điểm transfer
 * confirm (đã được `InventoryLedgerService::transferOut` cập nhật lúc trừ kho); fallback `Sku.effectiveCost()`
 * nếu chưa có giá ở kho.
 *
 * Lưu ý: cùng TK 156 hai đầu — phân biệt qua `dim_warehouse_id` để Sổ chi tiết kho không lệch.
 */
class PostOnStockTransferConfirmed implements ShouldQueue
{
    public string $queue = 'accounting';

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private readonly JournalService $journals,
        private readonly PostRuleResolver $rules,
        private readonly AccountingSetupService $setup,
    ) {}

    public function handle(StockTransferConfirmed $event): void
    {
        $transfer = $event->transfer;
        $tenantId = (int) $transfer->tenant_id;
        if (! $this->setup->isInitialized($tenantId)) {
            return;
        }
        $rule = $this->rules->resolve($tenantId, 'inventory.stock_transfer');
        if ($rule === null || ! $rule['enabled']) {
            return;
        }

        $debitAcc = $this->getPostableAccount($tenantId, $rule['debit']);
        $creditAcc = $this->getPostableAccount($tenantId, $rule['credit']);
        if ($debitAcc === null || $creditAcc === null) {
            Log::warning('Accounting: transfer post-rule missing account', ['rule' => $rule, 'tenant_id' => $tenantId, 'transfer' => $transfer->code]);

            return;
        }

        $items = StockTransferItem::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('stock_transfer_id', $transfer->id)
            ->get();
        $fromWh = (int) $transfer->from_warehouse_id;
        $toWh = (int) $transfer->to_warehouse_id;

        // Pre-load cost per SKU (1 query gom).
        $skuIds = $items->pluck('sku_id')->unique()->values()->all();
        $levelCosts = InventoryLevel::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('sku_id', $skuIds)
            ->where('warehouse_id', $fromWh)
            ->get()->keyBy('sku_id');
        $skuFallback = Sku::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $skuIds)->get()->keyBy('id');

        $totalCost = 0;
        $drLines = [];
        $crLines = [];
        foreach ($items as $it) {
            $qty = (int) $it->qty;
            if ($qty <= 0) {
                continue;
            }
            $skuId = (int) $it->sku_id;
            $cost = (int) ($levelCosts->get($skuId)?->cost_price ?? 0);
            if ($cost <= 0) {
                $cost = (int) ($skuFallback->get($skuId)?->effectiveCost() ?? 0);
            }
            if ($cost <= 0) {
                continue; // SKU chưa có giá vốn — không hạch toán dòng này (sẽ flag trong UI)
            }
            $amount = $qty * $cost;
            $totalCost += $amount;
            $drLines[] = JournalLineDTO::debit($debitAcc->code, $amount, [
                'dim_warehouse_id' => $toWh, 'dim_sku_id' => $skuId,
                'memo' => sprintf('Nhận về kho — SKU#%d × %d', $skuId, $qty),
            ]);
            $crLines[] = JournalLineDTO::credit($creditAcc->code, $amount, [
                'dim_warehouse_id' => $fromWh, 'dim_sku_id' => $skuId,
                'memo' => sprintf('Xuất từ kho — SKU#%d × %d', $skuId, $qty),
            ]);
        }
        if ($totalCost === 0) {
            return;
        }

        $dto = new JournalEntryDTO(
            tenantId: $tenantId,
            postedAt: $transfer->confirmed_at ?? now(),
            sourceModule: 'inventory',
            sourceType: 'stock_transfer',
            sourceId: (int) $transfer->id,
            idempotencyKey: sprintf('inventory.stock_transfer.%d.posted', (int) $transfer->id),
            lines: array_merge($drLines, $crLines),
            narration: sprintf('Chuyển kho %s', (string) $transfer->code),
            createdBy: $transfer->confirmed_by ?? null,
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
