<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Models\ProTrialGrant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialGrantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_id_is_unique(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        ProTrialGrant::query()->create([
            'tenant_id' => $tenant->getKey(), 'granted_at' => now(),
            'expires_at' => now()->addMonth(), 'terms_accepted_at' => now(),
            'terms_version' => 'refund-v1',
        ]);

        $this->expectException(QueryException::class);
        ProTrialGrant::query()->withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'granted_at' => now(),
            'expires_at' => now()->addMonth(), 'terms_accepted_at' => now(),
            'terms_version' => 'refund-v1',
        ]);
    }
}
