<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AiAssistantRun;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageDraft;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test AI suggestion (SPEC-0024 S6): generate → draft → accept/reject, billing
 * feature gate (messaging_ai = Business), monthly limit. Provider `manual`
 * (deterministic, free) ⇒ pipeline E2E không tốn LLM credit.
 */
class MessagingAiSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Conversation $conv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'AiShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activate(Plan::CODE_BUSINESS);

        // Super-admin đã bật provider manual.
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true, 'default_model' => 'manual-v1']);

        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_ai_1',
            'shop_name' => 'AI Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);

        $this->conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_ai_1',
            'buyer_external_id' => 'buyer_1',
            'buyer_name' => 'Anh Khách',
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now()->subMinutes(2),
        ]);

        Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $this->conv->id,
            'external_message_id' => 'in_1',
            'direction' => Message::DIRECTION_INBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'Shop ơi đơn của em bao giờ giao? SĐT 0912345678',
            'delivery_status' => Message::STATUS_SENT,
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activate(string $planCode): void
    {
        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    public function test_generate_creates_draft(): void
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/ai-suggestion")
            ->assertOk();

        $draftId = $res->json('data.draft_id');
        $this->assertNotNull($draftId);
        $this->assertNotEmpty($res->json('data.draft_text'));

        $this->assertDatabaseHas('message_drafts', ['id' => $draftId, 'status' => MessageDraft::STATUS_PENDING]);

        // Run audit ghi success + đếm PII redacted (SĐT trong inbound).
        $run = AiAssistantRun::query()->latest('id')->first();
        $this->assertSame(AiAssistantRun::STATUS_SUCCESS, $run->status);
        $this->assertGreaterThanOrEqual(1, (int) ($run->meta['redacted_count'] ?? 0));
    }

    public function test_accept_sends_message_as_ai(): void
    {
        Queue::fake();

        $draftId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/ai-suggestion")
            ->json('data.draft_id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/ai-suggestion/{$draftId}/accept")
            ->assertStatus(202)
            ->assertJsonPath('data.sent_by_ai', true)
            ->assertJsonPath('data.direction', Message::DIRECTION_OUTBOUND);

        $this->assertDatabaseHas('message_drafts', ['id' => $draftId, 'status' => MessageDraft::STATUS_ACCEPTED]);
        $msg = Message::query()->where('conversation_id', $this->conv->id)->where('direction', 'outbound')->first();
        $this->assertNotNull($msg->meta['ai_run_id'] ?? null);
    }

    public function test_reject_marks_draft_rejected(): void
    {
        $draftId = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/ai-suggestion")
            ->json('data.draft_id');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/conversations/{$this->conv->id}/ai-suggestion/{$draftId}")
            ->assertOk();

        $this->assertDatabaseHas('message_drafts', ['id' => $draftId, 'status' => MessageDraft::STATUS_REJECTED]);
    }

    public function test_non_business_plan_blocked_by_feature(): void
    {
        $this->activate(Plan::CODE_STARTER); // Starter không có feature messaging_ai (SPEC 0032)

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/ai-suggestion")
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_FEATURE_LOCKED');
    }

    public function test_monthly_limit_reached(): void
    {
        // Đặt hạn mức Business = 1 reply/tháng.
        $plan = Plan::query()->where('code', Plan::CODE_BUSINESS)->firstOrFail();
        $plan->update(['limits' => array_merge($plan->limits, ['messaging_ai_replies_monthly' => 1])]);

        // Lần 1 OK.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/ai-suggestion")
            ->assertOk();

        // Lần 2 vượt hạn mức.
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/ai-suggestion")
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'PLAN_LIMIT_REACHED');
    }
}
