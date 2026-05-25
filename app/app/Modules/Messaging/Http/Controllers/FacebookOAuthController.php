<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillFacebookComments;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        Gate::authorize('messaging.connect');

        $tenantId = app(CurrentTenant::class)->id();
        $state = OAuthState::issue(self::PROVIDER, (int) $tenantId, $request->user()?->id, '/messaging/channels?connected=facebook_page');

        // KHÔNG truyền redirect_uri — connector dùng URI canonical (config/APP_URL)
        // cho cả dialog login lẫn đổi token, đảm bảo Meta thấy 2 URI giống hệt.
        $connector = $this->registry->for(self::PROVIDER);
        $url = $connector->buildAuthorizationUrl($state->state);

        return response()->json(['data' => ['authorize_url' => $url]]);
    }

    /** [web, no auth] Callback từ Meta sau khi user authorize. */
    public function callback(Request $request): Response
    {
        $code = (string) $request->query('code', '');
        $stateToken = (string) $request->query('state', '');

        if ($request->query('error') || $code === '' || $stateToken === '') {
            return $this->finish('/messaging/channels?error=facebook_oauth_'.preg_replace('/[^a-z0-9_]/i', '_', strtolower((string) $request->query('error', 'missing_params'))));
        }

        $state = OAuthState::query()->where('state', $stateToken)->where('provider', self::PROVIDER)->first();
        if (! $state || $state->isExpired()) {
            return $this->finish('/messaging/channels?error=facebook_oauth_state');
        }

        try {
            $connector = $this->registry->for(self::PROVIDER);
            $userToken = $connector->exchangeCodeForToken($code)->accessToken;

            $pages = $this->fetchPages($userToken);
            if ($pages === []) {
                return $this->finish('/messaging/channels?error=facebook_no_pages');
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
                MessagingAccountMeta::withoutGlobalScope(TenantScope::class)
                    ->updateOrCreate(
                        ['channel_account_id' => (int) $account->getKey()],
                        ['tenant_id' => (int) $account->tenant_id, 'messaging_enabled' => true,
                            'sync_status' => MessagingAccountMeta::SYNC_QUEUED],
                    );
                BackfillMessagingChannel::dispatch((int) $account->getKey());
                BackfillFacebookComments::dispatch((int) $account->getKey());
                $connected++;
            }

            $state->delete(); // one-time use
            AuditLog::record('messaging.facebook.connected', null, ['pages' => $connected]);
        } catch (\Throwable $e) {
            Log::warning('messaging.facebook.oauth_failed', ['error' => $e->getMessage()]);

            return $this->finish('/messaging/channels?error=facebook_oauth_failed');
        }

        return $this->finish($state->redirect_after ?: '/messaging/channels?connected=facebook_page');
    }

    /** Trả view popup-friendly: popup → postMessage + close; không popup → redirect SPA. */
    private function finish(string $redirect): Response
    {
        return response()->view('oauth-callback', ['redirect' => $redirect]);
    }

    /**
     * Liệt kê page user có thể nối + page access token, gồm CẢ page thuộc Business
     * Manager (tài khoản doanh nghiệp).
     *
     * `/me/accounts` chỉ trả page user là admin classic (gắn trực tiếp profile cá nhân).
     * Page giao qua Business Manager là "business asset" ⇒ KHÔNG xuất hiện ở đây — phải
     * duyệt `/me/businesses` rồi lấy `owned_pages` + `client_pages` từng business (cần
     * scope `business_management`). Gộp + dedupe theo page id; edge business đôi khi
     * không trả `access_token` ⇒ backfill `/{page-id}?fields=access_token`.
     *
     * @return list<array{id: string, name: string|null, access_token: string|null}>
     */
    private function fetchPages(string $userToken): array
    {
        $version = (string) config('integrations.messaging_facebook_page.graph_version', 'v19.0');
        $base = "https://graph.facebook.com/{$version}";
        $pageFields = 'id,name,access_token';

        /** @var array<string, array{id: string, name: string|null, access_token: string|null}> $pages */
        $pages = [];

        // (1) Page user là admin classic.
        $this->mergePages($pages, $this->graphData("{$base}/me/accounts", [
            'access_token' => $userToken, 'fields' => $pageFields, 'limit' => 200,
        ]));

        // (2) Page thuộc Business Manager — duyệt từng business (best-effort: thiếu
        //     scope/biz ⇒ trả [] và bỏ qua, KHÔNG chặn page classic ở (1)).
        foreach ($this->graphData("{$base}/me/businesses", ['access_token' => $userToken, 'limit' => 100]) as $biz) {
            $bizId = $biz['id'] ?? null;
            if (! $bizId) {
                continue;
            }
            foreach (['owned_pages', 'client_pages'] as $edge) {
                $this->mergePages($pages, $this->graphData("{$base}/{$bizId}/{$edge}", [
                    'access_token' => $userToken, 'fields' => $pageFields, 'limit' => 200,
                ]));
            }
        }

        // Backfill page token còn thiếu (edge business hay thiếu access_token).
        foreach ($pages as $id => $page) {
            if (! empty($page['access_token'])) {
                continue;
            }
            $res = Http::timeout(20)->get("{$base}/{$id}", ['access_token' => $userToken, 'fields' => 'access_token']);
            if ($res->successful() && $res->json('access_token')) {
                $pages[$id]['access_token'] = (string) $res->json('access_token');
            }
        }

        return array_values($pages);
    }

    /**
     * GET 1 edge Graph → mảng `data` (trang đầu, đủ cho danh sách page). Lỗi ⇒ [].
     *
     * @return list<array<string, mixed>>
     */
    private function graphData(string $url, array $query): array
    {
        $res = Http::timeout(20)->get($url, $query);

        return $res->successful() ? array_values((array) $res->json('data', [])) : [];
    }

    /**
     * Gộp page vào accumulator (keyed by id) — dedupe; ưu tiên giữ bản có access_token.
     *
     * @param  array<string, array{id: string, name: string|null, access_token: string|null}>  $acc
     * @param  list<array<string, mixed>>  $incoming
     */
    private function mergePages(array &$acc, array $incoming): void
    {
        foreach ($incoming as $p) {
            $id = isset($p['id']) ? (string) $p['id'] : '';
            if ($id === '') {
                continue;
            }
            $name = isset($p['name']) ? (string) $p['name'] : null;
            $token = isset($p['access_token']) ? (string) $p['access_token'] : null;
            if (! isset($acc[$id]) || ($acc[$id]['access_token'] === null && $token !== null)) {
                $acc[$id] = ['id' => $id, 'name' => $name, 'access_token' => $token];
            }
        }
    }
}
