<?php

namespace CMBcoreSeller\Modules\VisualSearch\Console\Commands;

use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;
use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use CMBcoreSeller\Modules\VisualSearch\Jobs\EmbedTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Services\CollectionNaming;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Re-embed ảnh visual training vào Qdrant (backfill / đổi model).
 *   --tenant= : chỉ 1 tenant   |   --fresh : recreate collection (xoá sạch index cũ).
 */
class ReindexVisualTraining extends Command
{
    protected $signature = 'visualsearch:reindex {--tenant= : Chỉ reindex 1 tenant} {--fresh : Recreate collection trước khi index}';

    protected $description = 'Re-embed ảnh visual training vào Qdrant (queue visual-index)';

    public function handle(ImageEmbedder $embedder, VectorStore $store): int
    {
        if (! $embedder->enabled() || ! $store->enabled()) {
            $this->error('Image embedder hoặc Vector store chưa cấu hình (IMAGE_EMBEDDING_URL / VECTOR_QDRANT_URL).');

            return self::FAILURE;
        }

        $collection = CollectionNaming::for($embedder->modelKey());
        if ($this->option('fresh')) {
            $store->recreateCollection($collection, $embedder->dimension());
            $this->warn("Đã recreate collection {$collection} (dim {$embedder->dimension()}).");
        } else {
            $store->ensureCollection($collection, $embedder->dimension());
        }

        $query = VisualTrainingImage::withoutGlobalScopes();
        if ($tenant = $this->option('tenant')) {
            $query->where('tenant_id', (int) $tenant);
        }

        $count = 0;
        $query->orderBy('id')->chunkById(200, function (Collection $images) use (&$count) {
            foreach ($images as $image) {
                EmbedTrainingImage::dispatch($image->id);
                $count++;
            }
        });

        $this->info("Đã dispatch {$count} job embed vào queue visual-index.");

        return self::SUCCESS;
    }
}
