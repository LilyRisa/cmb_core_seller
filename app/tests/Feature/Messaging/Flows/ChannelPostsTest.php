<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Post picker endpoint cho trigger comment_on_post (Flow Builder S4): liệt kê bài
 * đăng FB của 1 kênh đã kết nối qua connector listPosts.
 */
class ChannelPostsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_secret' => 'secret',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'PostShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);
        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => 'PAGE_1', 'status' => 'active', 'access_token' => 'TOK', 'messaging_enabled' => true,
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_lists_facebook_posts_for_picker(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'data' => [
                ['id' => 'PAGE_1_111', 'message' => 'Sale 50%', 'created_time' => '2026-05-01T10:00:00+0000', 'permalink_url' => 'https://fb.com/1', 'full_picture' => 'https://cdn/1.jpg'],
            ],
            'paging' => ['cursors' => ['after' => 'C2'], 'next' => 'https://g/next'],
        ], 200)]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson("/api/v1/messaging/channels/{$this->account->id}/posts")
            ->assertOk()
            ->assertJsonPath('data.items.0.id', 'PAGE_1_111')
            ->assertJsonPath('data.items.0.message', 'Sale 50%')
            ->assertJsonPath('data.has_more', true);
    }
}
