<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use RuntimeException;

/**
 * Raised when TikTok returns a non-zero `code` (or a non-2xx HTTP status).
 * Carries the TikTok error code in {@see getCode()} and the HTTP status in
 * {@see $httpStatus} so jobs/connector can decide whether to refresh the token,
 * back off on 429, or surface a hard failure.
 */
class TikTokApiException extends RuntimeException
{
    public function __construct(string $message, int $tiktokCode = 0, public readonly int $httpStatus = 0)
    {
        parent::__construct($message, $tiktokCode);
    }

    /** TikTok auth-related error codes (token invalid/expired) — see Partner API docs; refine when wiring real sandbox. */
    public function isAuthError(): bool
    {
        return $this->httpStatus === 401 || in_array($this->getCode(), [105000, 105001, 105002, 36004003, 36004004], true)
            || str_contains(strtolower($this->getMessage()), 'access_token');
    }

    /** TikTok 105005: "Access denied — the app's granted scopes don't include the required scope for this endpoint." */
    public function isScopeDenied(): bool
    {
        return $this->getCode() === 105005
            || str_contains(strtolower($this->getMessage()), 'access scope');
    }

    public function isRateLimited(): bool
    {
        return $this->httpStatus === 429;
    }
}
