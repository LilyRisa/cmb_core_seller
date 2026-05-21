<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Test rate-limit AI-suggestion theo tenant (SPEC-0024 hardening §6.2):
 * limiter `ai-suggestion` = 20/phút/tenant. Lần 21 trong cùng phút ⇒ 429.
 *
 * Provider `manual` (deterministic, free) + Business plan (AI replies = ∞)
 * nên không vướng feature-gate / monthly limit trước khi đụng rate-limit.
 */
class AiSuggestionRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Conversation $conv;

    protected function setUp(): void
    {
        parent::setUp();
        // Cache (array driver ở test) lưu counter của RateLimiter giữa các test ⇒ flush.
        Cache::flush();
        $this->seed(BillingPlanSeeder::class);

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'AiRateShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activate(Plan::CODE_BUSINESS);

        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true, 'default_model' => 'manual-v1']);

        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_rate_1',
            'shop_name' => 'AI Rate Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);

        $this->conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_rate_1',
            'buyer_external_id' => 'buyer_1',
            'buyer_name' => 'Anh Khách',
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now()->subMinutes(2),
        ]);

        Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $this->conv->id,
            'external_message_id' => 'in_1',
            'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'Shop ơi đơn của em bao giờ giao?',
            'delivery_status' => Message::STATUS_SENT,
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activate(string $planCode): void
    {
        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    public function test_blocks_after_20_requests_per_minute(): void
    {
        $url = "/api/v1/messaging/conversations/{$this->conv->id}/ai-suggestion";

        // 20 request đầu trong phút: KHÔNG bị throttle (≠ 429).
        for ($i = 1; $i <= 20; $i++) {
            $res = $this->actingAs($this->owner)->withHeaders($this->h())->postJson($url);
            $this->assertNotSame(429, $res->getStatusCode(), "Request #{$i} không được bị throttle (status {$res->getStatusCode()}).");
        }

        // Request thứ 21 vượt 20/phút ⇒ 429.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson($url)
            ->assertStatus(429);
    }
}
