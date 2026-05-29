<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowEngine;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Node "Gửi tin" đa phương tiện (S4): text + media (đã upload sẵn) ⇒ mỗi media 1 tin.
 */
class FlowMediaNodeTest extends TestCase
{
    use RefreshDatabase;

    private function conv(): Conversation
    {
        return Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c1',
            'buyer_external_id' => 'b1', 'status' => 'open', 'message_count' => 0,
        ]);
    }

    public function test_send_node_with_text_and_media_queues_separate_messages(): void
    {
        Queue::fake();
        $conv = $this->conv();
        $flow = AutomationFlow::create([
            'tenant_id' => 1, 'name' => 'F', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'enabled' => true,
            'graph' => [
                'nodes' => [
                    ['id' => 't', 'type' => 'trigger', 'data' => []],
                    ['id' => 's', 'type' => 'send_message', 'data' => [
                        'text' => 'Xem ảnh nhé',
                        'attachments' => [
                            ['kind' => 'image', 'storage_path' => 'tenants/1/messaging/flows/1/a.jpg', 'mime' => 'image/jpeg', 'filename' => 'a.jpg'],
                            ['kind' => 'audio', 'storage_path' => 'tenants/1/messaging/flows/1/v.mp3', 'mime' => 'audio/mpeg'],
                        ],
                    ]],
                    ['id' => 'e', 'type' => 'end', 'data' => []],
                ],
                'edges' => [
                    ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                    ['source' => 's', 'target' => 'e', 'sourceHandle' => null],
                ],
            ],
        ]);

        $run = app(FlowEngine::class)->start($flow, $conv, inboundBody: 'hi');
        $this->assertSame(FlowRun::STATUS_COMPLETED, $run->fresh()->status);

        $msgs = Message::withoutGlobalScope(TenantScope::class)->where('conversation_id', $conv->id)->get();
        $this->assertSame(1, $msgs->where('kind', Message::KIND_TEXT)->where('body', 'Xem ảnh nhé')->count());
        $this->assertSame(1, $msgs->where('kind', Message::KIND_IMAGE)->count());
        $this->assertSame(1, $msgs->where('kind', Message::KIND_AUDIO)->count());

        $atts = MessageAttachment::withoutGlobalScope(TenantScope::class)->whereIn('message_id', $msgs->pluck('id'))->get();
        $this->assertSame(2, $atts->count());
        $this->assertSame('downloaded', $atts->first()->status);
        $this->assertContains('tenants/1/messaging/flows/1/a.jpg', $atts->pluck('storage_path')->all());
    }
}
