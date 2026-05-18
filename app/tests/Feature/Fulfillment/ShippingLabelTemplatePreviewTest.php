<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Support\GotenbergClient;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShippingLabelTemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_saved_template_returns_url(): void
    {
        $t = Tenant::factory()->create();
        $u = User::factory()->create();
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);
        $tpl = ShippingLabelTemplate::create(['tenant_id' => $t->id, 'name' => 'A6', 'paper' => 'A6',
            'paper_w_mm' => 105, 'paper_h_mm' => 148, 'schema_version' => 1,
            'schema' => ['fields' => []], 'is_default' => false]);

        $g = $this->createMock(GotenbergClient::class);
        $g->method('htmlToPdf')->willReturn('PDF');
        $this->app->instance(GotenbergClient::class, $g);
        $m = $this->createMock(MediaUploader::class);
        $m->method('storeBytes')->willReturn(['url' => 'https://r2/preview.pdf', 'path' => 'p']);
        $this->app->instance(MediaUploader::class, $m);

        Sanctum::actingAs($u);
        $this->withHeader('X-Tenant-Id', (string) $t->id)
             ->postJson("/api/v1/shipping-label-templates/{$tpl->id}/preview", ['sample_profile' => 'one_item_short_address'])
             ->assertOk()->assertJsonPath('data.url', 'https://r2/preview.pdf');
    }

    public function test_preview_rejects_unknown_sample_profile(): void
    {
        $t = Tenant::factory()->create();
        $u = User::factory()->create();
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);
        $tpl = ShippingLabelTemplate::create(['tenant_id' => $t->id, 'name' => 'A6', 'paper' => 'A6',
            'paper_w_mm' => 105, 'paper_h_mm' => 148, 'schema_version' => 1,
            'schema' => ['fields' => []], 'is_default' => false]);
        Sanctum::actingAs($u);
        $this->withHeader('X-Tenant-Id', (string) $t->id)
             ->postJson("/api/v1/shipping-label-templates/{$tpl->id}/preview", ['sample_profile' => 'invalid'])
             ->assertStatus(422);
    }

    public function test_preview_inline_works_with_unsaved_schema(): void
    {
        $t = Tenant::factory()->create();
        $u = User::factory()->create();
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);
        $g = $this->createMock(GotenbergClient::class);
        $g->method('htmlToPdf')->willReturn('PDF');
        $this->app->instance(GotenbergClient::class, $g);
        $m = $this->createMock(MediaUploader::class);
        $m->method('storeBytes')->willReturn(['url' => 'https://r2/p.pdf', 'path' => 'p']);
        $this->app->instance(MediaUploader::class, $m);

        Sanctum::actingAs($u);
        $this->withHeader('X-Tenant-Id', (string) $t->id)
             ->postJson('/api/v1/shipping-label-templates/preview', [
                 'paper' => 'A6', 'paper_w_mm' => 105, 'paper_h_mm' => 148,
                 'schema' => ['fields' => [['id' => 'a', 'type' => 'text', 'x' => 5, 'y' => 5,
                     'w' => 50, 'h' => 6, 'text' => 'OK', 'style' => ['fontSize' => 11]]]],
                 'sample_profile' => 'one_item_short_address',
             ])->assertOk()->assertJsonPath('data.url', 'https://r2/p.pdf');
    }
}
