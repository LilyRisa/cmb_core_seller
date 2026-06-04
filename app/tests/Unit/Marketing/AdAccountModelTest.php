<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdAccountModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_encrypted_and_tenant_autoset(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);

        $a = AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1',
            'name' => 'Shop', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'SECRET',
        ]);

        $this->assertSame((int) $tenant->id, (int) $a->tenant_id); // auto-set by BelongsToTenant
        $this->assertSame('SECRET', $a->fresh()->access_token);     // decrypts
        $raw = DB::table('ad_accounts')->where('id', $a->id)->value('access_token');
        $this->assertNotSame('SECRET', $raw);                       // stored encrypted
    }
}
