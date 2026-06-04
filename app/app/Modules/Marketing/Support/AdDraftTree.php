<?php

namespace CMBcoreSeller\Modules\Marketing\Support;

/**
 * Normalizes an AdDraft payload into ONE tree shape {adsets:[{...,ads:[...]}]}.
 * Accepts both the new tree payload and the legacy flat v1 payload (which is
 * wrapped into a single ad set + single ad), so mapper/publish have one code path.
 */
final class AdDraftTree
{
    /**
     * @param  array<string,mixed>  $payload
     * @return array{adsets: list<array<string,mixed>>}
     */
    public static function normalize(array $payload): array
    {
        if (isset($payload['adsets']) && is_array($payload['adsets'])) {
            return ['adsets' => array_values(array_filter($payload['adsets'], 'is_array'))];
        }

        // Legacy flat payload → one ad set + one ad. Empty payload → no ad sets.
        $hasFlat = isset($payload['creative']) || isset($payload['targeting']) || isset($payload['budget']);
        if (! $hasFlat) {
            return ['adsets' => []];
        }

        return ['adsets' => [[
            'key' => 'adset-1',
            'name' => 'Nhóm 1',
            'budget' => (array) ($payload['budget'] ?? []),
            'targeting' => (array) ($payload['targeting'] ?? []),
            'placements' => $payload['placements'] ?? 'automatic',
            'placement_platforms' => (array) ($payload['placement_platforms'] ?? []),
            'schedule' => (array) ($payload['schedule'] ?? []),
            'external_id' => null,
            'ads' => [[
                'key' => 'ad-1',
                'name' => 'Quảng cáo 1',
                'external_id' => null,
                'creative' => (array) ($payload['creative'] ?? []),
            ]],
        ]]];
    }
}
