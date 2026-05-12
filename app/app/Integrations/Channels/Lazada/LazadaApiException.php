<?php

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use RuntimeException;

/**
 * Raised when Lazada returns a non-"0" `code` (or a non-2xx HTTP status).
 * Carries the Lazada error code string in {@see $lazadaCode} and the HTTP status
 * in {@see $httpStatus} so jobs/connector can decide whether to refresh the token,
 * back off, or surface a hard failure. See docs/04-channels/lazada.md.
 */
class LazadaApiException extends RuntimeException
{
    public function __construct(string $message, public readonly string $lazadaCode = '', public readonly int $httpStatus = 0)
    {
        parent::__construct($message);
    }

    /** Lazada token-related errors (IllegalAccessToken / InvalidToken / token expired). */
    public function isAuthError(): bool
    {
        return $this->httpStatus === 401
            || in_array($this->lazadaCode, ['IllegalAccessToken', 'InvalidToken', 'InvalidAccessToken', 'AccessTokenExpired', 'MissingAccessToken'], true)
            || str_contains(strtolower($this->getMessage()), 'access_token')
            || str_contains(strtolower($this->getMessage()), 'access token');
    }

    public function isRateLimited(): bool
    {
        return $this->httpStatus === 429
            || in_array($this->lazadaCode, ['ApiCallLimit', 'RateLimitExceeded', 'SystemBusy'], true);
    }
}
