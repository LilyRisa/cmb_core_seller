<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelMessagingToggleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'TogShop']);
        config(['integrations.messaging' => ['lazada_chat']]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($user->getKey(), ['role' => $role->value]);

        return $user;
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function account(string $provider): ChannelAccount
    {
        return ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => $provider,
            'external_shop_id' => $provider.'_1', 'shop_name' => $provider, 'status' => 'active',
            'messaging_enabled' => false,
        ]);
    }

    public function test_owner_enables_messaging_for_lazada(): void
    {
        $a = $this->account('lazada_im');

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->patchJson("/api/v1/channel-accounts/{$a->id}/messaging", ['messaging_enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.messaging_enabled', true)
            ->assertJsonPath('data.messaging_available', true);

        $this->assertDatabaseHas('channel_accounts', ['id' => $a->id, 'messaging_enabled' => true]);
    }

    public function test_owner_disables_messaging_for_lazada(): void
    {
        $a = $this->account('lazada_im');
        $a->forceFill(['messaging_enabled' => true])->save();

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->patchJson("/api/v1/channel-accounts/{$a->id}/messaging", ['messaging_enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.messaging_enabled', false);

        $this->assertDatabaseHas('channel_accounts', ['id' => $a->id, 'messaging_enabled' => false]);
    }

    public function test_toggle_rejected_for_provider_without_messaging_connector(): void
    {
        // tiktok_chat KHÔNG bật trong config ⇒ registry không có ⇒ 422.
        $a = $this->account('tiktok');

        $this->actingAs($this->userWithRole(Role::Owner))
            ->withHeaders($this->h())
            ->patchJson("/api/v1/channel-accounts/{$a->id}/messaging", ['messaging_enabled' => true])
            ->assertStatus(422);
    }

    public function test_staff_cs_cannot_toggle(): void
    {
        $a = $this->account('lazada');

        $this->actingAs($this->userWithRole(Role::StaffCs))
            ->withHeaders($this->h())
            ->patchJson("/api/v1/channel-accounts/{$a->id}/messaging", ['messaging_enabled' => true])
            ->assertStatus(403);
    }
}
