<?php

namespace CMBcoreSeller\Integrations\Channels\Contracts;

use Carbon\CarbonImmutable;
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
 * Contract every marketplace integration must implement.
 *
 * THE GOLDEN RULE (see docs/01-architecture/extensibility-rules.md):
 * core code never knows the name of a specific marketplace. Adding a new
 * channel = a new class implementing this interface + one line in
 * ChannelRegistry + a row in config/integrations.php. No `if ($provider === ...)`
 * anywhere in the domain modules.
 *
 * A connector that does not support an operation must throw
 * {@see UnsupportedOperation};
 * callers should consult capabilities()/supports() first.
 */
interface ChannelConnector
{
    // --- Identity ---------------------------------------------------------

    /** Stable provider code, e.g. 'tiktok' | 'shopee' | 'lazada' | 'manual'. */
    public function code(): string;

    /** Human-readable name, e.g. 'TikTok Shop'. */
    public function displayName(): string;

    /**
     * Capability flags, e.g. ['orders.fetch' => true, 'listings.publish' => false].
     * Core checks supports() before calling optional methods.
     *
     * @return array<string, bool>
     */
    public function capabilities(): array;

    public function supports(string $capability): bool;

    // --- OAuth / connection ----------------------------------------------

    /**
     * @param  array<string, mixed>  $opts
     */
    public function buildAuthorizationUrl(string $state, array $opts = []): string;

    public function exchangeCodeForToken(string $code): TokenDTO;

    public function refreshToken(string $refreshToken): TokenDTO;

    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO;

    /** Subscribe this shop to the marketplace's webhooks, if supported. */
    public function registerWebhooks(AuthContext $auth): void;

    public function revoke(AuthContext $auth): void;

    // --- Orders -----------------------------------------------------------

    /**
     * @param  array{updatedFrom?:CarbonImmutable,updatedTo?:CarbonImmutable,statuses?:list<string>,cursor?:string,pageSize?:int}  $query
     * @return Page<OrderDTO>
     */
    public function fetchOrders(AuthContext $auth, array $query = []): Page;

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO;

    /** Parse an incoming webhook HTTP request into a normalized event. */
    public function parseWebhook(Request $request): WebhookEventDTO;

    /** Verify the signature of an incoming webhook request. */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Map a marketplace raw status onto the canonical status. This is the
     * ONLY place a marketplace's status strings should appear.
     *
     * @param  array<string, mixed>  $rawOrder
     */
    public function mapStatus(string $rawStatus, array $rawOrder = []): StandardOrderStatus;

    // --- Inventory --------------------------------------------------------

    /**
     * Set the available stock of a listing on the marketplace.
     *
     * @param  array{external_product_id?:string|null,warehouse_id?:string|int|null}  $context  extra ids some APIs need (e.g. TikTok needs the product id)
     */
    public function updateStock(AuthContext $auth, string $externalSkuId, int $available, array $context = []): void;

    // --- Fulfillment ------------------------------------------------------

    /**
     * Ask the marketplace to arrange shipment for an order; returns tracking info.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array;

    /**
     * Fetch the shipping document (label PDF, packing slip, ...) bytes.
     *
     * @param  array{type?:string,format?:string,externalPackageId?:string}  $query
     * @return array{filename:string,mime:string,bytes:string}
     */
    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array;
}
