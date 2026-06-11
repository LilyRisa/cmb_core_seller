<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * A5 — extension PAT scoped to `copy-product:push`, không hết hạn.
 *
 * Token cấp qua SPA session chỉ mang ability `copy-product:push` ⇒ gọi được route
 * tạo sản phẩm (gate `abilities:copy-product:push`) nhưng bị 403 ở các endpoint khác
 * có gate ability (vd GET /orders cần `orders:read`).
 */
class ExtensionTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_mints_a_non_expiring_token_limited_to_copy_product_push(): void
    {
        [$user, $tenant] = $this->userWithTenant();

        $response = $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->postJson('/api/v1/extension-tokens', ['name' => 'My Chrome'])
            ->assertOk();

        $plain = $response->json('data.token');
        $this->assertNotEmpty($plain);
        $this->assertNotNull($response->json('data.id'));

        $token = PersonalAccessToken::findToken($plain);
        $this->assertNotNull($token);
        $this->assertSame(['copy-product:push'], $token->abilities);
        // Non-expiring: createToken không truyền expiresAt + sanctum.expiration = null.
        $this->assertNull($token->expires_at);
        $this->assertNull(config('sanctum.expiration'));
        $this->assertSame('My Chrome', $token->name);
    }

    public function test_destroy_revokes_only_own_token(): void
    {
        [$user, $tenant] = $this->userWithTenant();
        $own = $user->createToken('ext', ['copy-product:push'])->accessToken;

        $this->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->deleteJson('/api/v1/extension-tokens/'.$own->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $own->getKey()]);
    }

    public function test_blocks_a_copy_push_token_from_reading_orders(): void
    {
        [$user, $tenant] = $this->userWithTenant();
        $plain = $user->createToken('ext', ['copy-product:push'])->plainTextToken;

        // Token hẹp xác thực OK nhưng thiếu `orders:read` ⇒ 403 (MissingAbilityException).
        $this->withToken($plain)
            ->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson('/api/v1/orders')
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Tenant}
     */
    protected function userWithTenant(Role $role = Role::Owner): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::create(['name' => 'Shop '.uniqid()]);
        $tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return [$user, $tenant];
    }
}
