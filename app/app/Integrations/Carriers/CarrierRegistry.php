<?php

namespace CMBcoreSeller\Integrations\Carriers;

use CMBcoreSeller\Integrations\Carriers\Contracts\CarrierConnector;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Holds the set of available shipping-carrier connectors keyed by carrier code.
 * Mirror of ChannelRegistry. Domain code goes through this — never `new GhnConnector()`.
 */
class CarrierRegistry
{
    /** @var array<string, class-string<CarrierConnector>> */
    protected array $connectors = [];

    public function __construct(protected Container $container) {}

    /**
     * @param  class-string<CarrierConnector>  $connectorClass
     */
    public function register(string $carrier, string $connectorClass): void
    {
        $this->connectors[$carrier] = $connectorClass;
    }

    public function has(string $carrier): bool
    {
        return isset($this->connectors[$carrier]);
    }

    /** @return list<string> */
    public function carriers(): array
    {
        return array_keys($this->connectors);
    }

    public function for(string $carrier): CarrierConnector
    {
        if (! $this->has($carrier)) {
            throw new InvalidArgumentException("No carrier connector registered for [{$carrier}].");
        }

        return $this->container->make($this->connectors[$carrier]);
    }
}
