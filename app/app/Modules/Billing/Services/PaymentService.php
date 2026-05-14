<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Integrations\Payments\DTO\PaymentNotification;
use CMBcoreSeller\Modules\Billing\Events\InvoicePaid;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Payment;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Áp `PaymentNotification` từ gateway vào DB:
 *   - dedupe unique `(gateway, external_ref)` ⇒ webhook chạy 2 lần = 1 row.
 *   - match invoice qua `reference` (= `invoice.code`).
 *   - underpay ⇒ payment ghi nhận `succeeded` nhưng invoice GIỮ NGUYÊN `pending` (user phải chuyển thêm).
 *   - đủ tiền ⇒ phát event `InvoicePaid` ⇒ listener `ActivateSubscription` chạy.
 *
 * @return array{outcome:'created'|'duplicate'|'orphan'|'failed', payment?:Payment, invoice?:Invoice}
 */
class PaymentService
{
    /**
     * @return array{outcome:string, payment?:Payment, invoice?:Invoice, reason?:string}
     */
    public function applyNotification(PaymentNotification $notification): array
    {
        // 1) Webhook báo failed ⇒ ghi log + bỏ qua (không insert payment).
        if (! $notification->isSucceeded()) {
            Log::warning('payments.webhook.non_succeeded', ['gateway' => $notification->gateway, 'ref' => $notification->reference]);
            return ['outcome' => 'failed', 'reason' => 'non_succeeded'];
        }

        // 2) Dedupe theo (gateway, external_ref) — webhook chạy 2 lần = no-op.
        $existing = Payment::query()->withoutGlobalScope(TenantScope::class)
            ->where('gateway', $notification->gateway)
            ->where('external_ref', $notification->externalRef)
            ->first();
        if ($existing !== null) {
            return ['outcome' => 'duplicate', 'payment' => $existing];
        }

        // 3) Match invoice qua reference.
        if ($notification->reference === '') {
            return ['outcome' => 'orphan', 'reason' => 'no_reference'];
        }
        $invoice = Invoice::query()->withoutGlobalScope(TenantScope::class)
            ->where('code', $notification->reference)
            ->first();
        if ($invoice === null) {
            Log::warning('payments.webhook.orphan', ['ref' => $notification->reference, 'gateway' => $notification->gateway]);
            return ['outcome' => 'orphan', 'reason' => 'invoice_not_found'];
        }

        // 4) Insert payment + cập nhật invoice atomic.
        return DB::transaction(function () use ($notification, $invoice) {
            $payment = Payment::query()->create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->getKey(),
                'gateway' => $notification->gateway,
                'external_ref' => $notification->externalRef,
                'amount' => $notification->amount,
                'status' => Payment::STATUS_SUCCEEDED,
                'raw_payload' => $notification->rawPayload,
                'occurred_at' => $notification->occurredAt,
            ]);

            // Tổng đã thanh toán cho invoice (cộng các payments succeeded khác — nếu có).
            $totalPaid = (int) Payment::query()->withoutGlobalScope(TenantScope::class)
                ->where('invoice_id', $invoice->getKey())
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->sum('amount');

            if ($totalPaid >= (int) $invoice->total && $invoice->status !== Invoice::STATUS_PAID) {
                $invoice->forceFill([
                    'status' => Invoice::STATUS_PAID,
                    'paid_at' => now(),
                ])->save();

                InvoicePaid::dispatch($invoice->fresh(), $payment);

                return ['outcome' => 'created', 'payment' => $payment, 'invoice' => $invoice->fresh()];
            }

            // Underpay — invoice giữ pending; FE poll sẽ thấy chưa paid.
            return ['outcome' => 'created', 'payment' => $payment, 'invoice' => $invoice];
        });
    }
}
