<?php

namespace CMBcoreSeller\Integrations\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\Contracts\EInvoiceConnector;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Tập các nhà cung cấp HĐĐT đã đăng ký, key theo provider code.
 * Đăng ký trong IntegrationsServiceProvider, gated bởi config('integrations.einvoice.enabled').
 * Module EInvoice PHẢI đi qua registry — không `new MisaMeInvoiceConnector()` trực tiếp.
 */
class EInvoiceRegistry
{
    /** @var array<string, class-string<EInvoiceConnector>> */
    protected array $connectors = [];

    public function __construct(protected Container $container) {}

    /** @param class-string<EInvoiceConnector> $connectorClass */
    public function register(string $provider, string $connectorClass): void
    {
        $this->connectors[$provider] = $connectorClass;
    }

    public function has(string $provider): bool
    {
        return isset($this->connectors[$provider]);
    }

    /** @return list<string> */
    public function providers(): array
    {
        return array_keys($this->connectors);
    }

    public function for(string $provider): EInvoiceConnector
    {
        if (! $this->has($provider)) {
            throw new InvalidArgumentException("Chưa đăng ký nhà cung cấp HĐĐT [{$provider}].");
        }

        return $this->container->make($this->connectors[$provider]);
    }
}
