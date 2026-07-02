<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Support\MessagePreview;
use PHPUnit\Framework\TestCase;

/**
 * Preview danh sách hội thoại kiểu Facebook: có text → text gọn; tin đính kèm không
 * text → "Đã gửi <loại>" thay vì "[image]"/"[sticker]".
 */
class MessagePreviewTest extends TestCase
{
    public function test_text_body_returned_trimmed(): void
    {
        $this->assertSame('Cảm ơn shop nhé', MessagePreview::build("  Cảm ơn\n shop   nhé ", 'text'));
    }

    /** @dataProvider attachmentKinds */
    public function test_attachment_without_text_describes_kind(string $kind, string $expected): void
    {
        $this->assertSame($expected, MessagePreview::build(null, $kind));
        // Caption rỗng cũng coi như không có text.
        $this->assertSame($expected, MessagePreview::build('   ', $kind));
    }

    /** @return array<string, array{0:string, 1:string}> */
    public static function attachmentKinds(): array
    {
        return [
            'image' => ['image', 'Đã gửi một hình ảnh'],
            'sticker' => ['sticker', 'Đã gửi một nhãn dán'],
            'video' => ['video', 'Đã gửi một video'],
            'audio' => ['audio', 'Đã gửi một tin nhắn thoại'],
            'file' => ['file', 'Đã gửi một tệp đính kèm'],
            'unknown' => ['template', 'Đã gửi một tin nhắn'],
        ];
    }

    public function test_caption_takes_priority_over_kind_phrase(): void
    {
        $this->assertSame('Ảnh sản phẩm mới', MessagePreview::build('Ảnh sản phẩm mới', 'image'));
    }
}
