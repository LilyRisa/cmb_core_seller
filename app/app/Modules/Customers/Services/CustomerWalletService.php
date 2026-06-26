<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Modules\Accounting\Contracts\CustomerAdvanceLedger;
use CMBcoreSeller\Modules\Customers\Contracts\CustomerWallet;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerWalletTransaction;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Ví trả trước của khách (operational source of truth) — số dư denormalized + sổ append-only.
 * GL nạp ví đi qua {@see CustomerAdvanceLedger} (Dr tiền/Cr 131). SPEC 2026-06-26.
 */
class CustomerWalletService implements CustomerWallet
{
    public function __construct(private readonly CustomerAdvanceLedger $ledger) {}

    public function topup(int $tenantId, int $customerId, int $amount, string $paymentMethod, string $invoiceRef, ?string $note, ?int $userId): CustomerWalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Số tiền nạp phải lớn hơn 0.');
        }
        if (trim($invoiceRef) === '') {
            throw new RuntimeException('Phải nhập số/mã hóa đơn khi nạp tiền.');
        }

        return DB::transaction(function () use ($tenantId, $customerId, $amount, $paymentMethod, $invoiceRef, $note, $userId) {
            $customer = $this->lockCustomer($tenantId, $customerId);
            // GL trước (trong cùng transaction) — Dr tiền/Cr 131.
            $memo = 'Nạp ví khách — HĐ '.trim($invoiceRef).($note ? ' — '.$note : '');
            $jeId = $this->ledger->recordTopup($tenantId, $customerId, $amount, $paymentMethod, $memo, $userId);
            $balance = (int) $customer->prepaid_balance + $amount;
            $customer->forceFill(['prepaid_balance' => $balance])->save();

            return $this->log($tenantId, $customerId, null, CustomerWalletTransaction::TYPE_TOPUP, $amount, $balance, $paymentMethod, trim($invoiceRef), $jeId, $note, $userId);
        });
    }

    public function deductForOrder(int $tenantId, int $customerId, int $orderId, int $amount, ?int $userId): CustomerWalletTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('Số tiền trừ ví phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($tenantId, $customerId, $orderId, $amount, $userId) {
            $existing = CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
                ->where('order_id', $orderId)->where('type', CustomerWalletTransaction::TYPE_ORDER_PAYMENT)->first();
            if ($existing) {
                return $existing; // idempotent
            }
            $customer = $this->lockCustomer($tenantId, $customerId);
            if ((int) $customer->prepaid_balance < $amount) {
                throw new RuntimeException('Số dư ví không đủ.');
            }
            $balance = (int) $customer->prepaid_balance - $amount;
            $customer->forceFill(['prepaid_balance' => $balance])->save();

            return $this->log($tenantId, $customerId, $orderId, CustomerWalletTransaction::TYPE_ORDER_PAYMENT, -$amount, $balance, null, null, null, null, $userId);
        });
    }

    public function refundForOrder(int $tenantId, int $customerId, int $orderId, ?int $userId): ?CustomerWalletTransaction
    {
        return DB::transaction(function () use ($tenantId, $customerId, $orderId, $userId) {
            $payment = CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
                ->where('order_id', $orderId)->where('type', CustomerWalletTransaction::TYPE_ORDER_PAYMENT)->first();
            if (! $payment) {
                return null; // đơn không trả bằng ví
            }
            $already = CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)
                ->where('order_id', $orderId)->where('type', CustomerWalletTransaction::TYPE_REFUND)->exists();
            if ($already) {
                return null; // idempotent
            }
            $amount = abs((int) $payment->amount);
            $customer = $this->lockCustomer($tenantId, $customerId);
            $balance = (int) $customer->prepaid_balance + $amount;
            $customer->forceFill(['prepaid_balance' => $balance])->save();

            return $this->log($tenantId, $customerId, $orderId, CustomerWalletTransaction::TYPE_REFUND, $amount, $balance, null, null, null, 'Hoàn ví do huỷ/hoàn đơn', $userId);
        });
    }

    private function lockCustomer(int $tenantId, int $customerId): Customer
    {
        $c = Customer::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->whereKey($customerId)->lockForUpdate()->first();
        if (! $c) {
            throw new RuntimeException('Không tìm thấy khách hàng.');
        }

        return $c;
    }

    private function log(int $tenantId, int $customerId, ?int $orderId, string $type, int $amount, int $balanceAfter, ?string $method, ?string $invoiceRef, ?int $jeId, ?string $note, ?int $userId): CustomerWalletTransaction
    {
        return CustomerWalletTransaction::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId, 'customer_id' => $customerId, 'order_id' => $orderId,
            'type' => $type, 'amount' => $amount, 'balance_after' => $balanceAfter,
            'payment_method' => $method, 'invoice_ref' => $invoiceRef, 'journal_entry_id' => $jeId,
            'note' => $note, 'created_by' => $userId, 'created_at' => now(),
        ]);
    }
}
