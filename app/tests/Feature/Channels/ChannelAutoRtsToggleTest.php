<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelAutoRtsToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function shop(string $provider): ChannelAccount
    {
        return ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => $provider,
            'external_shop_id' => strtoupper($provider).'-1', 'shop_name' => $provider.' shop', 'status' => 'active',
        ]);
    }

    public function test_toggles_auto_rts_for_lazada_shop(): void
    {
        $shop = $this->shop('lazada');
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/channel-accounts/{$shop->getKey()}/auto-rts", ['auto_rts_after_print' => true])
            ->assertOk();
        $this->assertTrue($res->json('data.auto_rts_after_print'));
        $this->assertTrue((bool) $shop->fresh()->auto_rts_after_print);
    }

    public function test_rejects_auto_rts_for_non_lazada_shop(): void
    {
        $shop = $this->shop('tiktok');
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/channel-accounts/{$shop->getKey()}/auto-rts", ['auto_rts_after_print' => true])
            ->assertStatus(422);
    }
}
