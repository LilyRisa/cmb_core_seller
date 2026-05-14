<?php

namespace CMBcoreSeller\Integrations\Payments\Exceptions;

use RuntimeException;

/**
 * Cổng thanh toán không hỗ trợ thao tác này. Mirror pattern của ChannelConnector.
 */
class UnsupportedOperation extends RuntimeException
{
    public static function for(string $gateway, string $operation): self
    {
        return new self("Cổng `{$gateway}` không hỗ trợ thao tác `{$operation}`.");
    }
}
