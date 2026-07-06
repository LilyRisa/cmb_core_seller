<?php

namespace CMBcoreSeller\Modules\VisualSearch\Services;

use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Contracts\KnowledgeItemStore;
use CMBcoreSeller\Modules\VisualSearch\DTO\KnowledgeItemText;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;

class KnowledgeItemRepository implements KnowledgeItemStore
{
    public function __construct(private ItemTextComposer $composer) {}

    public function textFor(int $itemId): ?KnowledgeItemText
    {
        $item = VisualTrainingItem::withoutGlobalScope(TenantScope::class)->find($itemId);
        if (! $item) {
            return null;
        }

        return new KnowledgeItemText((int) $item->id, (int) $item->tenant_id, $this->composer->compose($item));
    }

    public function readyTitles(int $tenantId, ?int $channelAccountId, ?string $provider): array
    {
        $q = VisualTrainingItem::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('kb_status', VisualTrainingItem::KB_READY);
        if ($provider !== null) {
            $q->where('provider', $provider);
        }
        if ($channelAccountId !== null) {
            $q->where(fn ($w) => $w
                ->where('applies_all_pages', true)
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                    ->from('visual_training_item_page')
                    ->whereColumn('visual_training_item_page.item_id', 'visual_training_items.id')
                    ->where('visual_training_item_page.channel_account_id', $channelAccountId)));
        }

        /** @var array<int,string> $titles */
        $titles = $q->pluck('name', 'id')->all();

        return $titles;
    }

    public function markIndexed(int $itemId, int $chunkCount, ?string $embeddingModel): void
    {
        $item = VisualTrainingItem::withoutGlobalScope(TenantScope::class)->find($itemId);
        if (! $item) {
            return;
        }
        $item->forceFill([
            'kb_status' => VisualTrainingItem::KB_READY,
            'chunk_count' => $chunkCount,
            'kb_indexed_at' => now(),
            'embedding_model' => $embeddingModel,
            'embedding_version' => (int) $item->embedding_version + 1,
        ])->save();
    }

    public function markFailed(int $itemId): void
    {
        VisualTrainingItem::withoutGlobalScope(TenantScope::class)
            ->where('id', $itemId)
            ->update(['kb_status' => VisualTrainingItem::KB_FAILED]);
    }
}
