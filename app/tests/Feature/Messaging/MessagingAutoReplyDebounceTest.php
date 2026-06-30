<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Events\MessageReceived;
use CMBcoreSeller\Modules\Messaging\Jobs\RespondWithAiAutoReply;
use CMBcoreSeller\Modules\Messaging\Listeners\AiAutoModeOnInbound;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessagingSetting;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * SPEC-0024 §4.6 — debounce AI auto-reply: gộp burst (3 text rời / text + ảnh
 * tách event) thành DUY NHẤT 1 reply (latest-wins). Sửa bug "AI gửi 2 tin lặp".
 */
class MessagingAutoReplyDebounceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        Queue::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'DebounceShop']);
        $this->tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_BUSINESS)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);
        MessagingSetting::withoutGlobalScopes()->updateOrCreate(['tenant_id' => $this->tenant->getKey()], [
            'ai_provider_code' => 'manual', 'ai_enabled' => true,
            'auto_mode_marketplace' => true, 'auto_mode_facebook' => true,
        ]);
        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'manual',
            'external_shop_id' => 's', 'shop_name' => 's', 'status' => 'active', 'messaging_enabled' => true,
        ]);
    }

    private function conversation(): Conversation
    {
        return Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $this->account->id, 'provider' => 'manual',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c'.uniqid(),
            'buyer_external_id' => 'b', 'buyer_name' => 'Khách', 'status' => Conversation::STATUS_OPEN,
            'message_count' => 5, 'last_inbound_at' => now(),   // >1 ⇒ không vướng first_message
        ]);
    }

    private function inbound(Conversation $conv, ?string $body, string $kind = Message::KIND_TEXT, int $attachments = 0): Message
    {
        return Message::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_INBOUND, 'kind' => $kind, 'body' => $body,
            'attachments_count' => $attachments,
        ]);
    }

    private function fireListener(Conversation $conv, Message $msg): void
    {
        app(AiAutoModeOnInbound::class)->handle(new MessageReceived($msg->id, $conv->id));
    }

    private function runJob(Conversation $conv, Message $trigger): void
    {
        app()->call([new RespondWithAiAutoReply($conv->id, $trigger->id), 'handle']);
    }

    private function outboundCount(Conversation $conv): int
    {
        return Message::withoutGlobalScopes()
            ->where('conversation_id', $conv->id)
            ->where('direction', Message::DIRECTION_OUTBOUND)->count();
    }

    public function test_listener_only_dispatches_debounce_job_not_inline_reply(): void
    {
        $conv = $this->conversation();
        $msg = $this->inbound($conv, 'Đơn của em bao giờ giao ạ?');

        $this->fireListener($conv, $msg);

        Queue::assertPushed(RespondWithAiAutoReply::class, 1);
        $this->assertSame(0, $this->outboundCount($conv));   // chưa trả lời ngay (debounce)
    }

    public function test_text_plus_image_burst_yields_single_reply(): void
    {
        $conv = $this->conversation();
        $text = $this->inbound($conv, 'Tư vấn cho tôi sản phẩm này');
        $image = $this->inbound($conv, null, Message::KIND_IMAGE, 1);   // ảnh tách event, body null

        // Mỗi inbound hẹn 1 job; chạy cả hai (thứ tự bất kỳ).
        $this->runJob($conv, $text);    // không phải tin mới nhất ⇒ bỏ qua
        $this->runJob($conv, $image);   // mới nhất ⇒ trả lời 1 lần

        $this->assertSame(1, $this->outboundCount($conv));
    }

    public function test_three_consecutive_texts_yield_single_reply(): void
    {
        $conv = $this->conversation();
        $m1 = $this->inbound($conv, 'Shop ơi');
        $m2 = $this->inbound($conv, 'Cho hỏi sản phẩm A');
        $m3 = $this->inbound($conv, 'Còn hàng không ạ?');

        $this->runJob($conv, $m1);
        $this->runJob($conv, $m2);
        $this->runJob($conv, $m3);

        $this->assertSame(1, $this->outboundCount($conv));
    }

    public function test_job_skips_when_already_replied_after_trigger(): void
    {
        $conv = $this->conversation();
        $msg = $this->inbound($conv, 'Đơn của em bao giờ giao ạ?');
        // outbound tạo SAU tin này (vd Tầng 1 rule / NV đã trả lời) ⇒ AI thôi.
        Message::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'conversation_id' => $conv->id,
            'direction' => Message::DIRECTION_OUTBOUND, 'kind' => Message::KIND_TEXT, 'body' => 'Đã trả lời',
        ]);

        $this->runJob($conv, $msg);

        $this->assertSame(1, $this->outboundCount($conv));   // vẫn 1 (cái có sẵn), AI KHÔNG thêm
    }
}
