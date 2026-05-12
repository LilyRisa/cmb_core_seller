<?php

namespace CMBcoreSeller\Modules\Products\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Jobs\FetchChannelListings;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Products\Http\Resources\ChannelListingResource;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** /api/v1/channel-listings — products/variants on connected shops + their SKU mappings. See SPEC 0003 §6. */
class ChannelListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403, 'Bạn không có quyền xem listing.');
        $q = ChannelListing::query()->withCount('mappings')->with('mappings.sku');
        if ($cid = $request->query('channel_account_id')) {
            $q->where('channel_account_id', (int) $cid);
        }
        if ($status = $request->query('sync_status')) {
            $q->where('sync_status', (string) $status);
        }
        if ($request->has('mapped')) {
            $request->boolean('mapped') ? $q->whereHas('mappings') : $q->unmapped();
        }
        if ($term = trim((string) $request->query('q', ''))) {
            $q->where(fn ($w) => $w->where('title', 'like', "%{$term}%")->orWhere('seller_sku', 'like', "%{$term}%")->orWhere('external_sku_id', 'like', "%{$term}%"));
        }
        $q->orderByDesc('id');
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => ChannelListingResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    /** POST /api/v1/channel-listings/sync — pull listings from every active shop that supports it, then auto-match. */
    public function sync(Request $request, ChannelRegistry $registry): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map'), 403, 'Bạn không có quyền đồng bộ listing.');
        $n = 0;
        ChannelAccount::query()->active()->orderBy('id')->each(function (ChannelAccount $a) use ($registry, &$n) {
            if ($registry->has($a->provider) && $registry->for($a->provider)->supports('listings.fetch')) {
                FetchChannelListings::dispatch((int) $a->getKey());
                $n++;
            }
        });

        return response()->json(['data' => ['queued' => $n]]);
    }

    /** PATCH /api/v1/channel-listings/{id}  { is_stock_locked? } — pin/unpin auto-push. */
    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.map'), 403, 'Bạn không có quyền sửa listing.');
        $listing = ChannelListing::query()->findOrFail($id);
        $data = $request->validate(['is_stock_locked' => ['sometimes', 'boolean']]);
        $listing->forceFill($data)->save();

        return response()->json(['data' => new ChannelListingResource($listing->loadCount('mappings')->load('mappings.sku'))]);
    }
}
