<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Support\Events\SupportNewConversationOpened;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SupportNewConversationOpenedTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Shop Test']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => 'owner']);
    }

    /** @return array<string,string> */
    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_event_fires_only_on_first_message_of_open_conversation(): void
    {
        Event::fake([SupportNewConversationOpened::class]);

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/support/messages', ['body' => 'Tin đầu tiên'])
            ->assertCreated();
        Event::assertDispatched(SupportNewConversationOpened::class, 1);

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/support/messages', ['body' => 'Tin thứ hai cùng cuộc'])
            ->assertCreated();
        Event::assertDispatched(SupportNewConversationOpened::class, 1);
    }

    public function test_event_fires_again_after_conversation_closed(): void
    {
        Event::fake([SupportNewConversationOpened::class]);

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/support/messages', ['body' => 'Tin đầu tiên'])
            ->assertCreated();

        SupportConversation::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $this->tenant->getKey())
            ->latest('id')->first()
            ->forceFill(['status' => SupportConversation::STATUS_CLOSED])->save();

        $this->actingAs($this->user)->withHeaders($this->h())
            ->postJson('/api/v1/support/messages', ['body' => 'Tin sau khi đóng'])
            ->assertCreated();

        Event::assertDispatched(SupportNewConversationOpened::class, 2);
    }
}
