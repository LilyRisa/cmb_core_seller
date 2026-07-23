<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPageView;
use CMBcoreSeller\Modules\Notifications\Contracts\NotificationDispatcherContract;
use CMBcoreSeller\Modules\Notifications\Support\NotificationType;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Plan C (2026-07-23) — tenant user xem 1 trang "Chung" đã nhận qua panel thông báo. Chỉ cho
 * xem nếu tenant hiện tại THẬT SỰ nằm trong audience đã gửi — kiểm tra qua
 * {@see NotificationDispatcherContract::hasReceived()} (module Notifications), KHÔNG query
 * thẳng Model của module khác (luật module dependency). Không public theo slug.
 */
class GeneralNotificationPageViewController extends Controller
{
    public function __construct(private NotificationDispatcherContract $dispatcher) {}

    public function show(Request $request, string $slug, CurrentTenant $currentTenant): JsonResponse
    {
        $tenantId = $currentTenant->id();
        $userId = (int) $request->user()?->getKey();

        $page = GeneralNotificationPage::query()->where('slug', $slug)->first();
        if ($page === null) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy nội dung.']], 404);
        }

        if ($tenantId === null) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Bạn không có quyền xem nội dung này.']], 403);
        }

        $received = $this->dispatcher->hasReceived($tenantId, NotificationType::GENERAL_PAGE, 'general.page:'.$page->getKey());
        if (! $received) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Bạn không có quyền xem nội dung này.']], 403);
        }

        if ($page->isExpired()) {
            return response()->json(['error' => ['code' => 'PAGE_EXPIRED', 'message' => 'Nội dung đã hết hạn.']], 410);
        }

        $this->recordView($page, $tenantId, $userId);

        return response()->json(['data' => [
            'title' => $page->title,
            'body_html' => $page->body_html,
            'cover_image_url' => $page->cover_image_url,
            'cta_label' => $page->cta_label,
            'cta_url' => $page->cta_url,
            'sent_at' => $page->sent_at?->toIso8601String(),
        ]]);
    }

    private function recordView(GeneralNotificationPage $page, int $tenantId, int $userId): void
    {
        try {
            GeneralNotificationPageView::create([
                'page_id' => $page->getKey(), 'tenant_id' => $tenantId, 'user_id' => $userId, 'viewed_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Đã xem trước đó (race hoặc F5 lại) — bỏ qua, không phải lỗi.
        }
    }
}
