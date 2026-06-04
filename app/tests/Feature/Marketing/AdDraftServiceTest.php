<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Services\AdDraftService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdDraftServiceTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): AdDraftService
    {
        return app(AdDraftService::class);
    }

    public function test_create_persists_defaults(): void
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));

        $draft = $this->svc()->create(11, 7, ['name' => 'N', 'objective' => 'messages', 'payload' => ['a' => 1]]);

        $this->assertSame(11, $draft->ad_account_id);
        $this->assertSame(7, $draft->created_by);
        $this->assertSame('draft', $draft->status);
        $this->assertSame(['a' => 1], $draft->payload);
    }

    public function test_update_only_touches_provided_fields(): void
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));
        $draft = $this->svc()->create(11, 7, ['name' => 'Old', 'objective' => 'messages', 'payload' => ['a' => 1]]);

        $updated = $this->svc()->update($draft, ['payload' => ['a' => 2, 'b' => 3]]);

        $this->assertSame('Old', $updated->name);
        $this->assertSame('messages', $updated->objective);
        $this->assertSame(['a' => 2, 'b' => 3], $updated->payload);
    }
}
