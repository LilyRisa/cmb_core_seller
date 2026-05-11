<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Http\Resources\ChannelAccountResource;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Channels\Services\ChannelConnectionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /api/v1/channel-accounts — list connected shops, start an OAuth connect,
 * disconnect, trigger a resync. (No route-model binding: tenant-scoped lookups
 * happen in the action where CurrentTenant is set.) See SPEC 0001 §6.
 */
class ChannelAccountController extends Controller
{
    /** GET /api/v1/channel-accounts */
    public function index(Request $request, ChannelRegistry $registry): JsonResponse
    {
        $this->authorizeView($request);

        $accounts = ChannelAccount::query()->orderByDesc('created_at')->get();
        $connectable = collect($registry->providers())
            ->filter(fn ($p) => $p !== 'manual' && $registry->for($p)->supports('orders.fetch'))
            ->map(fn ($p) => ['code' => $p, 'name' => $registry->for($p)->displayName()])
            ->values();

        return response()->json([
            'data' => ChannelAccountResource::collection($accounts),
            'meta' => ['connectable_providers' => $connectable],
        ]);
    }

    /** POST /api/v1/channel-accounts/{provider}/connect -> { auth_url } */
    public function connect(Request $request, string $provider, ChannelConnectionService $service, CurrentTenant $tenant): JsonResponse
    {
        $this->authorizeManage($request);

        try {
            $authUrl = $service->startConnect(
                $provider,
                (int) $tenant->getOrFail()->getKey(),
                $request->user()?->getKey(),
                $request->string('redirect_after')->value() ?: null,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => ['code' => 'PROVIDER_NOT_CONNECTABLE', 'message' => $e->getMessage()]], 422);
        }

        return response()->json(['data' => ['auth_url' => $authUrl, 'provider' => $provider]]);
    }

    /** DELETE /api/v1/channel-accounts/{id} */
    public function destroy(Request $request, int $id, ChannelConnectionService $service): JsonResponse
    {
        $this->authorizeManage($request);
        $account = ChannelAccount::query()->findOrFail($id);

        $service->disconnect($account);

        return response()->json(['data' => new ChannelAccountResource($account->refresh())]);
    }

    /** POST /api/v1/channel-accounts/{id}/resync */
    public function resync(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);
        $account = ChannelAccount::query()->findOrFail($id);
        abort_unless($account->isActive(), 409, 'Gian hàng không ở trạng thái hoạt động.');

        SyncOrdersForShop::dispatch((int) $account->getKey(), null, SyncRun::TYPE_POLL);

        return response()->json(['data' => ['queued' => true, 'channel_account_id' => $account->getKey()]]);
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->can('channels.view'), 403, 'Bạn không có quyền xem gian hàng.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()?->can('channels.manage'), 403, 'Chỉ chủ sở hữu / quản trị mới quản lý gian hàng.');
    }
}
