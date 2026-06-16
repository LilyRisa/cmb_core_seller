<?php

namespace CMBcoreSeller\Modules\VisualSearch\Services;

use CMBcoreSeller\Modules\VisualSearch\Jobs\EmbedTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Jobs\RemoveTrainingImageVector;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingEmbedding;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Lưu/xoá ảnh training (metadata đầy đủ) + dispatch index/remove vector. */
class TrainingImageService
{
    /** Lưu 1 ảnh cho item; dedupe theo image_hash; ảnh đầu tiên thành ảnh đại diện. */
    public function storeImage(VisualTrainingItem $item, string $bytes, string $mime): VisualTrainingImage
    {
        $disk = (string) config('visual_search.media_disk', 'local');
        $hash = hash('sha256', $bytes);

        $existing = VisualTrainingImage::where('item_id', $item->id)->where('image_hash', $hash)->first();
        if ($existing !== null) {
            return $existing;
        }

        [$width, $height] = $this->dimensions($bytes);
        $path = 'tenants/'.$item->tenant_id.'/visual-training/'.$item->id.'/'.Str::uuid().'.'.$this->extension($mime);
        Storage::disk($disk)->put($path, $bytes);

        $nextSort = (int) VisualTrainingImage::where('item_id', $item->id)->max('sort_order') + 1;

        $image = VisualTrainingImage::create([
            'tenant_id' => $item->tenant_id,
            'item_id' => $item->id,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'image_hash' => $hash,
            'mime_type' => $mime,
            'width' => $width,
            'height' => $height,
            'size_bytes' => strlen($bytes),
            'sort_order' => $nextSort,
        ]);

        // Ảnh đầu tiên → ảnh đại diện mặc định.
        if (! $item->primary_image_id) {
            $item->update(['primary_image_id' => $image->id]);
        }

        EmbedTrainingImage::dispatch($image->id);

        return $image;
    }

    public function deleteImage(VisualTrainingImage $image): void
    {
        $points = VisualTrainingEmbedding::withoutGlobalScopes()
            ->where('image_id', $image->id)
            ->get()
            ->map(fn (VisualTrainingEmbedding $r) => ['collection' => $r->collection, 'vector_id' => $r->vector_id])
            ->values()
            ->all();

        if ($points !== []) {
            RemoveTrainingImageVector::dispatch($points);
        }
        VisualTrainingEmbedding::withoutGlobalScopes()->where('image_id', $image->id)->delete();

        $disk = Storage::disk($image->storage_disk);
        if ($disk->exists($image->storage_path)) {
            $disk->delete($image->storage_path);
        }

        $item = VisualTrainingItem::withoutGlobalScopes()->find($image->item_id);
        $imageId = $image->id;
        $image->delete();

        // Đổi ảnh đại diện nếu vừa xoá đúng nó.
        if ($item !== null && (int) $item->primary_image_id === (int) $imageId) {
            $next = VisualTrainingImage::where('item_id', $item->id)->orderBy('sort_order')->first();
            $item->update(['primary_image_id' => $next?->id]);
        }
    }

    /** @return array{0:int,1:int} */
    private function dimensions(string $bytes): array
    {
        $info = @getimagesizefromstring($bytes);

        return [(int) ($info[0] ?? 0), (int) ($info[1] ?? 0)];
    }

    private function extension(string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
