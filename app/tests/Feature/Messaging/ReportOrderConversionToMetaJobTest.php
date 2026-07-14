<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\ReportOrderConversionToMeta;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportOrderConversionToMetaJobTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'FbShop']);
        app(MessagingRegistry::class)->register('facebook_page', FacebookPageConnector::class);

        $this->account = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id, 'provider' => 'facebook_page',
            'external_shop_id' => 'page_1', 'shop_name' => 'My Page',
            'status' => 'active', 'access_token' => 'PAGE_TOKEN',
        ]);
    }

    private function makeMeta(array $fbConversions): MessagingAccountMeta
    {
        return MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->create([
            'channel_account_id' => $this->account->id, 'tenant_id' => $this->tenant->id,
            'settings' => ['fb_conversions' => $fbConversions],
        ]);
    }

    private function makeConversation(): Conversation
    {
        return Conversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id, 'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page', 'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'PSID_1', 'buyer_external_id' => 'PSID_1',
            'meta' => ['ad_referral' => ['source' => 'ADS', 'ad_id' => 'AD_1']],
        ]);
    }

    private function makeOrder(array $extra = []): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)->create(array_merge([
            'tenant_id' => $this->tenant->id, 'source' => 'manual', 'status' => 'processing',
            'order_number' => 'M-JOB-'.uniqid(), 'grand_total' => 150000, 'is_cod' => true,
        ], $extra));
    }

    public function test_reports_purchase_and_marks_order_idempotent(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        $this->makeMeta(['enabled' => true, 'dataset_id' => 'DATASET_1']);
        $conv = $this->makeConversation();
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        $order->refresh();
        $this->assertNotEmpty($order->meta['fb_conversion_reported_at'] ?? null);
        Http::assertSentCount(1);

        // Chạy lại lần 2 — KHÔNG gọi Graph nữa (idempotent).
        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);
        Http::assertSentCount(1);
    }

    public function test_skips_when_toggle_disabled(): void
    {
        Http::fake();
        $this->makeMeta(['enabled' => false, 'dataset_id' => 'DATASET_1']);
        $conv = $this->makeConversation();
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        Http::assertNothingSent();
        $this->assertEmpty($order->fresh()->meta['fb_conversion_reported_at'] ?? null);
    }

    public function test_skips_when_no_ad_referral(): void
    {
        Http::fake();
        $this->makeMeta(['enabled' => true, 'dataset_id' => 'DATASET_1']);
        $conv = Conversation::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->id, 'channel_account_id' => $this->account->id,
            'provider' => 'facebook_page', 'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => 'PSID_2', 'buyer_external_id' => 'PSID_2',
        ]);
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        Http::assertNothingSent();
    }

    public function test_missing_scope_sets_error_flag_and_does_not_rethrow(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['type' => 'OAuthException', 'code' => 200, 'message' => 'Missing permission page_events'],
        ], 400)]);
        $this->makeMeta(['enabled' => true, 'dataset_id' => 'DATASET_1']);
        $conv = $this->makeConversation();
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($this->account->id);
        $this->assertSame('missing_scope', $meta->settings['fb_conversions']['last_error'] ?? null);
        $this->assertNotEmpty($meta->settings['fb_conversions']['last_error_at'] ?? null);
        $this->assertEmpty($order->fresh()->meta['fb_conversion_reported_at'] ?? null);
    }

    public function test_ensures_dataset_when_missing_then_persists_it(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'DATASET_NEW'], 200)]);
        $this->makeMeta(['enabled' => true]);   // chưa có dataset_id
        $conv = $this->makeConversation();
        $order = $this->makeOrder();

        ReportOrderConversionToMeta::dispatchSync($conv->id, $order->id);

        $meta = MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->find($this->account->id);
        $this->assertSame('DATASET_NEW', $meta->settings['fb_conversions']['dataset_id'] ?? null);
        $this->assertNotEmpty($order->fresh()->meta['fb_conversion_reported_at'] ?? null);
    }
}
