<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Jobs\PushStockForSku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AutoRtsAfterPrintTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([PushStockForSku::class]);
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    /** Tạo: gian hàng Lazada (autoRts), đơn channel `processing`, vận đơn `created`, print job `label`. Trả [shipment, jobId]. */
    private function scenario(bool $autoRts): array
    {
        $shop = ChannelAccount::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'lazada',
            'external_shop_id' => 'LZ-1', 'shop_name' => 'LZ', 'status' => 'active',
            'auto_rts_after_print' => $autoRts,
        ]);
        $order = Order::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $shop->getKey(),
            'source' => 'lazada', 'external_order_id' => 'EO-1', 'status' => 'processing',
        ]);
        $shipment = Shipment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'order_id' => $order->getKey(),
            'carrier' => 'manual', 'tracking_no' => 'LZTN-1', 'status' => Shipment::STATUS_CREATED,
        ]);
        $job = PrintJob::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'type' => PrintJob::TYPE_LABEL,
            'scope' => ['shipment_ids' => [$shipment->getKey()]],
            'status' => PrintJob::STATUS_DONE, 'created_by' => $this->owner->getKey(),
        ]);

        return [$shipment, $job->getKey()];
    }

    public function test_auto_rts_marks_packed_when_flag_on(): void
    {
        [$shipment, $jobId] = $this->scenario(autoRts: true);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/print-jobs/{$jobId}/mark-printed", ['copies' => 1])->assertOk();
        $this->assertSame(Shipment::STATUS_PACKED, Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey())->status);
    }

    public function test_no_auto_rts_when_flag_off(): void
    {
        [$shipment, $jobId] = $this->scenario(autoRts: false);
        $this->actingAs($this->owner)->withHeaders($this->h())->postJson("/api/v1/print-jobs/{$jobId}/mark-printed", ['copies' => 1])->assertOk();
        $this->assertSame(Shipment::STATUS_CREATED, Shipment::withoutGlobalScope(TenantScope::class)->find($shipment->getKey())->status);
    }
}
