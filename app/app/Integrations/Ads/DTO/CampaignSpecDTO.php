<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class CampaignSpecDTO
{
    /** @param list<string> $specialAdCategories */
    public function __construct(
        public string $objective,               // internal code: messages|engagement|traffic
        public string $name,
        public string $status = 'PAUSED',
        public array $specialAdCategories = [], // Graph requires the key (default none)
        public ?int $dailyBudgetMajor = null,   // set ⇒ CBO (campaign-level budget)
        public ?string $currency = null,
        public string $bidStrategy = 'LOWEST_COST_WITHOUT_CAP',
    ) {}
}
