<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcquisitionCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_stores_utm_and_server_observed_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van B',
            'email' => 'b@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'event_id' => 'evt-123',
            'acquisition' => [
                'utm_source' => 'facebook',
                'utm_campaign' => 'summer_sale',
                'fbclid' => 'FBCLID_ABC',
                'landing_page' => '/pricing',
            ],
        ], ['User-Agent' => 'TestAgent/1.0']);

        $response->assertCreated();

        $tenant = Tenant::where('name', 'Nguyen Van B Shop')->firstOrFail();
        $this->assertSame('facebook', $tenant->acquisition['utm_source']);
        $this->assertSame('summer_sale', $tenant->acquisition['utm_campaign']);
        $this->assertSame('FBCLID_ABC', $tenant->acquisition['fbclid']);
        $this->assertSame('evt-123', $tenant->acquisition['event_id']);
        $this->assertSame('TestAgent/1.0', $tenant->acquisition['user_agent']);
        $this->assertNotEmpty($tenant->acquisition['ip']);
        $this->assertNotEmpty($tenant->acquisition['captured_at']);
    }

    public function test_register_without_acquisition_still_succeeds(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nguyen Van C',
            'email' => 'c@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();
        $tenant = Tenant::where('name', 'Nguyen Van C Shop')->firstOrFail();
        $this->assertNull($tenant->acquisition['utm_source'] ?? null);
        $this->assertNotEmpty($tenant->acquisition['captured_at']);
    }
}
