<?php

namespace CMBcoreSeller\Modules\Messaging\Support;

use Illuminate\Support\Str;

/**
 * Sinh chuỗi "tin nhắn gần nhất" cho danh sách hội thoại theo kiểu Facebook.
 *
 * Có nội dung text (body/caption) → hiển thị text gọn. Tin đính kèm không kèm text
 * → mô tả loại nội dung ("Đã gửi một hình ảnh" / "…một nhãn dán" / …) thay vì chuỗi
 * kỹ thuật thô "[image]"/"[sticker]".
 *
 * Dùng CHUNG cho mọi nơi ghi `conversations.last_message_preview` (ingest inbound,
 * outbound, lệnh recompute) để không lệch định dạng.
 */
class MessagePreview
{
    /** Khớp độ dài cột `conversations.last_message_preview` (string(200)). */
    private const MAX = 197;

    /** Body/caption có nội dung → text gọn; ngược lại → mô tả theo loại tin. */
    public static function build(?string $body, string $kind): string
    {
        $text = $body !== null ? trim((string) preg_replace('/\s+/', ' ', $body)) : '';
        if ($text !== '') {
            return Str::limit($text, self::MAX);
        }

        return 'Đã gửi '.self::kindPhrase($kind);
    }

    /** Cụm danh từ tiếng Việt cho từng loại tin đính kèm/không-text. */
    public static function kindPhrase(string $kind): string
    {
        return match ($kind) {
            'image' => 'một hình ảnh',
            'sticker' => 'một nhãn dán',
            'video' => 'một video',
            'audio' => 'một tin nhắn thoại',
            'file' => 'một tệp đính kèm',
            default => 'một tin nhắn',
        };
    }
}
