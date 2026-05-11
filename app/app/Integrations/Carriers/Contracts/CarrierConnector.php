<?php

namespace CMBcoreSeller\Integrations\Carriers\Contracts;

use Symfony\Component\HttpFoundation\Request;

/**
 * Contract every shipping-carrier integration must implement (GHN, GHTK,
 * J&T Express, Viettel Post, ...). Used for self-fulfilled orders (manual
 * orders, or marketplace orders the seller ships themselves).
 *
 * Same golden rule as ChannelConnector: core never knows the carrier name.
 * Add a carrier = a new class + one line in CarrierRegistry +
 * config/integrations.php. See docs/03-domain/fulfillment-and-printing.md.
 *
 * The first array argument represents a CarrierAccount (tenant credentials);
 * a typed model will replace `array` once the Fulfillment module lands.
 */
interface CarrierConnector
{
    /** Stable carrier code, e.g. 'ghn' | 'ghtk' | 'jt' | 'viettelpost'. */
    public function code(): string;

    public function displayName(): string;

    /**
     * @param  array<string, mixed>  $account
     * @return list<array<string, mixed>>  Available services + coverage.
     */
    public function services(array $account): array;

    /**
     * @param  array<string, mixed>  $account
     * @param  array<string, mixed>  $request
     * @return list<array<string, mixed>>  Quotes: fee + estimated delivery time.
     */
    public function quote(array $account, array $request): array;

    /**
     * @param  array<string, mixed>  $account
     * @param  array<string, mixed>  $shipment
     * @return array<string, mixed>  At least: tracking_no, carrier, status.
     */
    public function createShipment(array $account, array $shipment): array;

    /**
     * @param  array<string, mixed>  $account
     * @return array{filename:string,mime:string,bytes:string}
     */
    public function getLabel(array $account, string $trackingNo, string $format = 'A6'): array;

    /**
     * @param  array<string, mixed>  $account
     * @return array<string, mixed>  Tracking status + events.
     */
    public function getTracking(array $account, string $trackingNo): array;

    /**
     * @param  array<string, mixed>  $account
     */
    public function cancel(array $account, string $trackingNo): void;

    /** Parse a carrier webhook (tracking update), if the carrier has one. */
    public function parseWebhook(Request $request): array;
}
