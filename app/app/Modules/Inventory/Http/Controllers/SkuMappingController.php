<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Inventory\Http\Resources\SkuMappingResource;
use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Services\OrderInventoryService;
use CMBcoreSeller\Modules\Inventory\Services\SkuMappingService;
use CMBcoreSeller\Modules\Inventory\Support\SkuCodeNormalizer;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderItem;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** /api/v1/sku-mappings — link channel listings to master SKUs. See SPEC 0003 §6. */
class SkuMappingController extends Controller
{
    /** POST /api/v1/sku-mappings  { channel_listing_id, type, lines:[{sku_id, quantity}] } */
    public function store(Request $request, SkuMappingService $service, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map'), 403, 'Bạn không có quyền ghép SKU.');
        $data = $request->validate([
            'channel_listing_id' => ['required', 'integer'],
            'type' => ['sometimes', 'in:single,bundle'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sku_id' => ['required', 'integer'],
            'lines.*.quantity' => ['sometimes', 'integer', 'min:1'],
        ]);
        $listing = ChannelListing::query()->findOrFail($data['channel_listing_id']);   // 404 if not this tenant's
        $mappings = $service->setMapping((int) $tenant->id(), $listing, $data['type'] ?? 'single', $data['lines'], $request->user()->getKey());
        foreach ($mappings as $m) {
            $m->load('sku');
        }

        return response()->json(['data' => SkuMappingResource::collection($mappings)], 201);
    }

    /** DELETE /api/v1/sku-mappings/{id} */
    public function destroy(Request $request, int $id, SkuMappingService $service): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map'), 403, 'Bạn không có quyền bỏ ghép SKU.');
        $mapping = SkuMapping::query()->findOrFail($id);
        $service->removeMapping($mapping);

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** POST /api/v1/sku-mappings/auto-match */
    public function autoMatch(Request $request, SkuMappingService $service, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map'), 403, 'Bạn không có quyền ghép SKU.');
        $matched = $service->autoMatchUnmapped((int) $tenant->id(), $request->user()->getKey());

        return response()->json(['data' => ['matched' => $matched]]);
    }

    /**
     * GET /api/v1/orders/unmapped-skus  ?order_ids=1,2,3  — distinct channel SKUs (merged) across
     * orders whose items still have sku_id = null. See SPEC 0004 §3.3.
     */
    public function unmappedFromOrders(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map') && $request->user()->can('orders.view'), 403, 'Bạn không có quyền liên kết SKU.');
        $orderIds = array_values(array_filter(array_map('intval', explode(',', (string) $request->query('order_ids', '')))));

        $items = OrderItem::query()->whereNull('sku_id')
            ->whereHas('order', function ($o) use ($orderIds) {
                $o->where('source', '!=', 'manual')->whereNull('deleted_at');
                if ($orderIds !== []) {
                    $o->whereIn('id', $orderIds);
                }
            })
            ->with('order:id,channel_account_id')
            ->get(['id', 'order_id', 'external_sku_id', 'seller_sku', 'name']);

        // group by (channel_account_id, external_sku_id || seller_sku)
        /** @var array<string,array<string,mixed>> $groups */
        $groups = [];
        foreach ($items as $it) {
            $cid = $it->order?->channel_account_id;
            $extSku = $it->external_sku_id ?: ($it->seller_sku ?: null);
            if (! $cid || ! $extSku) {
                continue;
            }
            $key = $cid.'|'.$extSku;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'channel_account_id' => (int) $cid, 'external_sku_id' => $it->external_sku_id, 'seller_sku' => $it->seller_sku,
                    'sample_name' => $it->name, 'order_ids' => [], 'item_count' => 0,
                ];
            }
            $groups[$key]['order_ids'][] = (int) $it->order_id;
            $groups[$key]['item_count']++;
        }

        $shopNames = ChannelAccount::query()->whereIn('id', collect($groups)->pluck('channel_account_id')->unique())->get()
            ->mapWithKeys(fn ($a) => [(int) $a->getKey() => $a->effectiveName()]);
        $skuByCode = [];
        foreach (Sku::query()->get(['id', 'sku_code']) as $sku) {
            $skuByCode[SkuCodeNormalizer::normalize($sku->sku_code)] = (int) $sku->getKey();
        }

        $data = collect($groups)->map(function ($g) use ($shopNames, $skuByCode) {
            $extSku = $g['external_sku_id'] ?: $g['seller_sku'];
            $existingListing = ChannelListing::query()->where('channel_account_id', $g['channel_account_id'])
                ->where(fn ($q) => $q->where('external_sku_id', $extSku)->orWhere('seller_sku', $g['seller_sku'] ?: $extSku))->first();

            return [
                'channel_account_id' => $g['channel_account_id'],
                'channel_account_name' => $shopNames[$g['channel_account_id']] ?? ('#'.$g['channel_account_id']),
                'external_sku_id' => $g['external_sku_id'],
                'seller_sku' => $g['seller_sku'],
                'sample_name' => $g['sample_name'],
                'order_count' => count(array_unique($g['order_ids'])),
                'item_count' => $g['item_count'],
                'existing_listing_id' => $existingListing?->getKey(),
                'suggested_sku_id' => ($existingListing?->mappings()->value('sku_id')) ?? ($skuByCode[SkuCodeNormalizer::normalize($g['seller_sku'])] ?? null),
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    /**
     * POST /api/v1/orders/link-skus  { links:[{ channel_account_id, external_sku_id?, seller_sku?, sku_id }] }
     * — for each link: ensure a channel_listing, set a single×1 mapping, then synchronously re-resolve
     * every still-unmapped order (resolve sku_id / reserve stock / clear has_issue) so the response is
     * fully consistent. Idempotent. See SPEC 0004 §3.3.
     */
    public function linkFromOrders(Request $request, SkuMappingService $service, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map'), 403, 'Bạn không có quyền liên kết SKU.');
        $data = $request->validate([
            'links' => ['required', 'array', 'min:1', 'max:200'],
            'links.*.channel_account_id' => ['required', 'integer'],
            'links.*.external_sku_id' => ['sometimes', 'nullable', 'string', 'max:120'],
            'links.*.seller_sku' => ['sometimes', 'nullable', 'string', 'max:120'],
            'links.*.sku_id' => ['required', 'integer'],
        ]);
        $tenantId = (int) $tenant->id();
        $userId = $request->user()->getKey();

        $validShops = ChannelAccount::query()->whereIn('id', collect($data['links'])->pluck('channel_account_id')->unique())->pluck('id')->map(fn ($v) => (int) $v)->all();
        $validSkus = Sku::query()->whereIn('id', collect($data['links'])->pluck('sku_id')->unique())->pluck('id')->map(fn ($v) => (int) $v)->all();

        $listingsCreated = 0;
        foreach ($data['links'] as $link) {
            $cid = (int) $link['channel_account_id'];
            $skuId = (int) $link['sku_id'];
            $extSku = $link['external_sku_id'] ?: ($link['seller_sku'] ?: null);
            if ($extSku === null || ! in_array($cid, $validShops, true) || ! in_array($skuId, $validSkus, true)) {
                throw ValidationException::withMessages(['links' => 'Link không hợp lệ (gian hàng / SKU không thuộc workspace, hoặc thiếu mã SKU sàn).']);
            }
            $listing = ChannelListing::query()->firstOrCreate(
                ['channel_account_id' => $cid, 'external_sku_id' => $extSku],
                ['tenant_id' => $tenantId, 'seller_sku' => $link['seller_sku'] ?? null, 'title' => null, 'currency' => 'VND', 'is_active' => true],
            );
            if ($listing->wasRecentlyCreated) {
                $listingsCreated++;
            }
            $service->setMapping($tenantId, $listing, 'single', [['sku_id' => $skuId, 'quantity' => 1]], $userId);
        }

        // Re-resolve every channel order that still has an unmapped line — SYNCHRONOUSLY, so the API
        // response reflects the fully-resolved state. (An async re-fire would leave "Chưa liên kết SKU"
        // tags on similar orders until the queue catches up — the bug this fixes.) OrderInventoryService::apply()
        // resolves order_items.sku_id, reserves stock, clears has_issue, fires InventoryChanged (→ debounced
        // PushStockForSku) — idempotent (the ledger dedupes per (order_item, sku, type)).
        $resolved = 0;
        $inventory = app(OrderInventoryService::class);
        Order::query()->where('source', '!=', 'manual')->whereNull('deleted_at')
            ->whereHas('items', fn ($i) => $i->whereNull('sku_id'))->orderBy('id')->get()
            ->each(function (Order $order) use ($inventory, &$resolved) {
                $inventory->apply($order);
                $resolved++;
            });

        return response()->json(['data' => ['linked' => count($data['links']), 'listings_created' => $listingsCreated, 'orders_resolved' => $resolved]]);
    }
}
