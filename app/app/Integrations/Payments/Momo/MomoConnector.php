<?php

namespace CMBcoreSeller\Integrations\Payments\Momo;

use CMBcoreSeller\Integrations\Payments\Contracts\PaymentGatewayConnector;
use CMBcoreSeller\Integrations\Payments\DTO\CheckoutRequest;
use CMBcoreSeller\Integrations\Payments\DTO\CheckoutSession;
use CMBcoreSeller\Integrations\Payments\DTO\PaymentNotification;
use CMBcoreSeller\Integrations\Payments\Exceptions\UnsupportedOperation;
use Illuminate\Http\Request;

/**
 * MoMo — SKELETON (Phase 6.4 PR3, SPEC 0018 §4.3).
 *
 * Tất cả method ném {@see UnsupportedOperation}. Khi shop có nhu cầu thật ⇒ implement đầy đủ
 * (giống VnPayConnector pattern) — config đã chừa sẵn trong `config/integrations.php`.
 *
 * Lý do giữ skeleton thay vì ẩn hẳn: FE có thể render option MoMo dưới dạng "Sắp có"
 * + admin có thể test capability map qua API.
 */
class MomoConnector implements PaymentGatewayConnector
{
    public function __construct(protected array $config) {}

    public function code(): string
    {
        return 'momo';
    }

    public function displayName(): string
    {
        return 'MoMo (sắp có)';
    }

    public function capabilities(): array
    {
        return ['checkout' => false, 'webhook' => false, 'refund' => false, 'query' => false];
    }

    public function supports(string $capability): bool
    {
        return false;
    }

    public function method(): string
    {
        return 'redirect';
    }

    public function assertConfigured(): void
    {
        throw UnsupportedOperation::for('momo', 'assertConfigured');
    }

    public function checkout(CheckoutRequest $request): CheckoutSession
    {
        throw UnsupportedOperation::for('momo', 'checkout');
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(Request $request): PaymentNotification
    {
        throw UnsupportedOperation::for('momo', 'parseWebhook');
    }

    public function queryStatus(string $reference): PaymentNotification
    {
        throw UnsupportedOperation::for('momo', 'queryStatus');
    }
}
