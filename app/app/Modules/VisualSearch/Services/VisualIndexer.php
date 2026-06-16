<?php

namespace CMBcoreSeller\Modules\VisualSearch\Services;

use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;
use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingEmbedding;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Embed ảnh training → upsert Qdrant + ghi visual_training_embeddings.
 * Idempotent theo (image_id, model, version): re-index dùng lại vector_id (upsert đè).
 * Lỗi embedder/store ⇒ đánh status=failed, KHÔNG ném (job không retry vô ích).
 */
class VisualIndexer
{
    public function __construct(
        private ImageEmbedder $embedder,
        private VectorStore $store,
    ) {}

    public function indexImage(VisualTrainingImage $image): void
    {
        $version = 1;
        $model = $this->embedder->modelKey();
        $collection = CollectionNaming::for($model);

        $row = VisualTrainingEmbedding::withoutGlobalScopes()->firstOrNew([
            'image_id' => $image->id,
            'model' => $model,
            'version' => $version,
        ]);
        $row->tenant_id = $image->tenant_id;
        $row->collection = $collection;
        $row->vector_id = $row->vector_id ?: (string) Str::uuid();
        $row->dim = $this->embedder->dimension();
        $row->status = VisualTrainingEmbedding::STATUS_PENDING;
        $row->save();

        $bytes = $this->readBytes($image);
        if ($bytes === null) {
            $this->fail($row, 'không đọc được file ảnh');

            return;
        }

        try {
            $vec = $this->embedder->embedImage($bytes, $image->mime_type);
        } catch (\Throwable $e) {
            $this->fail($row, substr($e->getMessage(), 0, 240));

            return;
        }

        $this->store->ensureCollection($collection, $vec->dim ?: $this->embedder->dimension());
        $ok = $this->store->upsert($collection, [[
            'id' => $row->vector_id,
            'vector' => $vec->vector,
            'payload' => [
                'tenant_id' => (int) $image->tenant_id,
                'item_id' => (int) $image->item_id,
                'image_id' => (int) $image->id,
                'image_hash' => (string) $image->image_hash,
            ],
        ]]);

        if (! $ok) {
            $this->fail($row, 'upsert Qdrant thất bại');

            return;
        }

        $row->dim = $vec->dim ?: $row->dim;
        $row->status = VisualTrainingEmbedding::STATUS_INDEXED;
        $row->error = null;
        $row->indexed_at = now();
        $row->save();
    }

    /**
     * Xoá vector của 1 ảnh khỏi Qdrant + xoá embedding rows. Gọi TRƯỚC khi xoá image row.
     */
    public function removeImageVectors(VisualTrainingImage $image): void
    {
        $rows = VisualTrainingEmbedding::withoutGlobalScopes()->where('image_id', $image->id)->get();
        foreach ($rows as $row) {
            $this->store->deleteIds($row->collection, [$row->vector_id]);
            $row->delete();
        }
    }

    private function readBytes(VisualTrainingImage $image): ?string
    {
        try {
            $disk = Storage::disk($image->storage_disk);
            if (! $disk->exists($image->storage_path)) {
                return null;
            }

            return (string) $disk->get($image->storage_path);
        } catch (\Throwable $e) {
            Log::warning('visual_search.read_bytes_failed', ['image_id' => $image->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function fail(VisualTrainingEmbedding $row, string $error): void
    {
        $row->status = VisualTrainingEmbedding::STATUS_FAILED;
        $row->error = $error;
        $row->save();
        Log::warning('visual_search.index_failed', ['image_id' => $row->image_id, 'error' => $error]);
    }
}
