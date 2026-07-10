<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTermsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_checkout_requires_terms_accepted(): void
    {
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly', 'gateway' => 'sepay',
                'terms_accepted' => false, 'terms_version' => 'refund-v1',
            ])->assertStatus(422);
    }

    public function test_checkout_records_terms_in_invoice_meta(): void
    {
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/billing/checkout', [
                'plan_code' => Plan::CODE_PRO, 'cycle' => 'monthly', 'gateway' => 'sepay',
                'terms_accepted' => true, 'terms_version' => 'refund-v1',
            ])->assertCreated();

        $code = $resp->json('data.invoice.code');
        $meta = Invoice::query()->where('code', $code)->value('meta');
        $this->assertSame('refund-v1', $meta['terms_version'] ?? null);
        $this->assertNotNull($meta['terms_accepted_at'] ?? null);
    }
}
