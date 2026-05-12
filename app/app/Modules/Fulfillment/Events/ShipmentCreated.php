<?php

namespace CMBcoreSeller\Modules\Fulfillment\Events;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired when a shipment (parcel) has been created for an order via a carrier. */
class ShipmentCreated
{
    use Dispatchable;

    public function __construct(public readonly Shipment $shipment) {}
}
