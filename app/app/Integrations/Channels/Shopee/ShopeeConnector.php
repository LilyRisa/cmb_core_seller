<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\Page;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shopee Open Platform v2 connector. Mirrors Lazada/TikTok. See docs/04-channels/shopee.md
 * + spec docs/superpowers/specs/2026-05-20-shopee-channel-connector-design.md.
 */
class ShopeeConnector implements ChannelConnector
{
    public function __construct(private ShopeeClient $client) {}

    public function code(): string
    {
        return 'shopee';
    }

    public function displayName(): string
    {
        return 'Shopee';
    }

    public function capabilities(): array
    {
        $cfg = $this->client->cfg();
        $fulfill = (bool) ($cfg['fulfillment_enabled'] ?? true);

        return [
            'orders.fetch' => true, 'orders.webhook' => true, 'orders.confirm' => false,
            'shipping.arrange' => $fulfill, 'shipping.ready_to_ship' => false,
            'shipping.document' => $fulfill, 'shipping.tracking' => true,
            'listings.fetch' => true, 'listings.publish' => false,
            'listings.updateStock' => true, 'listings.updatePrice' => false,
            'finance.settlements' => (bool) ($cfg['finance_enabled'] ?? false),
            'returns.fetch' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        $redirect = (string) ($opts['redirect_uri'] ?? $this->client->redirectUri());
        $redirect .= (str_contains($redirect, '?') ? '&' : '?').'state='.urlencode($state);

        return $this->client->authorizeUrl($redirect);
    }

    public function exchangeCodeForToken(string $code, array $context = []): TokenDTO
    {
        $shopId = (string) ($context['shop_id'] ?? '');

        return ShopeeMappers::token($this->client->getAccessToken($code, $shopId), $shopId);
    }

    public function refreshToken(string $refreshToken, array $context = []): TokenDTO
    {
        $shopId = (string) ($context['shop_id'] ?? '');

        return ShopeeMappers::token($this->client->refreshAccessToken($refreshToken, $shopId), $shopId);
    }

    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO
    {
        $shopId = (string) ($auth->extra['token_raw']['shop_id'] ?? $auth->externalShopId);
        $shopAuth = new AuthContext(0, 'shopee', $shopId, $auth->accessToken);
        $res = $this->client->shopGet($shopAuth, $this->client->endpoint('shop_info'));

        return ShopeeMappers::shopInfo($res, $shopId);
    }

    public function registerWebhooks(AuthContext $auth): void
    {
        // Shopee push URL is configured once in the app console — nothing to subscribe per-shop.
    }

    public function revoke(AuthContext $auth): void
    {
        // No Shopee API to revoke partner authorization from our side; seller cancels in Seller Center.
    }

    public function fetchOrders(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchOrders'); // Task 5
    }

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO
    {
        throw UnsupportedOperation::for($this->code(), 'fetchOrderDetail'); // Task 5
    }

    public function parseWebhook(Request $request): WebhookEventDTO
    {
        throw UnsupportedOperation::for($this->code(), 'parseWebhook'); // Task 6
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return false; // Task 6
    }

    public function mapStatus(string $rawStatus, array $rawOrder = []): StandardOrderStatus
    {
        return ShopeeStatusMap::toStandard($rawStatus);
    }

    public function unprocessedRawStatuses(): array
    {
        return ['READY_TO_SHIP'];
    }

    public function fetchListings(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchListings'); // Task 7
    }

    public function updateStock(AuthContext $auth, string $externalSkuId, int $available, array $context = []): void
    {
        throw UnsupportedOperation::for($this->code(), 'updateStock'); // Task 7
    }

    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'arrangeShipment'); // Task 6b
    }

    public function pushReadyToShip(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'pushReadyToShip'); // Shopee has no separate RTS step
    }

    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'getShippingDocument'); // Task 6b
    }

    public function fetchSettlements(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchSettlements'); // Task 8
    }
}
