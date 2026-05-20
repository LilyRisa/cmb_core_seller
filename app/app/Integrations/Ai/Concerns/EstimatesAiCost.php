<?php

namespace CMBcoreSeller\Integrations\Ai\Concerns;

/**
 * Tính `cost_micro_vnd` từ token usage × pricing (super-admin nhập). Pricing là
 * list `{kind:'input_token'|'output_token'|'embedding_token', unit:N, micro_vnd:M}`
 * — chi phí = round(tokens / unit × micro_vnd) cộng dồn theo kind.
 */
trait EstimatesAiCost
{
    /**
     * @param  list<array{kind:string, unit:int, micro_vnd:int}>  $pricing
     */
    protected function estimateCostMicroVnd(array $pricing, int $inputTokens, int $outputTokens, int $embeddingTokens = 0): int
    {
        $cost = 0;
        foreach ($pricing as $p) {
            $unit = max(1, (int) ($p['unit'] ?? 1000));
            $micro = (int) ($p['micro_vnd'] ?? 0);
            $tokens = match ($p['kind'] ?? '') {
                'input_token' => $inputTokens,
                'output_token' => $outputTokens,
                'embedding_token' => $embeddingTokens,
                default => 0,
            };
            $cost += (int) round($tokens / $unit * $micro);
        }

        return $cost;
    }
}
