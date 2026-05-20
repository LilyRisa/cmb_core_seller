<?php

namespace CMBcoreSeller\Modules\Messaging\Exceptions;

use RuntimeException;

/**
 * Media upload vi phạm MIME whitelist / size limit (SPEC-0024 §7).
 * Controller map → HTTP 422 `ATTACHMENT_INVALID`.
 */
class AttachmentInvalid extends RuntimeException
{
    public static function mime(string $kind, string $mime): self
    {
        return new self("MIME [{$mime}] không hợp lệ cho loại [{$kind}].");
    }

    public static function size(string $kind, int $limitBytes): self
    {
        $mb = (int) round($limitBytes / 1024 / 1024);

        return new self("File [{$kind}] vượt giới hạn {$mb}MB.");
    }
}
