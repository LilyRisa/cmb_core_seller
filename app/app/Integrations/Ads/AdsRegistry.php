<?php

namespace CMBcoreSeller\Integrations\Ads;

use CMBcoreSeller\Integrations\Ads\Contracts\AdsConnector;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Registry for ads providers (ADR-0017). Only providers listed in
 * `config('integrations.ads')` are registered. Mirror of MessagingRegistry.
 */
class AdsRegistry
{
    /** @var array<string,class-string> */
    private array $connectors = [];

    public function __construct(private Container $container) {}

    public function register(string $provider, string $connectorClass): void
    {
        $this->connectors[$provider] = $connectorClass;
    }

    public function has(string $provider): bool
    {
        return isset($this->connectors[$provider]);
    }

    public function for(string $provider): AdsConnector
    {
        if (! $this->has($provider)) {
            throw new RuntimeException("Ads connector [{$provider}] not registered.");
        }

        return $this->container->make($this->connectors[$provider]);
    }

    /** @return list<string> */
    public function providers(): array
    {
        return array_keys($this->connectors);
    }
}
