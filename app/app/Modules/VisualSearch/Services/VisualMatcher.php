<?php

namespace CMBcoreSeller\Modules\VisualSearch\Services;

use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;
use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use CMBcoreSeller\Modules\VisualSearch\Contracts\VisualItemSearch;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemCandidate;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemImage;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualLookupOptions;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Tra cứu visual: embed ảnh khách → Qdrant top-K ẢNH (filter tenant) → GROUP theo
 * item_id → aggregate điểm → top-N ITEM → (tuỳ chọn) vision re-rank → tri-state.
 * Mục tiêu: tìm "item đúng", không phải "ảnh đúng". Lỗi/tắt ⇒ not_found (không ném).
 */
class VisualMatcher implements VisualItemSearch
{
    public function __construct(
        private ImageEmbedder $embedder,
        private VectorStore $store,
        private VisionReRanker $reranker,
    ) {}

    public function lookup(int $tenantId, VisualImageInput $image, VisualLookupOptions $opts): VisualMatchResult
    {
        if (! $this->embedder->enabled() || ! $this->store->enabled()) {
            return VisualMatchResult::notFound();
        }

        try {
            $vec = $this->embedder->embedImage($image->bytes, $image->mime);
        } catch (\Throwable) {
            return VisualMatchResult::notFound();
        }

        $cfg = (array) config('visual_search.match', []);
        $topKImages = $opts->topKImages ?? (int) ($cfg['top_k_images'] ?? 20);
        $topNItems = $opts->topNItems ?? (int) ($cfg['top_n_items'] ?? 5);
        $recallFloor = (float) ($cfg['recall_floor'] ?? 0.2);
        $matchMin = (float) ($cfg['match_min'] ?? 0.3);
        $ambiguousDelta = (float) ($cfg['ambiguous_delta'] ?? 0.03);
        $aggregate = (string) ($cfg['aggregate'] ?? 'max');

        $collection = CollectionNaming::for($this->embedder->modelKey());
        $hits = $this->store->search($collection, $vec->vector, $topKImages, ['tenant_id' => $tenantId]);
        if ($hits === []) {
            return VisualMatchResult::notFound();
        }

        // Group điểm theo item_id (bỏ ảnh dưới recall_floor).
        $scores = [];
        foreach ($hits as $h) {
            $itemId = (int) ($h['payload']['item_id'] ?? 0);
            $score = (float) $h['score'];
            if ($itemId <= 0 || $score < $recallFloor) {
                continue;
            }
            $scores[$itemId][] = $score;
        }
        if ($scores === []) {
            return VisualMatchResult::notFound();
        }

        $itemScore = [];
        foreach ($scores as $id => $list) {
            $itemScore[$id] = $aggregate === 'mean' ? array_sum($list) / count($list) : max($list);
        }
        arsort($itemScore);

        $ids = array_keys($itemScore);
        $items = VisualTrainingItem::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        // Lọc per-page (SPEC 0035) — query pivot trực tiếp (job không có tenant context).
        $pageItemIds = null;
        if ($opts->channelAccountId !== null) {
            $pageItemIds = DB::table('visual_training_item_page')
                ->where('channel_account_id', $opts->channelAccountId)
                ->whereIn('item_id', $ids)
                ->pluck('item_id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $ordered = [];
        foreach ($itemScore as $id => $score) {
            $item = $items->get($id);
            if ($item === null) {
                continue;
            }
            if ($pageItemIds !== null && ! $item->applies_all_pages && ! in_array((int) $id, $pageItemIds, true)) {
                continue;
            }
            $ordered[] = ['item' => $item, 'score' => (float) $score];
            if (count($ordered) >= $topNItems) {
                break;
            }
        }
        if ($ordered === []) {
            return VisualMatchResult::notFound();
        }

        /** @var list<VisualItemCandidate> $candidates */
        $candidates = array_map(fn ($o) => $this->toCandidate($o['item'], $o['score']), $ordered);

        // Vision re-rank (tuỳ chọn).
        $rerankEnabled = $opts->rerank
            && (bool) config('visual_search.rerank.enabled', true)
            && $opts->providerCode !== null
            && $opts->aiContext !== null;

        if ($rerankEnabled) {
            $withImages = array_map(fn ($o) => [
                'candidate' => $this->toCandidate($o['item'], $o['score']),
                'image' => $this->representativeDataUrl($o['item']),
            ], $ordered);

            $picked = $this->reranker->pick($tenantId, (string) $opts->providerCode, $opts->aiContext, $image, $withImages);

            if ($picked > 0) {
                foreach ($candidates as $c) {
                    if ($c->itemId === $picked) {
                        return VisualMatchResult::matched($c, 'rerank');
                    }
                }
            } elseif ($picked === VisionReRanker::NONE) {
                return VisualMatchResult::notFound('rerank');
            }
            // NOT_RUN ⇒ fallback recall.
        }

        return $this->recallDecision($candidates, $matchMin, $ambiguousDelta, $recallFloor);
    }

    public function findByName(int $tenantId, string $text, ?int $channelAccountId = null): VisualMatchResult
    {
        $needle = mb_strtolower(trim($text));
        if ($needle === '') {
            return VisualMatchResult::notFound();
        }

        // Danh mục training item nhỏ ⇒ nạp rồi so khớp chứa-chuỗi trong PHP (portable, không phụ thuộc DB collation).
        $items = VisualTrainingItem::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        // Lọc per-page (SPEC 0035): item applies_all_pages, hoặc gắn page này.
        $pageItemIds = null;
        if ($channelAccountId !== null) {
            $pageItemIds = DB::table('visual_training_item_page')
                ->where('channel_account_id', $channelAccountId)
                ->pluck('item_id')->map(fn ($v) => (int) $v)->all();
        }

        $matches = [];
        foreach ($items as $item) {
            if ($pageItemIds !== null && ! $item->applies_all_pages && ! in_array((int) $item->id, $pageItemIds, true)) {
                continue;
            }
            $name = mb_strtolower(trim((string) $item->name));
            $ref = mb_strtolower(trim((string) $item->ref_code));
            $hit = ($name !== '' && str_contains($needle, $name))
                || ($ref !== '' && str_contains($needle, $ref));
            if ($hit) {
                $matches[$item->id] = $this->toCandidate($item, 1.0);
            }
        }

        $matches = array_values($matches);
        if ($matches === []) {
            return VisualMatchResult::notFound();
        }
        if (count($matches) === 1) {
            return VisualMatchResult::matched($matches[0]);
        }

        return VisualMatchResult::ambiguous(array_slice($matches, 0, 5));
    }

    public function imagesForItem(int $tenantId, int $itemId, int $limit = 3): array
    {
        $item = VisualTrainingItem::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->find($itemId);
        if ($item === null) {
            return [];
        }

        $images = VisualTrainingImage::withoutGlobalScopes()
            ->where('item_id', $item->id)
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [(int) $item->primary_image_id])
            ->orderBy('sort_order')
            ->limit(max(1, $limit))
            ->get();

        $out = [];
        foreach ($images as $img) {
            try {
                $disk = Storage::disk($img->storage_disk);
                if (! $disk->exists($img->storage_path)) {
                    continue;
                }
                $bytes = (string) $disk->get($img->storage_path);
            } catch (\Throwable) {
                continue;
            }
            if ($bytes === '') {
                continue;
            }
            $out[] = new VisualItemImage((string) ($img->mime_type ?: 'image/jpeg'), $bytes);
        }

        return $out;
    }

    /**
     * @param  list<VisualItemCandidate>  $candidates
     */
    private function recallDecision(array $candidates, float $matchMin, float $delta, float $floor): VisualMatchResult
    {
        $top1 = $candidates[0];
        $top2 = $candidates[1] ?? null;

        if ($top1->confidence >= $matchMin && ($top2 === null || ($top1->confidence - $top2->confidence) >= $delta)) {
            return VisualMatchResult::matched($top1);
        }
        if ($top1->confidence >= $floor) {
            $amb = array_values(array_filter(
                $candidates,
                fn (VisualItemCandidate $c) => $c === $top1 || ($top1->confidence - $c->confidence) <= $delta,
            ));
            if (count($amb) < 2) {
                $amb = array_slice($candidates, 0, min(3, count($candidates)));
            }

            return VisualMatchResult::ambiguous($amb);
        }

        return VisualMatchResult::notFound();
    }

    private function toCandidate(VisualTrainingItem $item, float $score): VisualItemCandidate
    {
        return new VisualItemCandidate(
            itemId: (int) $item->id,
            name: (string) $item->name,
            description: $item->description,
            attributes: (array) $item->attributes,
            confidence: $score,
        );
    }

    private function representativeDataUrl(VisualTrainingItem $item): ?string
    {
        $img = $item->primary_image_id
            ? VisualTrainingImage::withoutGlobalScopes()->find($item->primary_image_id)
            : VisualTrainingImage::withoutGlobalScopes()->where('item_id', $item->id)->orderBy('sort_order')->first();

        if ($img === null) {
            return null;
        }
        try {
            $disk = Storage::disk($img->storage_disk);
            if (! $disk->exists($img->storage_path)) {
                return null;
            }
            $bytes = (string) $disk->get($img->storage_path);
        } catch (\Throwable) {
            return null;
        }

        return 'data:'.$img->mime_type.';base64,'.base64_encode($bytes);
    }
}
