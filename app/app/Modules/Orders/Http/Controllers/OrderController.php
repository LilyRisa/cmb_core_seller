<?php

namespace CMBcoreSeller\Modules\Orders\Http\Controllers;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Orders\Http\Resources\OrderResource;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Services\ManualOrderService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /api/v1/orders — list/filter/sort orders, view one, edit tags/note, status
 * counts. Conventions: docs/05-api/conventions.md. The canonical status of a
 * channel order is not user-editable in Phase 1 (only tags/note); see SPEC 0001 §4.
 */
class OrderController extends Controller
{
    private const SORTABLE = ['placed_at', 'grand_total', 'created_at', 'source_updated_at'];

    /** GET /api/v1/orders */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        $query = $this->applyFilters($request, Order::query());

        // sort: ?sort=-placed_at  (default newest first)
        $sort = (string) $request->query('sort', '-placed_at');
        $dir = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');
        if (! in_array($field, self::SORTABLE, true)) {
            $field = 'placed_at';
            $dir = 'desc';
        }
        $query->orderBy($field, $dir)->orderByDesc('id');

        if (in_array('items', explode(',', (string) $request->query('include', '')), true)) {
            $query->with(['items', 'channelAccount']);
        } else {
            $query->withCount('items')->with('channelAccount');
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => OrderResource::collection($page->getCollection()),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    /** POST /api/v1/orders — create a manual order (source=manual). See SPEC 0003 §6. */
    public function store(Request $request, ManualOrderService $service, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($request->user()?->can('orders.create'), 403, 'Bạn không có quyền tạo đơn.');
        $data = $request->validate([
            'sub_source' => ['sometimes', 'nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'in:pending,processing'],
            'buyer' => ['sometimes', 'array'],
            'buyer.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'buyer.phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'buyer.address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'buyer.ward' => ['sometimes', 'nullable', 'string', 'max:120'],
            'buyer.district' => ['sometimes', 'nullable', 'string', 'max:120'],
            'buyer.province' => ['sometimes', 'nullable', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_id' => ['required', 'integer'],
            'items.*.name' => ['sometimes', 'string', 'max:255'],
            'items.*.variation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.quantity' => ['sometimes', 'integer', 'min:1'],
            'items.*.unit_price' => ['sometimes', 'integer', 'min:0'],
            'items.*.discount' => ['sometimes', 'integer', 'min:0'],
            'shipping_fee' => ['sometimes', 'integer', 'min:0'],
            'tax' => ['sometimes', 'integer', 'min:0'],
            'is_cod' => ['sometimes', 'boolean'],
            'cod_amount' => ['sometimes', 'integer', 'min:0'],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'tags' => ['sometimes', 'array'],
        ]);
        $order = $service->create((int) $tenant->id(), $request->user()->getKey(), $data);

        return response()->json(['data' => new OrderResource($order->load(['items', 'statusHistory']))], 201);
    }

    /** PATCH /api/v1/orders/{id} — edit a manual order (buyer / fees / note / tags; not line items). */
    public function update(Request $request, int $id, ManualOrderService $service): JsonResponse
    {
        abort_unless($request->user()?->can('orders.update'), 403, 'Bạn không có quyền sửa đơn.');
        $order = Order::query()->findOrFail($id);
        $data = $request->validate([
            'buyer' => ['sometimes', 'array'],
            'shipping_fee' => ['sometimes', 'integer', 'min:0'],
            'tax' => ['sometimes', 'integer', 'min:0'],
            'is_cod' => ['sometimes', 'boolean'],
            'cod_amount' => ['sometimes', 'integer', 'min:0'],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'tags' => ['sometimes', 'array'],
        ]);
        $order = $service->update($order, $data);

        return response()->json(['data' => new OrderResource($order->load(['items', 'statusHistory']))]);
    }

    /** POST /api/v1/orders/{id}/cancel — cancel a manual order (releases stock). */
    public function cancel(Request $request, int $id, ManualOrderService $service): JsonResponse
    {
        abort_unless($request->user()?->can('orders.update'), 403, 'Bạn không có quyền huỷ đơn.');
        $order = Order::query()->findOrFail($id);
        $data = $request->validate(['reason' => ['sometimes', 'nullable', 'string', 'max:255']]);
        $order = $service->cancel($order, $request->user()->getKey(), $data['reason'] ?? null);

        return response()->json(['data' => new OrderResource($order->load(['items', 'statusHistory']))]);
    }

    /** GET /api/v1/orders/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeView($request);

        $order = Order::query()->with(['items', 'statusHistory'])->findOrFail($id);

        return response()->json(['data' => new OrderResource($order)]);
    }

    /**
     * GET /api/v1/orders/stats — faceted counts for the "Lọc" panel + status tabs.
     * - status counts use every filter EXCEPT status/has_issue (so the tabs show their own counts);
     * - source/shop/carrier counts use every filter EXCEPT source/channel_account_id/carrier
     *   (so each chip row shows full counts regardless of the current chip selection in the others).
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        // Base for the status tabs (everything except status/has_issue).
        $statusBase = $this->applyFilters($request, Order::query(), skip: ['status', 'has_issue']);
        $counts = (clone $statusBase)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $byStatus = [];
        foreach (StandardOrderStatus::cases() as $s) {
            $byStatus[$s->value] = (int) ($counts[$s->value] ?? 0);
        }

        // Base for the chip rows (everything except the chip facets themselves).
        $facetBase = $this->applyFilters($request, Order::query(), skip: ['source', 'channel_account_id', 'carrier']);
        $bySource = (clone $facetBase)->selectRaw('source, count(*) as c')->groupBy('source')->orderByDesc('c')
            ->pluck('c', 'source')->map(fn ($n, $src) => ['source' => (string) $src, 'count' => (int) $n])->values()->all();
        $byShop = (clone $facetBase)->whereNotNull('channel_account_id')->selectRaw('channel_account_id, count(*) as c')->groupBy('channel_account_id')->orderByDesc('c')
            ->pluck('c', 'channel_account_id')->map(fn ($n, $cid) => ['channel_account_id' => (int) $cid, 'count' => (int) $n])->values()->all();
        $byCarrier = (clone $facetBase)->whereNotNull('carrier')->selectRaw('carrier, count(*) as c')->groupBy('carrier')->orderByDesc('c')
            ->pluck('c', 'carrier')->map(fn ($n, $carrier) => ['carrier' => (string) $carrier, 'count' => (int) $n])->values()->all();

        return response()->json(['data' => [
            'total' => (clone $statusBase)->count(),
            'has_issue' => (clone $statusBase)->where('has_issue', true)->count(),
            'unmapped' => (clone $statusBase)->where('has_issue', true)->where('issue_reason', 'SKU chưa ghép')->count(),
            'by_status' => $byStatus,
            'by_source' => $bySource,
            'by_shop' => $byShop,
            'by_carrier' => $byCarrier,
        ]]);
    }

    /** POST /api/v1/orders/sync — dispatch an order sync for every active connected shop of the tenant. */
    public function sync(Request $request): JsonResponse
    {
        $this->authorizeView($request);
        $n = 0;
        ChannelAccount::query()->active()->orderBy('id')->each(function (ChannelAccount $a) use (&$n) {
            SyncOrdersForShop::dispatch((int) $a->getKey(), null, SyncRun::TYPE_POLL);
            $n++;
        });

        return response()->json(['data' => ['queued' => $n]]);
    }

    /** POST /api/v1/orders/{id}/tags  { add?: string[], remove?: string[] } */
    public function updateTags(Request $request, int $id): JsonResponse
    {
        $this->authorizeUpdate($request);
        $data = $request->validate([
            'add' => ['sometimes', 'array'], 'add.*' => ['string', 'max:50'],
            'remove' => ['sometimes', 'array'], 'remove.*' => ['string', 'max:50'],
        ]);

        $order = Order::query()->findOrFail($id);
        $tags = collect($order->tags ?? [])
            ->merge($data['add'] ?? [])->reject(fn ($t) => in_array($t, $data['remove'] ?? [], true))
            ->map(fn ($t) => trim((string) $t))->filter()->unique()->values()->all();
        $order->forceFill(['tags' => $tags])->save();

        return response()->json(['data' => new OrderResource($order->loadCount('items'))]);
    }

    /** PATCH /api/v1/orders/{id}/note  { note: string|null } */
    public function updateNote(Request $request, int $id): JsonResponse
    {
        $this->authorizeUpdate($request);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        $order = Order::query()->findOrFail($id);
        $order->forceFill(['note' => $data['note'] ?: null])->save();

        return response()->json(['data' => new OrderResource($order->loadCount('items'))]);
    }

    // --- helpers -------------------------------------------------------------

    /**
     * @param  list<string>  $skip  filter keys to NOT apply (used by stats() for faceted counts):
     *                              status | source | channel_account_id | carrier | has_issue | q | sku | product | placed
     */
    private function applyFilters(Request $request, Builder $query, array $skip = []): Builder
    {
        $use = fn (string $key) => ! in_array($key, $skip, true);

        if ($use('status') && $status = $request->query('status')) {
            $values = array_values(array_filter(array_map('trim', explode(',', (string) $status))));
            $valid = array_intersect($values, array_map(fn ($s) => $s->value, StandardOrderStatus::cases()));
            if ($valid) {
                $query->statusIn($valid);
            }
        }
        if ($use('source') && $source = $request->query('source')) {
            $query->whereIn('source', array_map('trim', explode(',', (string) $source)));
        }
        if ($use('channel_account_id') && $cid = $request->query('channel_account_id')) {
            $query->where('channel_account_id', (int) $cid);
        }
        if ($use('carrier') && $carrier = $request->query('carrier')) {
            $query->whereIn('carrier', array_map('trim', explode(',', (string) $carrier)));
        }
        if ($use('has_issue') && $request->boolean('has_issue', false)) {
            $query->where('has_issue', true);
        }
        if ($use('q') && $q = trim((string) $request->query('q', ''))) {
            $query->search($q);
        }
        if ($use('sku') && $sku = trim((string) $request->query('sku', ''))) {
            $query->whereHas('items', fn (Builder $i) => $i->where('seller_sku', 'like', "%{$sku}%"));
        }
        if ($use('product') && $product = trim((string) $request->query('product', ''))) {
            $query->whereHas('items', fn (Builder $i) => $i->where('name', 'like', "%{$product}%"));
        }
        if ($use('placed') && $from = $request->query('placed_from')) {
            $query->where('placed_at', '>=', CarbonImmutable::parse($from)->startOfDay());
        }
        if ($use('placed') && $to = $request->query('placed_to')) {
            $query->where('placed_at', '<=', CarbonImmutable::parse($to)->endOfDay());
        }
        if ($tag = $request->query('tag')) {
            $query->whereJsonContains('tags', (string) $tag);
        }

        return $query;
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->can('orders.view'), 403, 'Bạn không có quyền xem đơn hàng.');
    }

    private function authorizeUpdate(Request $request): void
    {
        abort_unless($request->user()?->can('orders.update'), 403, 'Bạn không có quyền cập nhật đơn hàng.');
    }
}
