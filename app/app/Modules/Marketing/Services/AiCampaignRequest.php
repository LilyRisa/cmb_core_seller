<?php

namespace CMBcoreSeller\Modules\Marketing\Services;

/**
 * Ngữ cảnh đầu vào để AI sinh một chiến dịch Facebook từ một bài viết page.
 * Controller thu thập (bài viết + engagement + landing text) rồi truyền vào.
 */
final readonly class AiCampaignRequest
{
    public function __construct(
        public int $adAccountId,
        public int $tenantId,
        public ?int $userId,
        public string $objective,       // messages|engagement|traffic|conversions
        public string $mode,            // test|scale
        public string $placementMode,   // advantage_plus|manual
        public string $pageId,
        public string $pagePostId,
        public ?string $caption,
        public int $likes,
        public int $comments,
        public int $shares,
        public ?string $linkUrl,
        public ?string $ctaType,
        public ?string $landingText,
        public ?string $pixelId,
        public ?string $conversionEvent, // vd COMPLETE_REGISTRATION
        public ?string $startTime,       // ISO-8601 người dùng chọn; null ⇒ optimizer đề xuất
        public string $currency,
        public string $timezone,
        public string $prompt,           // ý định tự do của người dùng
    ) {}
}
