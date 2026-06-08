<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Admin\Http\Resources\AdminAnnouncementResource;
use CMBcoreSeller\Modules\Admin\Models\Announcement;
use CMBcoreSeller\Support\HtmlSanitizer;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD popup announcement toàn hệ thống (SPEC 0037) — guard `admin_web`. Body HTML (TipTap)
 * luôn được SANITIZE allowlist trước khi lưu. Upload ảnh/video trong editor → R2 (non-tenant).
 */
class AdminAnnouncementController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = Announcement::query()->latest('id')->paginate(30);

        return response()->json([
            'data' => AdminAnnouncementResource::collection($rows->items())->resolve(),
            'meta' => ['pagination' => ['total' => $rows->total()]],
        ]);
    }

    public function store(Request $request, HtmlSanitizer $sanitizer): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string', 'max:200000'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'dismiss_label' => ['sometimes', 'string', 'max:40'],
        ]);
        $data['body_html'] = $sanitizer->clean($data['body_html']);
        $data['created_by_user_id'] = (int) $request->user()?->getKey();

        $announcement = Announcement::create($data);

        return response()->json(['data' => (new AdminAnnouncementResource($announcement))->resolve()], 201);
    }

    public function update(Request $request, HtmlSanitizer $sanitizer, string $id): JsonResponse
    {
        $announcement = Announcement::query()->findOrFail((int) $id);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'body_html' => ['sometimes', 'string', 'max:200000'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'dismiss_label' => ['sometimes', 'string', 'max:40'],
        ]);
        if (isset($data['body_html'])) {
            $data['body_html'] = $sanitizer->clean($data['body_html']);
        }
        $announcement->update($data);

        return response()->json(['data' => (new AdminAnnouncementResource($announcement->fresh()))->resolve()]);
    }

    public function destroy(string $id): JsonResponse
    {
        Announcement::query()->findOrFail((int) $id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** Upload ảnh hoặc video trong editor → R2 (thư mục announcements, non-tenant). */
    public function media(Request $request, MediaUploader $uploader): JsonResponse
    {
        $mimes = implode(',', array_merge(
            (array) config('media.images.mimes', ['jpg', 'jpeg', 'png', 'webp']),
            (array) config('media.video.mimes', ['mp4', 'webm']),
        ));
        $maxKb = max((int) config('media.images.max_kb', 5120), (int) config('media.video.max_kb', 51200));
        $request->validate([
            'file' => ['required', 'file', 'mimes:'.$mimes, 'max:'.$maxKb],
        ]);

        $stored = $uploader->storePublic($request->file('file'), 'announcements');

        return response()->json(['data' => ['url' => $stored['url']]]);
    }
}
