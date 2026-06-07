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
     * @param  ?string  $schema  JSON schema hint the model must follow; null = the
     *                           legacy forecast schema (back-compat for AdsForecastService)
     * @param  ?\Closure(array<string,mixed>):array<string,mixed>  $fallback  deterministic
     *                                                                        output when no AI provider is active / parsing fails;
     *                                                                        null = the legacy forecast stub
     * @param  ?int  $tenantId  khi != null: tính 1 lượt AI (SPEC 0032) CHỈ khi provider THẬT
     *                          trả kết quả (không phải stub / no-provider / lỗi). null = không tính.
     * @return array{payload:array<string,mixed>, provider_code:?string, model:?string}
     */
    public function analyze(array $data, string $instruction, ?string $schema = null, ?\Closure $fallback = null, ?int $tenantId = null): array;
}
