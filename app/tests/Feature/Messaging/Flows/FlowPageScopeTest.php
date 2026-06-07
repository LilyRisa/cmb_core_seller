<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowMatcher;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC 0035 — kịch bản tự động theo từng page.
 * Flow gán page A không match conv page B; applies_all_pages match mọi page;
 * page-specific thắng all-pages (->first()).
 */
class FlowPageScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $pageA;

    private ChannelAccount $pageB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'FlowMultiPage']);
        $this->pageA = $this->account('fp_a');
        $this->pageB = $this->account('fp_b');
    }

    private function account(string $ext): ChannelAccount
    {
        return ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => $ext, 'shop_name' => $ext, 'status' => 'active', 'messaging_enabled' => true,
        ]);
    }

    private function flow(string $name, bool $allPages, ?ChannelAccount $page = null): AutomationFlow
    {
        $flow = AutomationFlow::create([
            'tenant_id' => $this->tenant->getKey(), 'name' => $name, 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => AutomationFlow::TRIGGER_INBOX_ANY,
            'trigger_config' => [], 'enabled' => true, 'applies_all_pages' => $allPages,
            'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []],
        ]);
        if ($page !== null) {
            $flow->pages()->attach($page->getKey(), ['tenant_id' => $this->tenant->getKey()]);
        }

        return $flow;
    }

    private function conv(ChannelAccount $page, string $ext): Conversation
    {
        return Conversation::create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $page->getKey(),
            'provider' => 'facebook_page', 'thread_type' => Conversation::THREAD_MESSAGE,
            'external_conversation_id' => $ext, 'buyer_external_id' => 'b', 'status' => 'open', 'message_count' => 1,
        ]);
    }

    private function matcher(): FlowMatcher
    {
        return app(FlowMatcher::class);
    }

    public function test_page_specific_flow_matches_only_that_page(): void
    {
        $this->flow('A only', allPages: false, page: $this->pageA);

        $hitA = $this->matcher()->matching($this->conv($this->pageA, 'c_a'), [AutomationFlow::TRIGGER_INBOX_ANY]);
        $missB = $this->matcher()->matching($this->conv($this->pageB, 'c_b'), [AutomationFlow::TRIGGER_INBOX_ANY]);

        $this->assertCount(1, $hitA);
        $this->assertCount(0, $missB);
    }

    public function test_all_pages_flow_matches_every_page(): void
    {
        $this->flow('all', allPages: true);

        $this->assertCount(1, $this->matcher()->matching($this->conv($this->pageA, 'c_a2'), [AutomationFlow::TRIGGER_INBOX_ANY]));
        $this->assertCount(1, $this->matcher()->matching($this->conv($this->pageB, 'c_b2'), [AutomationFlow::TRIGGER_INBOX_ANY]));
    }

    public function test_page_specific_beats_all_pages(): void
    {
        $all = $this->flow('all', allPages: true);          // id nhỏ hơn (tạo trước)
        $specific = $this->flow('A specific', allPages: false, page: $this->pageA);

        $matched = $this->matcher()->matching($this->conv($this->pageA, 'c_a3'), [AutomationFlow::TRIGGER_INBOX_ANY]);

        $this->assertSame($specific->id, $matched->first()->id, 'page-specific phải đứng đầu (->first())');
        $this->assertNotSame($all->id, $matched->first()->id);
    }
}
