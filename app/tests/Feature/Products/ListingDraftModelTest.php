<?php

namespace Tests\Feature\Products;

use CMBcoreSeller\Modules\Products\Models\ListingDraft;
use CMBcoreSeller\Modules\Products\Models\ListingDraftSku;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC draft — ListingDraft + ListingDraftSku models (tenant-scoped).
 *
 * Distinct from ChannelListing (stock-sync); these are draft products to be
 * published to a marketplace via the Connector layer.
 */
class ListingDraftModelTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'TestShop']);
        app(CurrentTenant::class)->set($this->tenant);
    }

    public function test_creates_a_draft_listing_scoped_to_tenant_with_skus(): void
    {
        $draft = ListingDraft::create([
            'product_id' => 1,
            'channel_account_id' => 1,
            'provider' => 'lazada',
            'attributes' => ['brand_id' => '40516'],
        ]);

        // Default status must be 'draft'
        $this->assertSame(ListingDraft::STATUS_DRAFT, $draft->fresh()->status);

        // Create a SKU via the relation
        $draft->skus()->create([
            'tenant_id' => $this->tenant->getKey(),
            'seller_sku' => 'SKU-001',
            'sale_props' => ['color_family' => 'Green'],
            'price' => 35000,
            'stock' => 3,
            'package_weight' => 0.5,
            'package_dims' => ['length' => 10, 'width' => 10, 'height' => 10],
        ]);

        $this->assertSame(1, $draft->skus()->count());

        /** @var ListingDraftSku $sku */
        $sku = $draft->skus()->first();
        $this->assertSame('Green', $sku->sale_props['color_family']);
    }
}
