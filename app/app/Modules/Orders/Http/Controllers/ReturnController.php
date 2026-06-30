<?php

namespace CMBcoreSeller\Modules\Orders\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\DTO\ReturnDTO;
use CMBcoreSeller\Modules\Channels\Jobs\SyncReturnsForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Http\Resources\ReturnResource;
use CMBcoreSeller\Modules\Orders\Models\OrderReturn;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Đơn Hoàn & Hủy (after-sales). List/detail = quyền `orders.view`; duyệt/từ chối = `orders.update`.
 * Duyệt/từ chối gọi connector sàn rồi re-poll để lấy trạng thái authoritative. SPEC 0025.
 */
class ReturnController extends Controller
{
    public function __construct(private ChannelRegistry $channels) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('orders.view'), 403, 'Bạn không có quyền xem đơn hoàn/hủy.');

        $query = OrderReturn::query()->with('order')->latest('id');
        if ($s = $request->query('status')) {
            $query->whereIn('status', array_map('strval', (array) $s));
        }
        if ($k = $request->query('kind')) {
            $query->whereIn('kind', array_map('strval', (array) $k));
        }
        if ($src = $request->query('source')) {
            $query->where('source', (string) $src);
        }
        if ($request->boolean('open_only')) {
            $query->open();
        }
        if ($term = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('external_order_id', 'like', "%{$term}%")->orWhere('external_return_id', 'like', "%{$term}%"));
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => ReturnResource::collection($page->getCollection()),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()?->can('orders.view'), 403, 'Bạn không có quyền xem đơn hoàn/hủy.');
        $ret = OrderReturn::query()->with('order')->findOrFail($id);

        return response()->json(['data' => new ReturnResource($ret)]);
    }

    public function stats(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('orders.view'), 403, 'Bạn không có quyền xem đơn hoàn/hủy.');
        $byStatus = OrderReturn::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return response()->json(['data' => [
            'by_status' => $byStatus,
            'open' => OrderReturn::query()->open()->count(),
            'requested' => (int) ($byStatus[AfterSalesStatus::Requested->value] ?? 0),
        ]]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        return $this->decide($request, $id, 'approve');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        return $this->decide($request, $id, 'reject');
    }

    private function decide(Request $request, int $id, string $action): JsonResponse
    {
        abort_unless($request->user()?->can('orders.update'), 403, 'Bạn không có quyền xử lý đơn hoàn/hủy.');
        /** @var OrderReturn $ret */
        $ret = OrderReturn::query()->findOrFail($id);
        $account = $ret->channel_account_id ? ChannelAccount::query()->find($ret->channel_account_id) : null;
        abort_if(! $account || ! $this->channels->has($account->provider), 422, 'Không tìm thấy gian hàng cho đơn hoàn/hủy này.');
        $connector = $this->channels->for($account->provider);
        abort_unless($connector->supports('returns.manage'), 422, 'Kênh bán này chưa hỗ trợ duyệt/từ chối hoàn-hủy.');

        // return_type + return_status để connector chọn đúng `decision` (TikTok bắt buộc). Connector
        // nào không cần (Shopee/Lazada) bỏ qua field thừa.
        $raw = (array) ($ret->raw ?? []);
        $params = array_filter([
            'comment' => $request->input('comment'),
            'return_type' => $raw['return_type'] ?? null,
            'return_status' => $raw['return_status'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        try {
            if ($ret->kind === ReturnDTO::KIND_CANCEL) {
                $connector->decideCancellation($account->authContext(), $ret->external_return_id, $action, $params);
            } else {
                $connector->decideReturn($account->authContext(), $ret->external_return_id, $action, $params);
            }
        } catch (\Throwable $e) {
            abort(422, 'Sàn từ chối thao tác: '.$e->getMessage());
        }

        // Cập nhật lạc quan + re-poll lấy trạng thái authoritative từ sàn.
        $ret->forceFill([
            'status' => $action === 'reject' ? AfterSalesStatus::Rejected : AfterSalesStatus::Approved,
            'decided_at' => now(),
        ])->save();
        SyncReturnsForShop::dispatch((int) $account->getKey());

        return response()->json(['data' => new ReturnResource($ret->fresh()->load('order'))]);
    }
}
