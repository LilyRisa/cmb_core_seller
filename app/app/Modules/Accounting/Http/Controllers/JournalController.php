<?php

namespace CMBcoreSeller\Modules\Accounting\Http\Controllers;

use CMBcoreSeller\Modules\Accounting\DTO\JournalEntryDTO;
use CMBcoreSeller\Modules\Accounting\DTO\JournalLineDTO;
use CMBcoreSeller\Modules\Accounting\Http\Resources\JournalEntryResource;
use CMBcoreSeller\Modules\Accounting\Models\JournalEntry;
use CMBcoreSeller\Modules\Accounting\Services\JournalService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

class JournalController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly JournalService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $q = JournalEntry::query()
            ->where('tenant_id', $this->tenant->id())
            ->with('period')
            ->orderBy('posted_at', 'desc')
            ->orderBy('id', 'desc');

        if ($period = $request->query('period')) {
            $q->whereHas('period', fn ($w) => $w->where('code', $period));
        }
        if ($src = $request->query('source_module')) {
            if ($src === 'manual') {
                $q->where('source_module', JournalEntry::SOURCE_MANUAL);
            } elseif ($src === 'auto') {
                $q->where('source_module', '!=', JournalEntry::SOURCE_MANUAL);
            } else {
                $q->where('source_module', $src);
            }
        }
        if ($from = $request->query('from')) {
            $q->where('posted_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $request->query('to')) {
            $q->where('posted_at', '<=', Carbon::parse($to)->endOfDay());
        }
        if ($s = $request->query('q')) {
            $q->where(function ($w) use ($s) {
                $w->where('code', 'like', "%$s%")->orWhere('narration', 'like', "%$s%");
            });
        }
        if ($acc = $request->query('account_code')) {
            $q->whereHas('lines', fn ($w) => $w->where('account_code', $acc));
        }

        $perPage = max(1, min(100, $request->integer('per_page', 20)));
        $rows = $q->paginate($perPage);

        return JournalEntryResource::collection($rows);
    }

    public function show(int $id): JournalEntryResource
    {
        $row = JournalEntry::query()
            ->where('tenant_id', $this->tenant->id())
            ->with(['period', 'lines.account'])
            ->findOrFail($id);

        return new JournalEntryResource($row);
    }

    /** POST /accounting/journals — bút toán tay */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'posted_at' => 'required|date',
            'narration' => 'nullable|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_code' => 'required|string|max:16',
            'lines.*.dr_amount' => 'sometimes|integer|min:0',
            'lines.*.cr_amount' => 'sometimes|integer|min:0',
            'lines.*.party_type' => 'nullable|string|in:customer,supplier,staff,channel',
            'lines.*.party_id' => 'nullable|integer|min:1',
            'lines.*.dim_warehouse_id' => 'nullable|integer|min:1',
            'lines.*.dim_shop_id' => 'nullable|integer|min:1',
            'lines.*.dim_sku_id' => 'nullable|integer|min:1',
            'lines.*.dim_order_id' => 'nullable|integer|min:1',
            'lines.*.memo' => 'nullable|string|max:500',
        ]);

        $idempotencyKey = (string) ($request->header('Idempotency-Key')
            ?: sprintf('manual.%d.%s', (int) $request->user()->getKey(), bin2hex(random_bytes(8))));

        $lineDtos = [];
        foreach ($request->input('lines') as $l) {
            $lineDtos[] = new JournalLineDTO(
                accountCode: (string) $l['account_code'],
                drAmount: (int) ($l['dr_amount'] ?? 0),
                crAmount: (int) ($l['cr_amount'] ?? 0),
                partyType: $l['party_type'] ?? null,
                partyId: isset($l['party_id']) ? (int) $l['party_id'] : null,
                dimWarehouseId: isset($l['dim_warehouse_id']) ? (int) $l['dim_warehouse_id'] : null,
                dimShopId: isset($l['dim_shop_id']) ? (int) $l['dim_shop_id'] : null,
                dimSkuId: isset($l['dim_sku_id']) ? (int) $l['dim_sku_id'] : null,
                dimOrderId: isset($l['dim_order_id']) ? (int) $l['dim_order_id'] : null,
                memo: $l['memo'] ?? null,
            );
        }

        $dto = new JournalEntryDTO(
            tenantId: (int) $this->tenant->id(),
            postedAt: Carbon::parse($request->input('posted_at')),
            sourceModule: JournalEntry::SOURCE_MANUAL,
            sourceType: 'manual',
            sourceId: null,
            idempotencyKey: 'manual:'.$idempotencyKey,
            lines: $lineDtos,
            narration: $request->input('narration'),
            createdBy: (int) $request->user()->getKey(),
        );

        $entry = $this->service->post($dto);
        $entry->load(['period', 'lines.account']);

        return (new JournalEntryResource($entry))->response()->setStatusCode(201);
    }

    public function reverse(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:255']);
        $entry = JournalEntry::query()
            ->where('tenant_id', $this->tenant->id())
            ->with(['lines', 'period'])
            ->findOrFail($id);
        $reversal = $this->service->reverse($entry, (int) $request->user()->getKey(), $request->input('reason'));
        $reversal->load(['period', 'lines.account']);
        // Trả 200 thay vì 201 — reverse là idempotent (gọi 2 lần trả entry cũ); semantically đây không
        // phải "create resource mới" từ phía API caller, mà là "get-or-create".
        return (new JournalEntryResource($reversal))->response()->setStatusCode(200);
    }
}
