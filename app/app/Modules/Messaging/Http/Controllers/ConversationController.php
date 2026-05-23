<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Messaging\Http\Resources\ConversationResource;
use CMBcoreSeller\Modules\Messaging\Http\Resources\MessageResource;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * REST inbox endpoints.
 *
 * Route group `auth:sanctum + verified + tenant + plan.feature:messaging_inbox`.
 * Permission gate `can:messaging.view` / `messaging.reply` ở từng action.
 *
 * S1: list + show + read. Send + AI suggestion ở `MessageController` riêng.
 */
class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $q = Conversation::query()->with(['channelAccount', 'pageMeta']); // nguồn gốc + avatar page — tránh N+1

        if ($provider = $request->query('provider')) {
            $q->whereIn('provider', explode(',', (string) $provider));
        }
        if ($status = $request->query('status')) {
            $q->whereIn('status', explode(',', (string) $status));
        } else {
            // Default: ẩn spam khỏi inbox
            $q->where('status', '!=', Conversation::STATUS_SPAM);
        }
        if ($request->boolean('blocked')) {
            $q->whereNotNull('blocked_at');
        } else {
            $q->whereNull('blocked_at');   // ẩn hội thoại đã chặn khỏi inbox mặc định
        }
        if ($request->boolean('unread')) {
            $q->where('unread_count', '>', 0);
        }
        if ($request->boolean('read')) {
            $q->where('unread_count', 0);
        }
        if ($request->boolean('has_phone')) {
            $q->where('has_phone', true);
        }
        if ($tags = $request->query('tags')) {
            $ids = array_filter(array_map('intval', explode(',', (string) $tags)));
            if ($ids !== []) {
                $q->where(function ($qq) use ($ids) {
                    foreach ($ids as $id) {
                        $qq->orWhereJsonContains('tags', $id);
                    }
                });
            }
        }
        if ($assigned = $request->query('assigned')) {
            if ($assigned === 'me') {
                $q->where('assigned_user_id', $request->user()->id);
            } elseif ($assigned === 'unassigned') {
                $q->whereNull('assigned_user_id');
            } elseif (ctype_digit((string) $assigned)) {
                $q->where('assigned_user_id', (int) $assigned);
            }
        }
        if ($channelId = $request->query('channel_account_id')) {
            // Hỗ trợ chọn NHIỀU trang/gian hàng: CSV "5,6,7" → whereIn. Cũng nhận 1 id.
            $ids = array_values(array_filter(array_map('intval', explode(',', (string) $channelId))));
            if ($ids !== []) {
                $q->whereIn('channel_account_id', $ids);
            }
        }
        if ($customerId = $request->query('customer_id')) {
            $q->where('customer_id', (int) $customerId);
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($qq) use ($search) {
                $qq->where('buyer_name', 'like', "%{$search}%")
                    ->orWhere('last_message_preview', 'like', "%{$search}%");
            });
        }

        $q->orderByDesc('last_message_at');

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        // Trả `meta.pagination` chuẩn (giống OrderController) để FE infinite-scroll
        // đọc được total_pages — `Resource::collection($paginate)` chỉ cho meta mặc định Laravel.
        return response()->json([
            'data' => ConversationResource::collection($page->getCollection())->toArray($request),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $conv = Conversation::query()->with(['channelAccount', 'pageMeta'])->findOrFail($id);

        // Thứ tự theo GIỜ TIN THẬT (sent_at), fallback created_at — tin backfill có
        // created_at = giờ ingest (gần như nhau) nên sort theo created_at sẽ sai thứ tự
        // (tin bị gom cụm theo thứ tự nạp, không theo thời gian). id để tie-break ổn định.
        $messagesQuery = Message::query()
            ->with('attachments')
            ->where('conversation_id', $conv->id)
            ->orderByRaw('COALESCE(sent_at, created_at) DESC')
            ->orderByDesc('id');

        // Cursor-style: ?before_message_id=N (lazy load older)
        if ($before = $request->query('before_message_id')) {
            $messagesQuery->where('id', '<', (int) $before);
        }

        $messages = $messagesQuery->limit(50)->get()->reverse()->values();

        return response()->json([
            'data' => [
                'conversation' => (new ConversationResource($conv))->toArray($request),
                'messages' => MessageResource::collection($messages)->toArray($request),
            ],
        ]);
    }

    public function markRead(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $conv = Conversation::query()->findOrFail($id);
        $conv->update(['unread_count' => 0]);

        // Cập nhật `read_at` cho inbound chưa đọc.
        Message::query()
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_INBOUND)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function markUnread(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $conv = Conversation::query()->findOrFail($id);

        $latestInbound = Message::query()
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_INBOUND)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $latestInbound) {
            return response()->json([
                'error' => ['code' => 'NO_INBOUND', 'message' => 'Không có tin của người mua để đánh dấu chưa đọc.'],
            ], 422);
        }

        $latestInbound->forceFill(['read_at' => null])->save();
        $conv->update(['unread_count' => max(1, (int) $conv->unread_count)]);

        return response()->json(['data' => (new ConversationResource($conv->fresh()))->toArray($request)]);
    }

    public function block(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $conv = Conversation::query()->findOrFail($id);
        $conv->update([
            'blocked_at' => now(),
            'blocked_by_user_id' => $request->user()->id,
        ]);
        AuditLog::record('messaging.conversation.blocked', null, [
            'conversation_id' => $conv->id, 'buyer_external_id' => $conv->buyer_external_id,
        ]);

        return response()->json(['data' => (new ConversationResource($conv->fresh()))->toArray($request)]);
    }

    public function unblock(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.reply');

        $conv = Conversation::query()->findOrFail($id);
        $conv->update(['blocked_at' => null, 'blocked_by_user_id' => null]);
        AuditLog::record('messaging.conversation.unblocked', null, [
            'conversation_id' => $conv->id,
        ]);

        return response()->json(['data' => (new ConversationResource($conv->fresh()))->toArray($request)]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $data = $request->validate([
            'status' => ['nullable', 'in:open,snoozed,resolved,spam'],
            'snoozed_until' => ['nullable', 'date'],
            'assigned_user_id' => ['nullable', 'integer'],
            'tags' => ['nullable', 'array'],
        ]);

        $conv = Conversation::query()->findOrFail($id);

        if (isset($data['assigned_user_id'])) {
            Gate::authorize('messaging.assign');
        }

        if (isset($data['status']) && $data['status'] === Conversation::STATUS_SNOOZED && empty($data['snoozed_until'])) {
            return response()->json([
                'error' => ['code' => 'VALIDATION_FAILED', 'message' => 'snoozed_until là bắt buộc khi status=snoozed.'],
            ], 422);
        }

        $conv->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return response()->json(['data' => (new ConversationResource($conv))->toArray($request)]);
    }
}
