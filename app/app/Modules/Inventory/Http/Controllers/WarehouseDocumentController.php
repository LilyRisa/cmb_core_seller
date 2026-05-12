<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Inventory\Models\GoodsReceipt;
use CMBcoreSeller\Modules\Inventory\Models\GoodsReceiptItem;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\Stocktake;
use CMBcoreSeller\Modules\Inventory\Models\StocktakeItem;
use CMBcoreSeller\Modules\Inventory\Models\StockTransfer;
use CMBcoreSeller\Modules\Inventory\Models\StockTransferItem;
use CMBcoreSeller\Modules\Inventory\Models\Warehouse;
use CMBcoreSeller\Modules\Inventory\Services\InventoryLedgerService;
use CMBcoreSeller\Modules\Inventory\Services\WarehouseDocumentService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * WMS "phiếu" — nhập kho / chuyển kho / kiểm kê (Phase 5 — SPEC 0010). One route shape per
 * `{type}` ∈ goods-receipts | stock-transfers | stocktakes. Tạo ⇒ phiếu `draft`; `confirm` áp
 * vào sổ cái tồn (qua {@see WarehouseDocumentService}); `cancel` chỉ huỷ phiếu `draft`. Phiếu
 * `confirmed` bất biến (sửa = ra phiếu điều chỉnh mới) — sổ cái luôn là dấu vết kiểm toán trung thực.
 */
class WarehouseDocumentController extends Controller
{
    private const PERM = ['goods-receipts' => 'inventory.adjust', 'stock-transfers' => 'inventory.transfer', 'stocktakes' => 'inventory.stocktake'];

    private const PREFIX = ['goods-receipts' => 'PNK', 'stock-transfers' => 'PCK', 'stocktakes' => 'PKK'];

    private function authorizeFor(Request $request, string $type, bool $write): void
    {
        abort_unless(isset(self::PERM[$type]), 404);
        $perm = $write ? self::PERM[$type] : 'inventory.view';
        abort_unless($request->user()?->can($perm), 403, 'Bạn không có quyền với phiếu kho này.');
    }

    /** @return Builder<GoodsReceipt>|Builder<StockTransfer>|Builder<Stocktake> */
    private function query(string $type): Builder
    {
        return match ($type) {
            'goods-receipts' => GoodsReceipt::query(),
            'stock-transfers' => StockTransfer::query(),
            default => Stocktake::query(),
        };
    }

    private function find(string $type, int $id): GoodsReceipt|StockTransfer|Stocktake
    {
        return match ($type) {
            'goods-receipts' => GoodsReceipt::query()->findOrFail($id),
            'stock-transfers' => StockTransfer::query()->findOrFail($id),
            default => Stocktake::query()->findOrFail($id),
        };
    }

    public function index(Request $request, string $type): JsonResponse
    {
        $this->authorizeFor($request, $type, write: false);
        $q = $this->query($type);
        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }
        if ($w = $request->query('warehouse_id')) {
            $q->where(fn ($x) => $x->where('warehouse_id', (int) $w)->orWhere('from_warehouse_id', (int) $w)->orWhere('to_warehouse_id', (int) $w));
        }
        if ($term = trim((string) $request->query('q', ''))) {
            $q->where('code', 'like', "%{$term}%");
        }
        $q->orderByDesc('id');
        $page = $q->paginate(min(100, max(1, (int) $request->query('per_page', 20))))->appends($request->query());

        return response()->json([
            'data' => $page->getCollection()->map(fn ($d) => $this->present($d, withItems: false))->all(),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $this->authorizeFor($request, $type, write: false);

        return response()->json(['data' => $this->present($this->find($type, $id), withItems: true)]);
    }

    public function store(Request $request, string $type, CurrentTenant $tenant, InventoryLedgerService $ledger): JsonResponse
    {
        $this->authorizeFor($request, $type, write: true);
        $tenantId = (int) $tenant->id();
        $userId = $request->user()->getKey();

        $rules = [
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.sku_id' => ['required', 'integer'],
        ];
        if ($type === 'stock-transfers') {
            $rules['from_warehouse_id'] = ['required', 'integer'];
            $rules['to_warehouse_id'] = ['required', 'integer', 'different:from_warehouse_id'];
            $rules['items.*.qty'] = ['required', 'integer', 'min:1'];
        } else {
            $rules['warehouse_id'] = ['required', 'integer'];
            if ($type === 'goods-receipts') {
                $rules['supplier'] = ['sometimes', 'nullable', 'string', 'max:191'];
                $rules['items.*.qty'] = ['required', 'integer', 'min:1'];
                $rules['items.*.unit_cost'] = ['sometimes', 'integer', 'min:0'];
            } else { // stocktakes
                $rules['items.*.counted_qty'] = ['required', 'integer', 'min:0'];
            }
        }
        $data = $request->validate($rules);

        $whIds = array_values(array_filter([$data['warehouse_id'] ?? null, $data['from_warehouse_id'] ?? null, $data['to_warehouse_id'] ?? null]));
        if ($whIds !== [] && Warehouse::query()->whereIn('id', $whIds)->count() !== count(array_unique($whIds))) {
            throw ValidationException::withMessages(['warehouse_id' => 'Kho không hợp lệ.']);
        }
        $skuIds = array_values(array_unique(array_map(fn ($i) => (int) $i['sku_id'], $data['items'])));
        if (Sku::query()->whereIn('id', $skuIds)->whereNull('deleted_at')->count() !== count($skuIds)) {
            throw ValidationException::withMessages(['items' => 'SKU không hợp lệ.']);
        }

        $doc = DB::transaction(function () use ($type, $data, $tenantId, $userId, $ledger): GoodsReceipt|StockTransfer|Stocktake {
            $code = self::PREFIX[$type].'-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
            if ($type === 'goods-receipts') {
                $doc = GoodsReceipt::query()->create(['tenant_id' => $tenantId, 'code' => $code, 'status' => 'draft', 'note' => $data['note'] ?? null,
                    'warehouse_id' => (int) $data['warehouse_id'], 'supplier' => $data['supplier'] ?? null, 'created_by' => $userId]);
                foreach ($data['items'] as $i) {
                    GoodsReceiptItem::query()->create(['tenant_id' => $tenantId, 'goods_receipt_id' => $doc->getKey(), 'sku_id' => (int) $i['sku_id'], 'qty' => (int) $i['qty'], 'unit_cost' => (int) ($i['unit_cost'] ?? 0)]);
                }

                return $doc;
            }
            if ($type === 'stock-transfers') {
                $doc = StockTransfer::query()->create(['tenant_id' => $tenantId, 'code' => $code, 'status' => 'draft', 'note' => $data['note'] ?? null,
                    'from_warehouse_id' => (int) $data['from_warehouse_id'], 'to_warehouse_id' => (int) $data['to_warehouse_id'], 'created_by' => $userId]);
                foreach ($data['items'] as $i) {
                    StockTransferItem::query()->create(['tenant_id' => $tenantId, 'stock_transfer_id' => $doc->getKey(), 'sku_id' => (int) $i['sku_id'], 'qty' => (int) $i['qty']]);
                }

                return $doc;
            }
            $whId = (int) $data['warehouse_id'];
            $doc = Stocktake::query()->create(['tenant_id' => $tenantId, 'code' => $code, 'status' => 'draft', 'note' => $data['note'] ?? null, 'warehouse_id' => $whId, 'created_by' => $userId]);
            foreach ($data['items'] as $i) {
                $sys = $ledger->onHand($tenantId, (int) $i['sku_id'], $whId);
                StocktakeItem::query()->create(['tenant_id' => $tenantId, 'stocktake_id' => $doc->getKey(), 'sku_id' => (int) $i['sku_id'], 'system_qty' => $sys, 'counted_qty' => (int) $i['counted_qty'], 'diff' => (int) $i['counted_qty'] - $sys]);
            }

            return $doc;
        });

        return response()->json(['data' => $this->present($doc->refresh(), withItems: true)], 201);
    }

    public function confirm(Request $request, string $type, int $id, WarehouseDocumentService $service): JsonResponse
    {
        $this->authorizeFor($request, $type, write: true);
        $doc = $this->find($type, $id);
        $userId = $request->user()->getKey();
        try {
            $doc = match (true) {
                $doc instanceof GoodsReceipt => $service->confirmGoodsReceipt($doc, $userId),
                $doc instanceof StockTransfer => $service->confirmTransfer($doc, $userId),
                default => $service->confirmStocktake($doc, $userId),
            };
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return response()->json(['data' => $this->present($doc, withItems: true)]);
    }

    public function cancel(Request $request, string $type, int $id, WarehouseDocumentService $service): JsonResponse
    {
        $this->authorizeFor($request, $type, write: true);
        $doc = $this->find($type, $id);
        try {
            $service->cancel($doc);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return response()->json(['data' => $this->present($doc->refresh(), withItems: true)]);
    }

    // ---- presentation --------------------------------------------------------

    /** @return array<string,mixed> */
    private function present(GoodsReceipt|StockTransfer|Stocktake $doc, bool $withItems): array
    {
        $base = [
            'id' => $doc->getKey(),
            'code' => $doc->code,
            'status' => $doc->status,
            'note' => $doc->note,
            'item_count' => $doc->items()->count(),
            'confirmed_at' => $doc->confirmed_at?->toIso8601String(),
            'created_at' => $doc->created_at?->toIso8601String(),
        ];
        if ($doc instanceof StockTransfer) {
            $base['type'] = 'stock-transfers';
            $base += ['from_warehouse_id' => $doc->from_warehouse_id, 'to_warehouse_id' => $doc->to_warehouse_id];
        } elseif ($doc instanceof GoodsReceipt) {
            $base['type'] = 'goods-receipts';
            $base += ['warehouse_id' => $doc->warehouse_id, 'supplier' => $doc->supplier, 'total_cost' => (int) $doc->total_cost];
        } else {
            $base['type'] = 'stocktakes';
            $base += ['warehouse_id' => $doc->warehouse_id];
        }
        if ($withItems) {
            $doc->load('items.sku');
            $base['items'] = $doc->items->map(function ($it): array {
                $row = ['id' => $it->getKey(), 'sku_id' => $it->sku_id, 'sku' => $it->sku ? ['id' => $it->sku->id, 'sku_code' => $it->sku->sku_code, 'name' => $it->sku->name] : null];
                if ($it instanceof GoodsReceiptItem) {
                    $row += ['qty' => (int) $it->qty, 'unit_cost' => (int) $it->unit_cost];
                } elseif ($it instanceof StockTransferItem) {
                    $row += ['qty' => (int) $it->qty];
                } else {
                    $row += ['system_qty' => (int) $it->system_qty, 'counted_qty' => (int) $it->counted_qty, 'diff' => (int) $it->diff];
                }

                return $row;
            })->all();
        }

        return $base;
    }
}
