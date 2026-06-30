<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowGraphValidator;
use Tests\TestCase;

/**
 * Validator cho node send_message có steps[] (Task 4 spec 2026-06-30-flow-node-with-steps-2a).
 * Kiểm tra các rule mới: unknown_step, step_text_empty, step_media_empty,
 * buttons_not_last, button_edge_missing (tái dùng), interactive_unsupported (tái dùng).
 * Node KHÔNG có steps → rule cũ giữ nguyên (non-breaking).
 */
class SteppedFlowValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_secret' => 'secret',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);
    }

    private function validator(): FlowGraphValidator
    {
        return app(FlowGraphValidator::class);
    }

    /** @param array<string,mixed> $graph */
    private function flow(array $graph, string $provider = 'facebook_page'): AutomationFlow
    {
        return new AutomationFlow([
            'tenant_id' => 1, 'name' => 'F', 'provider' => $provider,
            'status' => AutomationFlow::STATUS_DRAFT,
            'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'graph' => $graph,
        ]);
    }

    /** @param list<array{node_id?:string,code:string,message:string}> $errors */
    private function codes(array $errors): array
    {
        return array_values(array_unique(array_column($errors, 'code')));
    }

    /**
     * Dựng graph với node send_message có steps[]. Mặc định thêm edge null từ
     * s→e để BFS reachability pass; $extraEdges để bổ sung handle-specific edges.
     *
     * @param  list<array<string,mixed>>  $steps
     * @param  list<array<string,mixed>>  $extraEdges
     * @return array<string,mixed>
     */
    private function steppedGraph(array $steps, array $extraEdges = []): array
    {
        return [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 's', 'type' => 'send_message', 'data' => ['steps' => $steps]],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => array_merge([
                ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                ['source' => 's', 'target' => 'e', 'sourceHandle' => null],
            ], $extraEdges),
        ];
    }

    // ─── valid ───────────────────────────────────────────────────────────────

    public function test_valid_stepped_node_with_text_steps_has_no_errors(): void
    {
        $graph = $this->steppedGraph([
            ['id' => 'st1', 'type' => 'send_text', 'text' => 'Xin chào'],
            ['id' => 'st2', 'type' => 'send_text', 'text' => 'Thông báo'],
        ]);
        $this->assertSame([], $this->validator()->validate($this->flow($graph)));
    }

    public function test_valid_stepped_node_send_buttons_last_with_edges_has_no_errors(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 's', 'type' => 'send_message', 'data' => ['steps' => [
                    ['id' => 'st1', 'type' => 'send_text', 'text' => 'Chào bạn'],
                    ['id' => 'st2', 'type' => 'send_buttons', 'text' => 'Chọn', 'buttons' => [
                        ['id' => 'b1', 'label' => 'A', 'type' => 'postback'],
                    ]],
                ]]],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                ['source' => 's', 'target' => 'e', 'sourceHandle' => 'b1'],
            ],
        ];
        $this->assertSame([], $this->validator()->validate($this->flow($graph)));
    }

    // ─── unknown_step ────────────────────────────────────────────────────────

    public function test_unknown_step_type_returns_unknown_step(): void
    {
        $graph = $this->steppedGraph([
            ['id' => 'st1', 'type' => 'teleport_user'],
        ]);
        $this->assertContains('unknown_step', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    // ─── step_text_empty ─────────────────────────────────────────────────────

    public function test_send_text_with_empty_string_returns_step_text_empty(): void
    {
        $graph = $this->steppedGraph([
            ['id' => 'st1', 'type' => 'send_text', 'text' => ''],
        ]);
        $this->assertContains('step_text_empty', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    public function test_send_text_with_whitespace_only_returns_step_text_empty(): void
    {
        $graph = $this->steppedGraph([
            ['id' => 'st1', 'type' => 'send_text', 'text' => '   '],
        ]);
        $this->assertContains('step_text_empty', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    public function test_send_text_missing_key_returns_step_text_empty(): void
    {
        $graph = $this->steppedGraph([
            ['id' => 'st1', 'type' => 'send_text'],
        ]);
        $this->assertContains('step_text_empty', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    // ─── step_media_empty ────────────────────────────────────────────────────

    public function test_send_media_without_attachment_returns_step_media_empty(): void
    {
        $graph = $this->steppedGraph([
            ['id' => 'st1', 'type' => 'send_media', 'kind' => 'image'],
        ]);
        $this->assertContains('step_media_empty', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    // ─── buttons_not_last ────────────────────────────────────────────────────

    public function test_send_buttons_not_last_step_returns_buttons_not_last(): void
    {
        // send_buttons ở idx=0, send_text ở idx=1 → phải báo lỗi
        $graph = $this->steppedGraph([
            ['id' => 'st1', 'type' => 'send_buttons', 'text' => 'Chọn', 'buttons' => [
                ['id' => 'b1', 'label' => 'A', 'type' => 'postback'],
            ]],
            ['id' => 'st2', 'type' => 'send_text', 'text' => 'Nội dung sau nút'],
        ]);
        $this->assertContains('buttons_not_last', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    // ─── button_edge_missing (stepped) ───────────────────────────────────────

    public function test_stepped_send_buttons_postback_missing_edge_returns_button_edge_missing(): void
    {
        // b1 có edge, b2 không có → button_edge_missing
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 's', 'type' => 'send_message', 'data' => ['steps' => [
                    ['id' => 'st1', 'type' => 'send_buttons', 'text' => 'Chọn', 'buttons' => [
                        ['id' => 'b1', 'label' => 'A', 'type' => 'postback'],
                        ['id' => 'b2', 'label' => 'B', 'type' => 'postback'],
                    ]],
                ]]],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                ['source' => 's', 'target' => 'e', 'sourceHandle' => 'b1'],
                // b2 chưa nối
            ],
        ];
        $this->assertContains('button_edge_missing', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    public function test_stepped_send_buttons_url_button_does_not_require_edge(): void
    {
        // url button không cần edge → không báo lỗi
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 's', 'type' => 'send_message', 'data' => ['steps' => [
                    ['id' => 'st1', 'type' => 'send_buttons', 'text' => 'Chọn', 'buttons' => [
                        ['id' => 'u1', 'label' => 'Mở web', 'type' => 'url', 'url' => 'https://example.com'],
                    ]],
                ]]],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                ['source' => 's', 'target' => 'e', 'sourceHandle' => null],
            ],
        ];
        $codes = $this->codes($this->validator()->validate($this->flow($graph)));
        $this->assertNotContains('button_edge_missing', $codes);
    }

    // ─── interactive_unsupported (stepped) ───────────────────────────────────

    public function test_stepped_send_buttons_on_unsupported_provider_returns_interactive_unsupported(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 's', 'type' => 'send_message', 'data' => ['steps' => [
                    ['id' => 'st1', 'type' => 'send_buttons', 'text' => 'Chọn', 'buttons' => [
                        ['id' => 'b1', 'label' => 'A', 'type' => 'postback'],
                    ]],
                ]]],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                ['source' => 's', 'target' => 'e', 'sourceHandle' => 'b1'],
            ],
        ];
        $this->assertContains(
            'interactive_unsupported',
            $this->codes($this->validator()->validate($this->flow($graph, 'manual'))),
        );
    }

    // ─── non-breaking: legacy node ───────────────────────────────────────────

    public function test_legacy_send_message_node_without_steps_keeps_existing_behavior(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 's', 'type' => 'send_message', 'data' => ['text' => 'hello']],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                ['source' => 's', 'target' => 'e', 'sourceHandle' => null],
            ],
        ];
        $this->assertSame([], $this->validator()->validate($this->flow($graph)));
    }

    public function test_legacy_send_message_with_empty_steps_array_keeps_existing_behavior(): void
    {
        // steps=[] (empty) → nhánh cũ, không validate steps
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 's', 'type' => 'send_message', 'data' => ['text' => 'hello', 'steps' => []]],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                ['source' => 's', 'target' => 'e', 'sourceHandle' => null],
            ],
        ];
        $this->assertSame([], $this->validator()->validate($this->flow($graph)));
    }
}
