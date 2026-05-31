<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Jobs\SendMessage;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test media upload outbound (SPEC-0024 §6.1 / §7).
 *
 * Manual connector khai báo capability outbound.image/video/file (S1 stub) ⇒
 * upload đi qua được. Disk fake ⇒ kiểm file đã lưu + 422 khi sai MIME/size.
 */
class MessagingMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Conversation $conv;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('messaging.media_disk'));
        $this->seed(BillingPlanSeeder::class);

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'MediaShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_media_1',
            'shop_name' => 'Media Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);

        $this->conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'conv_media_1',
            'buyer_external_id' => 'buyer_1',
            'buyer_name' => 'Khách',
            'status' => Conversation::STATUS_OPEN,
            'last_inbound_at' => now()->subMinutes(3),
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_upload_image_stores_file_and_creates_attachment(): void
    {
        Queue::fake();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/messages/media", [
                'kind' => 'image',
                'file' => UploadedFile::fake()->image('photo.jpg', 320, 240),
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.kind', 'image')
            ->assertJsonPath('data.delivery_status', Message::STATUS_PENDING);

        $attachment = MessageAttachment::query()->first();
        $this->assertNotNull($attachment);
        $this->assertSame(MessageAttachment::STATUS_DOWNLOADED, $attachment->status);
        Storage::disk(config('messaging.media_disk'))->assertExists($attachment->storage_path);

        Queue::assertPushed(SendMessage::class);
    }

    public function test_send_media_job_resolves_attachment_without_tenant_context(): void
    {
        // Regression: job SendMessage chạy KHÔNG có CurrentTenant ⇒ quan hệ attachments()
        // bị TenantScope ràng tenant_id=0 ⇒ trước đây ném "Media message thiếu attachment.".
        // Phải withoutGlobalScope để tìm thấy attachment (đã tạo cùng message, tenant_id thật).
        $msg = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $this->conv->id,
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => Message::KIND_IMAGE,
            'attachments_count' => 1,
            'sent_by_user_id' => $this->owner->id,
            'delivery_status' => Message::STATUS_PENDING,
        ]);
        MessageAttachment::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'message_id' => $msg->id,
            'kind' => 'image',
            'mime' => 'image/jpeg',
            'size_bytes' => 1234,
            'external_url' => 'https://cdn.example/x.jpg', // có sẵn URL ⇒ không phụ thuộc storage
            'status' => MessageAttachment::STATUS_DOWNLOADED,
        ]);

        // Chạy đồng bộ trong ngữ cảnh KHÔNG có tenant (giống worker) — không được ném "thiếu attachment".
        SendMessage::dispatchSync($msg->id);

        $fresh = $msg->fresh();
        $this->assertNotSame(Message::STATUS_FAILED, $fresh->delivery_status, 'không được fail vì không thấy attachment');
        $this->assertNull($fresh->failure_code);
    }

    public function test_resend_failed_message_requeues_and_resets_status(): void
    {
        Queue::fake();
        $msg = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $this->conv->id,
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'hi',
            'delivery_status' => Message::STATUS_FAILED,
            'failure_code' => 'send_failed',
            'sent_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/messages/{$msg->id}/resend")
            ->assertStatus(202);

        $fresh = $msg->fresh();
        $this->assertSame(Message::STATUS_PENDING, $fresh->delivery_status);
        $this->assertNull($fresh->failure_code);
        Queue::assertPushed(SendMessage::class);
    }

    public function test_resend_rejects_message_not_failed(): void
    {
        $msg = Message::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'conversation_id' => $this->conv->id,
            'direction' => Message::DIRECTION_OUTBOUND,
            'kind' => Message::KIND_TEXT,
            'body' => 'đã gửi',
            'delivery_status' => Message::STATUS_SENT,
            'sent_by_user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/messages/{$msg->id}/resend")
            ->assertStatus(422);
    }

    public function test_reject_disallowed_mime(): void
    {
        Queue::fake();

        // file kind nhưng MIME .exe-like (text fake mime application/x-msdownload không trong whitelist)
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/messages/media", [
                'kind' => 'image',
                // PDF gửi như image ⇒ MIME application/pdf không thuộc allowed_mime.image
                'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ATTACHMENT_INVALID');

        $this->assertSame(0, MessageAttachment::query()->count());
    }

    public function test_reject_oversize_file(): void
    {
        Queue::fake();
        config()->set('messaging.limits.image', 1024); // 1KB limit để test nhanh

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/conversations/{$this->conv->id}/messages/media", [
                'kind' => 'image',
                'file' => UploadedFile::fake()->image('big.jpg', 2000, 2000)->size(50), // 50KB > 1KB
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ATTACHMENT_INVALID');
    }
}
