<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingEmbedding;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualTrainingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_images_embeddings_relations_and_tenant_autoset(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        app(CurrentTenant::class)->set($tenant);

        $item = VisualTrainingItem::create([
            'name' => 'iPhone 15',
            'description' => 'Điện thoại',
            'attributes' => ['color' => 'đen'],
        ]);
        $this->assertSame((int) $tenant->id, (int) $item->tenant_id);  // BelongsToTenant auto-set
        $this->assertTrue($item->fresh()->applies_all_pages);           // DB default
        $this->assertSame(VisualTrainingItem::STATUS_ACTIVE, $item->fresh()->status);

        $img = VisualTrainingImage::create([
            'item_id' => $item->id,
            'storage_disk' => 'local',
            'storage_path' => 'tenants/1/visual/a.jpg',
            'image_hash' => str_repeat('a', 64),
            'mime_type' => 'image/jpeg',
            'width' => 800, 'height' => 600, 'size_bytes' => 1234,
        ]);

        $emb = VisualTrainingEmbedding::create([
            'image_id' => $img->id,
            'model' => 'clip_vit_b32',
            'version' => 1,
            'collection' => 'visual_training__clip_vit_b32',
            'vector_id' => 'uuid-1',
            'dim' => 512,
            'status' => VisualTrainingEmbedding::STATUS_INDEXED,
        ]);

        // Ảnh đại diện
        $item->update(['primary_image_id' => $img->id]);

        $this->assertCount(1, $item->fresh()->images);
        $this->assertSame($img->id, $item->fresh()->primaryImage->id);
        $this->assertSame($item->id, $img->fresh()->item->id);
        $this->assertCount(1, $img->fresh()->embeddings);
        $this->assertSame($tenant->id, $emb->tenant_id);
        $this->assertSame(['color' => 'đen'], $item->fresh()->attributes);
    }

    public function test_tenant_scope_isolates_items(): void
    {
        $t1 = Tenant::create(['name' => 'A']);
        $t2 = Tenant::create(['name' => 'B']);

        app(CurrentTenant::class)->set($t1);
        VisualTrainingItem::create(['name' => 'A-item']);

        app(CurrentTenant::class)->set($t2);
        VisualTrainingItem::create(['name' => 'B-item']);

        $this->assertSame(1, VisualTrainingItem::count());            // chỉ thấy tenant hiện tại
        $this->assertSame('B-item', VisualTrainingItem::first()->name);
    }
}
