<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCampaignApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'AdShop']);
        $this->bindAi();
    }

    private function bindAi(): void
    {
        $this->app->instance(MarketingAnalysisClient::class, new class implements MarketingAnalysisClient
        {
            public function analyze(array $data, string $instruction, ?string $schema = null, ?\Closure $fallback = null, ?int $tenantId = null): array
            {
                return ['payload' => [
                    'campaign' => ['budget_mode' => 'adset'],
                    'adsets' => [[
                        'name' => 'AI Nhóm', 'budget' => ['daily_major' => 150000],
                        'targeting' => ['geo_locations' => ['countries' => ['VN']], 'age_min' => 20, 'age_max' => 45],
                        'placement_config' => ['automatic' => true],
                        'ads' => [['name' => 'AI QC', 'creative' => ['cta' => 'MESSAGE_PAGE']]],
                    ]],
                    'recommendations' => ['Theo dõi CPR 3 ngày rồi scale ngân sách 30%'],
                ], 'provider_code' => 'stub', 'model' => 'm'];
            }
        });
    }

    private function user(Role $role): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => $role->value]);

        return $u;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(): AdAccount
    {
        app(CurrentTenant::class)->set($this->tenant);

        return AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'X']);
    }

    public function test_owner_generates_ai_campaign_draft_with_recommendations(): void
    {
        $acc = $this->account();

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$acc->id}/ai-campaign", [
                'page_id' => '655064411022030', 'page_post_id' => '655064411022030_122',
                'objective' => 'messages', 'mode' => 'test', 'placement_mode' => 'advantage_plus',
                'prompt' => 'Chạy test bài này', 'caption' => 'Sale lớn', 'likes' => 12, 'comments' => 3, 'shares' => 1,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.objective', 'messages')
            ->assertJsonStructure(['data' => ['id', 'payload'], 'meta' => ['recommendations']]);
    }

    public function test_staff_forbidden(): void
    {
        $acc = $this->account();

        $this->actingAs($this->user(Role::StaffOrder))->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$acc->id}/ai-campaign", [
                'page_id' => 'P', 'page_post_id' => 'P_1', 'objective' => 'messages',
                'mode' => 'test', 'placement_mode' => 'advantage_plus',
            ])
            ->assertForbidden();
    }

    public function test_conversions_requires_pixel(): void
    {
        $acc = $this->account();

        $this->actingAs($this->user(Role::Owner))->withHeaders($this->h())
            ->postJson("/api/v1/marketing/ad-accounts/{$acc->id}/ai-campaign", [
                'page_id' => 'P', 'page_post_id' => 'P_1', 'objective' => 'conversions',
                'mode' => 'test', 'placement_mode' => 'advantage_plus',
                // thiếu pixel_id + conversion_event ⇒ 422 validate
            ])
            ->assertStatus(422);
    }
}
