<?php

namespace CMBcoreSeller\Integrations\Messaging\Exceptions;

use RuntimeException;

/**
 * Ném khi connector không hỗ trợ 1 operation. Core kiểm `supports($cap)` trước.
 * Pattern mirror Channels/Carriers/Payments (ADR-0004).
 */
class UnsupportedOperation extends RuntimeException
{
    public static function for(string $provider, string $operation): self
    {
        return new self("Messaging connector [{$provider}] does not support operation [{$operation}].");
    }
}
