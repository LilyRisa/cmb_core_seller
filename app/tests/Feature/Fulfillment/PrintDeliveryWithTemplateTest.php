<?php

namespace Tests\Feature\Fulfillment;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Fulfillment\Jobs\RenderPrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Fulfillment\Services\PrintService;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Support\GotenbergClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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
        $gotenberg->expects($this->once())->method('htmlToPdf')->willReturn('PDF-BYTES');
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
}
