<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdSetSpecDTO
{
    /**
     * @param  array<string,mixed>  $targeting  Graph targeting spec (geo/age/interests/custom_audiences)
     */
    public function __construct(
        public string $name,
        public string $campaignExternalId,
        public string $objective,           // internal code → FacebookObjectiveMap
        public int $dailyBudgetMajor,       // VND integer (core unit)
        public string $currency,            // ad account currency
        public array $targeting,
        public ?string $pageId = null,      // required when objective needs promoted_object
        public ?string $startTime = null,   // ISO-8601; null = now
        public string $status = 'PAUSED',
        /** @var array<string,mixed>|null */
        public ?array $placementConfig = null,
        public ?string $endTime = null,     // ISO-8601; null = runs until stopped
    ) {}
}
