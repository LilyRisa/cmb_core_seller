<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillFacebookComments;
use CMBcoreSeller\Modules\Messaging\Jobs\BackfillMessagingChannel;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\FacebookPageDisconnectService;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
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
    public function __construct(private MediaStorage $media) {}

    /** GET /api/v1/messaging/channels — list page Facebook đã kết nối (không trả token). */
    public function index(): JsonResponse
    {
        Gate::authorize('messaging.view');

        $pages = ChannelAccount::query()
            ->where('provider', 'facebook_page')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ChannelAccount $a) {
                $meta = MessagingAccountMeta::query()->find($a->id);
                $liveCount = (int) Conversation::query()
                    ->where('channel_account_id', $a->id)->sum('message_count');

                return [
                    'id' => $a->id,
                    'provider' => $a->provider,
                    'shop_name' => $a->shop_name,
                    'name' => $a->effectiveName(),
                    'external_shop_id' => $a->external_shop_id,
                    'status' => $a->status,
                    'messaging_enabled' => (bool) $a->messaging_enabled,
                    'token_expired' => $a->status === ChannelAccount::STATUS_EXPIRED,
                    'connected_at' => $a->created_at?->toIso8601String(),
                    'avatar_url' => $this->media->temporaryUrlForPath($meta?->page_avatar_path),
                    'message_count' => $liveCount,
                    'sync' => $meta !== null ? [
                        'status' => $meta->sync_status ?? 'idle',
                        'total' => $meta->sync_total_conversations,
                        'done' => (int) $meta->sync_done_conversations,
                        'message_count' => (int) $meta->sync_message_count,
                        'started_at' => $meta->sync_started_at?->toIso8601String(),
                        'finished_at' => $meta->sync_finished_at?->toIso8601String(),
                        'last_synced_at' => $meta->last_synced_at?->toIso8601String(),
                        'error' => $meta->sync_error,
                    ] : [
                        'status' => 'idle',
                        'total' => null,
                        'done' => 0,
                        'message_count' => 0,
                        'started_at' => null,
                        'finished_at' => null,
                        'last_synced_at' => null,
                        'error' => null,
                    ],
                ];
            });

        return response()->json(['data' => $pages]);
    }

    /** POST /channels/{id}/sync — đồng bộ lại lịch sử (manual backfill). */
    public function sync(int $id): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $account = ChannelAccount::query()->where('provider', 'facebook_page')->findOrFail($id);

        MessagingAccountMeta::query()->updateOrCreate(
            ['channel_account_id' => $account->id],
            ['tenant_id' => $account->tenant_id, 'sync_status' => MessagingAccountMeta::SYNC_QUEUED],
        );
        BackfillMessagingChannel::dispatch($account->id);
        BackfillFacebookComments::dispatch($account->id);
        AuditLog::record('messaging.facebook.sync.requested', null, ['external_shop_id' => $account->external_shop_id]);

        return response()->json(['data' => ['ok' => true]], 202);
    }

    /** GET /api/v1/messaging/capabilities — capability map của mọi messaging connector đang bật. */
    public function capabilities(MessagingRegistry $registry): JsonResponse
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
