<?php

namespace CMBcoreSeller\Integrations\EInvoice\Exceptions;

use RuntimeException;

/** Nhà cung cấp HĐĐT không hỗ trợ thao tác này. Mirror pattern Payments/Channels. */
class UnsupportedOperation extends RuntimeException
{
    public static function for(string $provider, string $operation): self
    {
        return new self("Nhà cung cấp HĐĐT `{$provider}` không hỗ trợ thao tác `{$operation}`.");
    }
}
