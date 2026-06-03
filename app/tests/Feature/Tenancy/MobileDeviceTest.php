<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\MobileDevice;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0029 — Mobile device registry (Expo push token).
 */
class MobileDeviceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'Push Shop']);
        $this->tenant->users()->attach($this->user->getKey(), ['role' => Role::Owner->value]);
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_register_device_creates_row(): void
    {
        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->postJson('/api/v1/me/devices', [
                'expo_push_token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
                'platform' => 'ios',
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'expo_push_token', 'platform']])
            ->assertJsonPath('data.platform', 'ios');

        $this->assertDatabaseHas('mobile_devices', [
            'user_id' => $this->user->getKey(),
            'tenant_id' => $this->tenant->getKey(),
            'expo_push_token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'platform' => 'ios',
        ]);
    }

    public function test_register_device_is_upsert_safe(): void
    {
        $token = 'ExponentPushToken[upsert_test_abc]';

        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->postJson('/api/v1/me/devices', ['expo_push_token' => $token, 'platform' => 'android'])
            ->assertCreated();

        // Đăng ký lại cùng token ⇒ upsert, không nhân đôi.
        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->postJson('/api/v1/me/devices', ['expo_push_token' => $token, 'platform' => 'ios'])
            ->assertCreated();

        $this->assertDatabaseCount('mobile_devices', 1);
        $this->assertDatabaseHas('mobile_devices', ['expo_push_token' => $token, 'platform' => 'ios']);
    }

    public function test_register_device_validates_platform(): void
    {
        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->postJson('/api/v1/me/devices', [
                'expo_push_token' => 'ExponentPushToken[platform_test]',
                'platform' => 'windows',
            ])
            ->assertStatus(422);
    }

    public function test_register_device_requires_auth(): void
    {
        $this->postJson('/api/v1/me/devices', [
            'expo_push_token' => 'ExponentPushToken[noauth]',
            'platform' => 'ios',
        ])->assertUnauthorized();
    }

    public function test_delete_device_removes_row(): void
    {
        $device = MobileDevice::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $this->user->getKey(),
            'expo_push_token' => 'ExponentPushToken[delete_me]',
            'platform' => 'ios',
        ]);

        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->deleteJson('/api/v1/me/devices/'.$device->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('mobile_devices', ['id' => $device->getKey()]);
    }

    public function test_cors_preflight_allows_authorization_and_tenant_headers(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:8081']]);

        $response = $this->call('OPTIONS', '/api/v1/me/devices', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:8081',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization,X-Tenant-Id,Content-Type,Accept',
        ]);

        $response->assertNoContent();
        $allowHeaders = strtolower((string) $response->headers->get('Access-Control-Allow-Headers', ''));
        $this->assertStringContainsString('authorization', $allowHeaders);
        $this->assertStringContainsString('x-tenant-id', $allowHeaders);
        $this->assertSame('http://localhost:8081', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_delete_device_returns_404_for_other_user(): void
    {
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($otherUser->getKey(), ['role' => Role::Viewer->value]);

        $device = MobileDevice::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $otherUser->getKey(),
            'expo_push_token' => 'ExponentPushToken[not_mine]',
            'platform' => 'android',
        ]);

        $this->actingAs($this->user)
            ->withHeaders($this->headers())
            ->deleteJson('/api/v1/me/devices/'.$device->getKey())
            ->assertNotFound();

        $this->assertDatabaseHas('mobile_devices', ['id' => $device->getKey()]);
    }
}
