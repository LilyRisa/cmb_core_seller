<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Events\JournalPosted;
use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\FiscalPeriod;
use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Nơi DUY NHẤT ghi sổ kế toán. Phase 7.1 — SPEC 0019.
 *
 *  - {@see post()}: validate cân bằng + period chưa locked + idempotent; insert entry + lines.
 *  - {@see reverse()}: tạo entry mới với Dr↔Cr swap, link `is_reversal_of_id`. Entry kỳ closed
 *    ⇒ entry đảo nhảy sang kỳ mở kế tiếp (`adjusted_period_id` ghi kỳ gốc).
 *  - Tất cả thao tác atomic (DB transaction) + lock period FOR UPDATE.
 *  - **Listener tự đặt `idempotency_key` deterministic** ⇒ retry queue không tạo trùng.
 */
class JournalService
{
    public function __construct(private readonly PeriodService $periods) {}

    /**
     * Post bút toán. Idempotent qua `idempotency_key` (unique per tenant). Trả entry mới HOẶC
     * entry cũ nếu đã từng post (replay).
     */
    public function post(JournalEntryDTO $dto): JournalEntry
    {
        $tenantId = $dto->tenantId;
        if ($tenantId <= 0) {
            throw AccountingException::invalidLines('tenant_id bắt buộc > 0.');
        }
        $lines = $dto->lines;
        if (count($lines) < 2) {
            throw AccountingException::invalidLines('Bút toán phải có ít nhất 2 dòng.');
        }

        $totalDr = 0;
        $totalCr = 0;
        foreach ($lines as $l) {
            /** @var JournalLineDTO $l */
            if (! ($l->drAmount > 0) && ! ($l->crAmount > 0)) {
                throw AccountingException::invalidLines('Mỗi dòng phải có Nợ hoặc Có > 0.');
            }
            if ($l->drAmount > 0 && $l->crAmount > 0) {
                throw AccountingException::invalidLines('Một dòng không được vừa Nợ vừa Có.');
            }
            $totalDr += $l->drAmount;
            $totalCr += $l->crAmount;
        }
        if ($totalDr !== $totalCr) {
            throw AccountingException::unbalanced($totalDr, $totalCr);
        }
        if ($totalDr <= 0) {
            throw AccountingException::invalidLines('Tổng phát sinh phải > 0.');
        }

        return DB::transaction(function () use ($dto, $tenantId, $lines, $totalDr) {
            // Idempotency check first (nhanh hơn lock period nếu replay).
            $existing = JournalEntry::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('idempotency_key', $dto->idempotencyKey)
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            // Resolve kỳ — auto-create tháng nếu chưa có (trong cửa sổ cho phép).
            $period = $this->periods->resolveForDate($tenantId, $dto->postedAt);
            // Lock period FOR UPDATE để chống race với PeriodService::close() — kiểm trạng thái sau khi lock.
            $period = FiscalPeriod::query()
                ->withoutGlobalScope(TenantScope::class)
                ->whereKey($period->id)->lockForUpdate()->first();
            if ($period === null) {
                throw AccountingException::invalidLines('Không resolve được kỳ kế toán.');
            }
            if ($period->isLocked()) {
                throw AccountingException::periodLocked($period->code);
            }
            if ($period->status === FiscalPeriod::STATUS_CLOSED && ! $dto->isAdjustment) {
                throw AccountingException::periodClosed($period->code);
            }

            // Resolve account ids — tất cả phải tồn tại + postable + active.
            $codes = collect($lines)->pluck('accountCode')->unique()->values()->all();
            $accounts = ChartAccount::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->whereIn('code', $codes)
                ->get()->keyBy('code');

            foreach ($codes as $c) {
                /** @var ChartAccount|null $acc */
                $acc = $accounts->get($c);
                if ($acc === null) {
                    throw AccountingException::accountNotFound($c);
                }
                if (! $acc->is_postable) {
                    throw AccountingException::accountNotPostable($c);
                }
                if (! $acc->is_active) {
                    throw AccountingException::accountNotPostable($c);
                }
            }

            // INSERT entry với retry — collision có 2 nguồn:
            //  (a) unique(tenant, code) — `nextEntryCode` đọc MAX không lock, 2 worker post cùng tháng cùng lúc;
            //  (b) unique(tenant, idempotency_key) — race window giữa check `$existing` và insert.
            // (a) retry tối đa 5 lần với nextEntryCode mới. (b) trả entry đã insert (idempotent semantics).
            $entry = null;
            $lastError = null;
            for ($attempt = 1; $attempt <= 5; $attempt++) {
                $code = $this->nextEntryCode($tenantId, $dto->postedAt);
                try {
                    $entry = JournalEntry::query()->create([
                        'tenant_id' => $tenantId,
                        'code' => $code,
                        'posted_at' => $dto->postedAt,
                        'period_id' => $period->id,
                        'narration' => $dto->narration,
                        'source_module' => $dto->sourceModule,
                        'source_type' => $dto->sourceType,
                        'source_id' => $dto->sourceId,
                        'idempotency_key' => $dto->idempotencyKey,
                        'is_adjustment' => $dto->isAdjustment,
                        'is_reversal_of_id' => $dto->isReversalOfId,
                        'adjusted_period_id' => $dto->adjustedPeriodId,
                        'total_debit' => $totalDr,
                        'total_credit' => $totalDr, // === $totalCr (đã validate ở trên)
                        'currency' => 'VND',
                        'created_by' => $dto->createdBy,
                        'created_at' => now(),
                    ]);
                    break;
                } catch (UniqueConstraintViolationException $e) {
                    $lastError = $e;
                    // Phân biệt 2 loại collision qua check entry với idempotency_key.
                    $existing = JournalEntry::query()
                        ->withoutGlobalScope(TenantScope::class)
                        ->where('tenant_id', $tenantId)
                        ->where('idempotency_key', $dto->idempotencyKey)
                        ->first();
                    if ($existing !== null) {
                        return $existing; // idempotent — worker khác đã insert cùng entry này
                    }
                    // Còn lại là collision code → retry với code mới
                }
            }
            if ($entry === null) {
                throw new \RuntimeException('Không thể tạo bút toán sau 5 lần thử — '.($lastError?->getMessage() ?? 'unknown'));
            }

            $rows = [];
            $now = $dto->postedAt;
            foreach ($lines as $idx => $l) {
                $acc = $accounts->get($l->accountCode);
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'entry_id' => $entry->id,
                    'posted_at' => $now,
                    'account_id' => $acc->id,
                    'account_code' => $acc->code,
                    'dr_amount' => $l->drAmount,
                    'cr_amount' => $l->crAmount,
                    'party_type' => $l->partyType,
                    'party_id' => $l->partyId,
                    'dim_warehouse_id' => $l->dimWarehouseId,
                    'dim_shop_id' => $l->dimShopId,
                    'dim_sku_id' => $l->dimSkuId,
                    'dim_order_id' => $l->dimOrderId,
                    'dim_tax_code' => $l->dimTaxCode,
                    'memo' => $l->memo,
                    'line_no' => $idx + 1,
                ];
            }
            JournalLine::query()->insert($rows);

            JournalPosted::dispatch($entry->refresh());

            return $entry;
        });
    }

    /**
     * Tạo entry đảo. Idempotent qua `is_reversal_of_id` (mỗi entry chỉ có tối đa 1 reversal).
     */
    public function reverse(JournalEntry $entry, ?int $userId = null, ?string $reason = null): JournalEntry
    {
        $tenantId = (int) $entry->tenant_id;

        $existingRev = JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('is_reversal_of_id', $entry->id)
            ->first();
        if ($existingRev !== null) {
            return $existingRev;
        }

        // Load relations bypass tenant scope — service có thể chạy trong queue worker chưa set tenant.
        $period = FiscalPeriod::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereKey($entry->period_id)->first();
        if ($period && $period->isLocked()) {
            throw AccountingException::periodLocked($period->code);
        }

        // Quyết định posted_at của entry đảo: nếu period gốc đã closed ⇒ post sang kỳ mở kế tiếp.
        $postedAt = $entry->posted_at;
        $adjustedPeriodId = null;
        $isAdjustment = false;
        if ($period && $period->status === FiscalPeriod::STATUS_CLOSED) {
            $nextOpen = $this->periods->nextOpen($tenantId, $period);
            $postedAt = Carbon::parse($nextOpen->start_date);
            $adjustedPeriodId = $entry->period_id;
            $isAdjustment = true;
        }

        $entryLines = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('entry_id', $entry->id)->orderBy('line_no')->get();
        $lines = $entryLines->map(function (JournalLine $l) {
            return new JournalLineDTO(
                accountCode: $l->account_code,
                drAmount: (int) $l->cr_amount,   // swap
                crAmount: (int) $l->dr_amount,   // swap
                partyType: $l->party_type,
                partyId: $l->party_id,
                dimWarehouseId: $l->dim_warehouse_id,
                dimShopId: $l->dim_shop_id,
                dimSkuId: $l->dim_sku_id,
                dimOrderId: $l->dim_order_id,
                dimTaxCode: $l->dim_tax_code,
                memo: $l->memo,
            );
        })->all();

        $narration = trim(sprintf('Đảo %s%s%s',
            $entry->code,
            $reason ? ' — '.$reason : '',
            $isAdjustment ? sprintf(' (điều chỉnh kỳ %s)', $period?->code ?? '') : '',
        ));

        $dto = new JournalEntryDTO(
            tenantId: $tenantId,
            postedAt: $postedAt,
            sourceModule: $entry->source_module,
            sourceType: $entry->source_type,
            sourceId: $entry->source_id,
            idempotencyKey: 'reverse.'.$entry->id,
            lines: $lines,
            narration: $narration,
            createdBy: $userId,
            isAdjustment: $isAdjustment,
            isReversalOfId: $entry->id,
            adjustedPeriodId: $adjustedPeriodId,
        );

        return $this->post($dto);
    }

    /**
     * Sinh mã JE-YYYYMM-NNNN per (tenant, năm-tháng). Race-safe qua unique `(tenant, code)`:
     * nếu collision ⇒ retry với +1 (Postgres advisory lock không cần).
     */
    private function nextEntryCode(int $tenantId, Carbon $postedAt): string
    {
        $prefix = 'JE-'.$postedAt->format('Ym').'-';
        $like = $prefix.'%';
        $max = JournalEntry::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('code', 'like', $like)
            ->selectRaw('MAX(code) as max_code')->value('max_code');
        $next = 1;
        if ($max !== null) {
            $tail = (int) substr($max, strlen($prefix));
            $next = $tail + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
