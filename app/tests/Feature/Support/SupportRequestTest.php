<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Support\Models\SupportRequest;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tab "Hỏi CSKH" — lưu yêu cầu + báo chờ phản hồi. */
class SupportRequestTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'HelpShop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
    }

    private function actor(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => 'owner']);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_create_support_request_persists_and_returns_waiting_message(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/support/requests', ['question' => 'Làm sao kết nối gian hàng TikTok?'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.message', fn ($m) => is_string($m) && str_contains($m, 'CSKH'));

        $this->assertDatabaseHas('support_requests', [
            'tenant_id' => $this->tenant->getKey(),
            'question' => 'Làm sao kết nối gian hàng TikTok?',
            'status' => SupportRequest::STATUS_PENDING,
        ]);
    }

    public function test_empty_question_returns_422(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/support/requests', ['question' => ''])
            ->assertStatus(422);
    }

    public function test_index_lists_only_current_tenant_requests(): void
    {
        $other = Tenant::create(['name' => 'Other']);
        SupportRequest::query()->create(['tenant_id' => $other->getKey(), 'question' => 'tin của tenant khác', 'status' => 'pending']);

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->postJson('/api/v1/support/requests', ['question' => 'câu hỏi của tôi'])->assertCreated();

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->getJson('/api/v1/support/requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.question', 'câu hỏi của tôi');
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v1/support/requests', ['question' => 'x'])->assertStatus(401);
    }
}
