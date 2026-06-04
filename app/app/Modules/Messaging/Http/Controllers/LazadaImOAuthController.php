<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\OAuthState;
use CMBcoreSeller\Modules\Messaging\Jobs\SyncConversationsForShop;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * OAuth connect cho Lazada IM Chat qua app "IM ERP" RIÊNG (ngoại lệ ADR-0019).
 *
 * Lazada gate quyền IM theo app ⇒ chat KHÔNG dùng chung app/token orders. Flow
 * riêng (như {@see FacebookOAuthController}): authorize app IM ERP → đổi code →
 * tạo `channel_accounts(provider=lazada_im)` với token riêng → poll. Tái dùng
 * bảng `oauth_states` cho CSRF state → tenant (callback không có session).
 *
 * Connector code = `lazada_chat` (provider `lazada_im` map sang nó qua
 * {@see ChannelAccount::messagingConnectorCode()}); credential đọc từ
 * `config('integrations.messaging_lazada_im')`.
 */
class LazadaImOAuthController extends Controller
{
    private const PROVIDER = 'lazada_im';

    private const CONNECTOR = 'lazada_chat';

    public function __construct(private MessagingRegistry $registry) {}

    /** [auth] Khởi tạo OAuth: trả authorize URL (app IM ERP) cho FE redirect sang Lazada. */
    public function start(Request $request): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $tenantId = app(CurrentTenant::class)->id();
        $state = OAuthState::issue(self::PROVIDER, (int) $tenantId, $request->user()?->id, '/messaging/channels?connected=lazada_im');

        $url = $this->registry->for(self::CONNECTOR)->buildAuthorizationUrl($state->state);

        return response()->json(['data' => ['authorize_url' => $url]]);
    }

    /** [web, no auth] Callback từ Lazada sau khi seller authorize app IM ERP. */
    public function callback(Request $request): Response
    {
        $code = (string) $request->query('code', '');
        $stateToken = (string) $request->query('state', '');

        if ($request->query('error') || $code === '' || $stateToken === '') {
            return $this->finish('/messaging/channels?error=lazada_im_oauth_'.preg_replace('/[^a-z0-9_]/i', '_', strtolower((string) $request->query('error', 'missing_params'))));
        }

        $state = OAuthState::query()->where('state', $stateToken)->where('provider', self::PROVIDER)->first();
        if (! $state || $state->isExpired()) {
            return $this->finish('/messaging/channels?error=lazada_im_oauth_state');
        }

        try {
            $token = $this->registry->for(self::CONNECTOR)->exchangeCodeForToken($code);

            // seller_id từ token raw (`country_user_info_list[0]` / legacy `country_user_info[0]`).
            // PHẢI khớp `data.seller_id` của poll/webhook để không "shop_not_found".
            $raw = $token->raw;
            $userInfo = (array) ($raw['country_user_info_list'][0] ?? $raw['country_user_info'][0] ?? []);
            $sellerId = (string) ($userInfo['seller_id'] ?? '');
            if ($sellerId === '') {
                return $this->finish('/messaging/channels?error=lazada_im_no_seller');
            }

            // withTrashed + restore: reconnect sau khi đã ngắt (soft-delete) phải KHÔI PHỤC hàng cũ.
            $account = ChannelAccount::withoutGlobalScope(TenantScope::class)->withTrashed()->firstOrNew([
                'tenant_id' => $state->tenant_id, 'provider' => self::PROVIDER, 'external_shop_id' => $sellerId,
            ]);
            $account->forceFill([
                'tenant_id' => $state->tenant_id,
                'shop_name' => isset($raw['account']) && (string) $raw['account'] !== '' ? (string) $raw['account'] : null,
                'shop_region' => isset($userInfo['country']) && (string) $userInfo['country'] !== '' ? strtoupper((string) $userInfo['country']) : 'VN',
                'access_token' => $token->accessToken,
                'refresh_token' => $token->refreshToken,
                'token_expires_at' => $token->expiresAt,
                'refresh_token_expires_at' => $token->refreshExpiresAt,
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

            SyncConversationsForShop::dispatch((int) $account->getKey());

            $state->delete(); // one-time use
            AuditLog::record('messaging.lazada_im.connected', $account, ['shop' => $sellerId]);
        } catch (\Throwable $e) {
            Log::warning('messaging.lazada_im.oauth_failed', ['error' => $e->getMessage()]);

            return $this->finish('/messaging/channels?error=lazada_im_oauth_failed');
        }

        return $this->finish($state->redirect_after ?: '/messaging/channels?connected=lazada_im');
    }

    /** Trả view popup-friendly: popup → postMessage + close; không popup → redirect SPA. */
    private function finish(string $redirect): Response
    {
        return response()->view('oauth-callback', ['redirect' => $redirect]);
    }
}
