<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OAuth connect cho Facebook Page (SPEC-0024 ADR-0019). Facebook là
 * MessagingConnector (không phải ChannelConnector) ⇒ flow OAuth riêng, KHÔNG
 * dùng `OAuthCallbackController` của Channels. Tái dùng bảng `oauth_states` cho
 * CSRF state → tenant (callback không có session).
 *
 * Flow:
 *   start (auth)   → issue state + build authorize URL (Meta dialog).
 *   callback (web) → verify state → exchange code → /me/accounts → upsert
 *                    channel_account per page (provider=facebook_page,
 *                    messaging_enabled=true) → subscribe webhook → redirect SPA.
 */
class FacebookOAuthController extends Controller
{
    private const PROVIDER = 'facebook_page';

    public function __construct(private MessagingRegistry $registry) {}

    /** [auth] Khởi tạo OAuth: trả authorize URL cho FE redirect sang Meta. */
    public function start(Request $request): JsonResponse
    {
        Gate::authorize('messaging.ai.config');

        $tenantId = app(CurrentTenant::class)->id();
        $state = OAuthState::issue(self::PROVIDER, (int) $tenantId, $request->user()?->id, '/messaging?connected=facebook_page');

        // KHÔNG truyền redirect_uri — connector dùng URI canonical (config/APP_URL)
        // cho cả dialog login lẫn đổi token, đảm bảo Meta thấy 2 URI giống hệt.
        $connector = $this->registry->for(self::PROVIDER);
        $url = $connector->buildAuthorizationUrl($state->state);

        return response()->json(['data' => ['authorize_url' => $url]]);
    }

    /** [web, no auth] Callback từ Meta sau khi user authorize. */
    public function callback(Request $request): RedirectResponse
    {
        $code = (string) $request->query('code', '');
        $stateToken = (string) $request->query('state', '');

        if ($request->query('error') || $code === '' || $stateToken === '') {
            return redirect('/messaging?error=facebook_oauth_'.preg_replace('/[^a-z0-9_]/i', '_', strtolower((string) $request->query('error', 'missing_params'))));
        }

        $state = OAuthState::query()->where('state', $stateToken)->where('provider', self::PROVIDER)->first();
        if (! $state || $state->isExpired()) {
            return redirect('/messaging?error=facebook_oauth_state');
        }

        try {
            $connector = $this->registry->for(self::PROVIDER);
            $userToken = $connector->exchangeCodeForToken($code)->accessToken;

            $pages = $this->fetchPages($userToken);
            if ($pages === []) {
                return redirect('/messaging?error=facebook_no_pages');
            }

            $connected = 0;
            foreach ($pages as $page) {
                $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->updateOrCreate(
                    ['tenant_id' => $state->tenant_id, 'provider' => self::PROVIDER, 'external_shop_id' => (string) $page['id']],
                    [
                        'shop_name' => $page['name'] ?? null,
                        'access_token' => $page['access_token'] ?? null,
                        'status' => ChannelAccount::STATUS_ACTIVE,
                        'messaging_enabled' => true,
                        'created_by' => $state->created_by,
                    ],
                );

                // Subscribe page vào app (best-effort).
                try {
                    $connector->registerWebhooks(new MessagingAuthContext(
                        channelAccountId: (int) $account->getKey(),
                        provider: self::PROVIDER,
                        externalShopId: (string) $page['id'],
                        accessToken: (string) ($page['access_token'] ?? ''),
                    ));
                } catch (\Throwable $e) {
                    Log::warning('messaging.facebook.subscribe_failed', ['page' => $page['id'], 'error' => $e->getMessage()]);
                }
                $connected++;
            }

            $state->delete(); // one-time use
            AuditLog::record('messaging.facebook.connected', null, ['pages' => $connected]);
        } catch (\Throwable $e) {
            Log::warning('messaging.facebook.oauth_failed', ['error' => $e->getMessage()]);

            return redirect('/messaging?error=facebook_oauth_failed');
        }

        return redirect($state->redirect_after ?: '/messaging?connected=facebook_page');
    }

    /**
     * GET /me/accounts — list pages user quản + page access token.
     *
     * @return list<array{id:string, name?:string, access_token?:string}>
     */
    private function fetchPages(string $userToken): array
    {
        $version = (string) config('integrations.messaging_facebook_page.graph_version', 'v19.0');
        $res = Http::timeout(20)->get("https://graph.facebook.com/{$version}/me/accounts", [
            'access_token' => $userToken,
            'fields' => 'id,name,access_token',
        ]);

        if (! $res->successful()) {
            return [];
        }

        return array_values((array) $res->json('data', []));
    }
}
