<?php

namespace CMBcoreSeller\Integrations\Channels\Manual;

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
 * The "manual" pseudo-channel. Manually created orders are modeled as a
 * channel so all order/inventory/fulfillment code can treat every source
 * uniformly. There is no external API behind it — everything throws
 * UnsupportedOperation; orders are created directly via the Orders module.
 */
class ManualConnector implements ChannelConnector
{
    public function code(): string
    {
        return 'manual';
    }

    public function displayName(): string
    {
        return 'Đơn thủ công';
    }

    public function capabilities(): array
    {
        // Manual orders are created in-app; nothing is fetched/pushed externally.
        return [];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        throw UnsupportedOperation::for($this->code(), 'buildAuthorizationUrl');
    }

    public function exchangeCodeForToken(string $code): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'exchangeCodeForToken');
    }

    public function refreshToken(string $refreshToken): TokenDTO
    {
        throw UnsupportedOperation::for($this->code(), 'refreshToken');
    }

    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO
    {
        throw UnsupportedOperation::for($this->code(), 'fetchShopInfo');
    }

    public function registerWebhooks(AuthContext $auth): void
    {
        // no-op
    }

    public function revoke(AuthContext $auth): void
    {
        // no-op
    }

    public function fetchOrders(AuthContext $auth, array $query = []): Page
    {
        return new Page(items: [], nextCursor: null, hasMore: false);
    }

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO
    {
        throw UnsupportedOperation::for($this->code(), 'fetchOrderDetail');
    }

    public function parseWebhook(Request $request): WebhookEventDTO
    {
        throw UnsupportedOperation::for($this->code(), 'parseWebhook');
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return false;
    }

    public function mapStatus(string $rawStatus, array $rawOrder = []): StandardOrderStatus
    {
        // Manual orders already use canonical status strings.
        return StandardOrderStatus::from($rawStatus);
    }

    public function fetchListings(AuthContext $auth, array $query = []): Page
    {
        return new Page(items: [], nextCursor: null, hasMore: false);
    }

    public function updateStock(AuthContext $auth, string $externalSkuId, int $available, array $context = []): void
    {
        // no-op: there is no external listing for manual orders.
    }

    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'arrangeShipment');
    }

    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'getShippingDocument');
    }
}
