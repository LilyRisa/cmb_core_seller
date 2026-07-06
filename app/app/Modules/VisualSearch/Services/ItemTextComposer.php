<?php

namespace CMBcoreSeller\Modules\VisualSearch\Services;

use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;

/** Ghép nội dung text của 1 mục tri thức để chunk + embed RAG. Thuần, không side-effect. */
class ItemTextComposer
{
    public function compose(VisualTrainingItem $item): string
    {
        $parts = [
            trim((string) $item->name),
            trim((string) $item->ref_code),
            trim((string) $item->description),
        ];
        foreach ((array) $item->attributes as $k => $v) {
            if (is_scalar($v) && trim((string) $v) !== '') {
                $parts[] = trim((string) $k).': '.trim((string) $v);
            }
        }
        $parts[] = trim((string) $item->content_text);

        return trim(implode("\n", array_filter($parts, fn ($p) => $p !== '')));
    }
}
