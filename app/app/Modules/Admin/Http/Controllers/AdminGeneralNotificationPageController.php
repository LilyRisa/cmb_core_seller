<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Admin\Models\GeneralNotificationPage;
use CMBcoreSeller\Modules\Admin\Services\GeneralNotificationPageService;
use CMBcoreSeller\Support\HtmlSanitizer;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Plan C (2026-07-23) — CRUD "trang thông báo chung" (ưu đãi/tin chung) admin soạn + gửi theo
 * tenant hoặc tất cả. `body_html` (TipTap) sanitize allowlist trước khi lưu — cùng cơ chế
 * Announcement (SPEC 0037). Xem `send()` (Task 5) cho hành động gửi thật.
 */
class AdminGeneralNotificationPageController extends Controller
{
    public function __construct(private GeneralNotificationPageService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));
        $rows = GeneralNotificationPage::query()->latest('id')->paginate($perPage);

        return response()->json([
            'data' => collect($rows->items())->map(fn (GeneralNotificationPage $p) => $this->resource($p))->all(),
            'meta' => ['pagination' => ['total' => $rows->total()]],
        ]);
    }

    public function store(Request $request, HtmlSanitizer $sanitizer): JsonResponse
    {
        $data = $this->validated($request);
        $data['body_html'] = $sanitizer->clean($data['body_html']);
        $data['slug'] = $this->service->generateUniqueSlug($data['title']);
        $data['status'] = ! empty($data['scheduled_at']) ? GeneralNotificationPage::STATUS_SCHEDULED : GeneralNotificationPage::STATUS_DRAFT;
        $data['created_by_user_id'] = (int) $request->user()?->getKey();

        $page = GeneralNotificationPage::create($data);

        return response()->json(['data' => $this->resource($page)], 201);
    }

    public function update(Request $request, HtmlSanitizer $sanitizer, string $id): JsonResponse
    {
        $page = GeneralNotificationPage::query()->findOrFail((int) $id);
        if ($page->status === GeneralNotificationPage::STATUS_SENT) {
            return response()->json(['error' => ['code' => 'PAGE_ALREADY_SENT', 'message' => 'Trang đã gửi, không thể sửa.']], 422);
        }
        $data = $this->validated($request, partial: true);
        if (isset($data['body_html'])) {
            $data['body_html'] = $sanitizer->clean($data['body_html']);
        }
        if (array_key_exists('scheduled_at', $data)) {
            $data['status'] = $data['scheduled_at'] ? GeneralNotificationPage::STATUS_SCHEDULED : GeneralNotificationPage::STATUS_DRAFT;
        }
        $page->update($data);

        return response()->json(['data' => $this->resource($page->fresh())]);
    }

    public function destroy(string $id): JsonResponse
    {
        GeneralNotificationPage::query()->findOrFail((int) $id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** Upload ảnh bìa → R2 (thư mục general-notification-pages, non-tenant). */
    public function media(Request $request, MediaUploader $uploader): JsonResponse
    {
        $mimes = implode(',', (array) config('media.images.mimes', ['jpg', 'jpeg', 'png', 'webp']));
        $request->validate([
            'file' => ['required', 'file', 'mimes:'.$mimes, 'max:'.(int) config('media.images.max_kb', 5120)],
        ]);
        $stored = $uploader->storePublic($request->file('file'), 'general-notification-pages');

        return response()->json(['data' => ['url' => $stored['url']]]);
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$req, 'string', 'max:255'],
            'body_html' => [$req, 'string', 'max:200000'],
            'cover_image_url' => ['nullable', 'string', 'max:512'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:512'],
            'audience_type' => [$req, Rule::in([GeneralNotificationPage::AUDIENCE_ALL, GeneralNotificationPage::AUDIENCE_TENANT_IDS])],
            'audience_tenant_ids' => [
                Rule::requiredIf(fn () => $request->input('audience_type') === GeneralNotificationPage::AUDIENCE_TENANT_IDS),
                'nullable', 'array',
            ],
            'audience_tenant_ids.*' => ['integer'],
            'scheduled_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);
    }

    /** @return array<string,mixed> */
    private function resource(GeneralNotificationPage $p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'body_html' => $p->body_html,
            'cover_image_url' => $p->cover_image_url,
            'cta_label' => $p->cta_label,
            'cta_url' => $p->cta_url,
            'audience_type' => $p->audience_type,
            'audience_tenant_ids' => $p->audience_tenant_ids,
            'status' => $p->status,
            'scheduled_at' => $p->scheduled_at?->toIso8601String(),
            'expires_at' => $p->expires_at?->toIso8601String(),
            'sent_at' => $p->sent_at?->toIso8601String(),
            'created_at' => $p->created_at?->toIso8601String(),
        ];
    }
}
