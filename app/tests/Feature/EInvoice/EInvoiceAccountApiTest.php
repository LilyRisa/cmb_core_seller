<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EInvoiceAccountApiTest extends TestCase
{
    use EInvoiceTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEInvoiceTenant();
    }

    private function payload(): array
    {
        return ['provider' => 'misa', 'name' => 'MISA chính', 'default_mode' => 'hsm',
            'credentials' => ['appid' => 'A', 'taxcode' => '0105922241', 'username' => 'u', 'password' => 'p']];
    }

    public function test_create_account_never_exposes_credentials(): void
    {
        Http::fake(['*' => Http::response(['Success' => true, 'Data' => 'TOKEN', 'ErrorCode' => ''])]);
        $resp = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/einvoice/accounts', $this->payload())->assertCreated();

        $resp->assertJsonPath('data.provider', 'misa')
            ->assertJsonPath('data.name', 'MISA chính');
        $this->assertContains('appid', $resp->json('data.credential_keys'));
        $this->assertArrayNotHasKey('credentials', $resp->json('data'));
    }

    public function test_verify_returns_ok_with_fake_misa(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => true, 'Data' => 'TOKEN', 'ErrorCode' => '']),
            '*/company*' => Http::response(['Success' => true, 'ErrorCode' => '', 'Data' => json_encode(['CompanyName' => 'Cty ABC', 'IsInvoiceWithCode' => true])]),
        ]);
        $create = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/einvoice/accounts', $this->payload())->assertCreated();
        $id = $create->json('data.id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/einvoice/accounts/{$id}/verify")
            ->assertOk()->assertJsonPath('data.ok', true);
    }

    public function test_viewer_cannot_configure(): void
    {
        $this->actingAs($this->viewer)->withHeaders($this->h())
            ->postJson('/api/v1/einvoice/accounts', $this->payload())
            ->assertStatus(403);
    }

    public function test_plan_locked_returns_402(): void
    {
        $this->activatePlan(Plan::CODE_STARTER);
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/einvoice/accounts')
            ->assertStatus(402)->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }
}
