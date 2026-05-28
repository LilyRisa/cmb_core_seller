<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;

/**
 * Rẽ nhánh theo từ khoá trong nội dung inbound.
 * data: { keywords: string[], match: 'any'|'all' } — edge handle: 'match'/'no_match'.
 */
class ConditionNodeExecutor implements NodeExecutor
{
    public function type(): string { return 'condition'; }

    public function execute(FlowNode $node, FlowContext $ctx): NodeResult
    {
        $keywords = array_values(array_filter(array_map(
            static fn ($k) => mb_strtolower(trim((string) $k)),
            (array) ($node->data['keywords'] ?? []),
        ), static fn (string $k) => $k !== ''));

        $haystack = mb_strtolower((string) ($ctx->inboundBody ?? ''));
        $matchMode = ($node->data['match'] ?? 'any') === 'all' ? 'all' : 'any';

        if ($keywords === []) {
            return NodeResult::advance('no_match');
        }

        $hits = array_filter($keywords, static fn (string $k) => str_contains($haystack, $k));
        $matched = $matchMode === 'all'
            ? count($hits) === count($keywords)
            : count($hits) > 0;

        return NodeResult::advance($matched ? 'match' : 'no_match');
    }
}
