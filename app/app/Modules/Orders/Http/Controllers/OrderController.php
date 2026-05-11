<?php

namespace CMBcoreSeller\Modules\Orders\Http\Controllers;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Orders\Http\Resources\OrderResource;
use CMBcoreSeller\Modules\Orders\Models\Order;
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
            $query->with('items');
        } else {
            $query->withCount('items');
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

    /** GET /api/v1/orders/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeView($request);

        $order = Order::query()->with(['items', 'statusHistory'])->findOrFail($id);

        return response()->json(['data' => new OrderResource($order)]);
    }

    /** GET /api/v1/orders/stats — counts per canonical status (for the list tabs). */
    public function stats(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        $base = $this->applyFilters($request, Order::query(), excludeStatus: true);
        $counts = (clone $base)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $byStatus = [];
        foreach (StandardOrderStatus::cases() as $s) {
            $byStatus[$s->value] = (int) ($counts[$s->value] ?? 0);
        }

        return response()->json(['data' => [
            'total' => (clone $base)->count(),
            'has_issue' => (clone $base)->where('has_issue', true)->count(),
            'by_status' => $byStatus,
        ]]);
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

    private function applyFilters(Request $request, Builder $query, bool $excludeStatus = false): Builder
    {
        if (! $excludeStatus && $status = $request->query('status')) {
            $values = array_values(array_filter(array_map('trim', explode(',', (string) $status))));
            $valid = array_intersect($values, array_map(fn ($s) => $s->value, StandardOrderStatus::cases()));
            if ($valid) {
                $query->statusIn($valid);
            }
        }
        if ($source = $request->query('source')) {
            $query->whereIn('source', array_map('trim', explode(',', (string) $source)));
        }
        if ($cid = $request->query('channel_account_id')) {
            $query->where('channel_account_id', (int) $cid);
        }
        if ($request->boolean('has_issue', false)) {
            $query->where('has_issue', true);
        }
        if ($q = trim((string) $request->query('q', ''))) {
            $query->search($q);
        }
        if ($from = $request->query('placed_from')) {
            $query->where('placed_at', '>=', CarbonImmutable::parse($from)->startOfDay());
        }
        if ($to = $request->query('placed_to')) {
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
