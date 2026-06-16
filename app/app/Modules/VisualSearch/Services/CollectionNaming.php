<?php

namespace CMBcoreSeller\Modules\VisualSearch\Services;

/** Tên collection Qdrant vật lý theo model: "{prefix}__{modelKey}" (mỗi model 1 collection). */
final class CollectionNaming
{
    public static function for(string $modelKey): string
    {
        $prefix = (string) config('visual_search.collection_prefix', 'visual_training');
        $safe = (string) preg_replace('/[^a-z0-9_]/i', '_', $modelKey);

        return $prefix.'__'.$safe;
    }
}
