<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdAuthoringPreviewApiTest extends TestCase
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

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(): AdAccount
    {
        app(CurrentTenant::class)->set($this->tenant);

        return AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
    }

    public function test_previews_returns_iframe_per_format(): void
    {
        Http::fake(['graph.facebook.com/*/generatepreviews*' => Http::response(['data' => [['body' => '<iframe></iframe>']]], 200)]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$acc->id}/ad-previews", [
                'creative' => ['page_id' => '123', 'link_data' => ['message' => 'Hi']],
                'formats' => ['DESKTOP_FEED_STANDARD'],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.format', 'DESKTOP_FEED_STANDARD')
            ->assertJsonPath('data.0.body', '<iframe></iframe>');
    }
}
