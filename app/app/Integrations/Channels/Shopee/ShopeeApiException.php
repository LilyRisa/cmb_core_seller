<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use RuntimeException;

/**
 * Raised when Shopee returns a non-empty `error` field or a non-2xx HTTP status.
 * Carries the Shopee error string (e.g. error_auth/error_sign/error_param) + HTTP status.
 */
class ShopeeApiException extends RuntimeException
{
    /** @param array<string,mixed>|null $response raw envelope (giữ result_list để bóc lý do batch error) */
    public function __construct(string $message, public readonly string $shopeeError = '', public readonly int $httpStatus = 0, public readonly ?array $response = null)
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

    /** App/shop lacks permission for this API (e.g. seller chat not granted to this app type). */
    public function isPermissionError(): bool
    {
        return $this->httpStatus === 403
            || in_array($this->shopeeError, ['error_api_permission', 'error_permission', 'no_permission'], true);
    }
}
