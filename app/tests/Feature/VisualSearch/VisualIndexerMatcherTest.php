<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Integrations\Embedding\Image\Contracts\ImageEmbedder;
use CMBcoreSeller\Integrations\Embedding\Image\DTO\ImageVectorDTO;
use CMBcoreSeller\Integrations\Vector\Contracts\VectorStore;
use CMBcoreSeller\Integrations\Vector\Qdrant\QdrantStore;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualLookupOptions;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingEmbedding;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use CMBcoreSeller\Modules\VisualSearch\Services\VisionReRanker;
use CMBcoreSeller\Modules\VisualSearch\Services\VisualIndexer;
use CMBcoreSeller\Modules\VisualSearch\Services\VisualMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisualIndexerMatcherTest extends TestCase
{
    use RefreshDatabase;

    private function fakeEmbedder(): ImageEmbedder
    {
        return new class implements ImageEmbedder
        {
            public function enabled(): bool
            {
                return true;
            }

            public function embedImage(string $bytes, string $mime): ImageVectorDTO
            {
                return new ImageVectorDTO([0.1, 0.2, 0.3], 3, 'clip_vit_b32');
            }

            public function modelKey(): string
            {
                return 'clip_vit_b32';
            }

            public function dimension(): int
            {
                return 3;
            }
        };
    }

    /** @param  list<array{id:string,score:float,payload:array<string,mixed>}>  $result */
    private function fakeStore(array $result = []): VectorStore
    {
        return new class($result) implements VectorStore
        {
            /** @var list<array{string,array}> */
            public array $upserts = [];

            public function __construct(private array $result) {}

            public function enabled(): bool
            {
                return true;
            }

            public function ensureCollection(string $collection, int $dim, string $distance = 'Cosine'): bool
            {
                return true;
            }

            public function recreateCollection(string $collection, int $dim, string $distance = 'Cosine'): bool
            {
                return true;
            }

            public function upsert(string $collection, array $points): bool
            {
                $this->upserts[] = [$collection, $points];

                return true;
            }

            public function search(string $collection, array $vector, int $topK, array $filter = []): array
            {
                return $this->result;
            }

            public function deleteIds(string $collection, array $ids): bool
            {
                return true;
            }
        };
    }

    public function test_indexer_embeds_and_upserts_point(): void
    {
        Storage::fake('local');
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);

        $item = VisualTrainingItem::create(['name' => 'X']);
        Storage::disk('local')->put('vt/a.jpg', 'BYTES');
        $image = VisualTrainingImage::create([
            'item_id' => $item->id, 'storage_disk' => 'local', 'storage_path' => 'vt/a.jpg',
            'image_hash' => str_repeat('a', 64), 'mime_type' => 'image/jpeg',
        ]);

        $store = $this->fakeStore();
        (new VisualIndexer($this->fakeEmbedder(), $store))->indexImage($image);

        $emb = VisualTrainingEmbedding::withoutGlobalScopes()->where('image_id', $image->id)->first();
        $this->assertNotNull($emb);
        $this->assertSame(VisualTrainingEmbedding::STATUS_INDEXED, $emb->status);
        $this->assertNotEmpty($emb->vector_id);
        $this->assertCount(1, $store->upserts);
        $this->assertSame((int) $item->id, $store->upserts[0][1][0]['payload']['item_id']);
    }

    private function matcher(array $searchResult): VisualMatcher
    {
        return new VisualMatcher($this->fakeEmbedder(), $this->fakeStore($searchResult), app(VisionReRanker::class));
    }

    private function input(): VisualImageInput
    {
        return VisualImageInput::fromBinary('BYTES', 'image/jpeg');
    }

    private function opts(): VisualLookupOptions
    {
        return new VisualLookupOptions(rerank: false);
    }

    public function test_match_groups_images_by_item_and_returns_item(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $a = VisualTrainingItem::create(['name' => 'A']);
        $b = VisualTrainingItem::create(['name' => 'B']);

        // Item A có 2 ảnh trúng (0.90, 0.85), item B 1 ảnh (0.40). Phải trả ITEM A, không phải ảnh.
        $hits = [
            ['id' => 'p1', 'score' => 0.90, 'payload' => ['item_id' => $a->id]],
            ['id' => 'p2', 'score' => 0.85, 'payload' => ['item_id' => $a->id]],
            ['id' => 'p3', 'score' => 0.40, 'payload' => ['item_id' => $b->id]],
        ];

        $res = $this->matcher($hits)->lookup($tenant->id, $this->input(), $this->opts());

        $this->assertSame(VisualMatchResult::STATUS_MATCHED, $res->status);
        $this->assertSame($a->id, $res->item->itemId);
        $this->assertSame(0.90, round($res->item->confidence, 2));   // aggregate max
    }

    public function test_match_ambiguous_when_scores_close(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $a = VisualTrainingItem::create(['name' => 'A']);
        $b = VisualTrainingItem::create(['name' => 'B']);

        $hits = [
            ['id' => 'p1', 'score' => 0.92, 'payload' => ['item_id' => $a->id]],
            ['id' => 'p2', 'score' => 0.91, 'payload' => ['item_id' => $b->id]],
        ];

        $res = $this->matcher($hits)->lookup($tenant->id, $this->input(), $this->opts());

        $this->assertSame(VisualMatchResult::STATUS_AMBIGUOUS, $res->status);
        $this->assertCount(2, $res->candidates);
    }

    public function test_match_not_found_when_below_recall_floor(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);
        $a = VisualTrainingItem::create(['name' => 'A']);

        $hits = [['id' => 'p1', 'score' => 0.10, 'payload' => ['item_id' => $a->id]]];

        $res = $this->matcher($hits)->lookup($tenant->id, $this->input(), $this->opts());

        $this->assertSame(VisualMatchResult::STATUS_NOT_FOUND, $res->status);
    }

    public function test_match_not_found_when_store_disabled(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);

        $disabledStore = new class extends QdrantStore
        {
            public function __construct()
            {
                parent::__construct(['url' => '', 'api_key' => '', 'timeout' => 5]);
            }
        };
        $matcher = new VisualMatcher($this->fakeEmbedder(), $disabledStore, app(VisionReRanker::class));

        $res = $matcher->lookup($tenant->id, $this->input(), $this->opts());
        $this->assertSame(VisualMatchResult::STATUS_NOT_FOUND, $res->status);
    }
}
