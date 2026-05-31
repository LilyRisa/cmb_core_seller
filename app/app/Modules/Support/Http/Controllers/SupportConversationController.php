<?php

namespace CMBcoreSeller\Modules\Support\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Support\Exceptions\AttachmentInvalid;
use CMBcoreSeller\Modules\Support\Http\Requests\StoreSupportMessageRequest;
use CMBcoreSeller\Modules\Support\Http\Resources\SupportConversationResource;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Support\Services\SupportConversationService;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;

/**
 * Hội thoại CSKH từ widget trợ giúp (tab "Hỏi CSKH"), theo tenant hiện tại.
 *
 * Controller mỏng: FormRequest → SupportConversationService → API Resource. Đính kèm
 * sai MIME/size ⇒ 422 ATTACHMENT_INVALID (giống Messaging SPEC-0024).
 */
class SupportConversationController extends Controller
{
    public function __construct(private SupportConversationService $service) {}

    /** Danh sách hội thoại CSKH của tenant (kèm tin + đính kèm). */
    public function index(): JsonResponse
    {
        $convs = SupportConversation::query()
            ->latest('id')
            ->limit(50)
            ->with(['messages' => fn ($q) => $q->orderBy('id')->with('attachments')])
            ->get();

        return response()->json(['data' => SupportConversationResource::collection($convs)->resolve()]);
    }

    /** Nguồn NHẸ cho badge widget: tổng tin CSKH chưa đọc của tenant. */
    public function unread(): JsonResponse
    {
        return response()->json(['data' => ['unread' => (int) SupportConversation::query()->sum('user_unread_count')]]);
    }

    /** Gửi tin — tự mở cuộc mới nếu cuộc gần nhất đã đóng. */
    public function store(StoreSupportMessageRequest $request): JsonResponse
    {
        try {
            $conv = $this->service->postUserMessage(
                (int) app(CurrentTenant::class)->id(),
                $request->user()?->getKey(),
                $request->input('body'),
                array_values($request->file('files', [])),
            );
        } catch (AttachmentInvalid $e) {
            return response()->json(['error' => ['code' => 'ATTACHMENT_INVALID', 'message' => $e->getMessage()]], 422);
        }

        $conv->load(['messages' => fn ($q) => $q->orderBy('id')->with('attachments')]);

        return response()->json(['data' => (new SupportConversationResource($conv))->resolve()], 201);
    }

    /** User đã xem ⇒ xoá unread của 1 cuộc; trả tổng unread còn lại. */
    public function read(string $id): JsonResponse
    {
        $conv = SupportConversation::query()->findOrFail((int) $id);
        $this->service->markUserRead($conv);

        return response()->json(['data' => ['unread' => (int) SupportConversation::query()->sum('user_unread_count')]]);
    }
}
