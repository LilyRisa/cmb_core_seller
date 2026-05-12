<?php

namespace CMBcoreSeller\Modules\Inventory\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Inventory\Http\Resources\SkuMappingResource;
use CMBcoreSeller\Modules\Inventory\Models\SkuMapping;
use CMBcoreSeller\Modules\Inventory\Services\SkuMappingService;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
