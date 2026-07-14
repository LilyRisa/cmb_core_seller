<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Toggle "Gửi dữ liệu chuyển đổi (mua hàng) về Facebook Ads" theo từng Page
 * (design 2026-07-14-fb-messenger-conversion-reporting).
 */
class FbConversionsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'FbShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'page_1', 'shop_name' => 'My Page',
            'status' => 'active', 'messaging_enabled' => true, 'access_token' => 'PAGE_TOKEN',
        ]);

        app(MessagingRegistry::class)->register('facebook_page', FacebookPageConnector::class);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_enabling_creates_dataset_and_persists_settings(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'DATASET_9'], 200)]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/channels/{$this->account->id}/fb-conversions", ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.fb_conversions.enabled', true)
            ->assertJsonPath('data.fb_conversions.dataset_id', 'DATASET_9');

        $meta = MessagingAccountMeta::query()->find($this->account->id);
        $this->assertTrue($meta->settings['fb_conversions']['enabled']);
        $this->assertSame('DATASET_9', $meta->settings['fb_conversions']['dataset_id']);
    }

    public function test_enabling_without_page_events_scope_returns_missing_scope_error(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['type' => 'OAuthException', 'code' => 200, 'message' => 'Missing permission page_events'],
        ], 400)]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/channels/{$this->account->id}/fb-conversions", ['enabled' => true])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'MISSING_SCOPE');

        $meta = MessagingAccountMeta::query()->find($this->account->id);
        $this->assertNull($meta);   // chưa lưu gì khi tạo dataset thất bại
    }

    public function test_disabling_keeps_dataset_id(): void
    {
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $this->account->id, 'tenant_id' => $this->tenant->getKey(),
            'settings' => ['fb_conversions' => ['enabled' => true, 'dataset_id' => 'DATASET_5']],
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/channels/{$this->account->id}/fb-conversions", ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.fb_conversions.enabled', false)
            ->assertJsonPath('data.fb_conversions.dataset_id', 'DATASET_5');

        $meta = MessagingAccountMeta::query()->find($this->account->id);
        $this->assertFalse($meta->settings['fb_conversions']['enabled']);
        $this->assertSame('DATASET_5', $meta->settings['fb_conversions']['dataset_id']);
    }

    public function test_channels_index_exposes_fb_conversions(): void
    {
        MessagingAccountMeta::query()->create([
            'channel_account_id' => $this->account->id, 'tenant_id' => $this->tenant->getKey(),
            'settings' => ['fb_conversions' => ['enabled' => true, 'dataset_id' => 'DATASET_5', 'last_error' => 'missing_scope']],
        ]);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/channels?provider=facebook_page')
            ->assertOk();

        $page = collect($res->json('data'))->firstWhere('id', $this->account->id);
        $this->assertTrue($page['fb_conversions']['enabled']);
        $this->assertSame('DATASET_5', $page['fb_conversions']['dataset_id']);
        $this->assertTrue($page['fb_conversions']['needs_reauth']);
    }
}
