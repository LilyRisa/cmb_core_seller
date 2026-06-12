<?php

namespace Tests\Feature\Auth;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Đăng nhập Chrome Extension qua redirect OAuth (EXTENSION_OAUTH_LOGIN_CONTRACT):
 * `GET /extension/connect` → mint token hẹp + 302 token ở fragment. Giữ luật verify email.
 */
class ExtensionConnectTest extends TestCase
{
    use RefreshDatabase;

    private const CB = 'https://abcdefghijklmnopabcdefghijklmnop.chromiumapp.org/';

    private function userWithTenant(bool $verified = true): User
    {
        $user = User::factory()->create(['email_verified_at' => $verified ? now() : null]);
        $tenant = Tenant::create(['name' => 'Shop']);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        return $user;
    }

    public function test_invalid_redirect_uri_is_rejected_with_400(): void
    {
        $user = $this->userWithTenant();

        $this->actingAs($user)
            ->get('/extension/connect?redirect_uri='.urlencode('https://evil.com/steal').'&state=x')
            ->assertStatus(400);
    }

    public function test_guest_is_redirected_to_login_with_return_path(): void
    {
        $res = $this->get('/extension/connect?redirect_uri='.urlencode(self::CB).'&state=x');

        $res->assertRedirect();
        $location = $res->headers->get('Location');
        $this->assertStringContainsString('/login?redirect=', (string) $location);
        $this->assertStringContainsString('extension', urldecode((string) $location));
    }

    public function test_unverified_user_is_sent_to_verify_not_given_a_token(): void
    {
        $user = $this->userWithTenant(verified: false);

        $res = $this->actingAs($user)
            ->get('/extension/connect?redirect_uri='.urlencode(self::CB).'&state=x');

        $res->assertRedirect();
        $location = (string) $res->headers->get('Location');
        // Về SPA để verify — KHÔNG mint token, KHÔNG 302 thẳng tới callback extension.
        $this->assertStringContainsString('redirect=', $location);
        $this->assertStringNotContainsString('#token=', $location);
        $this->assertStringStartsNotWith(self::CB, $location);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_verified_user_gets_token_and_fragment_redirect(): void
    {
        $user = $this->userWithTenant();

        $res = $this->actingAs($user)
            ->get('/extension/connect?redirect_uri='.urlencode(self::CB).'&state=nonce-123');

        $res->assertRedirect();
        $location = (string) $res->headers->get('Location');

        // 302 về callback extension, token ở FRAGMENT (#) không phải query (?).
        $this->assertStringStartsWith(self::CB.'#', $location);
        $fragment = substr($location, strpos($location, '#') + 1);
        parse_str($fragment, $parts);

        $this->assertArrayHasKey('token', $parts);
        $this->assertNotEmpty($parts['token']);
        $this->assertArrayHasKey('token_id', $parts);
        $this->assertArrayHasKey('tenant_id', $parts);
        $this->assertSame('nonce-123', $parts['state']);

        // Token hẹp: chỉ copy-product:push, không phải `*`.
        $token = $user->tokens()->first();
        $this->assertNotNull($token);
        $this->assertTrue($token->can('copy-product:push'));
        $this->assertFalse($token->can('orders:read'));
    }
}
