<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Integrations\Carriers\CarrierRegistry;
use CMBcoreSeller\Integrations\Carriers\JtExpress\JtExpressConnector;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class JtExpressInertConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_jt_connector_is_registered(): void
    {
        app(CarrierRegistry::class)->register('jt', JtExpressConnector::class);
        $registry = app(CarrierRegistry::class);

        $this->assertTrue($registry->has('jt'));
        $this->assertInstanceOf(JtExpressConnector::class, $registry->for('jt'));
        $this->assertSame(['createShipment', 'cancel', 'quote', 'getLabel', 'getTracking', 'webhook'], $registry->for('jt')->capabilities());
    }

    public function test_adding_jt_account_without_credentials_configured_is_inert_not_500(): void
    {
        Config::set('integrations.jt.api_account', '');
        Config::set('integrations.jt.private_key', '');
        app(CarrierRegistry::class)->register('jt', JtExpressConnector::class);

        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'JtShop']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);

        $res = $this->actingAs($owner)->withHeaders(['X-Tenant-Id' => (string) $tenant->getKey()])
            ->postJson('/api/v1/carrier-accounts', [
                'carrier' => 'jt', 'name' => 'J&T chính',
                'credentials' => ['customerCode' => '024E000014', 'password' => 'secret'],
                'meta' => ['pay_type' => 'PP_CASH', 'from_address' => ['name' => 'Shop', 'phone' => '0900000001', 'address' => 'Số 1', 'province_name' => 'Hồ Chí Minh', 'ward_name' => 'Phường 1']],
            ]);

        $res->assertCreated();   // không 500 — account vẫn tạo được, chỉ is_active=false
        $this->assertFalse((bool) $res->json('data.is_active'));
    }
}
