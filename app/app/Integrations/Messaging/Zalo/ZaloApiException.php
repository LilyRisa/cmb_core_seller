<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

class ZaloApiException extends \RuntimeException
{
    public function __construct(public int $zaloError, string $message)
    {
        parent::__construct($message, 0);
    }

    public static function from(int $error, string $message): self
    {
        return new self($error, "Zalo API error {$error}: {$message}");
    }
}
