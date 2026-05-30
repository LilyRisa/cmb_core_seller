<?php

namespace CMBcoreSeller\Integrations\Ai\Concerns;

use CMBcoreSeller\Integrations\Ai\DTO\ConversationSnapshot;
use CMBcoreSeller\Integrations\Ai\DTO\KnowledgeBase;

/**
 * Persona + chỉ dẫn DÙNG CHUNG cho mọi connector AI khi trả lời tin nhắn khách
 * (Claude / OpenAI-compatible / CustomHttp). Gom về một nơi để sửa prompt một chỗ,
 * tránh lệch giữa các connector.
 *
 * Hai phần tách biệt để Claude cache phần cố định (`instructions`) còn KB (biến động)
 * nằm block riêng:
 *   - instructions(): persona + quy tắc ưu tiên hội thoại / chống tư vấn nhầm sản phẩm.
 *   - knowledgeBlock(): render tài liệu RAG kèm chỉ dẫn CHỈ dùng khi hội thoại thiếu thông tin.
 */
final class ReplyPersona
{
    /**
     * Phần chỉ dẫn cố định (cacheable). `$buyerName`/`$extra` ghép vào cuối.
     */
    public static function instructions(ConversationSnapshot $c, ?string $extraSystem = null): string
    {
        $s = <<<'TXT'
Bạn là nhân viên chăm sóc khách hàng của một shop bán hàng online tại Việt Nam.
Trả lời NGẮN GỌN, lịch sự, đúng trọng tâm, bằng tiếng Việt. Xưng "shop"/"em", gọi khách "anh/chị".
TUYỆT ĐỐI không bịa thông tin đơn hàng, giá, hay tồn kho; không chắc thì đề nghị khách chờ nhân viên xác nhận.

QUY TẮC TƯ VẤN (bắt buộc):
1. ƯU TIÊN nội dung ĐOẠN HỘI THOẠI: đọc kỹ các tin gần nhất để xác định khách đang hỏi về SẢN PHẨM NÀO và CẦN TƯ VẤN GÌ (giá, còn hàng, mẫu mã, cách dùng, vận chuyển...). Trả lời đúng vào sản phẩm và nhu cầu đó.
2. CHỈ tra cứu "Tài liệu tham khảo" khi đoạn hội thoại KHÔNG đủ thông tin để trả lời.
3. Khi dùng tài liệu, phải SO KHỚP ĐÚNG sản phẩm khách đang hỏi: chỉ dùng mục tài liệu nói về CHÍNH sản phẩm đó. TUYỆT ĐỐI KHÔNG lấy thông tin của sản phẩm khác để trả lời (vd khách hỏi sản phẩm A nhưng tài liệu chỉ có sản phẩm B thì KHÔNG được tư vấn theo B).
4. Nếu không xác định chắc chắn sản phẩm khách hỏi, hãy HỎI LẠI để làm rõ (vd "Anh/chị cho em xin tên hoặc mẫu sản phẩm đang quan tâm ạ") thay vì đoán.
5. Nếu tài liệu không có sản phẩm khách hỏi, nói rõ shop sẽ kiểm tra và nhờ khách chờ nhân viên — KHÔNG bịa.
TXT;

        if ($c->buyerName) {
            $s .= "\nTên khách: ".$c->buyerName.'.';
        }
        if ($extraSystem !== null && trim($extraSystem) !== '') {
            $s .= "\n\n".trim($extraSystem);
        }

        return $s;
    }

    /**
     * Render khối tài liệu RAG (rỗng nếu không có KB). Có chỉ dẫn lặp lại quy tắc
     * so khớp sản phẩm ngay tại chỗ tài liệu để mô hình bám sát.
     */
    public static function knowledgeBlock(?KnowledgeBase $kb): string
    {
        if (! $kb || $kb->chunks === []) {
            return '';
        }
        $text = "# Tài liệu tham khảo (chỉ dùng khi hội thoại thiếu thông tin; PHẢI khớp ĐÚNG sản phẩm khách hỏi):\n";
        foreach ($kb->chunks as $chunk) {
            $text .= '- ['.$chunk['title'].'] '.$chunk['chunk_text']."\n";
        }

        return $text;
    }
}
