<?php

namespace CMBcoreSeller\Integrations\Ai\Concerns;

/**
 * Bóc bỏ "chuỗi suy luận" mà các model reasoning chèn thẳng vào nội dung trả lời
 * (DeepSeek-R1, Qwen-thinking, o1/o3 qua proxy, một số model local…). Nếu không bỏ,
 * khách sẽ thấy cả phần "show reasoning" thay vì chỉ câu trả lời.
 *
 * Hai dạng thường gặp được xử lý:
 *   1. Khối đóng-mở đầy đủ: <think>…</think> (và biến thể thinking/reasoning/reflection/thought).
 *   2. Template chat tự chèn thẻ mở ⇒ model chỉ sinh "suy luận…</think>câu trả lời"
 *      (mất thẻ mở, chỉ còn thẻ đóng) ⇒ bỏ mọi thứ TRƯỚC thẻ đóng đầu tiên.
 *   3. Bị cắt token giữa chừng ⇒ còn thẻ mở mà mất thẻ đóng ⇒ bỏ từ thẻ mở tới hết.
 *
 * Dùng chung cho mọi connector để sửa một chỗ, tránh lệch hành vi giữa các provider.
 */
trait SanitizesReasoning
{
    /** Các tên thẻ "suy luận" cần bóc (case-insensitive). */
    private const REASONING_TAGS = 'think|thinking|reason|reasoning|reflection|thought';

    private function stripReasoning(string $text): string
    {
        $tags = self::REASONING_TAGS;

        // 1) Khối đầy đủ <tag …>…</tag> (span nhiều dòng, non-greedy).
        $text = (string) preg_replace('#<('.$tags.')\b[^>]*>.*?</\1\s*>#is', '', $text);

        // 2) Còn thẻ đóng mà mất thẻ mở (template chèn sẵn thẻ mở) ⇒ bỏ phần trước nó.
        if (preg_match('#</(?:'.$tags.')\s*>#i', $text) === 1) {
            $text = (string) preg_replace('#^.*?</(?:'.$tags.')\s*>#is', '', $text);
        }

        // 3) Còn thẻ mở mà mất thẻ đóng (cắt token) ⇒ bỏ từ thẻ mở tới hết.
        $text = (string) preg_replace('#<(?:'.$tags.')\b[^>]*>.*$#is', '', $text);

        return trim($text);
    }
}
