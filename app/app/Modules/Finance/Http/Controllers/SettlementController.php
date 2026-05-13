<?php

namespace CMBcoreSeller\Modules\Finance\Http\Controllers;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Finance\Http\Resources\SettlementResource;
use CMBcoreSeller\Modules\Finance\Jobs\FetchSettlementsForShop;
use CMBcoreSeller\Modules\Finance\Models\Settlement;
use CMBcoreSeller\Modules\Finance\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * /api/v1/settlements + /channel-accounts/{id}/fetch-settlements. SPEC 0016.
 *
 * Permissions: `finance.view` (đọc), `finance.reconcile` (manual reconcile + fetch).
 */
class SettlementController extends Controller
{
    public function __construct(private readonly SettlementService $service) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('finance.view'), 403, 'Bạn không có quyền xem đối soát.');
        $q = Settlement::query()->with('channelAccount');
        if ($cid = $request->query('channel_account_id')) {
            $q->where('channel_account_id', (int) $cid);
        }
        if ($s = $request->query('status')) {
            $q->whereIn('status', array_filter(array_map('trim', explode(',', (string) $s))));
        }
        if ($from = $request->query('from')) {
            $q->where('period_end', '>=', CarbonImmutable::parse((string) $from)->startOfDay());
        }
        if ($to = $request->query('to')) {
            $q->where('period_start', '<=', CarbonImmutable::parse((string) $to)->endOfDay());
        }
        $q->orderByDesc('period_end');
        $page = $q->paginate(min(100, max(1, (int) $request->query('per_page', 20))))->appends($request->query());

        return response()->json([
            'data' => SettlementResource::collection($page->getCollection()),
            'meta' => ['pagination' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'total_pages' => $page->lastPage()]],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('finance.view'), 403, 'Bạn không có quyền xem đối soát.');
        $settlement = Settlement::query()->with(['channelAccount', 'lines.order:id,order_number,external_order_id'])->findOrFail($id);

        return response()->json(['data' => new SettlementResource($settlement)]);
    }

    public function reconcile(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('finance.reconcile'), 403, 'Bạn không có quyền đối chiếu.');
        $settlement = Settlement::query()->findOrFail($id);
        $matched = $this->service->reconcile($settlement);

        return response()->json(['data' => ['matched' => $matched, 'settlement' => new SettlementResource($settlement->fresh(['channelAccount']))]]);
    }

    /** POST /channel-accounts/{id}/fetch-settlements `{from?, to?, sync?}` — sync=true ⇒ chạy đồng bộ trong request (chủ yếu cho test/sandbox). */
    public function fetchForShop(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('finance.reconcile'), 403, 'Bạn không có quyền kéo đối soát.');
        $data = $request->validate([
            'from' => ['sometimes', 'nullable', 'date'], 'to' => ['sometimes', 'nullable', 'date', 'after_or_equal:from'],
            'sync' => ['sometimes', 'boolean'],
        ]);
        $account = ChannelAccount::query()->findOrFail($id);
        try {
            if (! empty($data['sync'])) {
                $r = $this->service->fetchForShop($account,
                    isset($data['from']) ? CarbonImmutable::parse($data['from']) : null,
                    isset($data['to']) ? CarbonImmutable::parse($data['to']) : null,
                    $request->user()->getKey(),
                );

                return response()->json(['data' => ['fetched' => $r['fetched'], 'lines' => $r['lines'], 'queued' => false]]);
            }
        } catch (UnsupportedOperation $e) {
            throw ValidationException::withMessages(['provider' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['provider' => $e->getMessage()]);
        }
        FetchSettlementsForShop::dispatch((int) $account->getKey(), $data['from'] ?? null, $data['to'] ?? null);

        return response()->json(['data' => ['queued' => true]]);
    }
}
