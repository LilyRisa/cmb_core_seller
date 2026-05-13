<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services;

use CMBcoreSeller\Modules\Fulfillment\Jobs\RenderPrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Support\GotenbergClient;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        if ($type === PrintJob::TYPE_LABEL) {
            $this->assertSinglePlatformAndCarrier($tenantId, $orderIds, $shipmentIds);
        }
        $job = PrintJob::query()->create([
            'tenant_id' => $tenantId, 'type' => $type,
            'scope' => array_filter(['order_ids' => array_values(array_unique(array_map('intval', $orderIds))), 'shipment_ids' => array_values(array_unique(array_map('intval', $shipmentIds)))]),
            'status' => PrintJob::STATUS_PENDING, 'created_by' => $userId,
        ]);
        RenderPrintJob::dispatch($job->getKey())->onQueue('labels');

        return $job;
    }

    /**
     * A label bundle MUST be one platform + one carrier — different marketplaces / carriers
     * use different label formats and pickup batches (SPEC 0009). Mixed selection ⇒ 422.
     *
     * @param  list<int>  $orderIds
     * @param  list<int>  $shipmentIds
     */
    private function assertSinglePlatformAndCarrier(int $tenantId, array $orderIds, array $shipmentIds): void
    {
        $q = Shipment::query()->where('tenant_id', $tenantId)->with('order:id,source');
        if ($shipmentIds !== []) {
            $q->whereIn('id', $shipmentIds);
        } elseif ($orderIds !== []) {
            $q->whereIn('order_id', $orderIds)->open();
        } else {
            return;
        }
        $shipments = $q->get(['id', 'order_id', 'carrier']);
        if ($shipments->isEmpty()) {
            return; // nothing to validate yet; render will report "no labels"
        }
        $carriers = $shipments->pluck('carrier')->filter()->unique();
        $platforms = $shipments->map(fn ($s) => $s->order?->source)->filter()->unique();
        if ($carriers->count() > 1 || $platforms->count() > 1) {
            throw ValidationException::withMessages([
                'shipment_ids' => 'Không thể in tem cho nhiều nền tảng hoặc nhiều đơn vị vận chuyển cùng lúc. Hãy lọc theo từng nền tảng / ĐVVC rồi in.',
            ]);
        }
    }

    /** Khổ phiếu in của tenant (tenant.settings.print.label_size → fallback config) — mỗi phiếu 1 trang. SPEC 0013. */
    private function paperSize(int $tenantId): string
    {
        $tenant = Tenant::query()->find($tenantId);
        $size = $tenant ? data_get($tenant->settings, 'print.label_size') : null;

        return (string) ($size ?: config('fulfillment.print.label_paper_size', 'A6'));
    }

    /**
     * "Đánh dấu đã in" (sau khi người dùng mở file PDF & xác nhận ở popup) — cộng `print_count` cho các vận đơn
     * trong phạm vi của print job + ghi `last_printed_at`. `copies` = số bản đã in (mặc định 1). SPEC 0013.
     *
     * @return array{shipment_ids: list<int>, copies: int}
     */
    public function markPrinted(PrintJob $job, int $copies = 1): array
    {
        $copies = max(1, $copies);
        $shipmentIds = collect((array) data_get($job->meta, 'shipment_ids', []))->map(fn ($v) => (int) $v)->filter()->values()->all();
        if ($shipmentIds === []) {
            $q = Shipment::query()->where('tenant_id', $job->tenant_id);
            if ($sids = $job->shipmentIds()) {
                $q->whereIn('id', $sids);
            } elseif ($oids = $job->orderIds()) {
                $q->whereIn('order_id', $oids)->open();
            } else {
                return ['shipment_ids' => [], 'copies' => $copies];
            }
            $shipmentIds = $q->pluck('id')->map(fn ($v) => (int) $v)->all();
        }
        if ($shipmentIds !== []) {
            Shipment::query()->whereIn('id', $shipmentIds)->update(['print_count' => DB::raw('print_count + '.$copies), 'last_printed_at' => now()]);
        }

        return ['shipment_ids' => $shipmentIds, 'copies' => $copies];
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
                PrintJob::TYPE_INVOICE => $this->renderInvoice($job),
                PrintJob::TYPE_DELIVERY => $this->renderDeliverySlip($job),
                default => throw new \InvalidArgumentException("Loại phiếu in [{$job->type}] không hợp lệ."),
            };
            $stored = $this->media->storeBytes($bytes, (int) $job->tenant_id, 'print', $job->type.'-'.Str::ulid(), 'pdf');
            $job->forceFill(['status' => PrintJob::STATUS_DONE, 'file_url' => $stored['url'], 'file_path' => $stored['path'], 'file_size' => strlen($bytes), 'meta' => $meta, 'error' => null])->save();
            // Lưu trữ phiếu giao hàng tự tạo cho đơn: gắn vào vận đơn nếu vận đơn chưa có tem của sàn/ĐVVC. SPEC 0013.
            if ($job->type === PrintJob::TYPE_DELIVERY && $job->orderIds()) {
                Shipment::query()->where('tenant_id', $job->tenant_id)->whereIn('order_id', $job->orderIds())->open()
                    ->whereNull('label_path')->update(['label_url' => $stored['url'], 'label_path' => $stored['path']]);
            }
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
        $includedIds = [];
        foreach ($shipments as $s) {
            $bytes = $s->label_path ? $this->media->get($s->label_path) : null;
            if ($bytes) {
                $pdfs[] = $bytes;
                $includedIds[] = (int) $s->getKey();
            } else {
                $skipped[] = (int) $s->getKey();
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
        $merged = $this->gotenberg->mergePdfs($pdfs);
        // KHÔNG tự tăng print_count ở đây — "đã in" do người dùng xác nhận ở popup sau khi mở PDF (POST
        // /print-jobs/{id}/mark-printed). Render = chuẩn bị file in; ghi meta để popup biết đơn/vận đơn nào. SPEC 0013.
        $orderIds = Shipment::query()->whereIn('id', $includedIds)->pluck('order_id')->map(fn ($v) => (int) $v)->unique()->values()->all();

        return [$merged, ['count' => count($pdfs), 'skipped' => $skipped, 'shipment_ids' => $includedIds, 'order_ids' => $orderIds]];
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

        return [$this->gotenberg->htmlToPdf(PrintTemplates::packingList($orders, $this->paperSize($tenantId), $this->skuMapFor($orders))), ['orders' => $orders->count()]];
    }

    /**
     * Map sku_id → {code, name} cho các dòng đơn đã ghép SKU — phiếu in fallback về SKU master khi `seller_sku`/
     * `name` của dòng (do sàn gửi) bị trống. SPEC 0013 (yêu cầu phiếu in luôn có tên SP + SKU + SL).
     *
     * @param  Collection<int, Order>  $orders  với `items` đã nạp
     * @return array<int, array{code:?string,name:?string}>
     */
    private function skuMapFor(Collection $orders): array
    {
        $skuIds = $orders->flatMap(fn (Order $o) => $o->items->pluck('sku_id'))->filter()->unique()->values();
        if ($skuIds->isEmpty()) {
            return [];
        }

        return Sku::withoutGlobalScope(TenantScope::class)->whereIn('id', $skuIds->all())->get(['id', 'sku_code', 'name'])
            ->keyBy('id')->map(fn (Sku $s) => ['code' => $s->sku_code, 'name' => $s->name])->all();
    }

    /** Sales invoice / order slip — one printable page per order. @return array{0:string,1:array<string,mixed>} */
    private function renderInvoice(PrintJob $job): array
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
        $shopName = (string) (Tenant::query()->whereKey($tenantId)->value('name') ?? 'Cửa hàng');

        return [$this->gotenberg->htmlToPdf(PrintTemplates::invoice($orders, $shopName, $this->paperSize($tenantId))), ['orders' => $orders->count()]];
    }

    /**
     * "Phiếu giao hàng" tự tạo — một trang/đơn: tên cửa hàng + mã đơn/ngày + người nhận + địa chỉ giao +
     * mã vận đơn / ĐVVC (nếu có) + bảng hàng + COD + ghi chú. Dùng cho bước "Chuẩn bị hàng" khi chưa kéo
     * được tem/AWB thật của sàn ("luồng A" = follow-up). SPEC 0013. @return array{0:string,1:array<string,mixed>}
     */
    private function renderDeliverySlip(PrintJob $job): array
    {
        $tenantId = (int) $job->tenant_id;
        $orderIds = $job->orderIds();
        if ($orderIds === [] && ($sids = $job->shipmentIds())) {
            $orderIds = Shipment::query()->where('tenant_id', $tenantId)->whereIn('id', $sids)->pluck('order_id')->all();
        }
        $orders = Order::query()->where('tenant_id', $tenantId)->whereIn('id', $orderIds)->whereNull('deleted_at')
            ->with(['items', 'shipments' => fn ($q) => $q->orderByDesc('id')])->get();
        if ($orders->isEmpty()) {
            throw new \RuntimeException('Không có đơn nào để in.');
        }
        $shopName = (string) (Tenant::query()->whereKey($tenantId)->value('name') ?? 'Cửa hàng');

        return [$this->gotenberg->htmlToPdf(PrintTemplates::deliverySlip($orders, $shopName, $this->paperSize($tenantId), $this->skuMapFor($orders))), ['orders' => $orders->count(), 'order_ids' => $orders->modelKeys()]];
    }
}
