<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use RuntimeException;

/**
 * Raised when Shopee returns a non-empty `error` field or a non-2xx HTTP status.
 * Carries the Shopee error string (e.g. error_auth/error_sign/error_param) + HTTP status.
 */
class ShopeeApiException extends RuntimeException
{
    public function __construct(string $message, public readonly string $shopeeError = '', public readonly int $httpStatus = 0)
    {
        parent::__construct($message);
    }

    public function isAuthError(): bool
    {
        return $this->httpStatus === 401
            || in_array($this->shopeeError, ['error_auth', 'error_token', 'invalid_access_token'], true)
            || str_contains(strtolower($this->getMessage()), 'access_token')
            || str_contains(strtolower($this->getMessage()), 'invalid token');
    }

    public function isRateLimited(): bool
    {
        return $this->httpStatus === 429 || in_array($this->shopeeError, ['error_busy', 'error_rate_limit'], true);
    }
}
