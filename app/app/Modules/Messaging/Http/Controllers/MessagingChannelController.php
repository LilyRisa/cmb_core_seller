<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Messaging\Contracts\ListsPostsConnector;
use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
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
use Illuminate\Http\Request;
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
                    // SPEC 0035 — AI tự trả lời theo từng page.
                    'ai_auto_mode' => $meta !== null && $meta->ai_auto_mode,
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
                    'comment_sync' => $meta !== null ? [
                        'status' => $meta->comment_sync_status ?? 'idle',
                        'synced_at' => $meta->comment_synced_at?->toIso8601String(),
                        'error' => $meta->comment_sync_error,
                    ] : [
                        'status' => 'idle',
                        'synced_at' => null,
                        'error' => null,
                    ],
                ];
            });

        return response()->json(['data' => $pages]);
    }

    /** PATCH /channels/{id}/ai-mode — bật/tắt AI tự trả lời cho 1 page (SPEC 0035). Body { ai_auto_mode: bool }. */
    public function aiMode(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.ai.config');

        $data = $request->validate(['ai_auto_mode' => ['required', 'boolean']]);
        $account = ChannelAccount::query()->where('provider', 'facebook_page')->findOrFail($id);

        MessagingAccountMeta::query()->updateOrCreate(
            ['channel_account_id' => $account->id],
            ['tenant_id' => $account->tenant_id, 'ai_auto_mode' => (bool) $data['ai_auto_mode']],
        );

        AuditLog::record('messaging.facebook.ai_mode', null, [
            'external_shop_id' => $account->external_shop_id,
            'ai_auto_mode' => (bool) $data['ai_auto_mode'],
        ]);

        return response()->json(['data' => ['ok' => true, 'ai_auto_mode' => (bool) $data['ai_auto_mode']]]);
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

    /**
     * POST /channels/bulk-sync — đồng bộ lại hàng loạt page đã chọn (body: { ids: int[] }).
     * Chỉ thao tác trên account facebook_page của tenant (global scope tự lọc); id
     * lạ / sàn khác bị bỏ qua. Ghi 1 audit log gộp cho cả lô.
     */
    public function bulkSync(Request $request): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $ids = $this->validatedIds($request);
        $accounts = ChannelAccount::query()
            ->where('provider', 'facebook_page')->whereIn('id', $ids)->get();

        foreach ($accounts as $account) {
            MessagingAccountMeta::query()->updateOrCreate(
                ['channel_account_id' => $account->id],
                ['tenant_id' => $account->tenant_id, 'sync_status' => MessagingAccountMeta::SYNC_QUEUED],
            );
            BackfillMessagingChannel::dispatch($account->id);
            BackfillFacebookComments::dispatch($account->id);
        }

        if ($accounts->isNotEmpty()) {
            AuditLog::record('messaging.facebook.bulk_sync', null, [
                'external_shop_ids' => $accounts->pluck('external_shop_id')->all(),
                'count' => $accounts->count(),
            ]);
        }

        return response()->json(['data' => ['ok' => true, 'processed' => $accounts->count()]], 202);
    }

    /**
     * GET /channels/{id}/posts — liệt kê bài đăng của page để chọn (post picker cho
     * trigger comment_on_post). Connector phải có năng lực `post.list`
     * ({@see ListsPostsConnector}) — kiểm theo tên năng lực, không phải tên sàn.
     */
    public function posts(int $id, MessagingRegistry $registry, Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $account = ChannelAccount::query()->where('provider', 'facebook_page')->findOrFail($id);

        $connector = $registry->has($account->provider) ? $registry->for($account->provider) : null;
        if (! $connector instanceof ListsPostsConnector) {
            return response()->json(['error' => ['code' => 'UNSUPPORTED', 'message' => 'Kênh này không hỗ trợ liệt kê bài đăng.']], 422);
        }

        $auth = new MessagingAuthContext(
            channelAccountId: (int) $account->id,
            provider: $account->provider,
            externalShopId: (string) $account->external_shop_id,
            accessToken: (string) ($account->access_token ?? ''),
            extra: (array) ($account->meta ?? []),
        );

        $result = $connector->listPosts($auth, array_filter([
            'cursor' => $request->query('cursor'),
            'pageSize' => (int) $request->query('per_page', 25),
        ]));

        return response()->json(['data' => [
            'items' => $result['items'],
            'next_cursor' => $result['nextCursor'],
            'has_more' => $result['hasMore'],
        ]]);
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

    /**
     * POST /channels/bulk-disconnect — ngắt kết nối hàng loạt page đã chọn (body:
     * { ids: int[] }). Mỗi page xoá hẳn + cascade qua {@see FacebookPageDisconnectService}.
     * Id lạ / sàn khác bị bỏ qua. Ghi 1 audit log gộp cho cả lô.
     */
    public function bulkDisconnect(Request $request, FacebookPageDisconnectService $service): JsonResponse
    {
        Gate::authorize('messaging.connect');

        $ids = $this->validatedIds($request);
        $accounts = ChannelAccount::query()
            ->where('provider', 'facebook_page')->whereIn('id', $ids)->get();

        $externalShopIds = [];
        $conversationsDeleted = 0;
        foreach ($accounts as $account) {
            $externalShopIds[] = $account->external_shop_id;
            $result = $service->disconnect($account);
            $conversationsDeleted += $result['conversations'];
        }

        if ($accounts->isNotEmpty()) {
            AuditLog::record('messaging.facebook.bulk_disconnected', null, [
                'external_shop_ids' => $externalShopIds,
                'count' => count($externalShopIds),
                'conversations_deleted' => $conversationsDeleted,
            ]);
        }

        return response()->json(['data' => [
            'ok' => true,
            'processed' => count($externalShopIds),
            'conversations_deleted' => $conversationsDeleted,
        ]]);
    }

    /**
     * Validate + chuẩn hoá body `ids` cho các hành động hàng loạt.
     *
     * @return array<int, int>
     */
    private function validatedIds(Request $request): array
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return array_values(array_unique(array_map('intval', $data['ids'])));
    }
}
