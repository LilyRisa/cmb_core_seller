<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task B1: phong cách chốt sale toàn shop (`sales_closing_style` +
 * `sales_closing_note`) lưu trong `messaging_settings.settings` (merge, không
 * ghi đè các khoá khác). Setup theo cùng khuôn với MessagingAutoModeTest.
 */
class SalesClosingStyleSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'SalesClosingShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_BUSINESS)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    private function owner(): User
    {
        return $this->owner;
    }

    private function tenantHeader(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_saves_and_returns_sales_closing_style(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->withHeaders($this->tenantHeader())
            ->patchJson('/api/v1/messaging/settings', ['sales_closing_style' => 'fast_close', 'sales_closing_note' => 'Nhấn freeship'])
            ->assertOk();

        $this->actingAs($owner)->withHeaders($this->tenantHeader())
            ->getJson('/api/v1/messaging/settings')
            ->assertOk()
            ->assertJsonPath('data.sales_closing_style', 'fast_close')
            ->assertJsonPath('data.sales_closing_note', 'Nhấn freeship');
    }

    public function test_rejects_invalid_style(): void
    {
        $this->actingAs($this->owner())->withHeaders($this->tenantHeader())
            ->patchJson('/api/v1/messaging/settings', ['sales_closing_style' => 'bogus'])
            ->assertStatus(422);
    }
}
