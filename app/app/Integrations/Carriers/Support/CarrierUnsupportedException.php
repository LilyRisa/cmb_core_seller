<?php

namespace CMBcoreSeller\Integrations\Carriers\Support;

use RuntimeException;

/** Thrown when a carrier connector is asked for an operation it doesn't support. */
class CarrierUnsupportedException extends RuntimeException
{
    public function __construct(string $carrier, string $op)
    {
        parent::__construct("Đơn vị vận chuyển [{$carrier}] không hỗ trợ thao tác [{$op}].");
    }
}
