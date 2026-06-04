<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoExclusionTemplateApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
        $this->tenant = Tenant::create(['name' => 'AdShop']);
    }

    private function owner(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => Role::Owner->value]);

        return $u;
    }

    /** @return array<string,string> */
    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_create_list_and_delete_template(): void
    {
        $owner = $this->owner();
        app(CurrentTenant::class)->set($this->tenant);

        $id = $this->actingAs($owner)->withHeaders($this->h())->postJson('/api/v1/marketing/exclusion-templates', [
            'name' => 'VN noi thanh',
            'payload' => [['key' => '1006824', 'name' => 'Hanoi', 'type' => 'city']],
        ])->assertCreated()->json('data.id');

        $this->actingAs($owner)->withHeaders($this->h())->getJson('/api/v1/marketing/exclusion-templates')
            ->assertOk()->assertJsonPath('data.0.name', 'VN noi thanh')
            ->assertJsonPath('data.0.payload.0.type', 'city');

        $this->actingAs($owner)->withHeaders($this->h())->deleteJson("/api/v1/marketing/exclusion-templates/{$id}")->assertNoContent();
        $this->actingAs($owner)->withHeaders($this->h())->getJson('/api/v1/marketing/exclusion-templates')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_template_isolated_per_tenant(): void
    {
        $owner = $this->owner();
        app(CurrentTenant::class)->set($this->tenant);
        $this->actingAs($owner)->withHeaders($this->h())->postJson('/api/v1/marketing/exclusion-templates', [
            'name' => 'OnlyMine', 'payload' => [],
        ])->assertCreated();

        // a second tenant sees none
        $other = Tenant::create(['name' => 'Other']);
        $u2 = User::factory()->create(['email_verified_at' => now()]);
        $other->users()->attach($u2->getKey(), ['role' => Role::Owner->value]);
        $this->actingAs($u2)->withHeaders(['X-Tenant-Id' => (string) $other->getKey()])
            ->getJson('/api/v1/marketing/exclusion-templates')->assertOk()->assertJsonCount(0, 'data');
    }
}
