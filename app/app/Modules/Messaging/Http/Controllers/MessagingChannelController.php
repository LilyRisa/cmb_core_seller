<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Services\FacebookPageDisconnectService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Quản lý kênh nhắn tin Facebook Page cho UI /messaging/channels (SPEC-0024
 * bổ khuyết, design 2026-05-20). List + ngắt kết nối (xoá hẳn). Kết nối &
 * kết nối-lại đi qua FacebookOAuthController (POST facebook/connect).
 */
class MessagingChannelController extends Controller
{
    /** GET /api/v1/messaging/channels — list page Facebook đã kết nối (không trả token). */
    public function index(): JsonResponse
    {
        Gate::authorize('messaging.view');

        $pages = ChannelAccount::query()
            ->where('provider', 'facebook_page')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ChannelAccount $a) => [
                'id' => $a->id,
                'provider' => $a->provider,
                'shop_name' => $a->shop_name,
                'name' => $a->effectiveName(),
                'external_shop_id' => $a->external_shop_id,
                'status' => $a->status,
                'messaging_enabled' => (bool) $a->messaging_enabled,
                'token_expired' => $a->status === ChannelAccount::STATUS_EXPIRED,
                'connected_at' => $a->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $pages]);
    }

    /** GET /api/v1/messaging/capabilities — capability map của mọi messaging connector đang bật. */
    public function capabilities(\CMBcoreSeller\Integrations\Messaging\MessagingRegistry $registry): JsonResponse
    {
        Gate::authorize('messaging.view');

        $caps = [];
        foreach ($registry->providers() as $code) {
            $caps[$code] = $registry->for($code)->capabilities();
        }

        return response()->json(['data' => $caps]);
    }

    /** DELETE /api/v1/messaging/channels/{id} — ngắt kết nối 1 page (xoá hẳn + cascade). */
    public function destroy(int $id, FacebookPageDisconnectService $service): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $account = ChannelAccount::query()->where('provider', 'facebook_page')->findOrFail($id);
        $externalShopId = $account->external_shop_id;

        $result = $service->disconnect($account);

        AuditLog::record('messaging.facebook.disconnected', null, [
            'external_shop_id' => $externalShopId,
            'conversations_deleted' => $result['conversations'],
        ]);

        return response()->json(['data' => ['ok' => true, 'conversations_deleted' => $result['conversations']]]);
    }
}
