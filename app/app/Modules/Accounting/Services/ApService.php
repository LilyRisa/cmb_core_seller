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
     * Aging buckets cho AP per supplier.
     *
     * @return array<int, array{supplier_id:int, total:int, b0_30:int, b31_60:int, b61_90:int, b90p:int}>
     */
    public function agingBySupplier(int $tenantId, ?Carbon $asOf = null): array
    {
        $asOf ??= Carbon::now();
        $rows = JournalLine::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('party_type', 'supplier')
            ->where('account_code', '331')
            ->where('posted_at', '<=', $asOf)
            ->selectRaw('party_id as supplier_id, posted_at, SUM(dr_amount) as dr, SUM(cr_amount) as cr')
            ->groupBy('party_id', 'posted_at')
            ->get();
        $bucketsBySupplier = [];
        foreach ($rows as $r) {
            $sid = (int) ($r->supplier_id ?? 0);
            if ($sid === 0) {
                continue;
            }
            // TK 331 credit-normal → số phải trả = cr - dr (dương = còn nợ NCC)
            $amount = (int) $r->cr - (int) $r->dr;
            if ($amount === 0) {
                continue;
            }
            $daysAgo = Carbon::parse($r->posted_at)->diffInDays($asOf);
            $bucket = match (true) {
                $daysAgo <= 30 => 'b0_30',
                $daysAgo <= 60 => 'b31_60',
                $daysAgo <= 90 => 'b61_90',
                default => 'b90p',
            };
            $bucketsBySupplier[$sid] ??= ['supplier_id' => $sid, 'total' => 0, 'b0_30' => 0, 'b31_60' => 0, 'b61_90' => 0, 'b90p' => 0];
            $bucketsBySupplier[$sid][$bucket] += $amount;
            $bucketsBySupplier[$sid]['total'] += $amount;
        }

        return array_values(array_filter($bucketsBySupplier, fn ($b) => $b['total'] > 0));
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
