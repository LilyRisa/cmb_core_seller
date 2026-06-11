<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Exceptions;

use RuntimeException;

/**
 * Provider-agnostic exception for marketplace product-publishing failures.
 *
 * Carries the provider code, an arbitrary context bag (validation errors or the
 * raw API envelope) and a `retryable` hint so jobs can back off and retry on
 * transient errors (token expiry, rate-limit, transient service failures) versus
 * surfacing a hard failure on validation / permanent errors.
 */
final class MarketplaceApiException extends RuntimeException
{
    /** @param array<string,mixed> $context */
    public function __construct(
        string $message,
        public readonly string $provider = '',
        public readonly array $context = [],
        public readonly bool $retryable = false,
    ) {
        parent::__construct($message);
    }

    /** @param array<string,string> $errors */
    public static function validation(string $provider, array $errors): self
    {
        return new self('Listing validation failed', $provider, ['errors' => $errors], false);
    }

    /** @param array<string,mixed> $resp */
    public static function fromLazada(array $resp): self
    {
        $code = (string) ($resp['code'] ?? '');
        $retry = in_array($code, ['IllegalAccessToken', 'ApiCallLimit', 'ServiceUnavailable', 'SystemError'], true);

        return new self((string) ($resp['message'] ?? ($code !== '' ? $code : 'Lazada API error')), 'lazada', ['response' => $resp], $retry);
    }

    /** @param array<string,mixed> $resp */
    public static function fromTikTok(array $resp): self
    {
        $code = (int) ($resp['code'] ?? -1);
        // retry only on transport/throttle/system errors, not business validation
        $retry = in_array($code, [11000000 /* system */, 11001000 /* rate limit */], true);

        return new self((string) ($resp['message'] ?? 'TikTok API error'), 'tiktok', ['response' => $resp], $retry);
    }

    /** @param array<string,mixed> $resp */
    public static function fromShopee(array $resp): self
    {
        $err = (string) ($resp['error'] ?? '');
        $retry = in_array($err, ['error_auth', 'error_server', 'error_busy'], true);

        return new self((string) ($resp['message'] ?? ($err !== '' ? $err : 'Shopee API error')), 'shopee', ['response' => $resp], $retry);
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
