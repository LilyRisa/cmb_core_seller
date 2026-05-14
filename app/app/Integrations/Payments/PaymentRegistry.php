<?php

namespace CMBcoreSeller\Integrations\Payments;

use CMBcoreSeller\Integrations\Payments\Contracts\PaymentGatewayConnector;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Tập các cổng thanh toán đã đăng ký, key theo gateway code.
 *
 * Đăng ký trong `IntegrationsServiceProvider`, gated bởi `config('integrations.payments.enabled')`.
 * Resolve bằng `for($gateway)`. Module Billing PHẢI đi qua registry — không `new SePayConnector()`
 * trực tiếp (SPEC 0018 + docs/01-architecture/extensibility-rules.md §4).
 */
class PaymentRegistry
{
    /** @var array<string, class-string<PaymentGatewayConnector>> */
    protected array $connectors = [];

    public function __construct(protected Container $container) {}

    /**
     * @param  class-string<PaymentGatewayConnector>  $connectorClass
     */
    public function register(string $gateway, string $connectorClass): void
    {
        $this->connectors[$gateway] = $connectorClass;
    }

    public function has(string $gateway): bool
    {
        return isset($this->connectors[$gateway]);
    }

    /** @return list<string> */
    public function gateways(): array
    {
        return array_keys($this->connectors);
    }

    public function for(string $gateway): PaymentGatewayConnector
    {
        if (! $this->has($gateway)) {
            throw new InvalidArgumentException("No payment gateway connector registered for [{$gateway}].");
        }

        return $this->container->make($this->connectors[$gateway]);
    }
}
