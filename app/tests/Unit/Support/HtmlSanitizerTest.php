<?php

namespace Tests\Unit\Support;

use CMBcoreSeller\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * SPEC 0037 — sanitize HTML editor: giữ thẻ hợp lệ + ảnh/video; loại script/iframe,
 * event-handler (onclick…), scheme javascript:; unwrap thẻ lạ vô hại giữ nội dung.
 */
class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $s;

    protected function setUp(): void
    {
        parent::setUp();
        $this->s = new HtmlSanitizer;
    }

    public function test_keeps_allowed_tags_and_media(): void
    {
        $html = '<p>Xin <strong>chào</strong></p><img src="https://cdn/r2/a.png" alt="x"><video src="https://cdn/r2/v.mp4" controls></video>';
        $out = $this->s->clean($html);

        $this->assertStringContainsString('<strong>chào</strong>', $out);
        $this->assertStringContainsString('<img src="https://cdn/r2/a.png" alt="x">', $out);
        $this->assertStringContainsString('<video src="https://cdn/r2/v.mp4" controls', $out);
    }

    public function test_strips_script_and_event_handlers_and_bad_schemes(): void
    {
        $html = '<p onclick="evil()">hi</p><script>steal()</script><a href="javascript:alert(1)">x</a><img src="javascript:evil">';
        $out = $this->s->clean($html);

        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringContainsString('hi', $out); // nội dung giữ lại
    }

    public function test_unwraps_unknown_tag_but_keeps_inner_content(): void
    {
        $out = $this->s->clean('<marquee><strong>chữ</strong></marquee>');

        $this->assertStringNotContainsString('marquee', $out);
        $this->assertStringContainsString('<strong>chữ</strong>', $out);
    }

    public function test_iframe_content_fully_removed(): void
    {
        $out = $this->s->clean('<p>ok</p><iframe src="https://evil"></iframe>');

        $this->assertStringNotContainsString('iframe', $out);
        $this->assertStringContainsString('ok', $out);
    }

    public function test_empty_input_returns_empty(): void
    {
        $this->assertSame('', $this->s->clean('   '));
    }
}
