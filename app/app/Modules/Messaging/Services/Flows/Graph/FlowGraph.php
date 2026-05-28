<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Graph;

/**
 * Đọc cấu trúc graph jsonb (nodes/edges) → tra node + tìm node kế tiếp theo handle.
 * KHÔNG có logic nghiệp vụ — chỉ điều hướng đồ thị.
 */
final class FlowGraph
{
    /** @var array<string,FlowNode> */
    private array $nodes = [];

    /** @var list<array{source:string,target:string,sourceHandle:?string}> */
    private array $edges = [];

    /** @param array<string,mixed> $graph */
    public function __construct(array $graph)
    {
        foreach ((array) ($graph['nodes'] ?? []) as $n) {
            if (! isset($n['id'], $n['type'])) {
                continue;
            }
            $this->nodes[(string) $n['id']] = new FlowNode(
                id: (string) $n['id'],
                type: (string) $n['type'],
                data: (array) ($n['data'] ?? []),
            );
        }
        foreach ((array) ($graph['edges'] ?? []) as $e) {
            if (! isset($e['source'], $e['target'])) {
                continue;
            }
            $this->edges[] = [
                'source' => (string) $e['source'],
                'target' => (string) $e['target'],
                'sourceHandle' => (isset($e['sourceHandle']) && $e['sourceHandle'] !== false && $e['sourceHandle'] !== 0 && $e['sourceHandle'] !== '')
                    ? (string) $e['sourceHandle'] : null,
            ];
        }
    }

    public function node(string $id): ?FlowNode
    {
        return $this->nodes[$id] ?? null;
    }

    public function triggerNode(): ?FlowNode
    {
        foreach ($this->nodes as $node) {
            if ($node->type === 'trigger') {
                return $node;
            }
        }

        return null;
    }

    /**
     * Node đích đi từ `$fromId` qua edge có `sourceHandle === $handle`.
     * Linear node: $handle = null. Trả null nếu không có edge khớp.
     */
    public function nextNodeId(string $fromId, ?string $handle = null): ?string
    {
        foreach ($this->edges as $edge) {
            if ($edge['source'] === $fromId && $edge['sourceHandle'] === $handle) {
                return $edge['target'];
            }
        }

        return null;
    }
}
