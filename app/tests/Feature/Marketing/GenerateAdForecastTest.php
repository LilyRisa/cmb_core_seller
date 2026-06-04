<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Marketing\Jobs\GenerateAdForecast;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Notifications\MarketingForecastReadyNotification;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GenerateAdForecastTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_and_emails_owner_and_admin_only(): void
    {
        Notification::fake();
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $account = AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'X']);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $staff = User::factory()->create(['email_verified_at' => now()]);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $tenant->users()->attach($admin->getKey(), ['role' => Role::Admin->value]);
        $tenant->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);

        (new GenerateAdForecast($account->id))->handle();

        $this->assertDatabaseHas('ad_forecasts', ['ad_account_id' => $account->id]);
        Notification::assertSentTo($owner, MarketingForecastReadyNotification::class);
        Notification::assertSentTo($admin, MarketingForecastReadyNotification::class);
        Notification::assertNotSentTo($staff, MarketingForecastReadyNotification::class);
    }
}
