<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Models\ProTrialOffer;
use CMBcoreSeller\Modules\Billing\Services\ProTrialService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProTrialOfferTest extends TestCase
{
    use RefreshDatabase;

    private function offerFor(int $tenantId): ?ProTrialOffer
    {
        return ProTrialOffer::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->first();
    }

    public function test_offer_creates_row_once_and_is_idempotent(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $service = app(ProTrialService::class);

        $service->offer($tenant->getKey());
        $service->offer($tenant->getKey()); // gọi lại (retry queue) không được tạo trùng

        $this->assertSame(
            1,
            ProTrialOffer::query()->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->getKey())->count(),
        );
        $offer = $this->offerFor($tenant->getKey());
        $this->assertNotNull($offer);
        $this->assertNotNull($offer->offered_at);
        $this->assertNull($offer->declined_at);
    }

    public function test_decline_sets_declined_at(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $service = app(ProTrialService::class);
        $service->offer($tenant->getKey());

        $service->decline($tenant->getKey());

        $offer = $this->offerFor($tenant->getKey());
        $this->assertNotNull($offer->declined_at);
    }

    public function test_decline_without_prior_offer_is_noop_safe(): void
    {
        $tenant = Tenant::create(['name' => 'Shop']);
        $service = app(ProTrialService::class);

        $service->decline($tenant->getKey()); // chưa từng offer — không được lỗi

        $this->assertSame(
            0,
            ProTrialOffer::query()->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->getKey())->count(),
        );
    }
}
