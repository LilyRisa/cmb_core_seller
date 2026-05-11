<?php

namespace CMBcoreSeller\Modules\Channels\Support;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountNeedsReconnect;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Refreshes a channel account's OAuth token via its connector. On success the
 * encrypted token + expiries are updated and status reset to active. On failure
 * the account is marked `expired` (sync stops; the user must reconnect) and
 * ChannelAccountNeedsReconnect is fired. See docs/03-domain/order-sync-pipeline.md §4 rule 6.
 */
class TokenRefresher
{
    public function __construct(private ChannelRegistry $registry) {}

    public function refresh(ChannelAccount $account): bool
    {
        if (! $account->refresh_token || ! $this->registry->has($account->provider)) {
            $this->markExpired($account, 'no refresh token');

            return false;
        }

        try {
            $token = $this->registry->for($account->provider)->refreshToken((string) $account->refresh_token);
        } catch (Throwable $e) {
            Log::warning('channel.token_refresh_failed', ['account' => $account->getKey(), 'provider' => $account->provider, 'error' => class_basename($e)]);
            $this->markExpired($account, 'token refresh failed: '.class_basename($e));

            return false;
        }

        $meta = $account->meta ?? [];
        if ($token->scope) {
            $meta['scope'] = $token->scope;
        }
        if (isset($token->raw['open_id'])) {
            $meta['open_id'] = $token->raw['open_id'];
        }

        $account->forceFill([
            'access_token' => $token->accessToken,
            'refresh_token' => $token->refreshToken ?: $account->refresh_token,
            'token_expires_at' => $token->expiresAt,
            'refresh_token_expires_at' => $token->refreshExpiresAt ?: $account->refresh_token_expires_at,
            'status' => ChannelAccount::STATUS_ACTIVE,
            'meta' => $meta ?: null,
        ])->save();

        return true;
    }

    private function markExpired(ChannelAccount $account, string $reason): void
    {
        if ($account->status !== ChannelAccount::STATUS_EXPIRED) {
            $account->forceFill(['status' => ChannelAccount::STATUS_EXPIRED])->save();
            ChannelAccountNeedsReconnect::dispatch($account, $reason);
        }
    }
}
