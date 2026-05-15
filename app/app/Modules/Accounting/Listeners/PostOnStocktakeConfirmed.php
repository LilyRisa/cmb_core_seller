<?php

namespace CMBcoreSeller\Modules\Accounting\Listeners;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Services\AccountingSetupService;
use CMBcoreSeller\Modules\Accounting\Services\JournalService;
use CMBcoreSeller\Modules\Accounting\Services\PostRuleResolver;
use CMBcoreSeller\Modules\Inventory\Events\StocktakeConfirmed;
use CMBcoreSeller\Modules\Inventory\Models\InventoryLevel;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\StocktakeItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Phase 7.1 — SPEC 0019.
 *
 * Listen `Inventory\StocktakeConfirmed` → 1 bút toán gom toàn phiếu kiểm kê:
 *   - dòng diff > 0 (thừa)  ⇒ Dr 156 / Cr 711
 *   - dòng diff < 0 (thiếu) ⇒ Dr 811 / Cr 156
 *
 * Mỗi loại có rule riêng (`stocktake_adjust.in` / `.out`). Cùng tenant có thể đổi TK đối ứng
 * sang TK chi tiết khác qua UI (vd 6428 thay 811 cho khoản hao hụt).
 */
class PostOnStocktakeConfirmed implements ShouldQueue
{
    public string $queue = 'accounting';

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private readonly JournalService $journals,
        private readonly PostRuleResolver $rules,
        private readonly AccountingSetupService $setup,
    ) {}

    public function handle(StocktakeConfirmed $event): void
    {
        $stocktake = $event->stocktake;
        $tenantId = (int) $stocktake->tenant_id;
        if (! $this->setup->isInitialized($tenantId)) {
            return;
        }
        $ruleIn = $this->rules->resolve($tenantId, 'inventory.stocktake_adjust.in');
        $ruleOut = $this->rules->resolve($tenantId, 'inventory.stocktake_adjust.out');
        if (($ruleIn === null || ! $ruleIn['enabled']) && ($ruleOut === null || ! $ruleOut['enabled'])) {
            return;
        }

        $items = StocktakeItem::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('stocktake_id', $stocktake->id)
            ->get();
        $whId = (int) $stocktake->warehouse_id;

        // Cost từng SKU tại kho.
        $skuIds = $items->pluck('sku_id')->unique()->values()->all();
        $levels = InventoryLevel::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('sku_id', $skuIds)
            ->where('warehouse_id', $whId)
            ->get()->keyBy('sku_id');
        $skuFallback = Sku::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $skuIds)->get()->keyBy('id');

        // Gom riêng phía in/out — có thể chỉ 1 trong 2 phía có phát sinh.
        $inLines = [];
        $outLines = [];
        $inTotal = 0;
        $outTotal = 0;
        foreach ($items as $it) {
            $diff = (int) $it->diff;
            if ($diff === 0) {
                continue;
            }
            $skuId = (int) $it->sku_id;
            $cost = (int) ($levels->get($skuId)?->cost_price ?? 0);
            if ($cost <= 0) {
                $cost = (int) ($skuFallback->get($skuId)?->effectiveCost() ?? 0);
            }
            if ($cost <= 0) {
                continue;
            }
            $amount = abs($diff) * $cost;
            if ($diff > 0) {
                $inTotal += $amount;
                $inLines[] = ['sku_id' => $skuId, 'amount' => $amount, 'qty' => $diff];
            } else {
                $outTotal += $amount;
                $outLines[] = ['sku_id' => $skuId, 'amount' => $amount, 'qty' => $diff];
            }
        }

        // Mỗi phía → 1 entry. Idempotency key khác nhau ⇒ không xung đột.
        if ($inTotal > 0 && $ruleIn && $ruleIn['enabled']) {
            $this->postSide(
                $tenantId, $stocktake, $whId, $ruleIn, $inLines, $inTotal,
                idempotencyKey: sprintf('inventory.stocktake.%d.in', (int) $stocktake->id),
                eventKey: 'in',
            );
        }
        if ($outTotal > 0 && $ruleOut && $ruleOut['enabled']) {
            $this->postSide(
                $tenantId, $stocktake, $whId, $ruleOut, $outLines, $outTotal,
                idempotencyKey: sprintf('inventory.stocktake.%d.out', (int) $stocktake->id),
                eventKey: 'out',
            );
        }
    }

    /**
     * @param  array<int, array{sku_id:int, amount:int, qty:int}>  $pieces
     */
    private function postSide(int $tenantId, $stocktake, int $whId, array $rule, array $pieces, int $total, string $idempotencyKey, string $eventKey): void
    {
        $debitAcc = $this->getPostableAccount($tenantId, $rule['debit']);
        $creditAcc = $this->getPostableAccount($tenantId, $rule['credit']);
        if ($debitAcc === null || $creditAcc === null) {
            Log::warning('Accounting: stocktake post-rule missing account', ['rule' => $rule, 'tenant_id' => $tenantId]);

            return;
        }

        $drLines = [];
        $crLines = [];
        foreach ($pieces as $p) {
            $drLines[] = JournalLineDTO::debit($debitAcc->code, $p['amount'], [
                'dim_warehouse_id' => $whId, 'dim_sku_id' => $p['sku_id'],
                'memo' => sprintf('SKU#%d × %d', $p['sku_id'], abs($p['qty'])),
            ]);
            $crLines[] = JournalLineDTO::credit($creditAcc->code, $p['amount'], [
                'dim_warehouse_id' => $whId, 'dim_sku_id' => $p['sku_id'],
                'memo' => sprintf('Kiểm kê %s — SKU#%d', $eventKey === 'in' ? 'thừa' : 'thiếu', $p['sku_id']),
            ]);
        }

        $dto = new JournalEntryDTO(
            tenantId: $tenantId,
            postedAt: $stocktake->confirmed_at ?? now(),
            sourceModule: 'inventory',
            sourceType: 'stocktake',
            sourceId: (int) $stocktake->id,
            idempotencyKey: $idempotencyKey,
            lines: array_merge($drLines, $crLines),
            narration: sprintf('Kiểm kê %s (%s)', (string) $stocktake->code, $eventKey === 'in' ? 'thừa' : 'thiếu'),
            createdBy: $stocktake->confirmed_by ?? null,
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
