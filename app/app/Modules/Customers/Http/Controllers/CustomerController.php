<?php

namespace CMBcoreSeller\Modules\Customers\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Customers\Http\Resources\CustomerNoteResource;
use CMBcoreSeller\Modules\Customers\Http\Resources\CustomerResource;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use CMBcoreSeller\Modules\Customers\Services\CustomerMergeService;
use CMBcoreSeller\Modules\Customers\Services\CustomerService;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Orders\Http\Resources\OrderResource;
use CMBcoreSeller\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /api/v1/customers — the internal buyer registry: list/filter, view, orders of a
 * customer, notes, block/unblock, tags, merge. Matching & stats are owned by the
 * Customers module's services; this controller is thin. See SPEC 0002 §6.1.
 */
class CustomerController extends Controller
{
    private const SORTABLE = [
        '-last_seen_at' => ['last_seen_at', 'desc'],
        'last_seen_at' => ['last_seen_at', 'asc'],
        '-lifetime_revenue' => ['lifetime_stats->revenue_completed', 'desc'],
        '-orders_total' => ['lifetime_stats->orders_total', 'desc'],
        '-cancellation_rate' => ['lifetime_stats->orders_cancelled', 'desc'],   // approximation (no stored rate)
        '-reputation_score' => ['reputation_score', 'desc'],
        'reputation_score' => ['reputation_score', 'asc'],
    ];

    /** GET /api/v1/customers */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        $query = $this->applyFilters($request, Customer::query());

        [$col, $dir] = self::SORTABLE[(string) $request->query('sort', '-last_seen_at')] ?? self::SORTABLE['-last_seen_at'];
        $query->orderBy($col, $dir)->orderByDesc('id');

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => CustomerResource::collection($page->getCollection()),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    /** GET /api/v1/customers/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeView($request);
        $customer = Customer::query()->with(['notes' => fn ($q) => $q->limit(50)])->findOrFail($id);

        return response()->json(['data' => new CustomerResource($customer)]);
    }

    /**
     * GET /api/v1/customers/lookup?phone=0912xxxxxx — SPEC 0021 / UI taodon.png.
     *
     * Khi user nhập SĐT lúc tạo đơn thủ công, FE gọi endpoint này:
     *  - Có khớp customer ⇒ trả `customer` (CustomerResource) + `addresses` (địa chỉ cũ — lấy
     *    từ `addresses_meta`, đã có) + `open_orders` (đơn đang xử lý) + `returning_orders`
     *    (đơn đang/đã hoàn) để FE hiện cảnh báo + danh sách order_number.
     *  - Không khớp ⇒ trả `{ customer: null }`.
     *
     * Không ném 404 nếu không khớp — đây là endpoint tra cứu nhanh, không phải get-by-id.
     */
    public function lookup(Request $request): JsonResponse
    {
        $this->authorizeView($request);
        $phone = (string) $request->query('phone', '');
        $hash = CustomerPhoneNormalizer::normalizeAndHash($phone);
        if ($hash === null) {
            return response()->json(['data' => ['customer' => null, 'addresses' => [], 'open_orders' => [], 'returning_orders' => []]]);
        }
        $customer = Customer::query()->where('phone_hash', $hash)->first();
        if (! $customer) {
            return response()->json(['data' => ['customer' => null, 'addresses' => [], 'open_orders' => [], 'returning_orders' => []]]);
        }

        // Lấy đơn của customer này — bỏ TenantScope vì global scope đã filter bởi current tenant.
        $orders = Order::query()->where('customer_id', $customer->getKey())
            ->orderByDesc('placed_at')->orderByDesc('id')->limit(100)->get(['id', 'order_number', 'status', 'placed_at', 'grand_total', 'source']);

        // "Đang xử lý" = các status pre-shipment + shipped (chưa giao xong).
        $openStatuses = ['unpaid', 'pending', 'processing', 'ready_to_ship', 'shipped'];
        $returningStatuses = ['returning', 'delivery_failed', 'returned_refunded'];

        $statusValue = fn ($o) => $o->status instanceof \BackedEnum ? $o->status->value : (string) $o->status;
        $mapOrder = fn ($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'status' => $statusValue($o),
            'placed_at' => $o->placed_at?->toIso8601String(),
            'grand_total' => (int) $o->grand_total,
            'source' => $o->source,
        ];

        return response()->json(['data' => [
            'customer' => new CustomerResource($customer),
            // `addresses_meta` đã lưu top 5 địa chỉ gần nhất (xem CustomerLinkingService::mergeAddresses).
            'addresses' => (array) ($customer->addresses_meta ?? []),
            'open_orders' => $orders->filter(fn ($o) => in_array($statusValue($o), $openStatuses, true))
                ->values()->map($mapOrder)->all(),
            'returning_orders' => $orders->filter(fn ($o) => in_array($statusValue($o), $returningStatuses, true))
                ->values()->map($mapOrder)->all(),
        ]]);
    }

    /** GET /api/v1/customers/{id}/orders */
    public function orders(Request $request, int $id): JsonResponse
    {
        $this->authorizeView($request);
        abort_unless($request->user()?->can('orders.view'), 403, 'Bạn không có quyền xem đơn hàng.');

        $customer = Customer::query()->findOrFail($id);

        $query = Order::query()->where('customer_id', $customer->getKey())->withCount('items');
        if ($source = $request->query('source')) {
            $query->whereIn('source', array_map('trim', explode(',', (string) $source)));
        }
        if ($status = $request->query('status')) {
            $query->whereIn('status', array_map('trim', explode(',', (string) $status)));
        }
        $query->orderByDesc('placed_at')->orderByDesc('id');

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => OrderResource::collection($page->getCollection()),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    /** POST /api/v1/customers/{id}/notes */
    public function storeNote(Request $request, int $id, CustomerService $service): JsonResponse
    {
        abort_unless($request->user()?->can('customers.note'), 403, 'Bạn không có quyền ghi chú khách hàng.');
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'severity' => ['sometimes', 'in:info,warning,danger'],
            'order_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $customer = Customer::query()->findOrFail($id);
        $note = $service->addNote($customer, $request->user(), $data['note'], $data['severity'] ?? CustomerNote::SEV_INFO, $data['order_id'] ?? null);

        return response()->json(['data' => new CustomerNoteResource($note)], 201);
    }

    /** DELETE /api/v1/customers/{id}/notes/{noteId} */
    public function destroyNote(Request $request, int $id, int $noteId, CustomerService $service): JsonResponse
    {
        abort_unless($request->user()?->can('customers.note'), 403, 'Bạn không có quyền sửa ghi chú.');
        $note = CustomerNote::query()->where('customer_id', $id)->findOrFail($noteId);

        $isOwnerAdmin = $request->user()->can('customers.merge'); // owner/admin have '*'
        abort_unless($isOwnerAdmin || $note->author_user_id === $request->user()->getKey(), 403, 'Chỉ người tạo (hoặc quản trị) mới xoá được ghi chú.');
        abort_if($note->isAuto(), 422, 'Không thể xoá ghi chú tự động của hệ thống.');

        $service->deleteNote($note);

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** POST /api/v1/customers/{id}/block */
    public function block(Request $request, int $id, CustomerService $service): JsonResponse
    {
        abort_unless($request->user()?->can('customers.block'), 403, 'Chỉ chủ sở hữu / quản trị mới chặn khách.');
        $data = $request->validate(['reason' => ['sometimes', 'nullable', 'string', 'max:255']]);
        $customer = Customer::query()->findOrFail($id);

        return response()->json(['data' => new CustomerResource($service->block($customer, $request->user(), $data['reason'] ?? null))]);
    }

    /** POST /api/v1/customers/{id}/unblock */
    public function unblock(Request $request, int $id, CustomerService $service): JsonResponse
    {
        abort_unless($request->user()?->can('customers.block'), 403, 'Chỉ chủ sở hữu / quản trị mới bỏ chặn khách.');
        $customer = Customer::query()->findOrFail($id);

        return response()->json(['data' => new CustomerResource($service->unblock($customer, $request->user()))]);
    }

    /** POST /api/v1/customers/{id}/tags */
    public function tags(Request $request, int $id, CustomerService $service): JsonResponse
    {
        abort_unless($request->user()?->can('customers.note'), 403, 'Bạn không có quyền sửa nhãn khách hàng.');
        $data = $request->validate([
            'add' => ['sometimes', 'array'], 'add.*' => ['string', 'max:50'],
            'remove' => ['sometimes', 'array'], 'remove.*' => ['string', 'max:50'],
        ]);
        $customer = Customer::query()->findOrFail($id);

        return response()->json(['data' => new CustomerResource($service->setTags($customer, $data['add'] ?? [], $data['remove'] ?? []))]);
    }

    /** POST /api/v1/customers/merge */
    public function merge(Request $request, CustomerMergeService $service): JsonResponse
    {
        abort_unless($request->user()?->can('customers.merge'), 403, 'Chỉ chủ sở hữu / quản trị mới gộp khách.');
        $data = $request->validate([
            'keep_id' => ['required', 'integer'],
            'remove_id' => ['required', 'integer', 'different:keep_id'],
        ]);
        $keep = Customer::query()->findOrFail($data['keep_id']);
        $remove = Customer::query()->findOrFail($data['remove_id']);

        return response()->json(['data' => new CustomerResource($service->merge($keep, $remove, $request->user()))]);
    }

    // --- helpers -------------------------------------------------------------

    private function applyFilters(Request $request, Builder $query): Builder
    {
        if ($q = trim((string) $request->query('q', ''))) {
            $hash = CustomerPhoneNormalizer::normalizeAndHash($q);
            if ($hash !== null) {
                $query->where('phone_hash', $hash);
            } else {
                $query->where('name', 'like', "%{$q}%");
            }
        }
        if ($rep = $request->query('reputation')) {
            $labels = array_intersect(array_map('trim', explode(',', (string) $rep)), [Customer::LABEL_OK, Customer::LABEL_WATCH, Customer::LABEL_RISK, Customer::LABEL_BLOCKED]);
            if ($labels) {
                $query->whereIn('reputation_label', array_values($labels));
            }
        }
        if ($tag = $request->query('tag')) {
            $query->whereJsonContains('tags', (string) $tag);
        }
        if ($request->filled('min_orders')) {
            $query->where('lifetime_stats->orders_total', '>=', (int) $request->query('min_orders'));
        }
        if ($request->boolean('has_note')) {
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from('customer_notes')->whereColumn('customer_notes.customer_id', 'customers.id'));
        }
        if ($request->boolean('blocked')) {
            $query->where('is_blocked', true);
        }

        return $query;
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->can('customers.view'), 403, 'Bạn không có quyền xem khách hàng.');
    }
}
