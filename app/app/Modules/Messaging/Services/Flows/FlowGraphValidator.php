<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

use CMBcoreSeller\Integrations\Messaging\Contracts\InteractiveMessagingConnector;
use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeExecutorRegistry;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\StepExecutorRegistry;

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
        private StepExecutorRegistry $steps,
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

        // Trigger "Bình luận trên bài viết" phải chọn ≥1 bài (nếu không, không khớp gì).
        if ($flow->trigger_type === AutomationFlow::TRIGGER_COMMENT_ON_POST) {
            $postIds = array_filter((array) (($flow->trigger_config ?? [])['post_ids'] ?? []));
            if ($postIds === []) {
                $errors[] = ['code' => 'no_post_selected', 'message' => 'Trigger "Bình luận trên bài viết" cần chọn ít nhất 1 bài viết.'];
            }
        }

        if ($nodeById === []) {
            return $errors === [] ? [['code' => 'empty', 'message' => 'Kịch bản chưa có bước nào.']] : $errors;
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

        // 6) send_buttons node: cần ≥1 nhánh ra; mỗi nút postback cần edge sourceHandle = nút.id.
        $hasButtonsNode = false;
        foreach ($nodeById as $id => $n) {
            if (($n['type'] ?? '') !== 'send_buttons') {
                continue;
            }
            $hasButtonsNode = true;
            $outs = $outgoing[$id] ?? [];
            if ($outs === []) {
                $errors[] = ['node_id' => $id, 'code' => 'buttons_no_exit', 'message' => 'Bước Gửi nút bấm cần ít nhất 1 nhánh ra.'];
            }
            $handles = array_column($outs, 'handle');
            $buttons = (array) (($n['data'] ?? [])['buttons'] ?? []);
            array_push($errors, ...$this->checkButtonEdges($id, $buttons, $handles));
        }

        // 7) Năng lực tin nút bấm: provider phải hỗ trợ outbound.interactive (node send_buttons cũ).
        if ($hasButtonsNode) {
            $capErr = $this->checkInteractiveCapability((string) $flow->provider);
            if ($capErr !== null) {
                $errors[] = $capErr;
            }
        }

        // 8) send_message node với steps[] không rỗng: validate từng bước.
        foreach ($nodeById as $id => $n) {
            if (($n['type'] ?? '') !== 'send_message') {
                continue;
            }
            $nodeSteps = (array) (($n['data'] ?? [])['steps'] ?? []);
            if ($nodeSteps === []) {
                continue; // không có steps ⇒ nhánh cũ, không validate ở đây
            }
            $handles = array_column($outgoing[$id] ?? [], 'handle');
            array_push($errors, ...$this->validateSteppedNode($id, $nodeSteps, $handles, (string) $flow->provider));
        }

        // 9) post_router: cần ≥1 nhánh ra; mỗi bài đã chọn cần edge sourceHandle = post id.
        foreach ($nodeById as $id => $n) {
            if (($n['type'] ?? '') !== 'post_router') {
                continue;
            }
            $outs = $outgoing[$id] ?? [];
            if ($outs === []) {
                $errors[] = ['node_id' => $id, 'code' => 'post_router_no_exit', 'message' => 'Bước Rẽ theo bài viết cần ít nhất 1 nhánh ra.'];
            }
            $handles = array_column($outs, 'handle');
            foreach ((array) (($n['data'] ?? [])['posts'] ?? []) as $p) {
                $p = (array) $p;
                $pid = (string) ($p['id'] ?? '');
                if ($pid !== '' && ! in_array($pid, $handles, true)) {
                    $label = (string) ($p['label'] ?? $pid);
                    $errors[] = ['node_id' => $id, 'code' => 'post_router_edge_missing', 'message' => "Bài \"{$label}\" chưa nối tới bước tiếp theo."];
                }
            }
        }

        return $errors;
    }

    /**
     * Kiểm tra mỗi nút postback có edge `sourceHandle === button.id` ra khỏi node.
     * URL button không cần edge. Tái dùng cho cả node send_buttons cũ lẫn step send_buttons.
     *
     * @param  list<array<string,mixed>>  $buttons
     * @param  list<?string>  $handles  Giá trị sourceHandle của outgoing edges
     * @return list<array{node_id:string,code:string,message:string}>
     */
    private function checkButtonEdges(string $nodeId, array $buttons, array $handles): array
    {
        $errors = [];
        foreach ($buttons as $b) {
            $b = (array) $b;
            if (((string) ($b['type'] ?? 'postback')) === 'url') {
                continue; // nút mở web không cần nhánh
            }
            $bid = isset($b['id']) ? (string) $b['id'] : '';
            if ($bid !== '' && ! in_array($bid, $handles, true)) {
                $label = (string) ($b['label'] ?? $b['title'] ?? $bid);
                $errors[] = ['node_id' => $nodeId, 'code' => 'button_edge_missing', 'message' => "Nút \"{$label}\" chưa nối tới bước tiếp theo."];
            }
        }

        return $errors;
    }

    /**
     * Kiểm năng lực gửi tin có nút bấm của provider (không biết tên sàn).
     * Trả lỗi nếu không hỗ trợ; null nếu OK.
     *
     * @return array{code:string,message:string}|null
     */
    private function checkInteractiveCapability(string $provider): ?array
    {
        $connector = $this->messaging->has($provider) ? $this->messaging->for($provider) : null;
        if (! $connector instanceof InteractiveMessagingConnector || ! $connector->supports('outbound.interactive')) {
            return ['code' => 'interactive_unsupported', 'message' => 'Kênh này không hỗ trợ tin có nút bấm.'];
        }

        return null;
    }

    /**
     * Validate danh sách bước của một node send_message có steps[] không rỗng.
     *
     * Rules:
     * - Mỗi step type phải đăng ký trong StepExecutorRegistry → `unknown_step`.
     * - send_text: cần text không rỗng → `step_text_empty`.
     * - send_media: cần attachment → `step_media_empty`.
     * - send_buttons: phải là bước CUỐI → `buttons_not_last` nếu không;
     *   mỗi postback button cần edge sourceHandle=button.id (tái dùng checkButtonEdges);
     *   cần capability interactive (tái dùng checkInteractiveCapability).
     *
     * @param  list<array<string,mixed>>  $steps  Raw steps từ node.data.steps[]
     * @param  list<?string>  $handles  sourceHandle values của outgoing edges của node
     * @return list<array{node_id:string,code:string,message:string}>
     */
    private function validateSteppedNode(string $nodeId, array $steps, array $handles, string $provider): array
    {
        $errors = [];
        $buttonStepIdx = null;
        $lastIdx = count($steps) - 1;

        foreach ($steps as $idx => $step) {
            $step = (array) $step;
            $type = (string) ($step['type'] ?? '');

            // Bước phải được đăng ký trong StepExecutorRegistry.
            if (! $this->steps->has($type)) {
                $errors[] = ['node_id' => $nodeId, 'code' => 'unknown_step', 'message' => "Loại bước không hỗ trợ: {$type}."];

                continue;
            }

            if ($type === 'send_text') {
                $text = trim((string) ($step['text'] ?? ''));
                if ($text === '') {
                    $errors[] = ['node_id' => $nodeId, 'code' => 'step_text_empty', 'message' => 'Bước gửi văn bản cần có nội dung.'];
                }
            } elseif ($type === 'send_media') {
                if (empty($step['attachment'] ?? null)) {
                    $errors[] = ['node_id' => $nodeId, 'code' => 'step_media_empty', 'message' => 'Bước gửi tệp đính kèm cần có tệp.'];
                }
            } elseif ($type === 'send_buttons') {
                $buttonStepIdx = $idx;
            }
        }

        // send_buttons phải là bước cuối cùng trong node.
        if ($buttonStepIdx !== null && $buttonStepIdx !== $lastIdx) {
            $errors[] = ['node_id' => $nodeId, 'code' => 'buttons_not_last', 'message' => 'Bước gửi nút bấm phải là bước cuối cùng.'];
        }

        // Kiểm tra bước send_buttons (nếu có): button edges + capability.
        if ($buttonStepIdx !== null) {
            $buttonStep = (array) $steps[$buttonStepIdx];
            $buttons = (array) ($buttonStep['buttons'] ?? []);
            // Tái dùng checkButtonEdges — cùng rule với node send_buttons cũ.
            array_push($errors, ...$this->checkButtonEdges($nodeId, $buttons, $handles));
            // Tái dùng checkInteractiveCapability — capability gate theo interface, không tên sàn.
            $capErr = $this->checkInteractiveCapability($provider);
            if ($capErr !== null) {
                $errors[] = $capErr;
            }
        }

        return $errors;
    }
}
