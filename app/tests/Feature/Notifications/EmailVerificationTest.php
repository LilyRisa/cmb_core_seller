<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Notifications\VerifyEmailNotification;
use CMBcoreSeller\Modules\Notifications\Notifications\WelcomeNotification;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * SPEC 0022 — email verification flow.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_dispatches_verify_email_notification(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_name' => 'Shop A',
        ])->assertCreated()->assertJsonPath('data.email_verified_at', null);

        $user = User::where('email', 'a@example.com')->first();
        $this->assertNotNull($user);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_verify_endpoint_marks_user_verified_and_fires_event(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create();
        $url = $this->signedVerifyUrl($user);

        $response = $this->get($url);

        $response->assertRedirect();
        $this->assertStringContainsString('status=success', $response->headers->get('Location'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_verify_with_invalid_hash_does_not_verify(): void
    {
        $user = User::factory()->unverified()->create();
        $badUrl = URL::temporarySignedRoute('api.v1.auth.email.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1('wrong@example.com'),
        ]);

        $response = $this->get($badUrl);

        $response->assertRedirect();
        $this->assertStringContainsString('status=invalid', $response->headers->get('Location'));
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verify_with_expired_signature_redirects_invalid(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('api.v1.auth.email.verify', now()->subMinutes(5), [
            'id' => $user->getKey(),
            'hash' => sha1((string) $user->email),
        ]);

        $response = $this->get($url);

        $response->assertRedirect();
        $this->assertStringContainsString('status=invalid', $response->headers->get('Location'));
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verify_second_click_redirects_already(): void
    {
        $user = User::factory()->create();   // already verified
        $url = $this->signedVerifyUrl($user);

        $response = $this->get($url);

        $response->assertRedirect();
        $this->assertStringContainsString('status=already', $response->headers->get('Location'));
    }

    public function test_resend_dispatches_when_not_verified(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/email/verify/resend')
            ->assertOk()
            ->assertJsonPath('data.sent', true);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_resend_noop_when_already_verified(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/email/verify/resend')
            ->assertOk()
            ->assertJsonPath('data.sent', false)
            ->assertJsonPath('data.reason', 'already_verified');

        Notification::assertNothingSent();
    }

    public function test_verified_middleware_blocks_unverified_from_tenant_routes(): void
    {
        $user = User::factory()->unverified()->create();
        $tenant = Tenant::create(['name' => 'Shop Test']);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson('/api/v1/tenant')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_verified_user_passes_tenant_routes(): void
    {
        $user = User::factory()->create();   // verified by default
        $tenant = Tenant::create(['name' => 'Shop Test']);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson('/api/v1/tenant')
            ->assertOk();
    }

    public function test_welcome_notification_fires_on_verified_event(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        event(new Verified($user));

        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_me_endpoint_exposes_email_verified_at_field(): void
    {
        $verified = User::factory()->create();
        $unverified = User::factory()->unverified()->create();

        $this->actingAs($verified)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonStructure(['data' => ['email_verified_at']]);

        $this->actingAs($unverified)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email_verified_at', null);
    }

    protected function signedVerifyUrl(User $user): string
    {
        return URL::temporarySignedRoute('api.v1.auth.email.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1((string) $user->email),
        ]);
    }
}
