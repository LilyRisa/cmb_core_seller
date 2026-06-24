<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Services\TenantRoleProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Websocket cho app mobile — endpoint /api/v1/broadcasting/auth (bearer token) cho phép
 * client mobile authorize private channel tin nhắn (Reverb).
 */
class MobileBroadcastAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'testkey',
            'broadcasting.connections.reverb.secret' => 'testsecret',
            'broadcasting.connections.reverb.app_id' => '1',
            'broadcasting.connections.reverb.options' => ['host' => 'localhost', 'port' => 8080, 'scheme' => 'http', 'useTLS' => false],
        ]);
        // Channels được đăng ký lúc boot trên driver mặc định cũ; đổi default ⇒ phải đăng ký lại
        // lên driver reverb để Broadcast::auth khớp pattern channel.
        require base_path('routes/channels.php');
    }

    /** @return array{0: User, 1: Tenant} */
    private function ownerOf(): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Shop']);
        $roles = app(TenantRoleProvisioner::class)->seedDefaults($tenant);
        $tenant->users()->attach($user->getKey(), ['role' => Role::Owner->value, 'role_id' => $roles[Role::Owner->value]->getKey()]);

        return [$user, $tenant];
    }

    public function test_unauthenticated_rejected(): void
    {
        $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678', 'channel_name' => 'private-tenant.1.messaging',
        ])->assertStatus(401);
    }

    public function test_member_can_authorize_messaging_channel(): void
    {
        [$user, $tenant] = $this->ownerOf();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-tenant.{$tenant->id}.messaging",
        ])->assertOk()->assertJsonStructure(['auth']);
    }

    public function test_non_member_forbidden(): void
    {
        [, $tenant] = $this->ownerOf();
        Sanctum::actingAs(User::factory()->create()); // user khác, không thuộc tenant

        $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-tenant.{$tenant->id}.messaging",
        ])->assertStatus(403);
    }
}
