<?php

namespace CMBcoreSeller\Modules\Support\Http\Controllers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Support\Exceptions\AttachmentInvalid;
use CMBcoreSeller\Modules\Support\Http\Requests\StoreAdminSupportMessageRequest;
use CMBcoreSeller\Modules\Support\Http\Resources\SupportMessageResource;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Support\Services\SupportConversationService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * /api/v1/admin/support-conversations — super-admin xem & trả lời hội thoại CSKH
 * XUYÊN tenant. CSKH nhắn NHIỀU tin trong cuộc open, tự đóng khi xong.
 *
 * Admin KHÔNG có CurrentTenant ⇒ mọi truy vấn bảng support_* phải `withoutGlobalScope`
 * (TenantScope ràng tenant_id=0). Tạo tin/đính kèm set tenant_id từ `$conv->tenant_id`
 * (lo trong SupportConversationService).
 */
class AdminSupportConversationController extends Controller
{
    public function __construct(private SupportConversationService $service) {}

    /** Danh sách hội thoại (lọc status / awaiting / tenant / q) — phân trang. */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $query = SupportConversation::query()->withoutGlobalScope(TenantScope::class)
            ->withCount(['messages' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)])
            ->with(['latestMessage' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if (($status = $request->query('status')) !== null && $status !== '') {
            $query->where('status', (string) $status);
        }
        if ($request->boolean('awaiting')) {
            $query->where('status', SupportConversation::STATUS_OPEN)
                ->where('last_sender', SupportConversation::SENDER_USER);
        }
        if ($tenantId = $request->query('tenant_id')) {
            $query->where('tenant_id', (int) $tenantId);
        }
        if ($q = $request->query('q')) {
            $query->whereHas('messages', fn ($m) => $m->withoutGlobalScope(TenantScope::class)->where('body', 'like', '%'.$q.'%'));
        }

        $page = $query->paginate($perPage);

        $tenantIds = collect($page->items())->pluck('tenant_id')->filter()->unique()->values();
        $userIds = collect($page->items())->pluck('user_id')->filter()->unique()->values();
        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get(['id', 'name'])->keyBy('id');
        $users = User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $rows = collect($page->items())->map(fn (SupportConversation $c) => $this->summary($c, $tenants, $users))->all();

        return response()->json([
            'data' => $rows,
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    /** Thread đầy đủ 1 hội thoại + nhãn tenant/người gửi. */
    public function show(string $id): JsonResponse
    {
        $conv = $this->findConv($id);
        $conv->load(['messages' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->orderBy('id')
            ->with(['attachments' => fn ($a) => $a->withoutGlobalScope(TenantScope::class)])]);

        $tenants = Tenant::query()->whereKey($conv->tenant_id)->get(['id', 'name'])->keyBy('id');
        $users = $conv->user_id ? User::query()->whereKey($conv->user_id)->get(['id', 'name', 'email'])->keyBy('id') : collect();

        return response()->json(['data' => $this->summary($conv, $tenants, $users) + [
            'messages' => SupportMessageResource::collection($conv->messages)->resolve(),
        ]]);
    }

    /** CSKH gửi tin (nhiều lần). Cuộc đã đóng ⇒ 422 CONVERSATION_CLOSED. */
    public function message(string $id, StoreAdminSupportMessageRequest $request): JsonResponse
    {
        $conv = $this->findConv($id);
        if ($conv->isClosed()) {
            return response()->json(['error' => ['code' => 'CONVERSATION_CLOSED', 'message' => 'Đoạn hội thoại đã đóng. Người dùng cần nhắn tin mới để mở cuộc mới.']], 422);
        }

        try {
            $this->service->postCskhMessage(
                $conv,
                (int) Auth::guard('admin_web')->id(),
                $request->input('body'),
                array_values($request->file('files', [])),
            );
        } catch (AttachmentInvalid $e) {
            return response()->json(['error' => ['code' => 'ATTACHMENT_INVALID', 'message' => $e->getMessage()]], 422);
        }

        AuditLog::record('support.conversation.message', $conv, ['support_conversation_id' => $conv->id, 'tenant_id' => $conv->tenant_id]);

        return $this->show($id);
    }

    /** Đóng hội thoại — chèn tin hệ thống + báo user. */
    public function close(string $id): JsonResponse
    {
        $conv = $this->findConv($id);
        $this->service->close($conv, (int) Auth::guard('admin_web')->id());

        AuditLog::record('support.conversation.close', $conv, ['support_conversation_id' => $conv->id, 'tenant_id' => $conv->tenant_id]);

        return $this->show($id);
    }

    private function findConv(string $id): SupportConversation
    {
        return SupportConversation::query()->withoutGlobalScope(TenantScope::class)->findOrFail((int) $id);
    }

    /**
     * @param  Collection<int,Tenant>  $tenants
     * @param  Collection<int,User>  $users
     * @return array<string,mixed>
     */
    private function summary(SupportConversation $c, $tenants, $users): array
    {
        return [
            'id' => $c->id,
            'tenant_id' => $c->tenant_id,
            'tenant' => $tenants->get($c->tenant_id)
                ? ['id' => $tenants[$c->tenant_id]->id, 'name' => $tenants[$c->tenant_id]->name]
                : null,
            'user' => $c->user_id && $users->get($c->user_id)
                ? ['id' => $users[$c->user_id]->id, 'name' => $users[$c->user_id]->name, 'email' => $users[$c->user_id]->email]
                : null,
            'status' => $c->status,
            'last_sender' => $c->last_sender,
            'awaiting' => $c->status === SupportConversation::STATUS_OPEN && $c->last_sender === SupportConversation::SENDER_USER,
            'message_count' => (int) ($c->messages_count ?? 0),
            'last_preview' => $c->relationLoaded('latestMessage') ? ($c->latestMessage?->body) : null,
            'user_unread_count' => (int) $c->user_unread_count,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
            'closed_at' => $c->closed_at?->toIso8601String(),
            'created_at' => $c->created_at?->toIso8601String(),
        ];
    }
}
