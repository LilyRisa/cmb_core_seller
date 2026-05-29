<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowGraphValidator;
use Tests\TestCase;

/**
 * Validator đồ thị flow (spec §5.4). Dùng container để lấy NodeExecutorRegistry +
 * MessagingRegistry đã wire; model KHÔNG lưu DB (chỉ đọc graph/provider).
 */
class FlowGraphValidatorTest extends TestCase
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

    public function test_valid_linear_graph_has_no_errors(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 's', 'type' => 'send_message', 'data' => ['text' => 'hi']],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                ['source' => 's', 'target' => 'e', 'sourceHandle' => null],
            ],
        ];
        $this->assertSame([], $this->validator()->validate($this->flow($graph)));
    }

    public function test_empty_graph(): void
    {
        $this->assertSame(['empty'], $this->codes($this->validator()->validate($this->flow([]))));
    }

    public function test_missing_trigger(): void
    {
        $graph = ['nodes' => [['id' => 'e', 'type' => 'end', 'data' => []]], 'edges' => []];
        $this->assertContains('no_trigger', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    public function test_multiple_triggers(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't1', 'type' => 'trigger', 'data' => []],
                ['id' => 't2', 'type' => 'trigger', 'data' => []],
            ],
            'edges' => [],
        ];
        $this->assertContains('multiple_triggers', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    public function test_unknown_node_type(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'x', 'type' => 'teleport', 'data' => []],
            ],
            'edges' => [['source' => 't', 'target' => 'x', 'sourceHandle' => null]],
        ];
        $this->assertContains('unknown_node', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    public function test_dangling_edge(): void
    {
        $graph = [
            'nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]],
            'edges' => [['source' => 't', 'target' => 'ghost', 'sourceHandle' => null]],
        ];
        $this->assertContains('dangling_edge', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    public function test_unreachable_node(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'island', 'type' => 'send_message', 'data' => ['text' => 'x']],
            ],
            'edges' => [],
        ];
        $this->assertContains('unreachable', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    public function test_wait_reply_without_exit(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'w', 'type' => 'wait_reply', 'data' => []],
            ],
            'edges' => [['source' => 't', 'target' => 'w', 'sourceHandle' => null]],
        ];
        $this->assertContains('wait_no_exit', $this->codes($this->validator()->validate($this->flow($graph))));
    }

    public function test_send_buttons_missing_button_edge(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'ask', 'type' => 'send_buttons', 'data' => [
                    'text' => 'chọn', 'buttons' => [
                        ['id' => 'b1', 'label' => 'A', 'type' => 'postback'],
                        ['id' => 'b2', 'label' => 'B', 'type' => 'postback'],
                    ],
                ]],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 'ask', 'sourceHandle' => null],
                ['source' => 'ask', 'target' => 'e', 'sourceHandle' => 'b1'], // b2 chưa nối
            ],
        ];
        $errors = $this->validator()->validate($this->flow($graph));
        $this->assertContains('button_edge_missing', $this->codes($errors));
    }

    public function test_valid_send_buttons_flow_on_facebook(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'ask', 'type' => 'send_buttons', 'data' => [
                    'text' => 'chọn', 'buttons' => [['id' => 'b1', 'label' => 'A', 'type' => 'postback']],
                ]],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 'ask', 'sourceHandle' => null],
                ['source' => 'ask', 'target' => 'e', 'sourceHandle' => 'b1'],
            ],
        ];
        $this->assertSame([], $this->validator()->validate($this->flow($graph)));
    }

    public function test_send_buttons_on_provider_without_capability(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 'ask', 'type' => 'send_buttons', 'data' => [
                    'text' => 'chọn', 'buttons' => [['id' => 'b1', 'label' => 'A', 'type' => 'postback']],
                ]],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 'ask', 'sourceHandle' => null],
                ['source' => 'ask', 'target' => 'e', 'sourceHandle' => 'b1'],
            ],
        ];
        // 'manual' connector không hỗ trợ outbound.interactive.
        $this->assertContains('interactive_unsupported', $this->codes($this->validator()->validate($this->flow($graph, 'manual'))));
    }
}
