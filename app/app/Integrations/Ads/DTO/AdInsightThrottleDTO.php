<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

/**
 * Snapshot of Facebook's `x-fb-ads-insights-throttle` header — drives adaptive
 * pacing (when hot, the caller paces/uses async to avoid BUC rate limits).
 */
final readonly class AdInsightThrottleDTO
{
    public function __construct(
        public float $appUtilPct = 0.0,
        public float $accUtilPct = 0.0,
        public string $accessTier = 'development',
    ) {}

    /** True when close to the limit ⇒ caller should pace / go async / back off. */
    public function isHot(float $threshold = 80.0): bool
    {
        return $this->appUtilPct >= $threshold || $this->accUtilPct >= $threshold;
    }
}
