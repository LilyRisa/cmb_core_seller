<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\ShippingLabelTemplateService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingLabelTemplateSetDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_default_clears_other_defaults_in_same_tenant(): void
    {
        $t = Tenant::factory()->create();
        $a = $this->makeTpl($t->id, 'A', true);
        $b = $this->makeTpl($t->id, 'B', false);

        app(ShippingLabelTemplateService::class)->setDefault($t->id, $b->id);

        $this->assertFalse($a->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_default);
    }

    public function test_set_default_does_not_affect_other_tenants(): void
    {
        $t1 = Tenant::factory()->create();
        $t2 = Tenant::factory()->create();
        $a1 = $this->makeTpl($t1->id, 'A', true);
        $a2 = $this->makeTpl($t2->id, 'A', true);
        $b1 = $this->makeTpl($t1->id, 'B', false);

        app(ShippingLabelTemplateService::class)->setDefault($t1->id, $b1->id);

        $this->assertFalse($a1->fresh()->is_default);
        $this->assertTrue($b1->fresh()->is_default);
        $this->assertTrue($a2->fresh()->is_default);   // cross-tenant untouched
    }

    public function test_duplicate_creates_copy_with_suffix(): void
    {
        $t = Tenant::factory()->create();
        $a = $this->makeTpl($t->id, 'Tem A', false);

        $copy = app(ShippingLabelTemplateService::class)->duplicate($t->id, $a->id, /*createdBy*/ null);

        $this->assertSame('Tem A (copy)', $copy->name);
        $this->assertFalse($copy->is_default);
        $this->assertSame($a->schema, $copy->schema);
    }

    private function makeTpl(int $tenantId, string $name, bool $isDefault): ShippingLabelTemplate
    {
        return ShippingLabelTemplate::create([
            'tenant_id' => $tenantId, 'name' => $name,
            'paper' => 'A6', 'paper_w_mm' => 105, 'paper_h_mm' => 148,
            'schema_version' => 1, 'schema' => ['fields' => []],
            'is_default' => $isDefault, 'created_by' => null,
        ]);
    }
}
