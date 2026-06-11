<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use Illuminate\Contracts\Container\Container;

/**
 * Holds the set of available product-publishing connectors keyed by provider code.
 *
 * Register connectors in IntegrationsServiceProvider. Resolve with for($provider).
 * Domain code must go through this registry — never `new LazadaPublisher()`.
 */
final class PublisherRegistry
{
    /** @var array<string, class-string> */
    private array $map = [];

    public function __construct(private Container $c) {}

    /**
     * @param  class-string  $cls
     */
    public function register(string $provider, string $cls): void
    {
        $this->map[$provider] = $cls;
    }

    public function for(string $provider): ProductPublishingConnector
    {
        if (! isset($this->map[$provider])) {
            throw UnsupportedOperation::for($provider, 'listings.publish');
        }

        return $this->c->make($this->map[$provider]);
    }

    public function has(string $provider): bool
    {
        return isset($this->map[$provider]);
    }
}
