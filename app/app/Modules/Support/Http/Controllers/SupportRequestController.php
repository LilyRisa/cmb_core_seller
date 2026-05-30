<?php

namespace CMBcoreSeller\Modules\Support\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Support\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Yêu cầu hỗ trợ CSKH từ widget trợ giúp (tab "Hỏi CSKH"). Lưu câu hỏi vào hàng đợi
 * chờ CSKH phản hồi (tenant auto-set qua BelongsToTenant). Dùng được cho mọi gói.
 */
class SupportRequestController extends Controller
{
    /** Lịch sử yêu cầu CSKH của tenant hiện tại. */
    public function index(Request $request): JsonResponse
    {
        $items = SupportRequest::query()
            ->latest('id')
            ->limit(50)
            ->get(['id', 'question', 'status', 'answer', 'answered_at', 'created_at'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'question' => $r->question,
                'status' => $r->status,
                'answer' => $r->answer,
                'answered_at' => $r->answered_at?->toIso8601String(),
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
        ]);

        $req = SupportRequest::query()->create([
            'user_id' => $request->user()?->getKey(),
            'question' => (string) $data['question'],
            'status' => SupportRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'data' => [
                'id' => $req->id,
                'status' => $req->status,
                'message' => 'Đã gửi yêu cầu tới CSKH. Vui lòng chờ trong giờ làm việc, nhân viên sẽ phản hồi sớm nhất.',
            ],
        ], 201);
    }
}
