<?php

namespace CMBcoreSeller\Integrations\Payments\Exceptions;

use RuntimeException;

/**
 * Cổng thanh toán thiếu credentials (env chưa set). Caller bắt và trả 422 GATEWAY_UNAVAILABLE.
 */
class GatewayNotConfigured extends RuntimeException
{
    public static function for(string $gateway, string $missing): self
    {
        return new self("Cổng `{$gateway}` chưa được cấu hình — thiếu `{$missing}`.");
    }
}
