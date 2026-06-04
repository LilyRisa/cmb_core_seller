<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Jobs\RenderPrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\PrintService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Support\GotenbergClient;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrintDeliveryWithTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_job_carries_template_id_in_meta(): void
    {
        Bus::fake();
        $t = Tenant::factory()->create();
        $u = User::factory()->create();
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);
        $tpl = ShippingLabelTemplate::create(['tenant_id' => $t->id, 'name' => 'A6', 'paper' => 'A6',
            'paper_w_mm' => 105, 'paper_h_mm' => 148, 'schema_version' => 1,
            'schema' => ['fields' => []], 'is_default' => true]);
        $o = Order::create(['tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-1',
            'shipping_address' => [], 'status' => 'pending']);

        Sanctum::actingAs($u);
        $r = $this->withHeader('X-Tenant-Id', (string) $t->id)->postJson('/api/v1/print-jobs', [
            'type' => 'delivery', 'order_ids' => [$o->id], 'template_id' => $tpl->id,
        ])->assertCreated();

        $job = PrintJob::query()->findOrFail($r->json('data.id'));
        $this->assertSame($tpl->id, (int) data_get($job->meta, 'template_id'));
        $this->assertSame('A6', data_get($job->meta, 'template_name'));
        Bus::assertDispatched(RenderPrintJob::class);
    }

    public function test_render_with_template_calls_gotenberg(): void
    {
        Storage::fake('public');
        $t = Tenant::factory()->create();
        $tpl = ShippingLabelTemplate::create(['tenant_id' => $t->id, 'name' => 'A6', 'paper' => 'A6',
            'paper_w_mm' => 105, 'paper_h_mm' => 148, 'schema_version' => 1,
            'schema' => ['fields' => [['id' => 'a', 'type' => 'text', 'x' => 5, 'y' => 5,
                'w' => 50, 'h' => 6, 'text' => 'OK', 'style' => ['fontSize' => 11]]]],
            'is_default' => false]);
        $o = Order::create(['tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-1',
            'shipping_address' => [], 'status' => 'pending']);
        OrderItem::create(['order_id' => $o->id, 'name' => 'X', 'quantity' => 1, 'tenant_id' => $t->id, 'external_item_id' => 'X-1']);

        $gotenberg = $this->createMock(GotenbergClient::class);
        // Template-based delivery slips go through htmlToLabelPdf so Gotenberg honours
        // @page CSS size and applies zero margins (preferCssPageSize=true). Without this
        // path, Gotenberg defaults to Letter + 0.39in margins and clips the right edge.
        $gotenberg->expects($this->once())->method('htmlToLabelPdf')->willReturn('PDF-BYTES');
        $this->app->instance(GotenbergClient::class, $gotenberg);

        $job = PrintJob::create(['tenant_id' => $t->id, 'type' => 'delivery',
            'scope' => ['order_ids' => [$o->id]], 'status' => 'pending',
            'meta' => ['template_id' => $tpl->id, 'template_name' => $tpl->name]]);
        app(PrintService::class)->render($job);

        $this->assertSame('done', $job->fresh()->status);
    }

    public function test_render_without_template_uses_legacy_path(): void
    {
        Storage::fake('public');
        $t = Tenant::factory()->create();
        $o = Order::create(['tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-1',
            'shipping_address' => [], 'status' => 'pending']);
        OrderItem::create(['order_id' => $o->id, 'name' => 'X', 'quantity' => 1, 'tenant_id' => $t->id, 'external_item_id' => 'X-1']);

        $gotenberg = $this->createMock(GotenbergClient::class);
        $gotenberg->expects($this->once())->method('htmlToPdf')->willReturn('PDF-BYTES');
        $this->app->instance(GotenbergClient::class, $gotenberg);

        $job = PrintJob::create(['tenant_id' => $t->id, 'type' => 'delivery',
            'scope' => ['order_ids' => [$o->id]], 'status' => 'pending']);
        app(PrintService::class)->render($job);

        $this->assertSame('done', $job->fresh()->status);
    }

    public function test_template_render_is_ephemeral_skips_r2_and_shipment_cache(): void
    {
        Cache::clear();
        $t = Tenant::factory()->create();
        $tpl = ShippingLabelTemplate::create(['tenant_id' => $t->id, 'name' => 'A6', 'paper' => 'A6',
            'paper_w_mm' => 105, 'paper_h_mm' => 148, 'schema_version' => 1,
            'schema' => ['fields' => [['id' => 'a', 'type' => 'text', 'x' => 5, 'y' => 5,
                'w' => 50, 'h' => 6, 'text' => 'OK', 'style' => ['fontSize' => 11]]]],
            'is_default' => false]);
        $o = Order::create(['tenant_id' => $t->id, 'source' => 'manual', 'order_number' => 'M-1',
            'shipping_address' => [], 'status' => 'pending']);
        OrderItem::create(['order_id' => $o->id, 'name' => 'X', 'quantity' => 1, 'tenant_id' => $t->id, 'external_item_id' => 'X-1']);
        $sh = Shipment::create(['tenant_id' => $t->id, 'order_id' => $o->id, 'carrier' => 'manual_ghn',
            'status' => 'created', 'tracking_no' => 'TRK-1']);

        $gotenberg = $this->createMock(GotenbergClient::class);
        $gotenberg->method('htmlToLabelPdf')->willReturn('PDF-BYTES');
        $this->app->instance(GotenbergClient::class, $gotenberg);
        // MediaUploader must NOT be hit for ephemeral renders — that's the whole point of
        // routing template-based slips through Redis instead of R2.
        $media = $this->createMock(MediaUploader::class);
        $media->expects($this->never())->method('storeBytes');
        $this->app->instance(MediaUploader::class, $media);

        $job = PrintJob::create(['tenant_id' => $t->id, 'type' => 'delivery',
            'scope' => ['order_ids' => [$o->id]], 'status' => 'pending',
            'meta' => ['template_id' => $tpl->id, 'template_name' => $tpl->name]]);
        app(PrintService::class)->render($job);

        $fresh = $job->fresh();
        $this->assertSame('done', $fresh->status);
        $this->assertNull($fresh->file_path, 'ephemeral jobs do not get an R2 file_path');
        $this->assertStringContainsString('/print-jobs/'.$fresh->id.'/download', $fresh->file_url);
        $this->assertTrue(data_get($fresh->meta, 'ephemeral') === true);
        // Bytes recoverable from cache for 1h.
        $this->assertSame('PDF-BYTES', Cache::get(app(PrintService::class)->ephemeralCacheKey((int) $fresh->id)));
        // shipment.label_url stays null — that field is reserved for carrier/marketplace labels.
        $this->assertNull($sh->fresh()->label_url);
        $this->assertNull($sh->fresh()->label_path);
    }
}
