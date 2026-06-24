<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Admin\Models\DesktopBackground;
use CMBcoreSeller\Support\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SPEC 0039 — quản lý thư viện hình nền màn Desktop. CRUD = guard `admin_web`;
 * `options()` = đọc cho người dùng (sanctum) chỉ preset đang bật.
 */
class AdminDesktopBackgroundController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = DesktopBackground::query()->orderBy('position')->orderBy('id')->get();

        return response()->json(['data' => $rows->map($this->adminRow(...))->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $data['created_by_user_id'] = (int) $request->user()?->getKey();

        $bg = DesktopBackground::create($data);

        return response()->json(['data' => $this->adminRow($bg)], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $bg = DesktopBackground::query()->findOrFail((int) $id);
        $bg->update($this->validateData($request, partial: true));

        return response()->json(['data' => $this->adminRow($bg->fresh())]);
    }

    public function destroy(string $id): JsonResponse
    {
        DesktopBackground::query()->findOrFail((int) $id)->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** Upload ảnh nền → R2 (thư mục desktop-backgrounds, non-tenant). */
    public function media(Request $request, MediaUploader $uploader): JsonResponse
    {
        $mimes = implode(',', (array) config('media.images.mimes', ['jpg', 'jpeg', 'png', 'webp']));
        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:'.$mimes, 'max:'.(int) config('media.images.max_kb', 5120)],
        ]);

        $stored = $uploader->storePublic($request->file('file'), 'desktop-backgrounds');

        return response()->json(['data' => ['url' => $stored['url'], 'path' => $stored['path']]]);
    }

    /** [user] Danh sách preset đang bật để chọn làm hình nền. */
    public function options(): JsonResponse
    {
        $rows = DesktopBackground::query()->activeOrdered()->get(['id', 'name', 'image_url']);

        return response()->json(['data' => $rows->all()]);
    }

    /** @return array<string,mixed> */
    private function validateData(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$req, 'string', 'max:120'],
            'image_url' => [$req, 'string', 'max:1024'],
            'image_path' => [$req, 'string', 'max:1024'],
            'is_active' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    /** @return array<string,mixed> */
    private function adminRow(DesktopBackground $bg): array
    {
        return [
            'id' => $bg->id,
            'name' => $bg->name,
            'image_url' => $bg->image_url,
            'image_path' => $bg->image_path,
            'is_active' => $bg->is_active,
            'position' => $bg->position,
        ];
    }
}
