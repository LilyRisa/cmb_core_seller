<?php

namespace CMBcoreSeller\Modules\Channels\Services;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountConnected;
use CMBcoreSeller\Modules\Channels\Events\ChannelAccountRevoked;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * OAuth connect/disconnect for marketplace shops. One flow for every provider —
 * the differences (auth URL, token exchange, shop info) are in the connector.
 * See docs/05-api/webhooks-and-oauth.md §2, SPEC 0001 §3.
 */
class ChannelConnectionService
{
    public function __construct(private ChannelRegistry $registry) {}

    public function assertProviderConnectable(string $provider): void
    {
        if (! $this->registry->has($provider)) {
            throw new RuntimeException("Provider [{$provider}] is not enabled.");
        }
        if (! $this->registry->for($provider)->supports('orders.fetch')) {
            throw new RuntimeException("Provider [{$provider}] does not support shop connection yet.");
        }
    }

    /** Begin OAuth: create a CSRF state and return the marketplace authorization URL. */
    public function startConnect(string $provider, int $tenantId, ?int $userId, ?string $redirectAfter = null): string
    {
        $this->assertProviderConnectable($provider);
        $state = OAuthState::issue($provider, $tenantId, $userId, $redirectAfter);
        $redirectUri = route('oauth.callback', ['provider' => $provider]);

        return $this->registry->for($provider)->buildAuthorizationUrl($state->state, ['redirect_uri' => $redirectUri]);
    }

    /**
     * Finish OAuth: exchange code -> token, fetch shop info, upsert the channel
     * account, subscribe webhooks (best effort), kick off a 90-day backfill.
     *
     * @return array{account: ChannelAccount, redirect: string, created: bool}
     */
    public function completeConnect(string $provider, string $code, string $stateValue): array
    {
        $state = OAuthState::query()->where('state', $stateValue)->first();
        if (! $state || $state->provider !== $provider) {
            throw new RuntimeException('OAUTH_STATE_INVALID');
        }
        if ($state->isExpired()) {
            $state->delete();
            throw new RuntimeException('OAUTH_STATE_EXPIRED');
        }
        $this->assertProviderConnectable($provider);
        $connector = $this->registry->for($provider);

        $token = $connector->exchangeCodeForToken($code);
        $shop = $connector->fetchShopInfo(new AuthContext(
            channelAccountId: 0, provider: $provider, externalShopId: '', accessToken: $token->accessToken,
        ));

        $shopCipher = $shop->raw['cipher'] ?? ($shop->raw['shop_cipher'] ?? null);
        $tenantId = (int) $state->tenant_id;

        // A shop may only be connected to one tenant.
        $existingElsewhere = ChannelAccount::withoutGlobalScope(TenantScope::class)
            ->where('provider', $provider)->where('external_shop_id', $shop->externalShopId)
            ->where('tenant_id', '!=', $tenantId)->whereNull('deleted_at')->exists();
        if ($existingElsewhere) {
            throw new RuntimeException('SHOP_ALREADY_CONNECTED_ELSEWHERE');
        }

        [$account, $created] = DB::transaction(function () use ($provider, $tenantId, $shop, $token, $shopCipher, $state) {
            /** @var ChannelAccount $account */
            $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->withTrashed()->firstOrNew([
                'tenant_id' => $tenantId, 'provider' => $provider, 'external_shop_id' => $shop->externalShopId,
            ]);
            $created = ! $account->exists;
            $meta = $account->meta ?? [];
            if ($shopCipher) {
                $meta['shop_cipher'] = (string) $shopCipher;
            }
            if (! empty($token->raw['open_id'])) {
                $meta['open_id'] = (string) $token->raw['open_id'];
            }
            if ($token->scope) {                       // ?string after TikTokMappers normalises granted_scopes
                $meta['scope'] = $token->scope;
            }
            $sellerType = $shop->sellerType ?: ($token->raw['user_type'] ?? null) ?: $account->seller_type;
            $account->forceFill([
                'shop_name' => $shop->name ?: $account->shop_name,
                'shop_region' => $shop->region ?: ($account->shop_region ?: 'VN'),
                'seller_type' => ($sellerType !== null && $sellerType !== '') ? (string) $sellerType : null,
                'status' => ChannelAccount::STATUS_ACTIVE,
                'access_token' => $token->accessToken,
                'refresh_token' => $token->refreshToken,
                'token_expires_at' => $token->expiresAt,
                'refresh_token_expires_at' => $token->refreshExpiresAt,
                'meta' => $meta ?: null,
                'created_by' => $account->created_by ?: $state->created_by,
                'deleted_at' => null,
            ])->save();
            $state->delete();

            return [$account->refresh(), $created];
        });

        // Best-effort webhook subscription, then a backfill.
        try {
            $connector->registerWebhooks($account->authContext());
        } catch (\Throwable $e) {
            Log::info('channel.register_webhooks_failed', ['provider' => $provider, 'shop' => $account->external_shop_id, 'error' => class_basename($e)]);
        }
        SyncOrdersForShop::dispatch((int) $account->getKey(), null, SyncRun::TYPE_BACKFILL);

        ChannelAccountConnected::dispatch($account, $created);

        return [
            'account' => $account,
            'redirect' => ($state->redirect_after ?: '/channels').'?connected='.$provider,
            'created' => $created,
        ];
    }

    public function disconnect(ChannelAccount $account): void
    {
        if ($this->registry->has($account->provider)) {
            try {
                $this->registry->for($account->provider)->revoke($account->authContext());
            } catch (\Throwable $e) {
                Log::info('channel.revoke_failed', ['provider' => $account->provider, 'error' => class_basename($e)]);
            }
        }
        $account->forceFill(['status' => ChannelAccount::STATUS_REVOKED])->save();
        ChannelAccountRevoked::dispatch($account, 'disconnected by user');
        // Order history is intentionally kept. (Buyer-data anonymization on disconnect: Phase 7.)
    }
}
