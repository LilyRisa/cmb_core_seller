<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\Ghn\GhnConnector;
use CMBcoreSeller\Integrations\Carriers\Ghtk\GhtkConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khoá hợp đồng (contract test) — endpoint GET /api/v1/carriers phơi capabilities đúng.
 * GHN hỗ trợ `failed_delivery_collect` (thu tiền khi giao thất bại).
 * GHTK KHÔNG hỗ trợ.
 */
class CarrierCapabilitiesApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        // Đăng ký carrier vào registry để endpoint carriers() tìm được.
        app(CarrierRegistry::class)->register('ghn', GhnConnector::class);
        app(CarrierRegistry::class)->register('ghtk', GhtkConnector::class);

        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_carriers_endpoint_exposes_capabilities(): void
    {
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/carriers');

        $resp->assertOk();
        $this->assertIsArray($resp['data']);

        // Tìm GHN trong list.
        $ghn = collect($resp['data'])->firstWhere('code', 'ghn');
        $this->assertNotNull($ghn, 'GHN phải có trong danh sách carriers');
        $this->assertIsArray($ghn['capabilities']);
        $this->assertContains('failed_delivery_collect', $ghn['capabilities'], 'GHN phải hỗ trợ failed_delivery_collect');

        // Tìm GHTK — phải KHÔNG có failed_delivery_collect.
        $ghtk = collect($resp['data'])->firstWhere('code', 'ghtk');
        $this->assertNotNull($ghtk, 'GHTK phải có trong danh sách carriers');
        $this->assertIsArray($ghtk['capabilities']);
        $this->assertNotContains('failed_delivery_collect', $ghtk['capabilities'], 'GHTK KHÔNG được hỗ trợ failed_delivery_collect');
    }
}
