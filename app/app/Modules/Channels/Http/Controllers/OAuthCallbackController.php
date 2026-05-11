<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokApiException;
use CMBcoreSeller\Modules\Channels\Services\ChannelConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GET /oauth/{provider}/callback — the marketplace redirects the seller here after
 * authorization (TikTok: ?app_key=&code=&state=&shop_region=…). Exchange the code,
 * create/update the channel account, then redirect into the SPA (/channels?connected=…
 * on success, ?error=… on failure). The tenant is resolved from the oauth_states row
 * by `state`, not from the session. See docs/05-api/webhooks-and-oauth.md §2.
 *
 * IMPORTANT: the redirect URL registered in the marketplace partner console must be
 * exactly  https://<APP_URL host>/oauth/<provider>/callback  (e.g. https://app.cmbcore.com/oauth/tiktok/callback).
 * If a 404 lands on this path in prod, check that the `web`/`app` containers are healthy
 * and `php artisan route:list --path=oauth` shows this route.
 */
class OAuthCallbackController extends Controller
{
    public function __invoke(Request $request, string $provider, ChannelConnectionService $service): RedirectResponse
    {
        $code = (string) $request->query('code', '');
        $state = (string) ($request->query('state', '') ?: $request->query('app_key_state', ''));

        Log::info('oauth.callback_received', [
            'provider' => $provider,
            'has_code' => $code !== '',
            'has_state' => $state !== '',
            'query_keys' => array_keys($request->query()),   // names only — never log the code/state values
        ]);

        if ($code === '' || $state === '') {
            Log::warning('oauth.callback_missing_params', ['provider' => $provider, 'query_keys' => array_keys($request->query())]);

            return redirect('/channels?error=oauth_missing_params');
        }

        try {
            $result = $service->completeConnect($provider, $code, $state);
        } catch (\Throwable $e) {
            $errorCode = match (true) {
                $e->getMessage() === 'OAUTH_STATE_INVALID', $e->getMessage() === 'OAUTH_STATE_EXPIRED' => 'oauth_state',
                $e->getMessage() === 'SHOP_ALREADY_CONNECTED_ELSEWHERE' => 'shop_already_connected',
                $e instanceof TikTokApiException && $e->isScopeDenied() => 'tiktok_scope_denied',
                $e instanceof TikTokApiException && $e->isAuthError() => 'tiktok_auth_failed',
                $e instanceof TikTokApiException => 'tiktok_api_error',
                default => 'oauth_failed',
            };
            Log::warning('oauth.callback_failed', ['provider' => $provider, 'reason' => $e->getMessage(), 'error' => class_basename($e)]);

            $params = ['error' => $errorCode];
            if ($e instanceof TikTokApiException) {
                $params['tt_code'] = $e->getCode();
            }

            return redirect('/channels?'.http_build_query($params));
        }

        Log::info('oauth.callback_ok', ['provider' => $provider, 'channel_account_id' => $result['account']->getKey()]);

        return redirect($result['redirect']);
    }
}
