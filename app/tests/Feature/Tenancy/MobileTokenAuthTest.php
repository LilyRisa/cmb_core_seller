<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Mobile token auth (SPEC 2026-06-01): Sanctum bearer token cho app mobile.
 * Cùng guard `auth:sanctum` phục vụ cả SPA cookie lẫn token ⇒ token dùng được
 * mọi endpoint nghiệp vụ hiện có.
 */
class MobileTokenAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_endpoint_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'm@example.com', 'password' => Hash::make('secret123')]);

        $this->postJson('/api/v1/auth/token', [
            'email' => 'm@example.com',
            'password' => 'wrong',
            'device_name' => 'iPhone 15',
        ])->assertStatus(422)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_endpoint_requires_device_name(): void
    {
        User::factory()->create(['email' => 'm@example.com', 'password' => Hash::make('secret123')]);

        $this->postJson('/api/v1/auth/token', [
            'email' => 'm@example.com',
            'password' => 'secret123',
        ])->assertStatus(422);
    }

    public function test_token_endpoint_issues_token_expiring_in_60_days(): void
    {
        [$user, $tenant] = $this->userWithTenant(Role::Owner);
        $user->forceFill(['email' => 'm@example.com', 'password' => Hash::make('secret123')])->save();

        $response = $this->postJson('/api/v1/auth/token', [
            'email' => 'm@example.com',
            'password' => 'secret123',
            'device_name' => 'iPhone 15',
        ])->assertCreated()
            ->assertJsonPath('data.user.email', 'm@example.com')
            ->assertJsonPath('data.user.tenants.0.id', $tenant->getKey());

        $this->assertNotEmpty($response->json('data.token'));

        $token = PersonalAccessToken::first();
        $this->assertSame('iPhone 15', $token->name);
        $this->assertNotNull($token->expires_at);
        // ~60 ngày (cho phép lệch 1 ngày do thời điểm chạy test).
        $this->assertEqualsWithDelta(60, abs($token->expires_at->diffInDays(now())), 1);
    }

    public function test_token_payload_includes_tenant_permissions_for_role(): void
    {
        // SPEC 0029 — mỗi tenant trong user payload phải kèm `permissions` (ability strings).
        $user = User::factory()->create(['email' => 'wh@example.com', 'password' => Hash::make('secret123')]);
        $tenant = Tenant::create(['name' => 'Warehouse Shop']);
        $tenant->users()->attach($user->getKey(), ['role' => Role::StaffWarehouse->value]);

        $response = $this->postJson('/api/v1/auth/token', [
            'email' => 'wh@example.com',
            'password' => 'secret123',
            'device_name' => 'iPhone 15',
        ])->assertCreated();

        $perms = $response->json('data.user.tenants.0.permissions');
        $this->assertIsArray($perms);
        $this->assertContains('fulfillment.scan', $perms);
        $this->assertContains('inventory.adjust', $perms);
        // Quyền không thuộc role kho ⇒ không có.
        $this->assertNotContains('billing.manage', $perms);
    }

    public function test_token_payload_permissions_wildcard_for_owner(): void
    {
        // Owner (Role::permissions() = ['*']) ⇒ permissions trả ['*'].
        $user = User::factory()->create(['email' => 'own@example.com', 'password' => Hash::make('secret123')]);
        $tenant = Tenant::create(['name' => 'Owner Shop']);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value]);

        $this->postJson('/api/v1/auth/token', [
            'email' => 'own@example.com',
            'password' => 'secret123',
            'device_name' => 'iPhone 15',
        ])->assertCreated()
            ->assertJsonPath('data.user.tenants.0.permissions', ['*']);
    }

    public function test_me_payload_includes_tenant_permissions(): void
    {
        // SPEC 0029 — flow tương tự cho GET /auth/me.
        $user = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::create(['name' => 'CS Shop']);
        $tenant->users()->attach($user->getKey(), ['role' => Role::StaffCs->value]);

        $perms = $this->actingAs($user)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->json('data.tenants.0.permissions');

        $this->assertContains('messaging.reply', $perms);
    }

    public function test_bearer_token_authenticates_existing_endpoints(): void
    {
        $user = User::factory()->create(['email' => 'm@example.com', 'password' => Hash::make('secret123')]);
        $plain = $user->createToken('iPhone 15')->plainTextToken;

        $this->withToken($plain)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->getKey());
    }

    public function test_revoke_current_token_logs_out(): void
    {
        $user = User::factory()->create();
        $plain = $user->createToken('iPhone 15')->plainTextToken;

        $this->withToken($plain)->deleteJson('/api/v1/auth/token')->assertNoContent();

        // Token đã bị thu hồi ⇒ DB sạch + request sau bằng token đó là 401.
        $this->assertDatabaseCount('personal_access_tokens', 0);
        // Test dùng chung app instance ⇒ guard cache user đã resolve; ép quên để
        // request sau xác thực lại token từ đầu (production mỗi request là instance mới).
        $this->app['auth']->forgetGuards();
        $this->withToken($plain)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_devices_lists_tokens_with_current_flag(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('iPhone 15')->plainTextToken;
        $user->createToken('iPad Air');

        $response = $this->withToken($current)->getJson('/api/v1/auth/devices')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $currents = collect($response->json('data'))->where('current', true);
        $this->assertCount(1, $currents);
        $this->assertSame('iPhone 15', $currents->first()['device_name']);
    }

    public function test_revoke_device_only_affects_own_tokens(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('iPhone 15')->plainTextToken;

        $other = User::factory()->create();
        $otherToken = $other->createToken('Stranger phone')->accessToken;

        // Thu hồi token của user khác ⇒ 404, token kia còn sống (ownership guard).
        $this->withToken($current)->deleteJson('/api/v1/auth/devices/'.$otherToken->getKey())
            ->assertNotFound();
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->getKey()]);
    }

    public function test_revoke_device_deletes_own_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('iPhone 15')->plainTextToken;
        $victim = $user->createToken('iPad Air')->accessToken;

        $this->withToken($current)->deleteJson('/api/v1/auth/devices/'.$victim->getKey())
            ->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $victim->getKey()]);
    }

    public function test_revoke_others_keeps_current_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('iPhone 15')->plainTextToken;
        $user->createToken('iPad Air');
        $user->createToken('Web');

        $this->withToken($current)->deleteJson('/api/v1/auth/devices')->assertNoContent();

        // Chỉ còn token hiện tại.
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->withToken($current)->getJson('/api/v1/auth/me')->assertOk();
    }

    /**
     * @return array{0: User, 1: Tenant}
     */
    protected function userWithTenant(Role $role = Role::Owner): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop '.uniqid()]);
        $tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return [$user, $tenant];
    }
}
