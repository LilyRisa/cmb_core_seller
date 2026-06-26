<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    private function membership(Tenant $tenant, User $user): TenantUser
    {
        return TenantUser::query()->where('tenant_id', $tenant->getKey())->where('user_id', $user->getKey())->first();
    }

    private function h(int $tenantId): array
    {
        return ['X-Tenant-Id' => (string) $tenantId];
    }

    public function test_current_tenant_is_owner_for_owner_membership(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'S']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $ct = app(CurrentTenant::class);
        $ct->set($tenant, $this->membership($tenant, $owner));
        $this->assertTrue($ct->isOwner());

        $staff = User::factory()->create();
        $tenant->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);
        $ct->set($tenant, $this->membership($tenant, $staff));
        $this->assertFalse($ct->isOwner());
    }

    public function test_owner_creates_lists_and_deletes_api_key(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::create(['name' => 'S']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $h = $this->h((int) $tenant->getKey());

        $create = $this->actingAs($owner)->withHeaders($h)
            ->postJson('/api/v1/tenant/api-keys', ['name' => 'Zapier', 'expires_at' => now()->addDays(30)->toIso8601String()])
            ->assertCreated();
        $token = $create->json('data.token');
        $id = $create->json('data.id');
        $this->assertNotEmpty($token);
        $this->assertSame(substr($token, -4), $create->json('data.token') ? substr($token, -4) : null);

        $this->actingAs($owner)->withHeaders($h)->getJson('/api/v1/tenant/api-keys')
            ->assertOk()->assertJsonPath('data.0.name', 'Zapier')
            ->assertJsonPath('data.0.last_four', substr($token, -4))
            ->assertJsonMissingPath('data.0.token');

        $this->actingAs($owner)->withHeaders($h)->deleteJson("/api/v1/tenant/api-keys/{$id}")->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $id]);
    }

    public function test_api_key_token_acts_as_web_scoped_to_tenant_and_revoked_on_delete(): void
    {
        // Tạo token TRỰC TIẾP (không actingAs để tránh session guard đè lên Bearer token).
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::create(['name' => 'S']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $new = $owner->createToken('k', ['*']);
        $new->accessToken->forceFill(['tenant_id' => $tenant->getKey(), 'kind' => 'api_key', 'last_four' => substr($new->plainTextToken, -4)])->save();
        $token = $new->plainTextToken;
        $id = $new->accessToken->id;

        // Token tự khóa tenant — KHÔNG gửi X-Tenant-Id mà vẫn vào được route tenant-scoped.
        $this->withToken($token)->getJson('/api/v1/dashboard/summary')->assertOk();

        // Thu hồi (xóa) ⇒ request sau bằng token đó → 401.
        \Laravel\Sanctum\PersonalAccessToken::query()->whereKey($id)->delete();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/dashboard/summary')->assertUnauthorized();
    }

    public function test_staff_cannot_manage_api_keys(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $staff = User::factory()->create(['email_verified_at' => now()]);
        $tenant = Tenant::create(['name' => 'S']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $tenant->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);
        $h = $this->h((int) $tenant->getKey());

        $this->actingAs($staff)->withHeaders($h)->getJson('/api/v1/tenant/api-keys')->assertForbidden();
        $this->actingAs($staff)->withHeaders($h)->postJson('/api/v1/tenant/api-keys', ['name' => 'x'])->assertForbidden();
    }
}
