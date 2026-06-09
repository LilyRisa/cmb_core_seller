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
 * OAuth connect cho TikTok Marketing API (Ads) — ADR-0025. Song song với
 * {@see AdsOAuthController} (Facebook), KHÔNG sửa luồng FB đang chạy.
 *
 * Khác FB: redirect TikTok trả về tham số `auth_code` (không phải `code`); token
 * dài hạn KHÔNG hết hạn ⇒ `token_expires_at = null`. `OAuthState.provider =
 * 'tiktok_marketing'`; AdsRegistry resolve connector `'tiktok'`.
 */
class TikTokAdsOAuthController extends Controller
{
    private const STATE_PROVIDER = 'tiktok_marketing';

    private const CONNECTOR = 'tiktok';

    public function __construct(private AdsRegistry $registry) {}

    /** [auth] Trả authorize URL cho FE redirect sang TikTok. */
    public function start(Request $request): JsonResponse
    {
        Gate::authorize('marketing.connect');

        abort_unless($this->registry->has(self::CONNECTOR), 422, 'Tính năng Quảng cáo TikTok chưa được bật. Thêm `tiktok` vào INTEGRATIONS_ADS.');
        $cfg = (array) config('integrations.ads_tiktok', []);
        abort_if(empty($cfg['app_id']) || empty($cfg['app_secret']), 422, 'Chưa cấu hình app TikTok Ads (TIKTOK_ADS_APP_ID / TIKTOK_ADS_APP_SECRET).');

        $tenantId = app(CurrentTenant::class)->id();
        $state = OAuthState::issue(self::STATE_PROVIDER, (int) $tenantId, $request->user()?->id, '/marketing?connected=tiktok_marketing');

        $url = $this->registry->for(self::CONNECTOR)->buildAuthorizationUrl($state->state);

        return response()->json(['data' => ['authorize_url' => $url]]);
    }

    /** [web, no auth] Callback từ TikTok sau khi advertiser ủy quyền. */
    public function callback(Request $request): Response
    {
        // TikTok đính `auth_code` (fallback `code` đề phòng).
        $code = (string) ($request->query('auth_code') ?: $request->query('code', ''));
        $stateToken = (string) $request->query('state', '');

        if ($request->query('error') || $code === '' || $stateToken === '') {
            return $this->finish('/marketing?error=tiktok_marketing_oauth_'.preg_replace('/[^a-z0-9_]/i', '_', strtolower((string) $request->query('error', 'missing_params'))));
        }

        $state = OAuthState::query()->where('state', $stateToken)->where('provider', self::STATE_PROVIDER)->first();
        if (! $state || $state->isExpired()) {
            return $this->finish('/marketing?error=tiktok_marketing_oauth_state');
        }

        try {
            $connector = $this->registry->for(self::CONNECTOR);
            $token = $connector->exchangeCodeForToken($code);
            $accounts = $connector->listAdAccounts($token['access_token']);
            if ($accounts === []) {
                return $this->finish('/marketing?error=tiktok_marketing_no_accounts');
            }

            $connected = 0;
            foreach ($accounts as $dto) {
                // withTrashed + restore: reconnect sau khi ngắt (soft-delete) phải KHÔI PHỤC
                // hàng cũ, không INSERT mới (tránh đụng unique tenant+provider+external_account_id).
                $account = AdAccount::withoutGlobalScope(TenantScope::class)->withTrashed()->firstOrNew([
                    'tenant_id' => $state->tenant_id,
                    'provider' => self::CONNECTOR,
                    'external_account_id' => $dto->externalAccountId,
                ]);
                $meta = (array) ($account->meta ?? []);
                $meta['timezone'] = $dto->raw['timezone'] ?? ($meta['timezone'] ?? null);
                $meta['tiktok_status'] = $dto->status;
                $account->forceFill([
                    'tenant_id' => $state->tenant_id,
                    'name' => $dto->name,
                    'currency' => $dto->currency,
                    'business_id' => $dto->businessId,        // owner_bc_id (nếu có)
                    'business_name' => null,
                    'business_picture_url' => null,
                    'fb_account_status' => null,
                    'disable_reason' => null,
                    'health_checked_at' => now(),
                    'access_token' => $token['access_token'],
                    'token_expires_at' => null,               // token TikTok không hết hạn
                    'status' => AdAccount::STATUS_ACTIVE,
                    'created_by' => $account->created_by ?? $state->created_by,
                    'meta' => $meta,
                    'deleted_at' => null,
                ])->save();
                SyncAdAccountEntities::dispatch((int) $account->getKey());
                $connected++;
            }

            $state->delete();
            AuditLog::record('marketing.tiktok_ads.connected', null, ['accounts' => $connected]);
        } catch (\Throwable $e) {
            Log::warning('marketing.tiktok_ads.oauth_failed', ['error' => $e->getMessage()]);

            return $this->finish('/marketing?error=tiktok_marketing_oauth_failed');
        }

        return $this->finish($state->redirect_after ?: '/marketing?connected=tiktok_marketing');
    }

    private function finish(string $redirect): Response
    {
        return response()->view('oauth-callback', ['redirect' => $redirect]);
    }
}
