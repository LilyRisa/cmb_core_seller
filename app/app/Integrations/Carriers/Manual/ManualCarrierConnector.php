<?php

namespace CMBcoreSeller\Integrations\Carriers\Manual;

use CMBcoreSeller\Integrations\Carriers\Support\AbstractCarrierConnector;
use Illuminate\Support\Str;

/**
 * The built-in "manual" carrier: the seller manages tracking themselves (drops off at
 * the post office, uses a carrier we don't integrate yet, etc.). createShipment just
 * records whatever tracking number the user typed — or generates a placeholder. No label,
 * no live tracking. Always available; needs no credentials. See SPEC 0006 §3.
 */
class ManualCarrierConnector extends AbstractCarrierConnector
{
    public function code(): string
    {
        return 'manual';
    }

    public function displayName(): string
    {
        return 'Tự vận chuyển / ĐVVC khác';
    }

    public function capabilities(): array
    {
        return ['createShipment'];
    }

    public function createShipment(array $account, array $shipment): array
    {
        $tracking = trim((string) ($shipment['tracking_no'] ?? '')) ?: 'MAN-'.strtoupper((string) Str::ulid());

        return [
            'tracking_no' => $tracking,
            'carrier' => 'manual',
            'status' => 'created',
            'fee' => (int) ($shipment['fee'] ?? 0),
            'raw' => ['manual' => true],
        ];
    }
}
