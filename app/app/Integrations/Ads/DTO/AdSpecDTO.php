<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdSpecDTO
{
    /**
     * Creative is EITHER an existing page post (pagePostId, keeps social proof)
     * OR a new creative (imageHash/videoId + copy). pageId always required.
     */
    public function __construct(
        public string $name,
        public string $adSetExternalId,
        public string $pageId,
        public ?string $pagePostId = null,  // object_story_id path
        public ?string $imageHash = null,   // object_story_spec path
        public ?string $videoId = null,
        public ?string $primaryText = null,
        public ?string $headline = null,
        public ?string $linkUrl = null,
        public string $cta = 'LEARN_MORE',
        public string $status = 'PAUSED',
        public bool $standardEnhancements = false, // Advantage+ creative (standard enhancements)
    ) {}
}
