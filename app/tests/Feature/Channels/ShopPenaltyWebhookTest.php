<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Contracts\PenaltyWebhookConnector;
use CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector;
use CMBcoreSeller\Modules\Channels\Events\ShopPenaltyDetected;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\ShopPenaltyEvent;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Channels\Support\TokenRefresher;
use CMBcoreSeller\Modules\Orders\Services\OrderUpsertService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Webhook điểm phạt Shopee (code 28/16) → lưu ShopPenaltyEvent + phát ShopPenaltyDetected. SPEC 2026-06-06.
 */
class ShopPenaltyWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.shopee.partner_id' => 1, 'integrations.shopee.partner_key' => 'k']);
        app(ChannelRegistry::class)->register('shopee', ShopeeConnector::class);
    }

    public function test_shopee_parses_penalty_point_push(): void
    {
        $c = app(ChannelRegistry::class)->for('shopee');
        $this->assertInstanceOf(PenaltyWebhookConnector::class, $c);

        $events = $c->parsePenaltyWebhook([
            'code' => 28, 'shop_id' => '600001', 'timestamp' => 1700000000,
            'data' => ['action_type' => 1, 'points_issued_data' => ['issued_points' => 3, 'violation_type' => 5], 'update_time' => 1700000000],
        ]);

        $this->assertCount(1, $events);
        $this->assertSame('penalty_issued', $events[0]->kind);
        $this->assertSame(3, $events[0]->points);
        $this->assertSame('Tỉ lệ giao trễ cao', $events[0]->violationLabel);
    }

    public function test_shopee_parses_listing_violation_push(): void
    {
        $c = app(ChannelRegistry::class)->for('shopee');
        $events = $c->parsePenaltyWebhook([
            'code' => 16, 'shop_id' => '600001', 'timestamp' => 1700000000,
            'data' => ['item_id' => 9991, 'item_name' => 'Áo cấm', 'violation_reason' => 'Prohibited Listing'],
        ]);

        $this->assertSame('listing_violation', $events[0]->kind);
        $this->assertSame('9991', $events[0]->itemId);
        $this->assertSame('Prohibited Listing', $events[0]->violationLabel);
    }

    public function test_webhook_records_penalty_event_and_dispatches(): void
    {
        Event::fake([ShopPenaltyDetected::class]);
        $tenant = Tenant::create(['name' => 'Shop A']);
        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'provider' => 'shopee',
            'external_shop_id' => '600001', 'shop_name' => 'S', 'status' => 'active',
        ]);

        $we = WebhookEvent::create([
            'provider' => 'shopee', 'event_type' => 'shop_penalty_update', 'external_shop_id' => '600001',
            'raw_type' => '28', 'signature_ok' => true, 'status' => WebhookEvent::STATUS_PENDING, 'received_at' => now(),
            'payload' => ['code' => 28, 'shop_id' => '600001', 'timestamp' => 1700000000,
                'data' => ['action_type' => 1, 'points_issued_data' => ['issued_points' => 2, 'violation_type' => 10], 'update_time' => 1700000000]],
        ]);

        (new ProcessWebhookEvent((int) $we->getKey()))->handle(
            app(ChannelRegistry::class), app(OrderUpsertService::class), app(TokenRefresher::class),
        );

        $row = ShopPenaltyEvent::withoutGlobalScope(TenantScope::class)->first();
        $this->assertNotNull($row);
        $this->assertSame('penalty_issued', $row->kind);
        $this->assertSame(2, $row->points);
        $this->assertSame('Hàng giả / vi phạm sở hữu trí tuệ', $row->violation_label);
        $this->assertSame((int) $shop->getKey(), (int) $row->channel_account_id);
        Event::assertDispatched(ShopPenaltyDetected::class);

        $this->assertSame(WebhookEvent::STATUS_PROCESSED, $we->fresh()->status);
    }
}
