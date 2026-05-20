<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

/**
 * Canonical message kind — kind cụ thể của từng sàn (sticker / voice / location / quick_reply)
 * map về `text` (với body mô tả) hoặc dropped per ADR-0017. Mở rộng = thêm case
 * vào enum + cập nhật `messages.kind` enum DB.
 */
enum MessageKind: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case File = 'file';
    case Template = 'template';
    case System = 'system';

    public function isMedia(): bool
    {
        return in_array($this, [self::Image, self::Video, self::File], true);
    }
}
