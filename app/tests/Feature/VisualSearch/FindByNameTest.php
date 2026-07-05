<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use CMBcoreSeller\Modules\VisualSearch\Contracts\VisualItemSearch;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualMatchResult;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingImage;
use CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FindByNameTest extends TestCase
{
    use RefreshDatabase;

    private function item(int $tenantId, string $name, string $ref = '', string $description = ''): VisualTrainingItem
    {
        return VisualTrainingItem::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId, 'name' => $name, 'ref_code' => $ref, 'description' => $description,
            'status' => 'active', 'applies_all_pages' => true,
        ]);
    }

    public function test_single_name_match_returns_matched(): void
    {
        $this->item(1, 'Áo thun cổ tròn');
        $this->item(1, 'Quần jean');

        $r = app(VisualItemSearch::class)->findByName(1, 'cho em xem áo thun cổ tròn với ạ');

        $this->assertSame(VisualMatchResult::STATUS_MATCHED, $r->status);
        $this->assertSame('Áo thun cổ tròn', $r->item->name);
    }

    public function test_multiple_matches_return_ambiguous(): void
    {
        $this->item(1, 'Áo thun');
        $this->item(1, 'Quần jean');

        $r = app(VisualItemSearch::class)->findByName(1, 'cho xem áo thun và quần jean');
        $this->assertSame(VisualMatchResult::STATUS_AMBIGUOUS, $r->status);
        $this->assertCount(2, $r->candidates);
    }

    public function test_no_match_returns_not_found(): void
    {
        $this->item(1, 'Áo thun');
        $r = app(VisualItemSearch::class)->findByName(1, 'giày thể thao');
        $this->assertSame(VisualMatchResult::STATUS_NOT_FOUND, $r->status);
    }

    public function test_partial_phrasing_matches_by_token_overlap(): void
    {
        // Ca prod thật: khách mô tả gần đúng ("có màn led") khác tên training ("ăn ten") —
        // khớp mềm theo từ khoá (bộ/thu/bluetooth = 3/5 = 0.6) ⇒ vẫn nhận đúng SP.
        $this->item(1, 'bộ thu bluetooth ăn ten');
        $this->item(1, 'Áo thun');

        $r = app(VisualItemSearch::class)->findByName(1, 'gửi cho tôi hình ảnh bộ thu bluetooth có màn led');

        $this->assertSame(VisualMatchResult::STATUS_MATCHED, $r->status);
        $this->assertSame('bộ thu bluetooth ăn ten', $r->item->name);
    }

    public function test_matches_by_description_when_name_is_a_code(): void
    {
        // Tên là MÃ (zkmt21) nhưng khách gọi theo MÔ TẢ ⇒ khớp mềm theo mô tả.
        $this->item(1, 'ZKMT21', 'zkmt21', 'tai nghe bluetooth chống ồn');
        $this->item(1, 'D800', 'd800', 'loa kéo di động');

        $r = app(VisualItemSearch::class)->findByName(1, 'cho em xem tai nghe bluetooth chống ồn ạ');

        $this->assertSame(VisualMatchResult::STATUS_MATCHED, $r->status);
        $this->assertSame('ZKMT21', $r->item->name);
    }

    public function test_weak_overlap_does_not_match(): void
    {
        // Chỉ trùng 1 từ khoá chung ⇒ dưới ngưỡng ⇒ không nhận nhầm.
        $this->item(1, 'máy lọc nước gia đình cao cấp');

        $r = app(VisualItemSearch::class)->findByName(1, 'cho xem máy đi shop');
        $this->assertSame(VisualMatchResult::STATUS_NOT_FOUND, $r->status);
    }

    public function test_close_partial_scores_return_ambiguous(): void
    {
        // Hai SP na ná (áo thun nam/nữ), khách chỉ nói "áo thun" ⇒ không tự gửi, hỏi lại.
        $this->item(1, 'áo thun nam');
        $this->item(1, 'áo thun nữ');

        $r = app(VisualItemSearch::class)->findByName(1, 'cho xem áo thun');
        $this->assertSame(VisualMatchResult::STATUS_AMBIGUOUS, $r->status);
        $this->assertCount(2, $r->candidates);
    }

    public function test_images_for_item_returns_primary_first(): void
    {
        Storage::fake('local');
        $item = $this->item(1, 'Áo thun');
        Storage::disk('local')->put('p/a.jpg', 'AAA');
        Storage::disk('local')->put('p/b.jpg', 'BBB');
        $img1 = VisualTrainingImage::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'item_id' => $item->id, 'storage_disk' => 'local', 'storage_path' => 'p/b.jpg',
            'image_hash' => 'h2', 'mime_type' => 'image/jpeg', 'width' => 1, 'height' => 1, 'size_bytes' => 3, 'sort_order' => 2,
        ]);
        $primary = VisualTrainingImage::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'item_id' => $item->id, 'storage_disk' => 'local', 'storage_path' => 'p/a.jpg',
            'image_hash' => 'h1', 'mime_type' => 'image/jpeg', 'width' => 1, 'height' => 1, 'size_bytes' => 3, 'sort_order' => 1,
        ]);
        $item->forceFill(['primary_image_id' => $primary->id])->save();

        $imgs = app(VisualItemSearch::class)->imagesForItem(1, $item->id, 3);
        $this->assertCount(2, $imgs);
        $this->assertSame('AAA', $imgs[0]->bytes); // primary first
        $this->assertSame('image/jpeg', $imgs[0]->mime);
    }
}
