<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Support\AccountHealth;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdAccountHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_connector_reads_account_status_and_disable_reason(): void
    {
        Http::fake(['graph.facebook.com/*/act_1*' => Http::response(['account_status' => 2, 'disable_reason' => 1], 200)]);

        $health = (new FacebookAdsConnector(['graph_version' => 'v19.0']))->fetchAccountStatus('tok', 'act_1');

        $this->assertSame(2, $health['account_status']);
        $this->assertSame(1, $health['disable_reason']);
    }

    public function test_health_mapping_labels_and_severity(): void
    {
        $this->assertSame('ok', AccountHealth::describe(1, 0)['severity']);

        $disabled = AccountHealth::describe(2, 1); // disabled + ads policy violation
        $this->assertSame('error', $disabled['severity']);
        $this->assertStringContainsString('vô hiệu hoá', $disabled['label']);
        $this->assertStringContainsString('Vi phạm chính sách', $disabled['label']);

        $unsettled = AccountHealth::describe(3, 0); // unpaid
        $this->assertSame('error', $unsettled['severity']);
        $this->assertStringContainsString('Chưa thanh toán', $unsettled['label']);

        $this->assertNull(AccountHealth::describe(null, null));
    }

    public function test_accounts_endpoint_exposes_health(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND',
            'status' => 'active', 'access_token' => 'T', 'fb_account_status' => 3, 'disable_reason' => 0,
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($user)->withHeaders(['X-Tenant-Id' => (string) $tenant->id])
            ->getJson('/api/v1/marketing/ad-accounts')
            ->assertOk()
            ->assertJsonPath('data.0.fb_account_status', 3)
            ->assertJsonPath('data.0.health.severity', 'error')
            ->assertJsonPath('data.0.health.ok', false);
    }
}
