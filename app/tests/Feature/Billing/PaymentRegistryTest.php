<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Integrations\Payments\DTO\CheckoutRequest;
use CMBcoreSeller\Integrations\Payments\PaymentRegistry;
use CMBcoreSeller\Integrations\Payments\SePay\SePayConnector;
use Tests\TestCase;

/**
 * Phase 6.4 / SPEC 0018 — PaymentRegistry resolve + SePay connector contract.
 */
class PaymentRegistryTest extends TestCase
{
    public function test_registry_resolves_sepay_when_enabled(): void
    {
        /** @var PaymentRegistry $registry */
        $registry = $this->app->make(PaymentRegistry::class);
        $this->assertTrue($registry->has('sepay'));
        $this->assertInstanceOf(SePayConnector::class, $registry->for('sepay'));
        $this->assertContains('sepay', $registry->gateways());
    }

    public function test_sepay_checkout_returns_qr_session_with_invoice_memo(): void
    {
        /** @var SePayConnector $connector */
        $connector = $this->app->make(PaymentRegistry::class)->for('sepay');

        $session = $connector->checkout(new CheckoutRequest(
            tenantId: 1,
            invoiceId: 100,
            reference: 'INV-202605-0042',
            amount: 199_000,
            description: 'Pro monthly',
        ));

        $this->assertSame('bank_transfer', $session->method);
        $this->assertSame('INV-202605-0042', $session->reference);
        $this->assertSame(199_000, $session->amount);
        $this->assertSame('9999999999', $session->accountNo);
        $this->assertSame('MB', $session->bankCode);
        $this->assertSame('INV-202605-0042', $session->memo);
        $this->assertNotNull($session->qrUrl);
        $this->assertStringContainsString('img.vietqr.io', $session->qrUrl);
        $this->assertStringContainsString('MB', $session->qrUrl);
        $this->assertStringContainsString('9999999999', $session->qrUrl);
        $this->assertStringContainsString('INV-202605-0042', $session->qrUrl);
        $this->assertStringContainsString('amount=199000', $session->qrUrl);
    }
}
