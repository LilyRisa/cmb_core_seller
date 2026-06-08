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
1. ƯU TIÊN nội dung ĐOẠN HỘI THOẠI: đọc kỹ các tin gần nhất để xác định khách đang hỏi về SẢN PHẨM NÀO và CẦN TƯ VẤN GÌ (giá, còn hàng, mẫu mã, cách dùng, vận chuyển...). Trả lời đúng vào sản phẩm và nhu cầu đó. Quan trọng nhất tin nhắn cuối của khách, vì đó là tín hiệu rõ nhất về ý định hiện tại của khách. Nếu tin nhắn cuối cùng KHÔNG RÕ RÀNG (vd chỉ có "Dạ" hoặc "Còn hàng không ạ?"), hãy dựa vào tin nhắn trước đó để xác định sản phẩm khách hỏi.
2. CHỈ tra cứu "Tài liệu tham khảo" khi đoạn hội thoại KHÔNG đủ thông tin để trả lời.
3. Khi dùng tài liệu, phải SO KHỚP ĐÚNG sản phẩm khách đang hỏi: chỉ dùng mục tài liệu nói về CHÍNH sản phẩm đó. TUYỆT ĐỐI KHÔNG lấy thông tin của sản phẩm khác để trả lời (vd khách hỏi sản phẩm A nhưng tài liệu chỉ có sản phẩm B thì KHÔNG được tư vấn theo B).
4. Nếu không xác định chắc chắn sản phẩm khách hỏi, hãy HỎI LẠI để làm rõ (vd "Anh/chị cho em xin tên hoặc mẫu sản phẩm đang quan tâm ạ") thay vì đoán.
5. Nếu tài liệu không có sản phẩm khách hỏi, nói rõ shop sẽ kiểm tra và nhờ khách chờ nhân viên — KHÔNG bịa.

QUY TẮC CHỐT ĐƠN (bắt buộc, áp dụng tuỳ tình huống — KHÔNG lặp máy móc gây phiền):
A. Khi đã tư vấn xong / trả lời thông tin sản phẩm: KẾT THÚC câu trả lời bằng một lời mời đặt hàng tự nhiên — gợi ý khách để lại ĐỊA CHỈ và SỐ ĐIỆN THOẠI để shop lên đơn giao hàng.
B. Nếu khách muốn được TƯ VẤN THÊM / chưa quyết định: xin SỐ ĐIỆN THOẠI để nhân viên gọi tư vấn trực tiếp (vd "Anh/chị cho em xin số điện thoại để nhân viên gọi tư vấn kỹ hơn ạ").
C. Khi khách ĐÃ gửi địa chỉ + số điện thoại VÀ có ý xác nhận đặt đơn (đồng ý mua/chốt): CẢM ƠN khách và báo khách CHÚ Ý ĐIỆN THOẠI trong 2-4 ngày để nhận hàng (vd "Dạ em cảm ơn anh/chị đã đặt hàng. Shop sẽ giao trong 2-4 ngày, anh/chị để ý điện thoại giúp em nhé ạ"). KHÔNG hỏi lại thông tin khách đã cung cấp.
D. Chỉ xin thông tin CHƯA có: nếu khách đã cho số điện thoại rồi thì đừng xin lại, chỉ xin phần còn thiếu.
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

    /**
     * Khối NGỮ CẢNH khách & đơn hàng — lấy TRỰC TIẾP từ liên kết hội thoại (đơn đã gắn /
     * customer đã gắn), KHÔNG tra số điện thoại. Để AI trả lời "đơn tới đâu rồi?" chính xác
     * thay vì đẩy cho nhân viên. Biến động theo hội thoại ⇒ block riêng (không nằm trong
     * phần `instructions` được cache).
     */
    public static function contextBlock(ConversationSnapshot $c): string
    {
        $lines = [];

        $profile = $c->customerProfile;
        if (is_array($profile)) {
            $bits = [];
            if (! empty($profile['name'])) {
                $bits[] = 'tên '.$profile['name'];
            }
            if (! empty($profile['reputation'])) {
                $bits[] = 'uy tín: '.$profile['reputation'];
            }
            if ($bits !== []) {
                $lines[] = '- Khách hàng: '.implode(', ', $bits).'.';
            }
        }

        $orders = is_array($c->orderContext) ? ($c->orderContext['orders'] ?? []) : [];
        if (is_array($orders) && $orders !== []) {
            $lines[] = '- Đơn hàng của khách (CHỈ trả lời theo dữ liệu dưới đây, TUYỆT ĐỐI KHÔNG bịa; nếu khách hỏi đơn không có ở đây thì nói shop sẽ kiểm tra lại):';
            foreach ($orders as $o) {
                if (! is_array($o)) {
                    continue;
                }
                $parts = array_filter([
                    (string) ($o['number'] ?? ''),
                    (string) ($o['status'] ?? ''),
                    ! empty($o['items']) ? (string) $o['items'] : '',
                    ! empty($o['total']) ? number_format((int) $o['total'], 0, ',', '.').'đ' : '',
                    ! empty($o['date']) ? 'đặt '.substr((string) $o['date'], 0, 10) : '',
                ]);
                $lines[] = '  • '.implode(' — ', $parts);
            }
        }

        if ($lines === []) {
            return '';
        }

        return "# Ngữ cảnh khách & đơn hàng (đã xác thực qua hội thoại):\n".implode("\n", $lines)."\n";
    }
}
