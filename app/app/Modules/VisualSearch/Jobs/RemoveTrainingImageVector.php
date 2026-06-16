<?php

namespace CMBcoreSeller\Modules\VisualSearch\Jobs;

use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Xoá vector của ảnh đã xoá khỏi Qdrant (gom điểm theo collection). */
class RemoveTrainingImageVector implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param  list<array{collection:string, vector_id:string}>  $points */
    public function __construct(public array $points)
    {
        $this->onQueue('visual-index');
    }

    public function handle(VectorStore $store): void
    {
        $byCollection = [];
        foreach ($this->points as $p) {
            $byCollection[$p['collection']][] = $p['vector_id'];
        }
        foreach ($byCollection as $collection => $ids) {
            $store->deleteIds($collection, $ids);
        }
    }
}
