<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Modules\Channels\Http\Resources\ChannelAccountResource;
use CMBcoreSeller\Modules\Channels\Jobs\FetchChannelListings;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Channels\Services\ChannelConnectionService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

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

    /**
     * GET /api/v1/channel-accounts/outbound-ip -> { ip: "1.2.3.4" }
     *
     * Trả IP outbound mà server đang dùng để gọi ra Internet — người dùng cần copy IP này vào
     * "IP Whitelist" của app Lazada (Lazada Open Platform → App Management → Security → IP Whitelist)
     * khi gặp mã lỗi `AppWhiteIpLimit`. Cache 30 phút để khỏi spam dịch vụ ngoài.
     */
    public function outboundIp(Request $request): JsonResponse
    {
        $this->authorizeManage($request);
        $ip = Cache::remember('channels.outbound_ip', now()->addMinutes(30), function () {
            foreach (['https://api.ipify.org?format=text', 'https://ifconfig.me/ip', 'https://ipinfo.io/ip'] as $url) {
                try {
                    $resp = Http::timeout(5)->get($url);
                    $ip = trim((string) $resp->body());
                    if ($resp->successful() && filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                } catch (ConnectionException) {
                    continue;
                }
            }

            return null;
        });

        return response()->json(['data' => ['ip' => $ip, 'detected' => $ip !== null]]);
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

    /**
     * DELETE /api/v1/channel-accounts/{id}  body: { confirm: "<tên gian hàng>" }
     * Xóa kết nối + TẤT CẢ đơn hàng của gian hàng + hủy mọi liên kết SKU của nó. Yêu cầu gõ đúng tên
     * gian hàng để xác nhận (chống xóa nhầm). Trả số đơn đã xóa & số liên kết SKU đã hủy.
     */
    public function destroy(Request $request, int $id, ChannelConnectionService $service): JsonResponse
    {
        $this->authorizeManage($request);
        $account = ChannelAccount::query()->findOrFail($id);
        $data = $request->validate(['confirm' => ['required', 'string', 'max:255']]);
        if (mb_strtolower(trim($data['confirm'])) !== mb_strtolower(trim($account->effectiveName()))) {
            throw ValidationException::withMessages(['confirm' => 'Mã xác nhận không khớp — gõ đúng tên gian hàng «'.$account->effectiveName().'» để xóa.']);
        }

        $result = $service->deleteWithOrders($account, $request->user()?->getKey());

        return response()->json(['data' => ['deleted_orders' => $result['deleted_orders'], 'unlinked_skus' => $result['unlinked_skus']]]);
    }

    /** PATCH /api/v1/channel-accounts/{id} — set a display alias (two shops can share the same shop_name). */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);
        $account = ChannelAccount::query()->findOrFail($id);
        $data = $request->validate(['display_name' => ['present', 'nullable', 'string', 'max:120']]);
        $account->forceFill(['display_name' => ($data['display_name'] ?? null) ?: null])->save();

        return response()->json(['data' => new ChannelAccountResource($account)]);
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

    /** POST /api/v1/channel-accounts/{id}/resync-listings — pull this shop's listings into channel_listings + auto-match. */
    public function resyncListings(Request $request, int $id, ChannelRegistry $registry): JsonResponse
    {
        $this->authorizeManage($request);
        $account = ChannelAccount::query()->findOrFail($id);
        abort_unless($account->isActive(), 409, 'Gian hàng không ở trạng thái hoạt động.');
        abort_unless($registry->has($account->provider) && $registry->for($account->provider)->supports('listings.fetch'), 422, 'Gian hàng này chưa hỗ trợ đồng bộ listing.');

        FetchChannelListings::dispatch((int) $account->getKey());

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
