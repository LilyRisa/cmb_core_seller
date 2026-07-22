<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Events\ProTrialActivated;
use CMBcoreSeller\Modules\Notifications\Notifications\ProTrialActivatedNotification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProTrialActivatedEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_trial_activated_event_emails_the_tenant_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $grantedAt = Carbon::now();
        $expiresAt = $grantedAt->copy()->addDays(30);
        event(new ProTrialActivated($tenant->getKey(), $grantedAt, $expiresAt));

        Notification::assertSentTo(
            $owner,
            ProTrialActivatedNotification::class,
            fn ($n) => $n->expiresAt->isSameDay($expiresAt),
        );
    }

    public function test_no_owner_no_crash(): void
    {
        Notification::fake();

        $tenant = Tenant::create(['name' => 'Shop No Owner']);
        $grantedAt = Carbon::now();

        event(new ProTrialActivated($tenant->getKey(), $grantedAt, $grantedAt->copy()->addDays(30)));

        Notification::assertNothingSent();
    }
}
