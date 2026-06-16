<?php

namespace CMBcoreSeller\Modules\VisualSearch\Jobs;

use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Services\VisualIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Embed 1 ảnh training vào Qdrant (queue `visual-index` — phải có trong Horizon supervisor). */
class EmbedTrainingImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $imageId)
    {
        $this->onQueue('visual-index');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(VisualIndexer $indexer): void
    {
        $image = VisualTrainingImage::withoutGlobalScopes()->find($this->imageId);
        if ($image === null) {
            return;
        }
        $indexer->indexImage($image);
    }
}
