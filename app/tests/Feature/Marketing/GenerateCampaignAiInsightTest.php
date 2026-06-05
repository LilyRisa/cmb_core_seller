<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Jobs\GenerateCampaignAiInsight;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Notifications\MarketingCampaignInsightReadyNotification;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GenerateCampaignAiInsightTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_and_emails_owner_and_admin_only(): void
    {
        Notification::fake();
        // Stub the AI client so no real call is made.
        $this->app->instance(MarketingAnalysisClient::class, new class implements MarketingAnalysisClient
        {
            public function analyze(array $data, string $instruction, ?string $schema = null, ?\Closure $fallback = null): array
            {
                return ['payload' => ['summary' => 'ổn'], 'provider_code' => 'fake', 'model' => 'fake-1'];
            }
        });

        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'X']);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $staff = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $tenant->users()->attach($admin->getKey(), ['role' => Role::Admin->value]);
        $tenant->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);

        (new GenerateCampaignAiInsight($account->id, 'C1', ['days' => 7, 'metrics' => ['spend'], 'include_engagement' => false]))->handle();

        $this->assertDatabaseHas('campaign_ai_insights', ['ad_account_id' => $account->id, 'campaign_external_id' => 'C1']);
        Notification::assertSentTo($owner, MarketingCampaignInsightReadyNotification::class);
        Notification::assertSentTo($admin, MarketingCampaignInsightReadyNotification::class);
        Notification::assertNotSentTo($staff, MarketingCampaignInsightReadyNotification::class);
    }
}
