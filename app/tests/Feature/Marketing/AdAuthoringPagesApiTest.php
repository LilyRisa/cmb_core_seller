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

class AdAuthoringPagesApiTest extends TestCase
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

        return AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'USERTOK']);
    }

    public function test_pages_lists_id_name_without_token(): void
    {
        Http::fake(['graph.facebook.com/*/me/accounts*' => Http::response([
            'data' => [['id' => '123', 'name' => 'Shop', 'access_token' => 'PAGETOK']],
        ], 200)]);
        $acc = $this->account();

        $res = $this->actingAs($this->owner())->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$acc->id}/pages")
            ->assertOk()
            ->assertJsonPath('data.0.id', '123')
            ->assertJsonPath('data.0.name', 'Shop');

        $this->assertStringNotContainsString('PAGETOK', $res->getContent());
    }

    public function test_page_posts_returns_engagement_and_media(): void
    {
        Http::fake([
            'graph.facebook.com/*/me/accounts*' => Http::response(['data' => [['id' => '123', 'name' => 'Shop', 'access_token' => 'PAGETOK']]], 200),
            'graph.facebook.com/*/123/published_posts*' => Http::response(['data' => [[
                'id' => '123_456', 'message' => 'Sale', 'created_time' => '2026-06-01T00:00:00+0000',
                'full_picture' => 'https://img', 'attachments' => ['data' => [['media_type' => 'share', 'target' => ['url' => 'https://shop.example/sale']]]],
                'call_to_action' => ['type' => 'SHOP_NOW', 'value' => ['link' => 'https://shop.example/sale']],
                'likes' => ['summary' => ['total_count' => 1200]],
                'comments' => ['summary' => ['total_count' => 89]],
                'shares' => ['count' => 45],
            ]]], 200),
        ]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$acc->id}/pages/123/posts")
            ->assertOk()
            ->assertJsonPath('data.0.id', '123_456')
            ->assertJsonPath('data.0.likes', 1200)
            ->assertJsonPath('data.0.comments', 89)
            ->assertJsonPath('data.0.shares', 45)
            ->assertJsonPath('data.0.media_type', 'share')
            ->assertJsonPath('data.0.image_url', 'https://img')
            ->assertJsonPath('data.0.link_url', 'https://shop.example/sale')
            ->assertJsonPath('data.0.cta_type', 'SHOP_NOW');
    }

    public function test_page_posts_404_for_unknown_page(): void
    {
        Http::fake(['graph.facebook.com/*/me/accounts*' => Http::response(['data' => []], 200)]);
        $acc = $this->account();

        $this->actingAs($this->owner())->withHeaders($this->h())
            ->getJson("/api/v1/marketing/ad-accounts/{$acc->id}/pages/999/posts")
            ->assertStatus(404);
    }
}
