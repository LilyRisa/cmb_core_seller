<?php

namespace CMBcoreSeller\Modules\Marketing\Contracts;

/**
 * AI analysis client OWNED by Marketing (separate from Integrations/Ai messaging
 * flow). Reads the dedicated `marketing_ai_providers` config. SPEC 2026-06-04.
 */
interface MarketingAnalysisClient
{
    /**
     * @param  array<string,mixed>  $data  structured data to analyze
     * @return array{payload:array<string,mixed>, provider_code:?string, model:?string}
     */
    public function analyze(array $data, string $instruction): array;
}
