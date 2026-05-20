<?php

namespace CMBcoreSeller\Integrations\Ai\Exceptions;

use RuntimeException;

/**
 * Provider không hỗ trợ 1 capability (vd `local_llm` không có embedding).
 * Core kiểm `supports($cap)` trước khi gọi.
 */
class UnsupportedOperation extends RuntimeException
{
    public static function for(string $providerCode, string $operation): self
    {
        return new self("AI provider [{$providerCode}] does not support operation [{$operation}].");
    }
}
