<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowGraph;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeExecutorRegistry;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Chạy flow theo state máy. start() vào flow khi trigger khớp; đi qua các node
 * "tức thì" cho tới khi gặp wait_reply ⇒ lưu state WAITING. resume() là nơi DUY
 * NHẤT cho 1 run waiting đi tiếp (claim nguyên tử waiting→active chống 2 worker
 * cùng chạy), tiếp tục từ node SAU wait với nội dung trả lời của khách.
 *
 * Idempotent: flow_runs unique partial (flow, conversation) WHERE active/waiting.
 * Ngoài auth tenant (listener) ⇒ tenant từ conversation. MAX_STEPS chống vòng lặp.
 */
class FlowEngine
{
    private const MAX_STEPS = 50;

    public function __construct(private NodeExecutorRegistry $registry) {}

    public function start(AutomationFlow $flow, Conversation $conv, ?string $inboundBody = null): FlowRun
    {
        $existing = FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $conv->tenant_id)
            ->where('flow_id', $flow->id)->where('conversation_id', $conv->id)
            ->whereIn('status', [FlowRun::STATUS_ACTIVE, FlowRun::STATUS_WAITING])->first();
        if ($existing) {
            return $existing;
        }

        try {
            $run = FlowRun::create([
                'tenant_id' => $conv->tenant_id,
                'flow_id' => $flow->id,
                'conversation_id' => $conv->id,
                'status' => FlowRun::STATUS_ACTIVE,
                'context' => [],
                'entered_at' => Carbon::now(),
            ]);
        } catch (QueryException) {
            return FlowRun::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $conv->tenant_id)
                ->where('flow_id', $flow->id)->where('conversation_id', $conv->id)
                ->whereIn('status', [FlowRun::STATUS_ACTIVE, FlowRun::STATUS_WAITING])->firstOrFail();
        }

        $graph = new FlowGraph((array) $flow->graph);
        $trigger = $graph->triggerNode();
        if (! $trigger) {
            $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => 'no_trigger_node']);

            return $run;
        }

        // Tin khởi động flow CÓ sẵn cho node tức thì (vd condition rẽ nhánh theo nội
        // dung tin/bình luận đầu). wait_reply vẫn dừng vì wait luôn pause.
        return $this->walk($run, $conv, $graph, $trigger->id, $inboundBody);
    }

    public function resume(FlowRun $run, Conversation $conv, ?string $inboundBody = null): FlowRun
    {
        if ($run->status !== FlowRun::STATUS_WAITING || ! $run->current_node_id) {
            return $run;
        }

        // Claim nguyên tử: chỉ worker chuyển được waiting→active mới chạy tiếp.
        $claimed = FlowRun::withoutGlobalScope(TenantScope::class)
            ->where('id', $run->id)
            ->where('status', FlowRun::STATUS_WAITING)
            ->update(['status' => FlowRun::STATUS_ACTIVE]);
        if ($claimed === 0) {
            return $run->fresh() ?? $run;
        }
        $run = $run->fresh() ?? $run;

        $flow = AutomationFlow::withoutGlobalScope(TenantScope::class)->find($run->flow_id);
        if (! $flow) {
            $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => 'flow_missing']);

            return $run;
        }

        $graph = new FlowGraph((array) $flow->graph);
        // Tin mới của khách là "trả lời" cho node wait ⇒ tiếp tục từ node SAU wait.
        $resumeFrom = $graph->nextNodeId((string) $run->current_node_id, null);
        if ($resumeFrom === null) {
            $run->update(['status' => FlowRun::STATUS_ENDED, 'last_advanced_at' => Carbon::now()]);

            return $run;
        }

        return $this->walk($run, $conv, $graph, $resumeFrom, $inboundBody);
    }

    private function walk(FlowRun $run, Conversation $conv, FlowGraph $graph, string $startNodeId, ?string $inboundBody): FlowRun
    {
        $nodeId = $startNodeId;

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $node = $graph->node($nodeId);
            if (! $node) {
                $run->update(['status' => FlowRun::STATUS_ENDED, 'last_advanced_at' => Carbon::now()]);

                return $run;
            }
            if (! $this->registry->has($node->type)) {
                Log::warning('flow.unknown_node_type', ['type' => $node->type, 'flow_run' => $run->id]);
                $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => 'unknown_node:'.$node->type]);

                return $run;
            }

            $result = $this->registry->for($node->type)->execute($node, new FlowContext($conv, $run, $inboundBody));
            $run = $run->fresh() ?? $run;

            if ($result->isWait()) {
                $run->update(['status' => FlowRun::STATUS_WAITING, 'current_node_id' => $nodeId, 'last_advanced_at' => Carbon::now()]);

                return $run;
            }
            if ($result->isEnd()) {
                $run->update(['status' => FlowRun::STATUS_COMPLETED, 'current_node_id' => $nodeId, 'last_advanced_at' => Carbon::now()]);

                return $run;
            }
            if ($result->isFail()) {
                $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => $result->error, 'current_node_id' => $nodeId]);

                return $run;
            }

            $next = $graph->nextNodeId($nodeId, $result->handle);
            if ($next === null) {
                $run->update(['status' => FlowRun::STATUS_ENDED, 'last_advanced_at' => Carbon::now()]);

                return $run;
            }
            $nodeId = $next;
        }

        Log::warning('flow.max_steps_exceeded', ['flow_run' => $run->id]);
        $run->update(['status' => FlowRun::STATUS_FAILED, 'error' => 'max_steps_exceeded']);

        return $run;
    }
}
