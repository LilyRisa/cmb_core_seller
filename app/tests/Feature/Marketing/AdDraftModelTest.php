<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdDraftModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_draft_with_tenant_autoset_and_payload_cast(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);

        $draft = AdDraft::create([
            'ad_account_id' => 11,
            'name' => 'Bản nháp Tết',
            'objective' => 'messages',
            'payload' => ['budget' => 150000, 'targeting' => ['age_min' => 18]],
        ]);

        $this->assertSame((int) $tenant->getKey(), $draft->tenant_id);
        $this->assertSame('draft', $draft->status);
        $this->assertIsArray($draft->fresh()->payload);
        $this->assertSame(150000, $draft->fresh()->payload['budget']);
    }

    public function test_tenant_scope_hides_other_tenants_drafts(): void
    {
        $t1 = Tenant::create(['name' => 'T1']);
        app(CurrentTenant::class)->set($t1);
        AdDraft::create(['ad_account_id' => 1, 'name' => 'mine', 'payload' => []]);

        $t2 = Tenant::create(['name' => 'T2']);
        app(CurrentTenant::class)->set($t2);

        $this->assertSame(0, AdDraft::query()->count());
    }
}
