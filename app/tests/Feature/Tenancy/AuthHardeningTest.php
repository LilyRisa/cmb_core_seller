<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Modules\Tenancy\Services\CaptchaVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Auth hardening (SPEC 2026-06-10): CAPTCHA Turnstile + chặn email dùng-một-lần.
 */
class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function strongReg(array $over = []): array
    {
        return array_merge([
            'name' => 'Shop', 'email' => 'real@gmail.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ], $over);
    }

    public function test_register_rejects_disposable_email(): void
    {
        $this->postJson('/api/v1/auth/register', $this->strongReg(['email' => 'bot@mailinator.com']))
            ->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'bot@mailinator.com']);
    }

    public function test_captcha_config_endpoint_exposes_site_key(): void
    {
        config(['captcha.enabled' => true, 'captcha.site_key' => 'SITE123']);
        $this->getJson('/api/v1/auth/captcha-config')->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.site_key', 'SITE123');
    }

    public function test_captcha_blocks_register_when_enabled_without_token(): void
    {
        config(['captcha.enabled' => true, 'captcha.secret' => 'SECRET']);
        $this->postJson('/api/v1/auth/register', $this->strongReg())
            ->assertStatus(422)->assertJsonPath('error.code', 'CAPTCHA_FAILED');
        $this->assertDatabaseMissing('users', ['email' => 'real@gmail.com']);
    }

    public function test_captcha_passes_register_with_valid_token(): void
    {
        config(['captcha.enabled' => true, 'captcha.secret' => 'SECRET']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true], 200)]);

        $this->postJson('/api/v1/auth/register', $this->strongReg(['captcha_token' => 'good']))
            ->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'real@gmail.com']);
    }

    public function test_verifier_passes_through_when_disabled(): void
    {
        config(['captcha.enabled' => false]);
        $this->assertTrue(app(CaptchaVerifier::class)->verify(null));
    }

    public function test_verifier_fails_on_unsuccessful_response(): void
    {
        config(['captcha.enabled' => true, 'captcha.secret' => 'SECRET']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false], 200)]);
        $this->assertFalse(app(CaptchaVerifier::class)->verify('bad', '1.2.3.4'));
    }
}
