<?php

namespace CMBcoreSeller\Integrations\Channels;

use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Holds the set of available channel connectors keyed by provider code.
 *
 * Register connectors in IntegrationsServiceProvider, gated by
 * config('integrations.channels'). Resolve with for($provider). Domain code
 * must go through this registry — never `new TikTokConnector()`.
 */
class ChannelRegistry
{
    /** @var array<string, class-string<ChannelConnector>> */
    protected array $connectors = [];

    public function __construct(protected Container $container) {}

    /**
     * @param  class-string<ChannelConnector>  $connectorClass
     */
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

    public function for(string $provider): ChannelConnector
    {
        if (! $this->has($provider)) {
            throw new InvalidArgumentException("No channel connector registered for provider [{$provider}].");
        }

        return $this->container->make($this->connectors[$provider]);
    }
}
