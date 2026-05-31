<?php

namespace CMBcoreSeller\Modules\Support\Exceptions;

use RuntimeException;

/**
 * Đính kèm CSKH vi phạm MIME whitelist / size limit (SPEC-0028).
 * Controller map → HTTP 422 `ATTACHMENT_INVALID`.
 */
class AttachmentInvalid extends RuntimeException
{
    public static function mime(string $mime): self
    {
        return new self("Định dạng tệp [{$mime}] không được hỗ trợ.");
    }

    public static function size(string $kind, int $limitBytes): self
    {
        $mb = (int) round($limitBytes / 1024 / 1024);

        return new self("Tệp [{$kind}] vượt giới hạn {$mb}MB.");
    }
}
