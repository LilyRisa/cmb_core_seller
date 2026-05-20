<?php

namespace CMBcoreSeller\Integrations\Messaging;

use CMBcoreSeller\Integrations\Messaging\Contracts\MessagingConnector;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Singleton — tập hợp messaging connector active, keyed by provider code.
 *
 * Register ở `IntegrationsServiceProvider::register()` gated by
 * `config('integrations.messaging')`. Resolve qua `for($provider)`. Domain
 * code (`Modules\Messaging`) PHẢI đi qua registry này — không `new
 * FacebookConnector()` trực tiếp.
 *
 * Mirror `ChannelRegistry` (ADR-0004 + ADR-0017).
 */
class MessagingRegistry
{
    /** @var array<string, class-string<MessagingConnector>> */
    protected array $connectors = [];

    public function __construct(protected Container $container) {}

    /**
     * @param  class-string<MessagingConnector>  $connectorClass
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

    public function for(string $provider): MessagingConnector
    {
        if (! $this->has($provider)) {
            throw new InvalidArgumentException("No messaging connector registered for provider [{$provider}].");
        }

        return $this->container->make($this->connectors[$provider]);
    }
}
