<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Integrations\Messaging\Zalo\ZaloOaConnector;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OAuth connect cho Zalo OA (Task 11). Zalo OA là MessagingConnector (không phải
 * ChannelConnector) ⇒ flow OAuth riêng như {@see FacebookOAuthController} /
 * {@see LazadaImOAuthController}. Tái dùng bảng `oauth_states` cho CSRF state →
 * tenant (callback không có session).
 *
 * Flow:
 *   start (auth)   → issue state + build authorize URL (Zalo permission dialog).
 *   callback (web) → verify state → exchange code → getoa (oa_id + tên/avatar) →
 *                    upsert channel_account (provider=zalo_oa, messaging_enabled=true)
 *                    → backfill → redirect SPA.
 */
class ZaloOaOAuthController extends Controller
{
    private const PROVIDER = 'zalo_oa';

    public function __construct(private MessagingRegistry $registry) {}

    /** [auth] Khởi tạo OAuth: trả authorize URL cho FE redirect sang Zalo. */
    public function start(Request $request): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $tenantId = app(CurrentTenant::class)->id();
        $state = OAuthState::issue(self::PROVIDER, (int) $tenantId, $request->user()?->id, '/messaging/channels?connected=zalo_oa');

        // PKCE (luồng OAuth Zalo hiện tại): sinh code_verifier, lưu theo state (TTL = state),
        // gửi code_challenge ở authorize; callback đọc lại verifier để đổi token.
        $verifier = Str::random(64);
        Cache::put($this->pkceKey($state->state), $verifier, now()->addMinutes(10));

        $connector = $this->registry->for(self::PROVIDER);
        $challenge = $connector instanceof ZaloOaConnector ? ZaloOaConnector::pkceChallenge($verifier) : '';
        $url = $connector->buildAuthorizationUrl($state->state, ['code_challenge' => $challenge]);

        return response()->json(['data' => ['authorize_url' => $url]]);
    }

    private function pkceKey(string $state): string
    {
        return 'zalo_oa_pkce:'.$state;
    }

    /** [web, no auth] Callback từ Zalo sau khi seller authorize OA. */
    public function callback(Request $request): Response
    {
        $code = (string) $request->query('code', '');
        $stateToken = (string) $request->query('state', '');

        if ($request->query('error') || $code === '' || $stateToken === '') {
            return $this->finish('/messaging/channels?error=zalo_oa_oauth_'.preg_replace('/[^a-z0-9_]/i', '_', strtolower((string) $request->query('error', 'missing_params'))));
        }

        $state = OAuthState::query()->where('state', $stateToken)->where('provider', self::PROVIDER)->first();
        if (! $state || $state->isExpired()) {
            return $this->finish('/messaging/channels?error=zalo_oa_oauth_state');
        }

        $connector = $this->registry->for(self::PROVIDER);
        if (! $connector instanceof ZaloOaConnector) {
            return $this->finish('/messaging/channels?error=zalo_oa_unavailable');
        }

        try {
            // PKCE: lấy code_verifier đã lưu lúc start (pull = dùng 1 lần).
            $verifier = (string) Cache::pull($this->pkceKey($stateToken));
            $token = $connector->exchangeCodeForTokenPkce($code, $verifier);

            $auth = new MessagingAuthContext(
                channelAccountId: 0,
                provider: self::PROVIDER,
                externalShopId: '',
                accessToken: $token->accessToken,
            );
            $profile = $connector->fetchPageProfile($auth);   // {name, avatar_url}
            // Callback Zalo trả oa_id ⇒ ưu tiên dùng, đỡ 1 call getoa; fallback fetchOaId.
            $oaId = (string) ($request->query('oa_id') ?: $connector->fetchOaId($auth));

            if ($oaId === '') {
                return $this->finish('/messaging/channels?error=zalo_oa_no_oa_id');
            }

            // withTrashed + restore: reconnect sau khi đã ngắt (soft-delete) phải KHÔI PHỤC hàng cũ.
            $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->withTrashed()->firstOrNew([
                'tenant_id' => $state->tenant_id, 'provider' => self::PROVIDER, 'external_shop_id' => $oaId,
            ]);
            $account->forceFill([
                'tenant_id' => $state->tenant_id,
                'shop_name' => $profile['name'] ?? 'Zalo OA',
                'access_token' => $token->accessToken,
                'refresh_token' => $token->refreshToken,
                'token_expires_at' => $token->expiresAt,
                'status' => ChannelAccount::STATUS_ACTIVE,
                'messaging_enabled' => true,
                'created_by' => $account->created_by ?? $state->created_by,
                'deleted_at' => null,
            ])->save();

            MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->updateOrCreate(
                ['channel_account_id' => (int) $account->getKey()],
                ['tenant_id' => (int) $account->tenant_id, 'messaging_enabled' => true,
                    'sync_status' => MessagingAccountMeta::SYNC_QUEUED],
            );

            BackfillMessagingChannel::dispatch((int) $account->getKey());

            $state->delete(); // one-time use
            AuditLog::record('messaging.zalo_oa.connected', $account, ['oa' => $oaId]);
        } catch (\Throwable $e) {
            Log::warning('messaging.zalo_oa.oauth_failed', ['error' => $e->getMessage()]);

            return $this->finish('/messaging/channels?error=zalo_oa_oauth_failed');
        }

        return $this->finish($state->redirect_after ?: '/messaging/channels?connected=zalo_oa');
    }

    /** Trả view popup-friendly: popup → postMessage + close; không popup → redirect SPA. */
    private function finish(string $redirect): Response
    {
        return response()->view('oauth-callback', ['redirect' => $redirect]);
    }
}
