<?php

namespace CMBcoreSeller\Integrations\Payments\SePay;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Payments\Contracts\PaymentGatewayConnector;
use CMBcoreSeller\Integrations\Payments\DTO\CheckoutRequest;
use CMBcoreSeller\Integrations\Payments\DTO\CheckoutSession;
use CMBcoreSeller\Integrations\Payments\DTO\PaymentNotification;
use CMBcoreSeller\Integrations\Payments\Exceptions\GatewayNotConfigured;
use CMBcoreSeller\Integrations\Payments\Exceptions\UnsupportedOperation;
use Illuminate\Http\Request;

/**
 * SePay — luồng "chuyển khoản tự động qua webhook sao kê". SPEC 0018 §4.1.
 *
 * Cách hoạt động:
 *   1. User chọn gói + cycle ⇒ BE tạo invoice (vd `INV-202605-0001`, total 199.000đ).
 *   2. `checkout()` trả về `CheckoutSession {method:'bank_transfer', qr_url, account_no, memo='INV-202605-0001', amount}`.
 *      `qr_url` được dựng động qua VietQR (`img.vietqr.io`).
 *   3. User mở app ngân hàng quét QR (hoặc nhập tay) + chuyển khoản. Ngân hàng nhận tiền.
 *   4. SePay quét sao kê + đẩy webhook về `/webhook/payments/sepay` với header `Authorization: Apikey <key>`.
 *   5. Backend verify → parse → match memo `INV-…` → tạo `payments` row, kích hoạt subscription.
 *
 * Idempotent: unique `(gateway, external_ref)` ở DB; payload SePay luôn có `id` ổn định
 * (transaction id ngân hàng).
 *
 * Không lưu thông tin tài khoản người chuyển (chỉ ngân hàng + 4 số cuối nếu SePay có gửi).
 */
class SePayConnector implements PaymentGatewayConnector
{
    public function __construct(protected array $config) {}

    public function code(): string
    {
        return 'sepay';
    }

    public function displayName(): string
    {
        return 'SePay (Chuyển khoản tự động)';
    }

    public function capabilities(): array
    {
        return [
            'checkout' => true,
            'webhook' => true,
            'refund' => false,
            'query' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function method(): string
    {
        return 'bank_transfer';
    }

    public function assertConfigured(): void
    {
        foreach (['account_no', 'account_name', 'bank_code'] as $key) {
            if (empty($this->config[$key])) {
                throw GatewayNotConfigured::for('sepay', $key);
            }
        }
    }

    public function checkout(CheckoutRequest $request): CheckoutSession
    {
        $this->assertConfigured();

        $accountNo = (string) $this->config['account_no'];
        $accountName = (string) $this->config['account_name'];
        $bankCode = (string) $this->config['bank_code'];
        // Memo = reference (invoice.code). SePay quét text trong giao dịch và khớp.
        $memo = $request->reference;

        // QR động — VietQR tiêu chuẩn ngân hàng VN. Định dạng URL theo `img.vietqr.io`:
        // https://img.vietqr.io/image/<bank>-<account>-<template>.png?amount=&addInfo=&accountName=
        $template = (string) ($this->config['qr_template'] ?? 'compact2');
        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s&accountName=%s',
            urlencode($bankCode),
            urlencode($accountNo),
            urlencode($template),
            $request->amount,
            urlencode($memo),
            urlencode($accountName),
        );

        return new CheckoutSession(
            method: 'bank_transfer',
            reference: $request->reference,
            amount: $request->amount,
            qrUrl: $qrUrl,
            accountNo: $accountNo,
            accountName: $accountName,
            bankCode: $bankCode,
            memo: $memo,
            message: 'Chuyển khoản với nội dung trên — hệ thống tự nhận diện trong vài giây sau khi giao dịch về.',
            expiresAt: now()->addDays(7)->getTimestamp(),
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return (new SePayWebhookVerifier((string) ($this->config['webhook_api_key'] ?? '')))->verify($request);
    }

    public function parseWebhook(Request $request): PaymentNotification
    {
        // SePay webhook payload (theo docs hiện tại):
        //   { id, gateway, transactionDate, accountNumber, content, transferType, transferAmount,
        //     accumulated, subAccount, referenceCode, description }
        //
        // - `transferType='in'` ⇒ tiền VỀ tài khoản (cái ta cần).
        // - `content` + `description` chứa memo (mã invoice).
        // - `id` = transaction id ngân hàng — unique, dùng làm `external_ref`.
        // - `transferAmount` = số tiền (số nguyên VND).
        $payload = $request->all();

        $direction = strtolower((string) ($payload['transferType'] ?? 'in'));
        $externalRef = (string) ($payload['id'] ?? '');
        $amount = (int) ($payload['transferAmount'] ?? 0);
        // Memo có thể nằm trong `content` hoặc `description` — match cả hai.
        $rawMemo = trim((string) ($payload['content'] ?? '').' '.(string) ($payload['description'] ?? ''));
        $reference = $this->extractInvoiceCode($rawMemo);

        $occurredAt = isset($payload['transactionDate']) && $payload['transactionDate']
            ? CarbonImmutable::parse((string) $payload['transactionDate'])
            : CarbonImmutable::now();

        // status=succeeded khi tiền VỀ thực sự (direction=in + amount>0); reference parse
        // được hay không là chuyện của PaymentService (orphan vs match). Mục đích tách:
        // connector chỉ chuẩn hoá payload thô; service quyết định "đi đâu".
        return new PaymentNotification(
            gateway: 'sepay',
            externalRef: $externalRef,
            reference: $reference ?? '',
            amount: $amount,
            status: ($direction === 'in' && $amount > 0) ? 'succeeded' : 'failed',
            occurredAt: $occurredAt,
            rawPayload: $this->redactPayload($payload),
        );
    }

    public function queryStatus(string $reference): PaymentNotification
    {
        throw UnsupportedOperation::for('sepay', 'queryStatus');
    }

    /** Tìm mã invoice `INV-YYYYMM-NNNN` trong memo. */
    protected function extractInvoiceCode(string $memo): ?string
    {
        if (preg_match('/INV-\d{6}-\d{4}/i', $memo, $m) === 1) {
            return strtoupper($m[0]);
        }

        return null;
    }

    /**
     * Loại field nhạy cảm (số tài khoản đầy đủ của buyer) trước khi lưu DB.
     * SePay không trả PAN; chỉ có `subAccount` có thể chứa số tài khoản người chuyển.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function redactPayload(array $payload): array
    {
        unset($payload['subAccount']);     // mask: không lưu tài khoản nguồn
        unset($payload['accountNumber']);  // tài khoản nhận = của chúng ta, không cần lưu DB
        return $payload;
    }
}
