<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeExecutorRegistry;

/**
 * Kiểm tra đồ thị flow trước khi XUẤT BẢN (spec §5.4). Trả danh sách lỗi
 * `[{ node_id?, code, message }]` — rỗng = hợp lệ. Thuần logic, không chạm DB;
 * FE highlight node lỗi theo `node_id`.
 *
 * KHÔNG biết tên sàn: năng lực tin nút bấm kiểm qua capability/interface
 * (`InteractiveMessagingConnector` + `supports('outbound.interactive')`).
 */
class FlowGraphValidator
{
    public function __construct(
        private NodeExecutorRegistry $nodes,
        private MessagingRegistry $messaging,
    ) {}

    /** @return list<array{node_id?:string, code:string, message:string}> */
    public function validate(AutomationFlow $flow): array
    {
        $graph = (array) $flow->graph;
        /** @var array<string,array<string,mixed>> $nodeById */
        $nodeById = [];
        foreach ((array) ($graph['nodes'] ?? []) as $n) {
            $n = (array) $n;
            if (! isset($n['id'], $n['type'])) {
                continue;
            }
            $nodeById[(string) $n['id']] = $n;
        }

        $errors = [];

        if ($nodeById === []) {
            return [['code' => 'empty', 'message' => 'Kịch bản chưa có bước nào.']];
        }

        // 1) Đúng 1 node trigger.
        $triggerIds = array_keys(array_filter($nodeById, fn ($n) => ($n['type'] ?? '') === 'trigger'));
        if ($triggerIds === []) {
            $errors[] = ['code' => 'no_trigger', 'message' => 'Thiếu bước Bắt đầu.'];
        } elseif (count($triggerIds) > 1) {
            foreach ($triggerIds as $tid) {
                $errors[] = ['node_id' => $tid, 'code' => 'multiple_triggers', 'message' => 'Chỉ được 1 bước Bắt đầu.'];
            }
        }

        // 2) Loại node phải được đăng ký (registry).
        foreach ($nodeById as $id => $n) {
            $type = (string) ($n['type'] ?? '');
            if (! $this->nodes->has($type)) {
                $errors[] = ['node_id' => $id, 'code' => 'unknown_node', 'message' => "Loại bước không hỗ trợ: {$type}."];
            }
        }

        // 3) Edge treo + dựng bảng edge ra theo node.
        /** @var array<string,list<array{target:string,handle:?string}>> $outgoing */
        $outgoing = [];
        foreach ((array) ($graph['edges'] ?? []) as $e) {
            $e = (array) $e;
            $src = isset($e['source']) ? (string) $e['source'] : '';
            $tgt = isset($e['target']) ? (string) $e['target'] : '';
            if ($src === '' || $tgt === '') {
                continue;
            }
            if (! isset($nodeById[$src])) {
                $errors[] = ['code' => 'dangling_edge', 'message' => "Đường nối có bước nguồn không tồn tại: {$src}."];
            }
            if (! isset($nodeById[$tgt])) {
                $errors[] = ['code' => 'dangling_edge', 'message' => "Đường nối có bước đích không tồn tại: {$tgt}."];
            }
            $handle = (isset($e['sourceHandle']) && $e['sourceHandle'] !== '' && $e['sourceHandle'] !== false)
                ? (string) $e['sourceHandle'] : null;
            $outgoing[$src][] = ['target' => $tgt, 'handle' => $handle];
        }

        // 4) Mọi node tới được từ trigger (BFS).
        if (count($triggerIds) >= 1) {
            $seen = [];
            $queue = [$triggerIds[0]];
            while ($queue !== []) {
                $cur = array_shift($queue);
                if (isset($seen[$cur])) {
                    continue;
                }
                $seen[$cur] = true;
                foreach ($outgoing[$cur] ?? [] as $edge) {
                    if (isset($nodeById[$edge['target']]) && ! isset($seen[$edge['target']])) {
                        $queue[] = $edge['target'];
                    }
                }
            }
            foreach ($nodeById as $id => $n) {
                if (! isset($seen[$id])) {
                    $errors[] = ['node_id' => $id, 'code' => 'unreachable', 'message' => 'Bước không nối được từ Bắt đầu.'];
                }
            }
        }

        // 5) Node "chờ" cần ≥1 nhánh ra.
        foreach ($nodeById as $id => $n) {
            if (($n['type'] ?? '') === 'wait_reply' && ($outgoing[$id] ?? []) === []) {
                $errors[] = ['node_id' => $id, 'code' => 'wait_no_exit', 'message' => 'Bước Chờ trả lời cần ít nhất 1 nhánh ra.'];
            }
        }

        // 6) send_buttons: cần ≥1 nhánh ra; mỗi nút postback cần edge sourceHandle = nút.id.
        $hasButtons = false;
        foreach ($nodeById as $id => $n) {
            if (($n['type'] ?? '') !== 'send_buttons') {
                continue;
            }
            $hasButtons = true;
            $outs = $outgoing[$id] ?? [];
            if ($outs === []) {
                $errors[] = ['node_id' => $id, 'code' => 'buttons_no_exit', 'message' => 'Bước Gửi nút bấm cần ít nhất 1 nhánh ra.'];
            }
            $handles = array_column($outs, 'handle');
            foreach ((array) (($n['data'] ?? [])['buttons'] ?? []) as $b) {
                $b = (array) $b;
                if (((string) ($b['type'] ?? 'postback')) === 'url') {
                    continue; // nút mở web không cần nhánh
                }
                $bid = isset($b['id']) ? (string) $b['id'] : '';
                if ($bid !== '' && ! in_array($bid, $handles, true)) {
                    $label = (string) ($b['label'] ?? $b['title'] ?? $bid);
                    $errors[] = ['node_id' => $id, 'code' => 'button_edge_missing', 'message' => "Nút \"{$label}\" chưa nối tới bước tiếp theo."];
                }
            }
        }

        // 7) Năng lực tin nút bấm: provider phải hỗ trợ outbound.interactive.
        if ($hasButtons) {
            $provider = (string) $flow->provider;
            $connector = $this->messaging->has($provider) ? $this->messaging->for($provider) : null;
            if (! $connector instanceof InteractiveMessagingConnector || ! $connector->supports('outbound.interactive')) {
                $errors[] = ['code' => 'interactive_unsupported', 'message' => 'Kênh này không hỗ trợ tin có nút bấm.'];
            }
        }

        return $errors;
    }
}
