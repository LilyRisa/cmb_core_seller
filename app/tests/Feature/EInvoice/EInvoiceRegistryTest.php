<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry;
use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\MisaMeInvoiceConnector;
use Tests\TestCase;

class EInvoiceRegistryTest extends TestCase
{
    public function test_registry_registers_and_resolves_provider(): void
    {
        $registry = new EInvoiceRegistry($this->app);
        $this->app->bind(MisaMeInvoiceConnector::class, fn () => new MisaMeInvoiceConnector(
            (array) config('integrations.einvoice.misa', [])
        ));
        $registry->register('misa', MisaMeInvoiceConnector::class);

        $this->assertTrue($registry->has('misa'));
        $this->assertContains('misa', $registry->providers());
        $this->assertInstanceOf(MisaMeInvoiceConnector::class, $registry->for('misa'));
    }

    public function test_unknown_provider_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new EInvoiceRegistry($this->app))->for('nope');
    }
}
