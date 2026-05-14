<?php

namespace CMBcoreSeller\Integrations\Payments\VnPay;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Payments\Contracts\PaymentGatewayConnector;
use CMBcoreSeller\Integrations\Payments\DTO\CheckoutRequest;
use CMBcoreSeller\Integrations\Payments\DTO\CheckoutSession;
use CMBcoreSeller\Integrations\Payments\DTO\PaymentNotification;
use CMBcoreSeller\Integrations\Payments\Exceptions\GatewayNotConfigured;
use CMBcoreSeller\Integrations\Payments\Exceptions\UnsupportedOperation;
use Illuminate\Http\Request;

/**
 * VNPay — redirect + IPN. SPEC 0018 §4.2.
 *
 * Quy trình:
 *   1. checkout() build URL `vnp_*` đã ký HMAC-SHA512 → FE redirect.
 *   2. User thanh toán ở VNPay → VNPay redirect về `/payments/vnpay/return` (UX, không tin).
 *   3. VNPay đẩy **IPN** server-to-server về `/webhook/payments/vnpay` (đây là nguồn sự thật).
 *   4. parseWebhook chuẩn hoá thành `PaymentNotification`.
 *
 * Tham chiếu: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html
 *
 * VNPay quy ước:
 *   - `vnp_Amount` = amount × 100 (đơn vị xu — 199.000đ → 19900000).
 *   - `vnp_TxnRef` BẮT BUỘC unique per merchant — dùng invoice.code (đảm bảo unique).
 *   - `vnp_OrderInfo` mô tả tự do.
 *   - `vnp_ResponseCode='00'` ⇒ thanh toán thành công.
 *
 * Capability `refund` = false (v1); cần `vnp_RefundUrl` thường tách hợp đồng riêng.
 */
class VnPayConnector implements PaymentGatewayConnector
{
    public function __construct(protected array $config) {}

    public function code(): string
    {
        return 'vnpay';
    }

    public function displayName(): string
    {
        return 'VNPay';
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
        return 'redirect';
    }

    public function assertConfigured(): void
    {
        foreach (['tmn_code', 'hash_secret', 'pay_url'] as $key) {
            if (empty($this->config[$key])) {
                throw GatewayNotConfigured::for('vnpay', $key);
            }
        }
    }

    public function checkout(CheckoutRequest $request): CheckoutSession
    {
        $this->assertConfigured();

        $now = now();
        $params = [
            'vnp_Version' => (string) ($this->config['version'] ?? '2.1.0'),
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => (string) $this->config['tmn_code'],
            'vnp_Amount' => (string) ($request->amount * 100),       // VNPay dùng đơn vị xu
            'vnp_CurrCode' => (string) ($this->config['curr_code'] ?? 'VND'),
            'vnp_TxnRef' => $request->reference,
            'vnp_OrderInfo' => $request->description,
            'vnp_OrderType' => 'billpayment',                                 // SaaS subscription
            'vnp_Locale' => (string) ($this->config['locale'] ?? 'vn'),
            'vnp_ReturnUrl' => $request->returnUrl
                ?? (string) ($this->config['return_url'] ?? url('/payments/vnpay/return')),
            'vnp_IpAddr' => request()?->ip() ?? '127.0.0.1',
            'vnp_CreateDate' => $now->format('YmdHis'),
            // Để ngắn (15 phút) thì user phải hoàn tất nhanh; sandbox cho phép tới 1 giờ.
            'vnp_ExpireDate' => $now->copy()->addMinutes(60)->format('YmdHis'),
        ];

        $signer = new VnPaySigner((string) $this->config['hash_secret']);
        $canonical = $signer->canonicalString($params);
        $signature = $signer->sign($params);

        $payUrl = rtrim((string) $this->config['pay_url'], '?');
        $redirectUrl = $payUrl.'?'.$canonical.'&vnp_SecureHash='.$signature;

        return new CheckoutSession(
            method: 'redirect',
            reference: $request->reference,
            amount: $request->amount,
            redirectUrl: $redirectUrl,
            message: 'Bạn sẽ được chuyển sang VNPay để thanh toán.',
            expiresAt: $now->copy()->addMinutes(60)->getTimestamp(),
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $params = $request->all();
        $signature = (string) ($params['vnp_SecureHash'] ?? '');
        if ($signature === '' || empty($this->config['hash_secret'])) {
            return false;
        }
        $signer = new VnPaySigner((string) $this->config['hash_secret']);

        return $signer->verify($params, $signature);
    }

    public function parseWebhook(Request $request): PaymentNotification
    {
        $payload = $request->all();
        $responseCode = (string) ($payload['vnp_ResponseCode'] ?? '');
        $txnStatus = (string) ($payload['vnp_TransactionStatus'] ?? $responseCode);

        $occurredAt = isset($payload['vnp_PayDate']) && $payload['vnp_PayDate']
            ? CarbonImmutable::createFromFormat('YmdHis', (string) $payload['vnp_PayDate'])
            : CarbonImmutable::now();
        if ($occurredAt === false) {
            $occurredAt = CarbonImmutable::now();
        }

        return new PaymentNotification(
            gateway: 'vnpay',
            externalRef: (string) ($payload['vnp_TransactionNo'] ?? $payload['vnp_TxnRef'] ?? ''),
            reference: (string) ($payload['vnp_TxnRef'] ?? ''),
            amount: (int) round(((int) ($payload['vnp_Amount'] ?? 0)) / 100),  // xu → đồng
            status: ($responseCode === '00' && $txnStatus === '00') ? 'succeeded' : 'failed',
            occurredAt: $occurredAt,
            rawPayload: $this->redactPayload($payload),
        );
    }

    public function queryStatus(string $reference): PaymentNotification
    {
        throw UnsupportedOperation::for('vnpay', 'queryStatus');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function redactPayload(array $payload): array
    {
        // VNPay không trả PAN/CVV. Giữ lại tất cả `vnp_*` không nhạy cảm.
        return $payload;
    }
}
