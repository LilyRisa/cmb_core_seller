<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Modules\EInvoice\Models\EInvoiceAccount;
use CMBcoreSeller\Modules\Tenancy\Database\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EInvoiceAccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_and_connector_array_shaped(): void
    {
        $acc = EInvoiceAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'provider' => 'misa', 'name' => 'MISA chính',
            'credentials' => ['appid' => 'A', 'taxcode' => '010', 'username' => 'u', 'password' => 'p'],
            'default_mode' => 'hsm',
        ]);

        // Cột thô trong DB là chuỗi mã hóa, không phải plaintext JSON.
        $raw = \DB::table('einvoice_accounts')->where('id', $acc->id)->value('credentials');
        $this->assertStringNotContainsString('appid', (string) $raw);

        $arr = $acc->toConnectorArray();
        $this->assertSame('misa', $arr['provider']);
        $this->assertSame('A', $arr['credentials']['appid']);
    }
}
