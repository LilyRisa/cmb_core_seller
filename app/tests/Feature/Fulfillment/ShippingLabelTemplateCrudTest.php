<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShippingLabelTemplateCrudTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $owner;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->owner = User::factory()->create();
        $this->viewer = User::factory()->create();
        $this->tenant->users()->attach($this->owner->id, ['role' => Role::Owner->value]);
        $this->tenant->users()->attach($this->viewer->id, ['role' => Role::Viewer->value]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tem A6 chuẩn',
            'paper' => 'A6', 'paper_w_mm' => 105, 'paper_h_mm' => 148,
            'schema' => ['fields' => [
                ['id' => 'a', 'type' => 'text', 'x' => 5, 'y' => 5, 'w' => 50, 'h' => 6,
                 'text' => 'Shop', 'style' => ['fontSize' => 11]],
            ]],
        ], $overrides);
    }

    public function test_owner_can_create_template(): void
    {
        Sanctum::actingAs($this->owner);
        $this->withHeader('X-Tenant-Id', (string) $this->tenant->id)
             ->postJson('/api/v1/shipping-label-templates', $this->payload())
             ->assertCreated()->assertJsonPath('data.name', 'Tem A6 chuẩn');
    }

    public function test_viewer_cannot_create_template(): void
    {
        Sanctum::actingAs($this->viewer);
        $this->withHeader('X-Tenant-Id', (string) $this->tenant->id)
             ->postJson('/api/v1/shipping-label-templates', $this->payload())
             ->assertForbidden();
    }

    public function test_viewer_can_list_templates(): void
    {
        ShippingLabelTemplate::create($this->payload() + ['tenant_id' => $this->tenant->id, 'schema_version' => 1, 'is_default' => false]);
        Sanctum::actingAs($this->viewer);
        $this->withHeader('X-Tenant-Id', (string) $this->tenant->id)
             ->getJson('/api/v1/shipping-label-templates')
             ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_create_rejects_overflow_field(): void
    {
        Sanctum::actingAs($this->owner);
        $payload = $this->payload();
        $payload['schema']['fields'][0]['w'] = 200;
        $resp = $this->withHeader('X-Tenant-Id', (string) $this->tenant->id)
             ->postJson('/api/v1/shipping-label-templates', $payload)
             ->assertStatus(422);
        $this->assertArrayHasKey('schema.fields.0.w', $resp->json('error.details'));
    }

    public function test_create_rejects_duplicate_name(): void
    {
        ShippingLabelTemplate::create($this->payload() + ['tenant_id' => $this->tenant->id, 'schema_version' => 1, 'is_default' => false]);
        Sanctum::actingAs($this->owner);
        $this->withHeader('X-Tenant-Id', (string) $this->tenant->id)
             ->postJson('/api/v1/shipping-label-templates', $this->payload())
             ->assertStatus(422);
    }

    public function test_destroy_soft_deletes(): void
    {
        $tpl = ShippingLabelTemplate::create($this->payload() + ['tenant_id' => $this->tenant->id, 'schema_version' => 1, 'is_default' => false]);
        Sanctum::actingAs($this->owner);
        $this->withHeader('X-Tenant-Id', (string) $this->tenant->id)
             ->deleteJson('/api/v1/shipping-label-templates/'.$tpl->id)
             ->assertOk();
        $this->assertSoftDeleted('shipping_label_templates', ['id' => $tpl->id]);
    }

    public function test_cross_tenant_access_returns_404(): void
    {
        $other = Tenant::factory()->create();
        $tpl = ShippingLabelTemplate::create($this->payload() + ['tenant_id' => $other->id, 'schema_version' => 1, 'is_default' => false]);
        Sanctum::actingAs($this->owner);
        $this->withHeader('X-Tenant-Id', (string) $this->tenant->id)
             ->getJson('/api/v1/shipping-label-templates/'.$tpl->id)
             ->assertNotFound();
    }
}
