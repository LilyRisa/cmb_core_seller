<?php

namespace CMBcoreSeller\Modules\Accounting\Services;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Exceptions\AccountingException;
use CMBcoreSeller\Modules\Accounting\Models\ChartAccount;
use CMBcoreSeller\Modules\Accounting\Models\CustomerReceipt;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phiếu thu (AR) — tạo và confirm để cấn trừ 131.
 * Phase 7.2 — SPEC 0019.
 *
 *  - Tạo draft: chưa post sổ.
 *  - Confirm: post Dr 1111|1121 / Cr 131 (party=customer).
 *  - Cancel: chỉ huỷ được khi draft.
 *
 * Cash/Bank account thực: Phase 7.4 sẽ wire `cash_accounts`. Trước đó dùng TK GL 1111/1121 trực
 * tiếp qua `payment_method` (cash → 1111, bank → 1121, ewallet → 1121).
 */
class CustomerReceiptService
{
    public function __construct(
        private readonly JournalService $journals,
        private readonly PostRuleResolver $rules,
    ) {}

    /**
     * @param  array{customer_id?:int,received_at:string,amount:int,payment_method:string,applied_orders?:array,memo?:string,cash_account_id?:int}  $payload
     */
    public function create(int $tenantId, array $payload, int $userId): CustomerReceipt
    {
        $row = CustomerReceipt::query()->create([
            'tenant_id' => $tenantId,
            'code' => $this->nextCode($tenantId),
            'customer_id' => $payload['customer_id'] ?? null,
            'received_at' => Carbon::parse($payload['received_at']),
            'amount' => (int) $payload['amount'],
            'payment_method' => $payload['payment_method'] ?? 'cash',
            'cash_account_id' => $payload['cash_account_id'] ?? null,
            'applied_orders' => $payload['applied_orders'] ?? null,
            'memo' => $payload['memo'] ?? null,
            'status' => CustomerReceipt::STATUS_DRAFT,
            'created_by' => $userId,
        ]);

        return $row;
    }

    public function confirm(CustomerReceipt $receipt, int $userId): CustomerReceipt
    {
        if ($receipt->status !== CustomerReceipt::STATUS_DRAFT) {
            throw AccountingException::invalidLines('Phiếu thu đã xử lý — không xác nhận lại.');
        }
        $tenantId = (int) $receipt->tenant_id;
        $key = $receipt->payment_method === 'cash' ? 'cash.receipt.from_customer' : 'bank.receipt.from_customer';
        $rule = $this->rules->resolve($tenantId, $key);
        if ($rule === null || ! $rule['enabled']) {
            throw AccountingException::invalidLines("Quy tắc hạch toán {$key} chưa cấu hình.");
        }

        $debitAcc = $this->postable($tenantId, $rule['debit']);
        $creditAcc = $this->postable($tenantId, $rule['credit']);
        if (! $debitAcc || ! $creditAcc) {
            throw AccountingException::accountNotPostable('debit/credit');
        }

        return DB::transaction(function () use ($receipt, $rule, $debitAcc, $creditAcc, $userId, $tenantId) {
            $partyOpts = $receipt->customer_id ? ['party_type' => 'customer', 'party_id' => (int) $receipt->customer_id] : [];

            $entry = $this->journals->post(new JournalEntryDTO(
                tenantId: $tenantId,
                postedAt: $receipt->received_at,
                sourceModule: 'accounting',
                sourceType: 'customer_receipt',
                sourceId: (int) $receipt->getKey(),
                idempotencyKey: sprintf('accounting.customer_receipt.%d.confirmed', (int) $receipt->getKey()),
                lines: [
                    JournalLineDTO::debit($debitAcc->code, (int) $receipt->amount, [
                        'memo' => sprintf('Thu tiền — %s', $receipt->code),
                    ]),
                    JournalLineDTO::credit($creditAcc->code, (int) $receipt->amount, array_merge($partyOpts, [
                        'memo' => sprintf('Cấn trừ công nợ khách qua phiếu %s', $receipt->code),
                    ])),
                ],
                narration: sprintf('Phiếu thu %s%s', $receipt->code, $receipt->memo ? ' — '.$receipt->memo : ''),
                createdBy: $userId,
            ));

            $receipt->forceFill([
                'status' => CustomerReceipt::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => $userId,
                'journal_entry_id' => $entry->getKey(),
            ])->save();

            return $receipt->refresh();
        });
    }

    public function cancel(CustomerReceipt $receipt): CustomerReceipt
    {
        if ($receipt->status !== CustomerReceipt::STATUS_DRAFT) {
            throw AccountingException::invalidLines('Chỉ huỷ được phiếu draft. Phiếu đã xác nhận → tạo phiếu điều chỉnh.');
        }
        $receipt->forceFill(['status' => CustomerReceipt::STATUS_CANCELLED])->save();

        return $receipt;
    }

    private function nextCode(int $tenantId): string
    {
        $prefix = 'PT-'.now()->format('Ym').'-';
        $max = CustomerReceipt::query()
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
