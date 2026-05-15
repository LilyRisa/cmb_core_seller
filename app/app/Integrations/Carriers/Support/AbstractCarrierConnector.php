<?php

namespace CMBcoreSeller\Integrations\Carriers\Support;

use CMBcoreSeller\Integrations\Carriers\Contracts\CarrierConnector;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sensible "not supported" defaults so a connector only implements what the carrier
 * actually offers. ManualCarrierConnector overrides the bits it can do; GhnConnector
 * overrides the real API calls. See app/Integrations/Carriers/Contracts/CarrierConnector.php.
 */
abstract class AbstractCarrierConnector implements CarrierConnector
{
    public function services(array $account): array
    {
        return [];
    }

    public function quote(array $account, array $request): array
    {
        return [];
    }

    public function getLabel(array $account, string $trackingNo, string $format = 'A6'): array
    {
        throw new CarrierUnsupportedException($this->code(), 'getLabel');
    }

    public function getTracking(array $account, string $trackingNo): array
    {
        return ['status' => null, 'events' => []];
    }

    public function cancel(array $account, string $trackingNo): void
    {
        // no-op by default
    }

    public function parseWebhook(Request $request): array
    {
        return [];
    }

    /**
     * A2 default — connector không có cách kiểm thì coi credentials hợp lệ. GhnConnector override để
     * gọi API thật. ManualCarrierConnector cũng dùng default (manual không có credentials).
     */
    public function verifyCredentials(array $account): array
    {
        return ['ok' => true, 'message' => 'ĐVVC này không cần kiểm tra credentials.', 'expires_at' => null];
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    /** @return list<string> e.g. ['createShipment', 'getLabel', 'getTracking', 'cancel', 'quote'] */
    abstract public function capabilities(): array;
}
