<?php

namespace CMBcoreSeller\Modules\Notifications\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Notifications\Http\Resources\NotificationResource;
use CMBcoreSeller\Modules\Notifications\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chuông thông báo in-app của user trong tenant hiện tại (SPEC 0036, Plan A mở rộng
 * category 2026-07-23). TenantScope + filter `user_id` đảm bảo chỉ thấy thông báo của
 * CHÍNH MÌNH trong tenant hiện tại. Controller mỏng — không cần Service riêng.
 */
class NotificationController extends Controller
{
    private const CATEGORIES = ['order', 'system', 'general'];

    /**
     * Danh sách (mới nhất trước); `?status=unread` lọc chưa đọc, `?category=` lọc theo
     * tab panel FE. `meta.unread_count` = tổng (không lọc); `meta.unread_count_by_category`
     * luôn trả đủ 3 khóa để FE hiện badge riêng từng tab.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()?->getKey();
        $limit = max(1, min(100, (int) $request->query('limit', 30)));

        $query = Notification::query()->where('user_id', $userId);
        if ($request->query('status') === 'unread') {
            $query->whereNull('read_at');
        }
        $category = $request->query('category');
        if (is_string($category) && in_array($category, self::CATEGORIES, true)) {
            $query->where('category', $category);
        }
        $items = $query->latest('id')->limit($limit)->get();

        return response()->json([
            'data' => NotificationResource::collection($items)->resolve(),
            'meta' => [
                'unread_count' => $this->unreadCount($userId),
                'unread_count_by_category' => $this->unreadCountByCategory($userId),
            ],
        ]);
    }

    /** Đánh dấu đã đọc 1 thông báo; trả `unread_count` còn lại. */
    public function read(Request $request, string $id): JsonResponse
    {
        $userId = (int) $request->user()?->getKey();
        $notification = Notification::query()->where('user_id', $userId)->findOrFail((int) $id);
        if ($notification->read_at === null) {
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json(['data' => ['unread_count' => $this->unreadCount($userId)]]);
    }

    /** Đánh dấu đã đọc tất cả; trả `unread_count` (=0). */
    public function readAll(Request $request): JsonResponse
    {
        $userId = (int) $request->user()?->getKey();
        Notification::query()->where('user_id', $userId)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['data' => ['unread_count' => 0]]);
    }

    private function unreadCount(int $userId): int
    {
        return Notification::query()->where('user_id', $userId)->whereNull('read_at')->count();
    }

    /** @return array{order:int,system:int,general:int} */
    private function unreadCountByCategory(int $userId): array
    {
        $counts = Notification::query()
            ->where('user_id', $userId)->whereNull('read_at')
            ->selectRaw('category, count(*) as c')->groupBy('category')
            ->pluck('c', 'category');

        return [
            'order' => (int) ($counts['order'] ?? 0),
            'system' => (int) ($counts['system'] ?? 0),
            'general' => (int) ($counts['general'] ?? 0),
        ];
    }
}
