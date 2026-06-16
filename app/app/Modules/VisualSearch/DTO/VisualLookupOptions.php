<?php

namespace CMBcoreSeller\Modules\VisualSearch\DTO;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;

/** Tuỳ chọn tra cứu. Giá trị null ⇒ matcher lấy mặc định từ config/visual_search.php. */
final class VisualLookupOptions
{
    public function __construct(
        public ?int $channelAccountId = null,
        public bool $rerank = false,
        public ?string $providerCode = null,
        public ?AiContext $aiContext = null,
        public ?int $topKImages = null,
        public ?int $topNItems = null,
    ) {}
}
