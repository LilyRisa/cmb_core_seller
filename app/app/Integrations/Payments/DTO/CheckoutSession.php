<?php

namespace CMBcoreSeller\Integrations\Payments\DTO;

/**
 * Phiên thanh toán trả về cho FE. Hai biến thể:
 *  - `method=bank_transfer` (SePay): FE hiện QR + thông tin chuyển khoản, poll
 *    `/billing/invoices/{id}/payment-status` để biết khi nào webhook khớp.
 *  - `method=redirect` (VNPay/MoMo): FE redirect tới `redirectUrl`.
 */
final class CheckoutSession
{
    public function __construct(
        public readonly string $method,           // 'bank_transfer' | 'redirect'
        public readonly string $reference,        // = invoice.code
        public readonly int $amount,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $qrUrl = null,
        public readonly ?string $accountNo = null,
        public readonly ?string $accountName = null,
        public readonly ?string $bankCode = null,
        public readonly ?string $memo = null,
        public readonly ?string $message = null,
        public readonly ?int $expiresAt = null,    // unix ts
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'method' => $this->method,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'redirect_url' => $this->redirectUrl,
            'qr_url' => $this->qrUrl,
            'account_no' => $this->accountNo,
            'account_name' => $this->accountName,
            'bank_code' => $this->bankCode,
            'memo' => $this->memo,
            'message' => $this->message,
            'expires_at' => $this->expiresAt,
        ], static fn ($v) => $v !== null);
    }
}
