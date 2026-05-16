<?php

namespace Tests\Feature\Notifications;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Notifications\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * SPEC 0022 — password reset flow.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_dispatches_reset_notification_for_existing_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'has@example.com']);

        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'has@example.com'])
            ->assertOk()
            ->assertJsonPath('data.sent', true);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_returns_generic_response_for_missing_user(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'ghost@example.com'])
            ->assertOk()
            ->assertJsonPath('data.sent', true);   // generic — không xác nhận user tồn tại

        Notification::assertNothingSent();
    }

    public function test_forgot_validates_email_format(): void
    {
        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_reset_with_valid_token_changes_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'new-password-99',
            'password_confirmation' => 'new-password-99',
        ])->assertOk()->assertJsonPath('data.reset', true);

        $this->assertTrue(Hash::check('new-password-99', $user->fresh()->password));
    }

    public function test_reset_with_invalid_token_returns_error(): void
    {
        User::factory()->create(['email' => 'reset2@example.com']);

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => 'reset2@example.com',
            'token' => 'not-a-real-token',
            'password' => 'new-password-99',
            'password_confirmation' => 'new-password-99',
        ])->assertStatus(422)->assertJsonPath('error.code', 'INVALID_RESET_TOKEN');
    }

    public function test_reset_rejects_weak_password(): void
    {
        User::factory()->create(['email' => 'reset3@example.com']);

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => 'reset3@example.com',
            'token' => 'irrelevant',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_reset_rejects_unconfirmed_password(): void
    {
        User::factory()->create(['email' => 'reset4@example.com']);

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => 'reset4@example.com',
            'token' => 'irrelevant',
            'password' => 'good-password-1',
            'password_confirmation' => 'different-password-1',
        ])->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }
}
