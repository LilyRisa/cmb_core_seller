<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use Tests\TestCase;

/**
 * Tin Facebook có NÚT BẤM (template/quick-reply ⇒ meta.buttons; khách bấm nút ⇒
 * meta.postback_title) phải được đưa text nút vào ngữ cảnh gửi AI — không bị bỏ qua
 * chỉ vì body rỗng.
 */
class AiSnapshotButtonTextTest extends TestCase
{
    private function text(array $attrs): ?string
    {
        $m = (new Message)->forceFill($attrs);

        return app(AiSuggestionService::class)->snapshotMessageText($m);
    }

    public function test_body_only_returns_body(): void
    {
        $this->assertSame('cho minh hoi gia', $this->text(['body' => 'cho minh hoi gia', 'meta' => []]));
    }

    public function test_button_only_message_uses_button_titles(): void
    {
        $t = (string) $this->text(['body' => null, 'meta' => ['buttons' => [
            ['title' => 'Mua ngay', 'payload' => 'BUY'], ['title' => 'Tư vấn'],
        ]]]);
        $this->assertStringContainsString('Mua ngay', $t);
        $this->assertStringContainsString('Tư vấn', $t);
    }

    public function test_body_and_buttons_combined(): void
    {
        $t = (string) $this->text(['body' => 'Chọn giúp shop', 'meta' => ['buttons' => [['title' => 'Mua ngay']]]]);
        $this->assertStringContainsString('Chọn giúp shop', $t);
        $this->assertStringContainsString('Mua ngay', $t);
    }

    public function test_postback_title_included_when_customer_taps_button(): void
    {
        $t = (string) $this->text(['body' => null, 'meta' => ['postback_title' => 'Xem sản phẩm']]);
        $this->assertStringContainsString('Xem sản phẩm', $t);
    }

    public function test_empty_message_returns_null(): void
    {
        $this->assertNull($this->text(['body' => null, 'meta' => []]));
    }
}
