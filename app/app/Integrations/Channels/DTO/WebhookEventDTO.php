<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

/**
 * Normalized webhook event after a connector parses an incoming push from
 * a marketplace. `type` is one of a small fixed vocabulary so core code can
 * switch on it without knowing the provider. See docs/05-api/webhooks-and-oauth.md.
 */
final readonly class WebhookEventDTO
{
    public const TYPE_ORDER_CREATED = 'order_created';
    public const TYPE_ORDER_STATUS_UPDATE = 'order_status_update';
    public const TYPE_ORDER_CANCEL = 'order_cancel';
    public const TYPE_RETURN_UPDATE = 'return_update';
    public const TYPE_SETTLEMENT_AVAILABLE = 'settlement_available';
    public const TYPE_PRODUCT_UPDATE = 'product_update';
    public const TYPE_SHOP_DEAUTHORIZED = 'shop_deauthorized';
    public const TYPE_DATA_DELETION = 'data_deletion';
    public const TYPE_UNKNOWN = 'unknown';

    public function __construct(
        public string $provider,
        public string $type,
        public ?string $externalShopId = null,
        public ?string $externalOrderId = null,
        /** @var array<string, string> Extra ids carried by the event. */
        public array $externalIds = [],
        public ?CarbonImmutable $occurredAt = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
