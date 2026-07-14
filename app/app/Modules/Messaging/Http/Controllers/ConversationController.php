<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Messaging\Http\Resources\ConversationResource;
use CMBcoreSeller\Modules\Messaging\Http\Resources\MessageResource;
use CMBcoreSeller\Modules\Messaging\Jobs\ReportOrderConversionToMeta;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\OrderConfirmationNotifier;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        if ($threadType = $request->query('thread_type')) {
            // Lọc loại hội thoại Facebook: message (DM) | comment. CSV cũng được.
            $q->whereIn('thread_type', explode(',', (string) $threadType));
        }
        if ($customerId = $request->query('customer_id')) {
            $q->where('customer_id', (int) $customerId);
        }
        $search = trim((string) $request->query('q', ''));
        // >=2 ký tự mới quét nội dung tin nhắn (messages.body) — chặn quét toàn bộ
        // lịch sử với 1 ký tự. Tên/SĐT/preview vẫn tìm với mọi độ dài.
        $searchLong = mb_strlen($search) >= 2;
        if ($search !== '') {
            // Postgres LIKE phân biệt hoa/thường ⇒ dùng ILIKE cho nhất quán với SQLite dev.
            $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $q->where(function ($qq) use ($search, $like, $searchLong) {
                $qq->where('buyer_name', $like, "%{$search}%")
                    ->orWhere('last_message_preview', $like, "%{$search}%")
                    ->orWhere('detected_phone', $like, "%{$search}%");
                if ($searchLong) {
                    $qq->orWhereHas('messages', fn ($m) => $m->where('body', $like, "%{$search}%"));
                }
            });
        }

        $q->orderByDesc('last_message_at');

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $q->paginate($perPage)->appends($request->query());

        // Khớp trong tin nhắn cũ ⇒ gắn `match_snippet` (đoạn trích quanh từ khoá) để FE
        // hiển thị lý do hội thoại xuất hiện. 1 truy vấn cho cả trang (≤per_page) — không N+1.
        if ($search !== '' && $searchLong) {
            $this->attachMatchSnippets($page->getCollection(), $search);
        }

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

    /**
     * Gắn `match_snippet` cho các hội thoại có từ khoá nằm trong nội dung tin nhắn cũ.
     * Lấy tin khớp MỚI NHẤT của từng hội thoại bằng 1 truy vấn (tenant global scope tự áp).
     *
     * @param  Collection<int,Conversation>  $conversations
     */
    private function attachMatchSnippets($conversations, string $search): void
    {
        $ids = $conversations->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $rows = Message::query()
            ->whereIn('conversation_id', $ids)
            ->where('body', $like, "%{$search}%")
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get(['conversation_id', 'body']);

        $byConv = [];
        foreach ($rows as $row) {
            // Bản mới nhất tới trước (đã orderByDesc) ⇒ chỉ giữ lần gặp đầu tiên.
            $byConv[$row->conversation_id] ??= $this->buildSnippet((string) $row->body, $search);
        }

        foreach ($conversations as $conv) {
            $conv->setAttribute('match_snippet', $byConv[$conv->id] ?? null);
        }
    }

    /** Cắt ~120 ký tự quanh vị trí khớp, thêm dấu … ở đầu/cuối nếu bị cắt. */
    private function buildSnippet(string $body, string $search): string
    {
        $pos = mb_stripos($body, $search);
        if ($pos === false) {
            return mb_substr($body, 0, 120);
        }
        $start = max(0, $pos - 40);
        $snippet = mb_substr($body, $start, 120);
        if ($start > 0) {
            $snippet = '…'.$snippet;
        }
        if ($start + 120 < mb_strlen($body)) {
            $snippet .= '…';
        }

        return $snippet;
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

    /**
     * Gắn 1 đơn (vừa tạo từ khung chat) vào hội thoại.
     *
     * Đơn phải thuộc tenant hiện tại (Order dùng BelongsToTenant ⇒ global scope tự lọc;
     * đơn của tenant khác trả null ⇒ 404). Ghi đè order_id cũ nếu có.
     */
    public function linkOrder(int $id, Request $request): JsonResponse
    {
        Gate::authorize('messaging.view');

        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            // SPEC 0031 — true khi đơn vừa được tạo TRONG khung chat ⇒ tự gửi tin xác nhận
            // cho khách (kèm link tra cứu). Link đơn cũ thủ công không đặt cờ này.
            'notify_customer' => ['sometimes', 'boolean'],
        ]);

        $conv = Conversation::query()->findOrFail($id);

        $order = Order::query()->find($data['order_id']);
        if ($order === null) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Đơn không tồn tại hoặc không thuộc gian hàng.'],
            ], 404);
        }

        $conv->update(['order_id' => $order->getKey()]);
        AuditLog::record('messaging.conversation.order_linked', null, [
            'conversation_id' => $conv->id,
            'order_id' => $order->getKey(),
        ]);

        // SPEC 0031 — best-effort, không bao giờ làm hỏng link nếu gửi lỗi (notifier tự nuốt lỗi).
        if ($request->boolean('notify_customer')) {
            app(OrderConfirmationNotifier::class)->notify($conv->fresh(), $order);
            // Design 2026-07-14 — best-effort, hội thoại/kênh không đủ điều kiện thì job tự bỏ qua.
            ReportOrderConversionToMeta::dispatch($conv->id, $order->id);
        }

        return response()->json(['data' => (new ConversationResource($conv->fresh()))->toArray($request)]);
    }
}
