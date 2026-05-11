<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\Page;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

/**
 * TikTok Shop connector (API generation "202309"). Orders + auth + webhook for
 * Phase 1; listings/finance/fulfillment land in later phases (declared as
 * unsupported in the capability map so core hides those features rather than
 * branching on the provider name). All TikTok specifics — signing, versions,
 * status & event maps — live under app/Integrations/Channels/TikTok/ and
 * config('integrations.tiktok'). See docs/04-channels/tiktok-shop.md, SPEC 0001.
 */
class TikTokConnector implements ChannelConnector
{
    public function __construct(
        protected TikTokClient $client,
        protected TikTokWebhookVerifier $webhook,
    ) {}

    public function code(): string
    {
        return 'tiktok';
    }

    public function displayName(): string
    {
        return 'TikTok Shop';
    }

    public function capabilities(): array
    {
        return [
            'orders.fetch' => true,
            'orders.webhook' => true,
            'orders.confirm' => false,        // Phase 3 (arrange shipment flow)
            'shipping.arrange' => false,      // Phase 3
            'shipping.document' => false,     // Phase 3
            'shipping.tracking' => false,     // Phase 3
            'listings.fetch' => false,        // Phase 2/5
            'listings.publish' => false,      // Phase 5
            'listings.updateStock' => false,  // Phase 2
            'listings.updatePrice' => false,  // Phase 5
            'finance.settlements' => false,   // Phase 6
            'returns.fetch' => false,         // Phase 7
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    // --- OAuth / connection --------------------------------------------------

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        $redirectUri = (string) ($opts['redirect_uri'] ?? route('oauth.callback', ['provider' => 'tiktok']));

        return $this->client->authorizeUrl($state, $redirectUri);
    }

    public function exchangeCodeForToken(string $code): TokenDTO
    {
        return TikTokMappers::token($this->client->getAccessToken($code));
    }

    public function refreshToken(string $refreshToken): TokenDTO
    {
        return TikTokMappers::token($this->client->refreshAccessToken($refreshToken));
    }

    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO
    {
        $ver = $this->client->versionFor('authorization');
        $data = $this->client->get("/authorization/{$ver}/shops", $auth, shopScoped: false);
        $shops = (array) ($data['shops'] ?? []);
        if ($shops === []) {
            throw new TikTokApiException('TikTok returned no authorized shops for this token.', 0, 200);
        }
        // First active shop. (Multi-shop tokens are rare for sandbox; refine if needed.)
        $shop = $shops[0];

        // Surface the shop_cipher to the caller via the DTO's raw payload (OAuthService stores it in meta).
        return TikTokMappers::shopInfo($shop);
    }

    public function registerWebhooks(AuthContext $auth): void
    {
        $events = (array) config('integrations.tiktok.subscribe_events', []);
        if ($events === []) {
            return;
        }
        $ver = $this->client->versionFor('event');
        $address = url('/webhook/tiktok');

        foreach ($events as $event) {
            try {
                $this->client->post("/event/{$ver}/webhooks", $auth, ['event_type' => $event, 'address' => $address]);
            } catch (\Throwable $e) {
                // Many apps configure webhook subscriptions in Partner Center instead — don't fail the connect flow.
                Log::info('tiktok.webhook.subscribe_failed', ['event' => $event, 'shop' => $auth->externalShopId, 'error' => class_basename($e)]);
            }
        }
    }

    public function revoke(AuthContext $auth): void
    {
        // The seller revokes authorization from their side; there is no reliable
        // "revoke my own token" endpoint. Disconnecting locally is enough — sync stops.
        Log::info('tiktok.revoke', ['shop' => $auth->externalShopId]);
    }

    // --- Orders --------------------------------------------------------------

    public function fetchOrders(AuthContext $auth, array $query = []): Page
    {
        $ver = $this->client->versionFor('order');
        $q = [
            'page_size' => (string) min(100, max(1, (int) ($query['pageSize'] ?? 50))),
            'sort_field' => 'update_time',
            'sort_order' => 'ASC',
        ];
        if (! empty($query['cursor'])) {
            $q['page_token'] = (string) $query['cursor'];
        }

        $body = [];
        if (! empty($query['updatedFrom'])) {
            $body['update_time_ge'] = $query['updatedFrom']->getTimestamp();
        }
        if (! empty($query['updatedTo'])) {
            $body['update_time_lt'] = $query['updatedTo']->getTimestamp();
        }
        if (! empty($query['statuses']) && count($query['statuses']) === 1) {
            $body['order_status'] = (string) $query['statuses'][0];
        }

        $data = $this->client->post("/order/{$ver}/orders/search", $auth, $body, $q);
        $orders = array_values(array_map(fn ($o) => TikTokMappers::order((array) $o), (array) ($data['orders'] ?? [])));
        $next = $data['next_page_token'] ?? null;

        return new Page(items: $orders, nextCursor: $next ?: null, hasMore: ! empty($next));
    }

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO
    {
        $ver = $this->client->versionFor('order');
        $data = $this->client->get("/order/{$ver}/orders", $auth, ['ids' => $externalOrderId]);
        $orders = (array) ($data['orders'] ?? []);
        if ($orders === []) {
            throw new TikTokApiException("TikTok order {$externalOrderId} not found.", 0, 404);
        }

        return TikTokMappers::order((array) $orders[0]);
    }

    public function parseWebhook(Request $request): WebhookEventDTO
    {
        return $this->webhook->parse($request);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->webhook->verify($request);
    }

    public function mapStatus(string $rawStatus, array $rawOrder = []): StandardOrderStatus
    {
        return TikTokStatusMap::toStandard($rawStatus, $rawOrder);
    }

    // --- Inventory (Phase 2) -------------------------------------------------

    public function updateStock(AuthContext $auth, string $externalSkuId, int $available): void
    {
        throw UnsupportedOperation::for($this->code(), 'updateStock');
    }

    // --- Fulfillment (Phase 3) -----------------------------------------------

    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'arrangeShipment');
    }

    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'getShippingDocument');
    }
}
