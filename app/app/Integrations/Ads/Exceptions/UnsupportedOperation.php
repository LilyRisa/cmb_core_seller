<?php

namespace CMBcoreSeller\Integrations\Ads\Exceptions;

use RuntimeException;

class UnsupportedOperation extends RuntimeException
{
    public static function for(string $provider, string $operation): self
    {
        return new self("Ads connector [{$provider}] does not support operation [{$operation}].");
    }
}
