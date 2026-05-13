<?php

namespace CMBcoreSeller\Modules\Finance\Services;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\SettlementDTO;
use CMBcoreSeller\Integrations\Channels\DTO\SettlementLineDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Models\Settlement;
use CMBcoreSeller\Modules\Finance\Models\SettlementLine;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Phase 6.2 — Kéo đối soát + đối chiếu line ↔ đơn (SPEC 0016).
 *
 *  - {@see fetchForShop()}: gọi `Connector::fetchSettlements`, upsert {@see Settlement} + bulk insert
 *    {@see SettlementLine}. Idempotent qua `(channel_account_id, external_id)` ở header và
 *    `(settlement_id, fee_type, external_order_id, external_line_id)` ở line.
 *  - {@see reconcile()}: map `settlement_lines.external_order_id → orders.external_order_id` → fill
 *    `order_id`; cập nhật `settlement.status = reconciled` + `reconciled_at`.
 */
class SettlementService
{
    public function __construct(private readonly ChannelRegistry $registry) {}

    public function fetchForShop(ChannelAccount $account, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null, ?int $userId = null): array
    {
        if (! $this->registry->has($account->provider)) {
            throw new RuntimeException("Provider [{$account->provider}] chưa được bật.");
        }
        $connector = $this->registry->for($account->provider);
        if (! $connector->supports('finance.settlements')) {
            throw UnsupportedOperation::for($account->provider, 'fetchSettlements');
        }
        $from ??= CarbonImmutable::now()->subDays(30);
        $to ??= CarbonImmutable::now();

        $created = 0;
        $upsertedLines = 0;
        $cursor = null;
        do {
            $page = $connector->fetchSettlements($account->authContext(), array_filter([
                'from' => $from, 'to' => $to, 'cursor' => $cursor, 'pageSize' => 50,
            ], fn ($v) => $v !== null));
            foreach ($page->items as $dto) {
                /** @var SettlementDTO $dto */
                $settlement = $this->upsertSettlement($account, $dto);
                $upsertedLines += $this->upsertLines($settlement, $dto->lines);
                $created++;
                // Reconcile ngay sau khi upsert (mọi line chưa có order_id sẽ được khớp).
                $this->reconcile($settlement);
            }
            $cursor = $page->nextCursor;
        } while ($page->hasMore && $cursor !== null);

        return ['fetched' => $created, 'lines' => $upsertedLines];
    }

    private function upsertSettlement(ChannelAccount $account, SettlementDTO $dto): Settlement
    {
        $tenantId = (int) $account->tenant_id;
        $external = $dto->externalId ?: ($account->getKey().'-'.$dto->periodStart->format('Ymd').'-'.$dto->periodEnd->format('Ymd'));

        return DB::transaction(function () use ($tenantId, $account, $dto, $external) {
            $row = Settlement::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->where('channel_account_id', $account->getKey())
                ->where('external_id', $external)->lockForUpdate()->first();
            $attrs = [
                'tenant_id' => $tenantId, 'channel_account_id' => (int) $account->getKey(),
                'external_id' => $external,
                'period_start' => $dto->periodStart, 'period_end' => $dto->periodEnd,
                'currency' => $dto->currency, 'total_payout' => $dto->totalPayout,
                'total_revenue' => $dto->totalRevenue, 'total_fee' => $dto->totalFee,
                'total_shipping_fee' => $dto->totalShippingFee,
                'paid_at' => $dto->paidAt, 'raw' => $dto->raw, 'fetched_at' => now(),
            ];
            if ($row) {
                $row->forceFill($attrs)->save();

                return $row;
            }
            $attrs['status'] = Settlement::STATUS_PENDING;

            return Settlement::query()->create($attrs);
        });
    }

    /** @param  list<SettlementLineDTO>  $lines */
    private function upsertLines(Settlement $settlement, array $lines): int
    {
        if ($lines === []) {
            return 0;
        }
        $tenantId = (int) $settlement->tenant_id;
        $count = 0;
        foreach (array_chunk($lines, 200) as $chunk) {
            DB::transaction(function () use ($settlement, $chunk, $tenantId, &$count) {
                // Xoá những line cũ có cùng `external_line_id` để upsert (Lazada không gửi `external_line_id` ổn định
                // ⇒ dedupe bổ sung theo (fee_type, external_order_id, amount, occurred_at)).
                foreach ($chunk as $l) {
                    /** @var SettlementLineDTO $l */
                    $existing = SettlementLine::withoutGlobalScope(TenantScope::class)
                        ->where('settlement_id', $settlement->getKey())
                        ->where('fee_type', $l->feeType)
                        ->where('external_order_id', $l->externalOrderId)
                        ->where('external_line_id', $l->externalLineId)
                        ->where('amount', $l->amount)
                        ->first();
                    if ($existing) {
                        continue;
                    }
                    SettlementLine::query()->create([
                        'tenant_id' => $tenantId, 'settlement_id' => (int) $settlement->getKey(),
                        'external_order_id' => $l->externalOrderId, 'external_line_id' => $l->externalLineId,
                        'fee_type' => $l->feeType, 'amount' => $l->amount,
                        'occurred_at' => $l->occurredAt, 'description' => $l->description,
                        'raw' => $l->raw, 'created_at' => now(),
                    ]);
                    $count++;
                }
            });
        }

        return $count;
    }

    /**
     * Match `settlement_lines.external_order_id → orders.external_order_id` cho cùng tenant + channel_account
     * của settlement; set `order_id` + chuyển `settlement.status = reconciled`. Idempotent. SPEC 0016.
     */
    public function reconcile(Settlement $settlement): int
    {
        $tenantId = (int) $settlement->tenant_id;
        $channelAccountId = (int) $settlement->channel_account_id;
        $matched = 0;

        $unmatched = $settlement->lines()->withoutGlobalScope(TenantScope::class)
            ->whereNull('order_id')->whereNotNull('external_order_id')->get();
        $extIds = $unmatched->pluck('external_order_id')->unique()->all();
        if ($extIds !== []) {
            $orderIds = Order::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)->where('channel_account_id', $channelAccountId)
                ->whereIn('external_order_id', $extIds)
                ->pluck('id', 'external_order_id');
            foreach ($unmatched as $line) {
                $oid = (int) ($orderIds[$line->external_order_id] ?? 0);
                if ($oid > 0) {
                    $line->forceFill(['order_id' => $oid])->save();
                    $matched++;
                }
            }
        }
        $unmatchedAfter = $settlement->lines()->withoutGlobalScope(TenantScope::class)->whereNull('order_id')->whereNotNull('external_order_id')->count();
        $settlement->forceFill([
            'status' => $unmatchedAfter === 0 ? Settlement::STATUS_RECONCILED : Settlement::STATUS_PENDING,
            'reconciled_at' => $unmatchedAfter === 0 ? now() : $settlement->reconciled_at,
        ])->save();
        Log::info('finance.settlement.reconciled', ['settlement' => $settlement->getKey(), 'matched' => $matched, 'unmatched_remaining' => $unmatchedAfter]);

        return $matched;
    }

    /**
     * Tổng phí thực của 1 đơn (tổng các `settlement_lines.amount` theo fee_type) — dùng trong báo cáo / lợi nhuận.
     *
     * @param  list<int>  $orderIds
     * @return array<int, array{revenue:int, fees:int, shipping:int, refund:int, payout:int}>
     */
    public function aggregateFeesForOrders(int $tenantId, array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }
        $rows = SettlementLine::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->whereIn('order_id', $orderIds)
            ->selectRaw('order_id, fee_type, SUM(amount) AS amount')
            ->groupBy('order_id', 'fee_type')->get();
        $out = [];
        foreach ($rows as $r) {
            $oid = (int) $r->order_id;
            $out[$oid] ??= ['revenue' => 0, 'fees' => 0, 'shipping' => 0, 'refund' => 0, 'payout' => 0];
            $a = (int) $r->amount;
            $out[$oid]['payout'] += $a;
            switch ($r->fee_type) {
                case SettlementLineDTO::TYPE_REVENUE:
                    $out[$oid]['revenue'] += $a;
                    break;
                case SettlementLineDTO::TYPE_COMMISSION:
                case SettlementLineDTO::TYPE_PAYMENT_FEE:
                case SettlementLineDTO::TYPE_VOUCHER_SELLER:
                    $out[$oid]['fees'] += $a;
                    break;
                case SettlementLineDTO::TYPE_SHIPPING_FEE:
                case SettlementLineDTO::TYPE_SHIPPING_SUBSIDY:
                    $out[$oid]['shipping'] += $a;
                    break;
                case SettlementLineDTO::TYPE_REFUND:
                    $out[$oid]['refund'] += $a;
                    break;
                default:
                    // adjustment / other / voucher_platform — gộp vào fees để hiện ròng.
                    $out[$oid]['fees'] += $a;
            }
        }

        return $out;
    }
}
