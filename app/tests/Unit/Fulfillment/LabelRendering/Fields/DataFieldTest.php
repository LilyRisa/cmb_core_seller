<?php

namespace Tests\Unit\Fulfillment\LabelRendering\Fields;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Fields\DataField;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\MakesDataContext;

class DataFieldTest extends TestCase
{
    use MakesDataContext;

    private DataField $f;

    private FieldRenderHelpers $h;

    protected function setUp(): void
    {
        $this->f = new DataField;
        $this->h = new FieldRenderHelpers;
    }

    public function test_validate_rejects_unknown_key(): void
    {
        $this->expectException(ValidationException::class);
        $this->f->validateProps(['key' => 'invalid_key', 'style' => ['fontSize' => 11]]);
    }

    public static function data_key_provider(): array
    {
        return [
            ['recipient_name', 'Nguyễn Văn B'],
            ['recipient_phone', '0911'],
            ['recipient_address', '34 Trần Hưng Đạo, Hai Bà Trưng, Hà Nội'],
            ['recipient_address_detail', '34 Trần Hưng Đạo'],
            ['recipient_address_admin', 'Hai Bà Trưng, Hà Nội'],
            ['sender_name', 'Shop A'],
            ['sender_phone', '0901'],
            ['sender_address', '12 Lê Lợi, Q1, TP.HCM'],
            ['order_number', 'M-001'],
            ['tracking_no', 'TRK123'],
            ['print_note', 'Cảm ơn quý khách'],
            ['created_at_fmt', '18/05/2026 10:30'],
            ['carrier_name', 'GIAO HÀNG NHANH'],
            ['weight', '500g'],
            ['cod', '250.000 đ'],
            ['total_qty', '2'],
        ];
    }

    /**
     * @dataProvider data_key_provider
     */
    public function test_render_each_key(string $key, string $expected): void
    {
        // 'created_at_fmt' input in MakesDataContext but DataField key is 'created_at'
        $dataFieldKey = $key === 'created_at_fmt' ? 'created_at' : $key;
        $field = ['type' => 'data', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 6,
            'key' => $dataFieldKey, 'style' => ['fontSize' => 11]];
        $html = $this->f->renderHtml($field, $this->makeContext(), $this->h);
        $this->assertStringContainsString($expected, $html);
    }

    public function test_prefix_suffix_applied(): void
    {
        $field = ['type' => 'data', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 6,
            'key' => 'order_number', 'style' => ['fontSize' => 11],
            'prefix' => 'Mã: ', 'suffix' => ' #'];
        $html = $this->f->renderHtml($field, $this->makeContext(), $this->h);
        $this->assertStringContainsString('Mã: M-001 #', $html);
    }

    public function test_cod_zero_renders_dash(): void
    {
        $field = ['type' => 'data', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 6,
            'key' => 'cod', 'style' => ['fontSize' => 11]];
        $html = $this->f->renderHtml($field, $this->makeContext(['cod' => 0]), $this->h);
        $this->assertStringContainsString('—', $html);
    }
}
