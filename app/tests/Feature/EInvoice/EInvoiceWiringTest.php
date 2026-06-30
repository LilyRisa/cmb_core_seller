<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\EInvoiceRegistry;
use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\MisaMeInvoiceConnector;
use Tests\TestCase;

class EInvoiceWiringTest extends TestCase
{
    public function test_registry_resolved_from_container_with_misa_enabled(): void
    {
        config(['integrations.einvoice.enabled' => ['misa']]);
        $this->app->forgetInstance(EInvoiceRegistry::class);

        $registry = $this->app->make(EInvoiceRegistry::class);
        $this->assertTrue($registry->has('misa'));
        $this->assertInstanceOf(MisaMeInvoiceConnector::class, $registry->for('misa'));
    }

    public function test_registry_is_inert_when_enabled_is_empty(): void
    {
        config(['integrations.einvoice.enabled' => []]);
        $this->app->forgetInstance(EInvoiceRegistry::class);

        $registry = $this->app->make(EInvoiceRegistry::class);
        $this->assertFalse($registry->has('misa'));
        $this->assertSame([], $registry->providers());
    }
}
