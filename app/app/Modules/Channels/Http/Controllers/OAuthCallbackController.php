<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Services\ChannelConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GET /oauth/{provider}/callback?code=...&state=... — exchange the code, create
 * the channel account, then redirect into the SPA (/channels?connected=... on
 * success, ?error=... on failure). The tenant is resolved from the oauth_states
 * row by `state`, not from the session. See docs/05-api/webhooks-and-oauth.md §2.
 */
class OAuthCallbackController extends Controller
{
    public function __invoke(Request $request, string $provider, ChannelConnectionService $service): RedirectResponse
    {
        $code = (string) $request->query('code', '');
        $state = (string) ($request->query('state', '') ?: $request->query('app_key_state', ''));

        if ($code === '' || $state === '') {
            return redirect('/channels?error=oauth_missing_params');
        }

        try {
            $result = $service->completeConnect($provider, $code, $state);
        } catch (\Throwable $e) {
            $code = match ($e->getMessage()) {
                'OAUTH_STATE_INVALID', 'OAUTH_STATE_EXPIRED' => 'oauth_state',
                'SHOP_ALREADY_CONNECTED_ELSEWHERE' => 'shop_already_connected',
                default => 'oauth_failed',
            };
            Log::warning('oauth.callback_failed', ['provider' => $provider, 'reason' => $e->getMessage()]);

            return redirect("/channels?error={$code}");
        }

        return redirect($result['redirect']);
    }
}
