<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Services\FacebookCapiReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookCapiReporterTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(array $acquisition = []): Tenant
    {
        $tenant = Tenant::create(['name' => 'CapiShop']);
        $tenant->forceFill(['acquisition' => $acquisition])->save();

        return $tenant->fresh();
    }

    public function test_sends_complete_registration_event_and_marks_reported(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        app(SystemSettingService::class)->set('growth.facebook.enabled', true);
        app(SystemSettingService::class)->set('growth.facebook.pixel_id', 'PIXEL_1');
        app(SystemSettingService::class)->set('growth.facebook.capi_access_token', 'TOKEN_1');

        $tenant = $this->makeTenant([
            'event_id' => 'evt-1', 'fbp' => 'fb.1.111.222', 'ip' => '1.2.3.4', 'user_agent' => 'UA',
        ]);

        $sent = app(FacebookCapiReporter::class)->reportCompleteRegistration($tenant, 'Owner@Example.com');

        $this->assertTrue($sent);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://graph.facebook.com/v25.0/PIXEL_1/events'
                && $body['data'][0]['event_name'] === 'CompleteRegistration'
                && $body['data'][0]['event_id'] === 'evt-1'
                && $body['data'][0]['user_data']['em'][0] === hash('sha256', 'owner@example.com')
                && $body['data'][0]['user_data']['fbp'] === 'fb.1.111.222'
                && $body['access_token'] === 'TOKEN_1';
        });
        $this->assertNotEmpty($tenant->fresh()->acquisition['capi_reported_at'] ?? null);
    }

    public function test_skips_when_disabled(): void
    {
        Http::fake();
        app(SystemSettingService::class)->set('growth.facebook.enabled', false);
        $tenant = $this->makeTenant();

        $sent = app(FacebookCapiReporter::class)->reportCompleteRegistration($tenant, 'owner@example.com');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_idempotent_when_already_reported(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1], 200)]);
        app(SystemSettingService::class)->set('growth.facebook.enabled', true);
        app(SystemSettingService::class)->set('growth.facebook.pixel_id', 'PIXEL_1');
        app(SystemSettingService::class)->set('growth.facebook.capi_access_token', 'TOKEN_1');
        $tenant = $this->makeTenant(['capi_reported_at' => now()->toIso8601String()]);

        $sent = app(FacebookCapiReporter::class)->reportCompleteRegistration($tenant, 'owner@example.com');

        $this->assertTrue($sent);
        Http::assertNothingSent();
    }

    public function test_returns_false_without_throwing_on_connection_failure(): void
    {
        Http::fake(['graph.facebook.com/*' => function () {
            throw new ConnectionException('Connection timed out');
        }]);
        app(SystemSettingService::class)->set('growth.facebook.enabled', true);
        app(SystemSettingService::class)->set('growth.facebook.pixel_id', 'PIXEL_1');
        app(SystemSettingService::class)->set('growth.facebook.capi_access_token', 'TOKEN_1');
        $tenant = $this->makeTenant(['event_id' => 'evt-2']);

        $sent = app(FacebookCapiReporter::class)->reportCompleteRegistration($tenant, 'owner@example.com');

        $this->assertFalse($sent);
        $this->assertArrayNotHasKey('capi_reported_at', $tenant->fresh()->acquisition ?? []);
    }
}
