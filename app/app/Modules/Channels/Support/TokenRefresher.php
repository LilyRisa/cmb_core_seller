<?php

namespace CMBcoreSeller\Modules\Channels\Support;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountNeedsReconnect;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Refreshes a channel account's OAuth token via its connector. On success the
 * encrypted token + expiries are updated and status reset to active.
 *
 * On failure we are deliberately conservative: the account is only marked `expired`
 * (sync stops; user must reconnect) when the failure is a *genuine* auth rejection
 * or the refresh window itself has elapsed. A transient failure (network blip, 5xx,
 * rate-limit, sign error, or a lost refresh-token-rotation race) leaves the account
 * `active` — the current access token is usually still valid (Shopee: 4h) so sync
 * keeps working and the next scheduled run retries. Expiring on the first hiccup is
 * what made Shopee shops "drop after a few hours" even with a 30-day refresh token.
 *
 * Shopee (and others) **rotate the refresh token on every refresh**, so concurrent
 * refreshes on the same stored token make the loser fail with a bogus auth error.
 * We serialize per-account with an atomic lock and re-read the latest token before
 * calling out. See docs/03-domain/order-sync-pipeline.md §4 rule 6.
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

        // Serialize refreshes per account: a sibling job (scheduled refresh + the inline refresh in
        // SyncOrders/Returns/Listings/Webhook jobs) must not race on the same rotating refresh token.
        $lock = Cache::lock('channel-token-refresh:'.$account->getKey(), 30);
        if (! $lock->get()) {
            $account->refresh(); // another refresh is in-flight; report success only if it already landed a fresh token

            return $account->status === ChannelAccount::STATUS_ACTIVE && ! $account->tokenExpiresWithin(60);
        }

        try {
            $account->refresh(); // re-read in case a sibling just rotated the refresh token
            $token = $this->registry->for($account->provider)->refreshToken((string) $account->refresh_token, ['shop_id' => $account->external_shop_id]);
        } catch (Throwable $e) {
            return $this->handleFailure($account, $e);
        } finally {
            $lock->release();
        }

        $meta = $account->meta ?? [];
        if ($token->scope) {                       // ?string (TikTokMappers joins TikTok's granted_scopes list)
            $meta['scope'] = $token->scope;
        }
        if (! empty($token->raw['open_id'])) {
            $meta['open_id'] = (string) $token->raw['open_id'];
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

    /**
     * Decide what a failed refresh means. Only a *genuine* auth rejection (connector reports
     * isAuthError — invalid/revoked refresh token) or an elapsed refresh window forces `expired`
     * + a reconnect prompt. Everything else is transient: keep the account active and let the
     * next scheduled refresh retry while the current access token (still valid) keeps sync alive.
     */
    private function handleFailure(ChannelAccount $account, Throwable $e): bool
    {
        // We hold the per-account lock here, so this is a real failure for the current token (not a
        // lost rotation race — the racing caller bailed at lock acquisition). Classify it.
        $refreshWindowOver = $account->refresh_token_expires_at !== null && $account->refresh_token_expires_at->isPast();
        $rejected = method_exists($e, 'isAuthError') && $e->isAuthError();
        $permanent = $refreshWindowOver || $rejected;

        Log::warning('channel.token_refresh_failed', [
            'account' => $account->getKey(),
            'provider' => $account->provider,
            'error' => class_basename($e),
            'permanent' => $permanent,
        ]);

        if ($permanent) {
            $this->markExpired($account, 'token refresh rejected: '.class_basename($e));
        }

        return false;
    }

    private function markExpired(ChannelAccount $account, string $reason): void
    {
        if ($account->status !== ChannelAccount::STATUS_EXPIRED) {
            $account->forceFill(['status' => ChannelAccount::STATUS_EXPIRED])->save();
            ChannelAccountNeedsReconnect::dispatch($account, $reason);
        }
    }
}
