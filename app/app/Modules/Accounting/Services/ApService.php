<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\JournalLine;
use CMBcoreSeller\Modules\Accounting\Models\VendorBill;
use CMBcoreSeller\Modules\Accounting\Models\VendorPayment;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AP — Sổ chi tiết 331 + vendor bills/payments. Phase 7.3 — SPEC 0019.
 *
 * Sổ chi tiết TK 331 (Phải trả NCC) — credit-normal: SUM(cr) − SUM(dr).
 */
class ApService
{
    public function __construct(
        private readonly JournalService $journals,
        private readonly PostRuleResolver $rules,
    ) {}

    /**
     * Aging buckets cho AP per supplier. TK 331 credit-normal — số phải trả = cr - dr.
     * Audit-fix: aggregate ở SQL CASE WHEN (giống ArService).
     *
     * @return array<int, array{supplier_id:int, total:int, b0_30:int, b31_60:int, b61_90:int, b90p:int}>
     */
    public function agingBySupplier(int $tenantId, ?Carbon $asOf = null): array
    {
        $asOf ??= Carbon::now();
        $asOfStr = $asOf->toDateTimeString();
        $daysExpr = $this->daysDiffExpr();

        $rows = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('party_type', 'supplier')
            ->where('account_code', '331')
            ->where('posted_at', '<=', $asOf)
            ->whereNotNull('party_id')
            ->selectRaw("party_id as supplier_id,
                SUM(CASE WHEN {$daysExpr} <= 30 THEN cr_amount - dr_amount ELSE 0 END) as b0_30,
                SUM(CASE WHEN {$daysExpr} > 30 AND {$daysExpr} <= 60 THEN cr_amount - dr_amount ELSE 0 END) as b31_60,
                SUM(CASE WHEN {$daysExpr} > 60 AND {$daysExpr} <= 90 THEN cr_amount - dr_amount ELSE 0 END) as b61_90,
                SUM(CASE WHEN {$daysExpr} > 90 THEN cr_amount - dr_amount ELSE 0 END) as b90p,
                SUM(cr_amount - dr_amount) as total",
                array_fill(0, 6, $asOfStr)) // 6 lần `?` (1+2+2+1)
            ->groupBy('party_id')
            ->havingRaw('SUM(cr_amount - dr_amount) > 0')
            ->get();

        return $rows->map(fn ($r) => [
            'supplier_id' => (int) $r->supplier_id,
            'total' => (int) $r->total,
            'b0_30' => (int) $r->b0_30,
            'b31_60' => (int) $r->b31_60,
            'b61_90' => (int) $r->b61_90,
            'b90p' => (int) $r->b90p,
        ])->all();
    }

    private function daysDiffExpr(): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => 'CAST(julianday(?) - julianday(posted_at) AS INTEGER)',
            'pgsql' => "EXTRACT(DAY FROM (?::timestamp - posted_at))",
            default => 'TIMESTAMPDIFF(DAY, posted_at, ?)',
        };
    }

    /** Tổng phải trả per supplier. */
    public function balanceBySupplier(int $tenantId, int $supplierId): int
    {
        $row = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('party_type', 'supplier')
            ->where('party_id', $supplierId)
            ->where('account_code', '331')
            ->selectRaw('SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->first();

        return (int) ($row->cr ?? 0) - (int) ($row->dr ?? 0);
    }

    public function createBill(int $tenantId, array $payload, int $userId): VendorBill
    {
        $subtotal = (int) ($payload['subtotal'] ?? 0);
        $tax = (int) ($payload['tax'] ?? 0);
        $total = $subtotal + $tax;

        return VendorBill::query()->create([
            'tenant_id' => $tenantId,
            'code' => $this->nextBillCode($tenantId),
            'supplier_id' => $payload['supplier_id'] ?? null,
            'purchase_order_id' => $payload['purchase_order_id'] ?? null,
            'goods_receipt_id' => $payload['goods_receipt_id'] ?? null,
            'bill_no' => $payload['bill_no'] ?? null,
            'bill_date' => Carbon::parse($payload['bill_date'] ?? now()),
            'due_date' => isset($payload['due_date']) ? Carbon::parse($payload['due_date']) : null,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'status' => VendorBill::STATUS_DRAFT,
            'memo' => $payload['memo'] ?? null,
            'created_by' => $userId,
        ]);
    }

    public function recordBill(VendorBill $bill, int $userId): VendorBill
    {
        if ($bill->status !== VendorBill::STATUS_DRAFT) {
            throw AccountingException::invalidLines('Hoá đơn đã ghi sổ — không sửa lại.');
        }
        $tenantId = (int) $bill->tenant_id;
        $rule = $this->rules->resolve($tenantId, 'procurement.vendor_bill.recorded');
        if ($rule === null || ! $rule['enabled']) {
            throw AccountingException::invalidLines('Quy tắc procurement.vendor_bill.recorded chưa cấu hình.');
        }
        $debitAcc = $this->postable($tenantId, $rule['debit']);
        $creditAcc = $this->postable($tenantId, $rule['credit']);
        if (! $debitAcc || ! $creditAcc) {
            throw AccountingException::accountNotPostable('debit/credit');
        }
        $partyOpts = $bill->supplier_id ? ['party_type' => 'supplier', 'party_id' => (int) $bill->supplier_id] : [];

        return DB::transaction(function () use ($bill, $debitAcc, $creditAcc, $userId, $tenantId, $partyOpts) {
            $lines = [
                JournalLineDTO::debit($debitAcc->code, (int) $bill->subtotal, array_merge($partyOpts, [
                    'memo' => 'Giá trị hàng hoá NCC',
                ])),
            ];
            if ((int) $bill->tax > 0) {
                $vatRule = $this->rules->resolve($tenantId, 'procurement.vendor_bill.vat');
                if ($vatRule && $vatRule['enabled']) {
                    $vatAcc = $this->postable($tenantId, $vatRule['debit']);
                    if ($vatAcc) {
                        $lines[] = JournalLineDTO::debit($vatAcc->code, (int) $bill->tax, array_merge($partyOpts, [
                            'memo' => 'VAT đầu vào',
                        ]));
                    }
                }
            }
            $lines[] = JournalLineDTO::credit($creditAcc->code, (int) $bill->total, array_merge($partyOpts, [
                'memo' => sprintf('Phải trả NCC theo HĐ %s', $bill->code),
            ]));

            $entry = $this->journals->post(new JournalEntryDTO(
                tenantId: $tenantId,
                postedAt: $bill->bill_date,
                sourceModule: 'procurement',
                sourceType: 'vendor_bill',
                sourceId: (int) $bill->getKey(),
                idempotencyKey: sprintf('procurement.vendor_bill.%d.recorded', (int) $bill->getKey()),
                lines: $lines,
                narration: sprintf('Hoá đơn NCC %s%s', $bill->code, $bill->bill_no ? ' — số '.$bill->bill_no : ''),
                createdBy: $userId,
            ));

            $bill->forceFill([
                'status' => VendorBill::STATUS_RECORDED,
                'recorded_at' => now(),
                'recorded_by' => $userId,
                'journal_entry_id' => $entry->getKey(),
            ])->save();

            return $bill->refresh();
        });
    }

    public function createPayment(int $tenantId, array $payload, int $userId): VendorPayment
    {
        return VendorPayment::query()->create([
            'tenant_id' => $tenantId,
            'code' => $this->nextPaymentCode($tenantId),
            'supplier_id' => $payload['supplier_id'] ?? null,
            'paid_at' => Carbon::parse($payload['paid_at']),
            'amount' => (int) $payload['amount'],
            'payment_method' => $payload['payment_method'] ?? 'cash',
            'applied_bills' => $payload['applied_bills'] ?? null,
            'memo' => $payload['memo'] ?? null,
            'status' => VendorPayment::STATUS_DRAFT,
            'created_by' => $userId,
        ]);
    }

    public function confirmPayment(VendorPayment $payment, int $userId): VendorPayment
    {
        if ($payment->status !== VendorPayment::STATUS_DRAFT) {
            throw AccountingException::invalidLines('Phiếu chi đã xử lý.');
        }
        $tenantId = (int) $payment->tenant_id;
        $key = $payment->payment_method === 'cash' ? 'cash.payment.to_supplier' : 'bank.payment.to_supplier';
        $rule = $this->rules->resolve($tenantId, $key);
        if ($rule === null || ! $rule['enabled']) {
            throw AccountingException::invalidLines("Quy tắc {$key} chưa cấu hình.");
        }
        $debitAcc = $this->postable($tenantId, $rule['debit']);
        $creditAcc = $this->postable($tenantId, $rule['credit']);
        if (! $debitAcc || ! $creditAcc) {
            throw AccountingException::accountNotPostable('debit/credit');
        }

        return DB::transaction(function () use ($payment, $debitAcc, $creditAcc, $userId, $tenantId) {
            $partyOpts = $payment->supplier_id ? ['party_type' => 'supplier', 'party_id' => (int) $payment->supplier_id] : [];
            $entry = $this->journals->post(new JournalEntryDTO(
                tenantId: $tenantId,
                postedAt: $payment->paid_at,
                sourceModule: 'accounting',
                sourceType: 'vendor_payment',
                sourceId: (int) $payment->getKey(),
                idempotencyKey: sprintf('accounting.vendor_payment.%d.confirmed', (int) $payment->getKey()),
                lines: [
                    JournalLineDTO::debit($debitAcc->code, (int) $payment->amount, array_merge($partyOpts, [
                        'memo' => sprintf('Trả công nợ NCC qua %s', $payment->code),
                    ])),
                    JournalLineDTO::credit($creditAcc->code, (int) $payment->amount, [
                        'memo' => sprintf('Chi tiền — %s', $payment->code),
                    ]),
                ],
                narration: sprintf('Phiếu chi %s%s', $payment->code, $payment->memo ? ' — '.$payment->memo : ''),
                createdBy: $userId,
            ));

            $payment->forceFill([
                'status' => VendorPayment::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => $userId,
                'journal_entry_id' => $entry->getKey(),
            ])->save();

            return $payment->refresh();
        });
    }

    private function nextBillCode(int $tenantId): string
    {
        return $this->nextCode($tenantId, 'HDNCC', VendorBill::class);
    }

    private function nextPaymentCode(int $tenantId): string
    {
        return $this->nextCode($tenantId, 'PC', VendorPayment::class);
    }

    private function nextCode(int $tenantId, string $prefixBase, string $modelClass): string
    {
        $prefix = $prefixBase.'-'.now()->format('Ym').'-';
        $max = $modelClass::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('code', 'like', $prefix.'%')
            ->selectRaw('MAX(code) as max_code')->value('max_code');
        $next = $max !== null ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function postable(int $tenantId, string $code): ?ChartAccount
    {
        return ChartAccount::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('code', $code)
            ->where('is_postable', true)->where('is_active', true)->first();
    }
}
