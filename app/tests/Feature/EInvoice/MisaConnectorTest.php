<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Integrations\EInvoice\MisaMeInvoice\MisaMeInvoiceConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MisaConnectorTest extends TestCase
{
    private function account(): array
    {
        return ['credentials' => [
            'appid' => 'APP1', 'taxcode' => '0105922241', 'username' => 'user', 'password' => 'pass',
        ]];
    }

    private function connector(): MisaMeInvoiceConnector
    {
        return new MisaMeInvoiceConnector(['base_url' => 'https://testapi.meinvoice.vn/api/v3', 'token_ttl_days' => 14, 'http' => ['timeout' => 30, 'retries' => 0, 'retry_sleep_ms' => 0]]);
    }

    public function test_verify_credentials_ok_when_token_and_company_succeed(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => true, 'Data' => 'TOKEN123', 'ErrorCode' => '']),
            '*/company*' => Http::response(['Success' => true, 'ErrorCode' => '', 'Data' => json_encode([
                'CompanyName' => 'Cty ABC', 'CompanyTaxCode' => '0105922241', 'IsInvoiceWithCode' => true,
            ])]),
        ]);

        $r = $this->connector()->verifyCredentials($this->account());
        $this->assertTrue($r['ok']);
        $this->assertStringContainsString('ABC', $r['message']);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/auth/token')
            && ($req->data()['appid'] ?? null) === 'APP1');
    }

    public function test_verify_credentials_fails_on_unauthorize(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => false, 'ErrorCode' => 'UnAuthorize', 'Data' => null]),
        ]);

        $r = $this->connector()->verifyCredentials($this->account());
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid_credentials', $r['error_code']);
    }

    public function test_verify_credentials_fails_fast_when_missing_field(): void
    {
        $r = $this->connector()->verifyCredentials(['credentials' => ['appid' => 'A']]);
        $this->assertFalse($r['ok']);
        $this->assertSame('invalid_credentials', $r['error_code']);
    }

    public function test_templates_parses_stringified_data_array(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => true, 'Data' => 'TOKEN123', 'ErrorCode' => '']),
            '*/itg/InvoicePublishing/templates*' => Http::response(['Success' => true, 'ErrorCode' => '', 'Data' => json_encode([
                ['IPTemplateID' => 'g1', 'TemplateName' => '01GTKT', 'InvSeries' => '1C25TAA', 'InvoiceType' => 1, 'IsPublished' => true, 'Inactive' => false],
            ])]),
        ]);

        $list = $this->connector()->templates($this->account(), 2026);
        $this->assertCount(1, $list);
        $this->assertSame('1C25TAA', $list[0]->invSeries);
    }

    public function test_capabilities_phase_a(): void
    {
        $c = $this->connector();
        $this->assertSame('misa', $c->code());
        $this->assertTrue($c->supports('company_info'));
        $this->assertFalse($c->supports('issue_hsm'));
    }

    /**
     * Khi company-info lần đầu trả TokenExpiredCode, client phải bust cache rồi lấy token mới
     * và thử lại — verifyCredentials cuối cùng phải trả ok=true.
     */
    public function test_verify_credentials_retries_after_token_expired(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => true, 'Data' => 'TOKEN_NEW', 'ErrorCode' => '']),
            '*/company*' => Http::sequence()
                ->push(['Success' => false, 'ErrorCode' => 'TokenExpiredCode', 'Data' => null])
                ->push(['Success' => true, 'ErrorCode' => '', 'Data' => json_encode([
                    'CompanyName' => 'Cty XYZ', 'CompanyTaxCode' => '0105922241', 'IsInvoiceWithCode' => true,
                ])]),
        ]);

        $r = $this->connector()->verifyCredentials($this->account());

        $this->assertTrue($r['ok'], 'verifyCredentials phải ok sau khi token được làm mới');
        $this->assertStringContainsString('XYZ', $r['message']);
    }

    /**
     * UnAuthorize (sai mật khẩu) KHÔNG được retry — phải trả ok=false ngay.
     */
    public function test_verify_credentials_does_not_retry_on_unauthorize(): void
    {
        Http::fake([
            '*/auth/token' => Http::response(['Success' => false, 'ErrorCode' => 'UnAuthorize', 'Data' => null]),
        ]);

        $r = $this->connector()->verifyCredentials($this->account());

        $this->assertFalse($r['ok']);
        $this->assertSame('invalid_credentials', $r['error_code']);
        // Token endpoint chỉ được gọi đúng 1 lần (không retry)
        Http::assertSentCount(1);
    }
}
