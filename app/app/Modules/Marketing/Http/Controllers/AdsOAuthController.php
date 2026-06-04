<?php

namespace CMBcoreSeller\Modules\Marketing\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Marketing\Jobs\SyncAdAccountEntities;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * OAuth connect cho Facebook Ads (Marketing API) — flow riêng, token `ads_read`
 * riêng (KHÔNG dùng page token/messaging). Mô phỏng LazadaImOAuthController.
 * `OAuthState.provider='facebook_ads'`; AdsRegistry resolve connector `'facebook'`.
 */
class AdsOAuthController extends Controller
{
    private const STATE_PROVIDER = 'facebook_ads';

    private const CONNECTOR = 'facebook';

    public function __construct(private AdsRegistry $registry) {}

    /** [auth] Trả authorize URL cho FE redirect sang Facebook. */
    public function start(Request $request): JsonResponse
    {
        Gate::authorize('marketing.connect');

        // Báo lỗi rõ ràng (422) thay vì 500 "not registered" khi chưa bật / chưa cấu hình app Ads RIÊNG.
        abort_unless($this->registry->has(self::CONNECTOR), 422, 'Tính năng Quảng cáo chưa được bật. Thêm `facebook` vào INTEGRATIONS_ADS.');
        $cfg = (array) config('integrations.ads_facebook', []);
        abort_if(empty($cfg['app_id']) || empty($cfg['app_secret']), 422, 'Chưa cấu hình app Facebook Ads RIÊNG (FACEBOOK_ADS_APP_ID / FACEBOOK_ADS_APP_SECRET) — không dùng lại app Facebook Page.');

        $tenantId = app(CurrentTenant::class)->id();
        $state = OAuthState::issue(self::STATE_PROVIDER, (int) $tenantId, $request->user()?->id, '/marketing?connected=facebook_ads');

        $url = $this->registry->for(self::CONNECTOR)->buildAuthorizationUrl($state->state);

        return response()->json(['data' => ['authorize_url' => $url]]);
    }

    /** [web, no auth] Callback từ Facebook sau khi seller authorize. */
    public function callback(Request $request): Response
    {
        $code = (string) $request->query('code', '');
        $stateToken = (string) $request->query('state', '');

        if ($request->query('error') || $code === '' || $stateToken === '') {
            return $this->finish('/marketing?error=facebook_ads_oauth_'.preg_replace('/[^a-z0-9_]/i', '_', strtolower((string) $request->query('error', 'missing_params'))));
        }

        $state = OAuthState::query()->where('state', $stateToken)->where('provider', self::STATE_PROVIDER)->first();
        if (! $state || $state->isExpired()) {
            return $this->finish('/marketing?error=facebook_ads_oauth_state');
        }

        try {
            $connector = $this->registry->for(self::CONNECTOR);
            $token = $connector->exchangeCodeForToken($code);
            $accounts = $connector->listAdAccounts($token['access_token']);
            if ($accounts === []) {
                return $this->finish('/marketing?error=facebook_ads_no_accounts');
            }

            $connected = 0;
            foreach ($accounts as $dto) {
                $account = AdAccount::withoutGlobalScope(TenantScope::class)->updateOrCreate(
                    ['tenant_id' => $state->tenant_id, 'provider' => self::CONNECTOR, 'external_account_id' => $dto->externalAccountId],
                    [
                        'name' => $dto->name,
                        'currency' => $dto->currency,
                        'business_id' => $dto->businessId,
                        'business_name' => $dto->businessName,
                        'access_token' => $token['access_token'],
                        'token_expires_at' => $token['expires_at'],
                        'status' => AdAccount::STATUS_ACTIVE,
                        'created_by' => $state->created_by,
                    ],
                );
                SyncAdAccountEntities::dispatch((int) $account->getKey());
                $connected++;
            }

            $state->delete();
            AuditLog::record('marketing.facebook_ads.connected', null, ['accounts' => $connected]);
        } catch (\Throwable $e) {
            Log::warning('marketing.facebook_ads.oauth_failed', ['error' => $e->getMessage()]);

            return $this->finish('/marketing?error=facebook_ads_oauth_failed');
        }

        return $this->finish($state->redirect_after ?: '/marketing?connected=facebook_ads');
    }

    private function finish(string $redirect): Response
    {
        return response()->view('oauth-callback', ['redirect' => $redirect]);
    }
}
