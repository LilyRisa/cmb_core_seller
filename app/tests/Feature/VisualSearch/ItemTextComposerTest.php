<?php

namespace Tests\Feature\VisualSearch;

use Tests\TestCase;

class ItemTextComposerTest extends TestCase
{
    public function test_compose_joins_name_description_attributes_content(): void
    {
        $item = new \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem([
            'name' => 'Bộ thu bluetooth', 'ref_code' => 'BT01',
            'description' => 'Kết nối 5.0', 'attributes' => ['màu' => 'đen', 'bảo hành' => '12 tháng'],
            'content_text' => 'Hỗ trợ AptX. Pin 10h.',
        ]);
        $out = app(\CMBcoreSeller\Modules\VisualSearch\Services\ItemTextComposer::class)->compose($item);
        $this->assertStringContainsString('Bộ thu bluetooth', $out);
        $this->assertStringContainsString('BT01', $out);
        $this->assertStringContainsString('Kết nối 5.0', $out);
        $this->assertStringContainsString('màu: đen', $out);
        $this->assertStringContainsString('Pin 10h', $out);
    }

    public function test_compose_empty_when_no_text(): void
    {
        $item = new \CMBcoreSeller\Modules\VisualSearch\Models\VisualTrainingItem(['name' => '']);
        $this->assertSame('', app(\CMBcoreSeller\Modules\VisualSearch\Services\ItemTextComposer::class)->compose($item));
    }
}
