<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Ai\Concerns\ReplyPersona;
use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;
use PHPUnit\Framework\TestCase;

/** Persona dùng chung cho reply tin nhắn khách — quy tắc ưu tiên hội thoại + so khớp sản phẩm. */
class ReplyPersonaTest extends TestCase
{
    private function snapshot(?string $buyerName = null): ConversationSnapshot
    {
        return new ConversationSnapshot(conversationId: 1, provider: 'facebook_page', buyerName: $buyerName, recentMessages: []);
    }

    public function test_instructions_contain_conversation_first_and_product_match_rules(): void
    {
        $s = ReplyPersona::instructions($this->snapshot());

        $this->assertStringContainsString('ƯU TIÊN nội dung ĐOẠN HỘI THOẠI', $s);
        $this->assertStringContainsString('CHỈ tra cứu "Tài liệu tham khảo" khi đoạn hội thoại KHÔNG đủ thông tin', $s);
        $this->assertStringContainsString('SO KHỚP ĐÚNG sản phẩm', $s);
        $this->assertStringContainsString('KHÔNG lấy thông tin của sản phẩm khác', $s);
        $this->assertStringContainsString('HỎI LẠI', $s);
    }

    public function test_instructions_contain_order_closing_rules(): void
    {
        $s = ReplyPersona::instructions($this->snapshot());

        $this->assertStringContainsString('QUY TẮC CHỐT ĐƠN', $s);
        // A: kết thúc bằng mời để lại địa chỉ + SĐT để lên đơn.
        $this->assertStringContainsString('để lại ĐỊA CHỈ và SỐ ĐIỆN THOẠI', $s);
        // B: muốn tư vấn → xin SĐT để nhân viên gọi.
        $this->assertStringContainsString('nhân viên gọi tư vấn', $s);
        // C: khách đã gửi địa chỉ + SĐT + xác nhận → cảm ơn + báo 2-4 ngày.
        $this->assertStringContainsString('2-4 ngày', $s);
        $this->assertStringContainsString('CHÚ Ý ĐIỆN THOẠI', $s);
    }

    public function test_instructions_append_buyer_name_and_extra(): void
    {
        $s = ReplyPersona::instructions($this->snapshot('Chị Lan'), 'Chỉ dẫn riêng tenant.');

        $this->assertStringContainsString('Tên khách: Chị Lan.', $s);
        $this->assertStringContainsString('Chỉ dẫn riêng tenant.', $s);
    }

    public function test_knowledge_block_empty_when_no_chunks(): void
    {
        $this->assertSame('', ReplyPersona::knowledgeBlock(null));
        $this->assertSame('', ReplyPersona::knowledgeBlock(new KnowledgeBase([])));
    }

    public function test_knowledge_block_renders_chunks_with_match_warning(): void
    {
        $kb = new KnowledgeBase([
            ['document_id' => 1, 'title' => 'Áo thun A', 'chunk_text' => 'Giá 199k, size M/L.', 'score' => 0.9],
        ]);
        $block = ReplyPersona::knowledgeBlock($kb);

        $this->assertStringContainsString('PHẢI khớp ĐÚNG sản phẩm khách hỏi', $block);
        $this->assertStringContainsString('[Áo thun A] Giá 199k, size M/L.', $block);
    }
}
