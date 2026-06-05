<?php

namespace CMBcoreSeller\Modules\Orders\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\PublicTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SPEC 0030 — public, un-authenticated order tracking by code (`order_number`).
 *
 * No auth, no tenant header: the order's tenant is inferred from the matched row.
 * We query without global scopes (tenant + soft-delete) and re-add the safe
 * constraints by hand; relations are eager-loaded with their tenant scope
 * stripped too (they're already FK-constrained to this order, so no leak).
 *
 * Only `source='manual'` orders are exposed. An empty/unknown/ambiguous code
 * returns a generic 404 — never revealing why.
 */
class PublicTrackingController extends Controller
{
    public function __construct(private readonly PublicTrackingService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            abort(404);
        }

        $orders = Order::query()
            ->withoutGlobalScopes()
            ->where('source', 'manual')
            ->where('order_number', $code)
            ->whereNull('deleted_at')
            ->with([
                'items' => fn ($q) => $q->withoutGlobalScopes(),
                'statusHistory' => fn ($q) => $q->withoutGlobalScopes(),
                'shipments' => fn ($q) => $q->withoutGlobalScopes()
                    ->with(['events' => fn ($e) => $e->withoutGlobalScopes()]),
            ])
            ->limit(2)
            ->get();

        // 0 = not found; >1 = ambiguous code across tenants (extremely rare) → hide.
        if ($orders->count() !== 1) {
            abort(404);
        }

        return response()->json(['data' => $this->service->build($orders->first())]);
    }
}
