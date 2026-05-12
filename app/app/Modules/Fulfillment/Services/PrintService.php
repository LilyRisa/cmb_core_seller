<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services;

use CMBcoreSeller\Modules\Fulfillment\Jobs\RenderPrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\GotenbergClient;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Support\Str;

/**
 * Creates print_jobs and produces the PDFs (queue `labels`, rule 3 of the domain doc):
 * `label` = merge the carrier label PDFs into one file; `picking` = a pick list grouped
 * by SKU; `packing` = one packing slip per order. v1: built-in HTML templates (custom
 * templates are a follow-up). See SPEC 0006 §3.3.
 */
class PrintService
{
    public function __construct(private readonly GotenbergClient $gotenberg, private readonly MediaUploader $media) {}

    /**
     * @param  list<int>  $orderIds
     * @param  list<int>  $shipmentIds
     */
    public function createJob(int $tenantId, string $type, array $orderIds, array $shipmentIds, ?int $userId): PrintJob
    {
        $job = PrintJob::query()->create([
            'tenant_id' => $tenantId, 'type' => $type,
            'scope' => array_filter(['order_ids' => array_values(array_unique(array_map('intval', $orderIds))), 'shipment_ids' => array_values(array_unique(array_map('intval', $shipmentIds)))]),
            'status' => PrintJob::STATUS_PENDING, 'created_by' => $userId,
        ]);
        RenderPrintJob::dispatch($job->getKey())->onQueue('labels');

        return $job;
    }

    /** Runs inside RenderPrintJob — fills in file_url/status or error. */
    public function render(PrintJob $job): void
    {
        $job->forceFill(['status' => PrintJob::STATUS_PROCESSING])->save();
        try {
            [$bytes, $meta] = match ($job->type) {
                PrintJob::TYPE_LABEL => $this->renderLabelBundle($job),
                PrintJob::TYPE_PICKING => $this->renderPickingList($job),
                PrintJob::TYPE_PACKING => $this->renderPackingList($job),
                default => throw new \InvalidArgumentException("Loại phiếu in [{$job->type}] không hợp lệ."),
            };
            $stored = $this->media->storeBytes($bytes, (int) $job->tenant_id, 'print', $job->type.'-'.Str::ulid(), 'pdf');
            $job->forceFill(['status' => PrintJob::STATUS_DONE, 'file_url' => $stored['url'], 'file_path' => $stored['path'], 'file_size' => strlen($bytes), 'meta' => $meta, 'error' => null])->save();
        } catch (\Throwable $e) {
            $job->forceFill(['status' => PrintJob::STATUS_ERROR, 'error' => $e->getMessage()])->save();
            throw $e;
        }
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function renderLabelBundle(PrintJob $job): array
    {
        $tenantId = (int) $job->tenant_id;
        $query = Shipment::query()->where('tenant_id', $tenantId);
        if ($ids = $job->shipmentIds()) {
            $query->whereIn('id', $ids);
        } elseif ($orderIds = $job->orderIds()) {
            $query->whereIn('order_id', $orderIds)->open();
        }
        $shipments = $query->whereNotNull('label_path')->orderBy('carrier')->orderBy('order_id')->get();
        $pdfs = [];
        $skipped = [];
        foreach ($shipments as $s) {
            $bytes = $s->label_path ? $this->media->get($s->label_path) : null;
            if ($bytes) {
                $pdfs[] = $bytes;
            } else {
                $skipped[] = $s->getKey();
            }
        }
        // shipments in scope that had no label at all
        $scopedAll = Shipment::query()->where('tenant_id', $tenantId)
            ->when($job->shipmentIds(), fn ($q, $v) => $q->whereIn('id', $v), fn ($q) => $q->whereIn('order_id', $job->orderIds())->open())
            ->whereNull('label_path')->pluck('id')->all();
        $skipped = array_values(array_unique([...$skipped, ...$scopedAll]));
        if ($pdfs === []) {
            throw new \RuntimeException('Không có vận đơn nào có tem để in.');
        }

        return [$this->gotenberg->mergePdfs($pdfs), ['count' => count($pdfs), 'skipped' => $skipped]];
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function renderPickingList(PrintJob $job): array
    {
        $tenantId = (int) $job->tenant_id;
        $orderIds = $job->orderIds();
        if ($orderIds === [] && ($sids = $job->shipmentIds())) {
            $orderIds = Shipment::query()->where('tenant_id', $tenantId)->whereIn('id', $sids)->pluck('order_id')->all();
        }
        $orders = Order::query()->where('tenant_id', $tenantId)->whereIn('id', $orderIds)->whereNull('deleted_at')->get(['id', 'order_number', 'external_order_id']);
        $items = OrderItem::withoutGlobalScope(TenantScope::class)->whereIn('order_id', $orders->modelKeys())->get(['order_id', 'sku_id', 'seller_sku', 'name', 'quantity']);
        $skuCodes = $items->pluck('sku_id')->filter()->unique()->all();
        $skus = $skuCodes ? Sku::withoutGlobalScope(TenantScope::class)->whereIn('id', $skuCodes)->get(['id', 'sku_code', 'name'])->keyBy('id') : collect();
        $orderLabel = fn ($oid) => optional($orders->firstWhere('id', $oid))->order_number ?? optional($orders->firstWhere('id', $oid))->external_order_id ?? ('#'.$oid);

        $grouped = [];
        foreach ($items as $it) {
            $key = $it->sku_id ? 'sku:'.$it->sku_id : 'raw:'.($it->seller_sku ?: $it->name);
            $code = $it->sku_id && $skus->get($it->sku_id) ? $skus->get($it->sku_id)->sku_code : ($it->seller_sku ?: '(chưa ghép)');
            $name = $it->sku_id && $skus->get($it->sku_id) ? $skus->get($it->sku_id)->name : $it->name;
            $grouped[$key] ??= ['code' => $code, 'name' => $name, 'qty' => 0, 'orders' => []];
            $grouped[$key]['qty'] += (int) $it->quantity;
            $grouped[$key]['orders'][] = $orderLabel($it->order_id).'×'.(int) $it->quantity;
        }
        ksort($grouped);

        return [$this->gotenberg->htmlToPdf(PrintTemplates::pickingList(array_values($grouped), $orders->count())), ['orders' => $orders->count(), 'lines' => count($grouped)]];
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function renderPackingList(PrintJob $job): array
    {
        $tenantId = (int) $job->tenant_id;
        $orderIds = $job->orderIds();
        if ($orderIds === [] && ($sids = $job->shipmentIds())) {
            $orderIds = Shipment::query()->where('tenant_id', $tenantId)->whereIn('id', $sids)->pluck('order_id')->all();
        }
        $orders = Order::query()->where('tenant_id', $tenantId)->whereIn('id', $orderIds)->whereNull('deleted_at')->with('items')->get();
        if ($orders->isEmpty()) {
            throw new \RuntimeException('Không có đơn nào để in.');
        }

        return [$this->gotenberg->htmlToPdf(PrintTemplates::packingList($orders)), ['orders' => $orders->count()]];
    }
}
