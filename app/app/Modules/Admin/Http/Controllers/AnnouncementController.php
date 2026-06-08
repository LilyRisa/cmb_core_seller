<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Admin\Models\Announcement;
use Illuminate\Http\JsonResponse;

/**
 * Đọc popup announcement đang active cho USER (SPEC 0037) — `auth:sanctum` + `verified`.
 * Toàn hệ thống (không tenant-scoped); FE tự nhớ-đã-xem theo tab (sessionStorage).
 */
class AnnouncementController extends Controller
{
    public function active(): JsonResponse
    {
        $rows = Announcement::query()->activeNow()->latest('id')->limit(10)->get();

        return response()->json([
            'data' => $rows->map(fn (Announcement $a): array => [
                'id' => $a->id,
                'title' => $a->title,
                'body_html' => $a->body_html,
                'dismiss_label' => $a->dismiss_label,
            ])->all(),
        ]);
    }
}
