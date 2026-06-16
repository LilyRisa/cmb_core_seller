<?php

namespace CMBcoreSeller\Modules\VisualSearch\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use CMBcoreSeller\Modules\VisualSearch\Services\TrainingImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** Upload/xoá ảnh + đặt ảnh đại diện cho item. Mutate cần `messaging.ai.train`. */
class TrainingImageController extends Controller
{
    public function __construct(private TrainingImageService $images) {}

    public function store(Request $request, int $itemId): JsonResponse
    {
        Gate::authorize('messaging.ai.train');

        $item = VisualTrainingItem::query()->findOrFail($itemId);
        $maxKb = (int) config('visual_search.image.max_size_kb', 8192);
        /** @var list<string> $mimes */
        $mimes = (array) config('visual_search.image.allowed_mime', ['image/jpeg', 'image/png', 'image/webp']);
        $maxPer = (int) config('visual_search.image.max_per_item', 12);

        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'image', 'max:'.$maxKb, 'mimetypes:'.implode(',', $mimes)],
        ]);

        $current = VisualTrainingImage::query()->where('item_id', $item->id)->count();
        $stored = [];
        foreach ((array) $request->file('images') as $file) {
            if ($current >= $maxPer) {
                break;
            }
            $bytes = (string) file_get_contents($file->getRealPath());
            $image = $this->images->storeImage($item, $bytes, (string) $file->getMimeType());
            $stored[] = ['id' => $image->id, 'width' => $image->width, 'height' => $image->height];
            $current++;
        }

        AuditLog::record('visual_search.image.upload', $item, ['count' => count($stored)]);

        return response()->json(['data' => ['images' => $stored, 'primary_image_id' => $item->refresh()->primary_image_id]], 201);
    }

    public function destroy(int $itemId, int $imageId): JsonResponse
    {
        Gate::authorize('messaging.ai.train');

        $image = VisualTrainingImage::query()->where('item_id', $itemId)->findOrFail($imageId);
        $this->images->deleteImage($image);

        return response()->json(['data' => ['ok' => true]]);
    }

    /** Phục vụ bytes ảnh (tenant-scoped) cho thumbnail FE. Đọc `messaging.view`. */
    public function raw(int $itemId, int $imageId): Response
    {
        Gate::authorize('messaging.view');

        $image = VisualTrainingImage::query()->where('item_id', $itemId)->findOrFail($imageId);
        $disk = Storage::disk($image->storage_disk);
        abort_unless($disk->exists($image->storage_path), 404);

        return response($disk->get($image->storage_path), 200, [
            'Content-Type' => $image->mime_type,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function setPrimary(int $itemId, int $imageId): JsonResponse
    {
        Gate::authorize('messaging.ai.train');

        $item = VisualTrainingItem::query()->findOrFail($itemId);
        VisualTrainingImage::query()->where('item_id', $item->id)->findOrFail($imageId);
        $item->update(['primary_image_id' => $imageId]);

        return response()->json(['data' => ['primary_image_id' => $imageId]]);
    }
}
