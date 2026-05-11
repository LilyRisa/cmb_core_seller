<?php

namespace CMBcoreSeller\Integrations\Channels\Exceptions;

use RuntimeException;

/**
 * Thrown by a ChannelConnector when the underlying marketplace does not
 * support a given operation. Core code should check capabilities() first.
 */
class UnsupportedOperation extends RuntimeException
{
    public static function for(string $provider, string $operation): self
    {
        return new self("Channel [{$provider}] does not support operation [{$operation}].");
    }
}
