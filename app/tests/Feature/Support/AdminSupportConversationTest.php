<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Support\Models\SupportMessage;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Admin xem & nhắn nhiều tin + đóng hội thoại CSKH XUYÊN tenant (SPEC-0028). */
class AdminSupportConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake((string) config('support.attachments.media_disk'));
    }

    protected function actingAdmin(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');
    }

    private function makeConv(Tenant $t, string $lastSender = 'user', string $status = 'open', int $unread = 0): SupportConversation
    {
        $c = new SupportConversation([
            'tenant_id' => $t->getKey(), 'status' => $status, 'last_sender' => $lastSender,
            'user_unread_count' => $unread, 'last_message_at' => now(),
        ]);
        $c->save();
        $m = new SupportMessage([
            'tenant_id' => $t->getKey(), 'support_conversation_id' => $c->getKey(),
            'sender' => $lastSender, 'type' => 'text', 'body' => 'Câu hỏi của '.$t->name,
        ]);
        $m->save();

        return $c;
    }

    public function test_admin_lists_conversations_across_all_tenants(): void
    {
        $this->makeConv(Tenant::create(['name' => 'Shop A']));
        $this->makeConv(Tenant::create(['name' => 'Shop B']));
        $this->actingAdmin();

        $this->getJson('/api/v1/admin/support-conversations')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonPath('data.0.tenant.name', fn ($n) => is_string($n));
    }

    public function test_admin_filters_awaiting_reply(): void
    {
        $this->makeConv(Tenant::create(['name' => 'Chờ CSKH']), 'user', 'open');
        $this->makeConv(Tenant::create(['name' => 'Đã rep']), 'cskh', 'open');
        $this->actingAdmin();

        $this->getJson('/api/v1/admin/support-conversations?awaiting=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.awaiting', true);
    }

    public function test_admin_shows_thread(): void
    {
        $conv = $this->makeConv(Tenant::create(['name' => 'Shop']));
        $this->actingAdmin();

        $this->getJson("/api/v1/admin/support-conversations/{$conv->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $conv->id)
            ->assertJsonCount(1, 'data.messages');
    }

    public function test_admin_message_appends_and_bumps_user_unread(): void
    {
        $conv = $this->makeConv(Tenant::create(['name' => 'Shop']), 'user', 'open', 0);
        $this->actingAdmin();

        $this->postJson("/api/v1/admin/support-conversations/{$conv->id}/messages", ['body' => 'Chào bạn, mình hỗ trợ nhé.'])
            ->assertOk()
            ->assertJsonPath('data.last_sender', 'cskh');

        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conv->id, 'sender' => 'cskh', 'body' => 'Chào bạn, mình hỗ trợ nhé.',
        ]);
        $this->assertDatabaseHas('support_conversations', ['id' => $conv->id, 'user_unread_count' => 1]);
    }

    public function test_admin_can_send_multiple_messages(): void
    {
        $conv = $this->makeConv(Tenant::create(['name' => 'Shop']), 'user', 'open', 0);
        $this->actingAdmin();

        $this->postJson("/api/v1/admin/support-conversations/{$conv->id}/messages", ['body' => 'tin 1'])->assertOk();
        $this->postJson("/api/v1/admin/support-conversations/{$conv->id}/messages", ['body' => 'tin 2'])->assertOk();

        $this->assertDatabaseHas('support_conversations', ['id' => $conv->id, 'user_unread_count' => 2]);
        // 1 (user gốc) + 2 (cskh) = 3 tin
        $this->assertSame(3, SupportMessage::query()->withoutGlobalScope(TenantScope::class)
            ->where('support_conversation_id', $conv->id)->count());
    }

    public function test_admin_message_with_attachment(): void
    {
        $conv = $this->makeConv(Tenant::create(['name' => 'Shop']), 'user', 'open', 0);
        $this->actingAdmin();

        $this->post("/api/v1/admin/support-conversations/{$conv->id}/messages", [
            'body' => 'Bạn xem ảnh hướng dẫn nhé',
            'files' => [UploadedFile::fake()->image('huongdan.png')],
        ])->assertOk();

        $this->assertDatabaseHas('support_message_attachments', [
            'tenant_id' => $conv->tenant_id, 'kind' => 'image', 'status' => 'stored',
        ]);
    }

    public function test_admin_cannot_message_closed_conversation(): void
    {
        $conv = $this->makeConv(Tenant::create(['name' => 'Shop']), 'cskh', 'closed');
        $this->actingAdmin();

        $this->postJson("/api/v1/admin/support-conversations/{$conv->id}/messages", ['body' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'CONVERSATION_CLOSED');
    }

    public function test_admin_close_inserts_system_message_and_notifies_user(): void
    {
        $conv = $this->makeConv(Tenant::create(['name' => 'Shop']), 'cskh', 'open', 0);
        $this->actingAdmin();

        $this->postJson("/api/v1/admin/support-conversations/{$conv->id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conv->id, 'sender' => 'cskh', 'type' => 'system',
        ]);
        $this->assertDatabaseHas('support_conversations', ['id' => $conv->id, 'status' => 'closed', 'user_unread_count' => 1]);
    }

    public function test_message_requires_admin_guard(): void
    {
        $conv = $this->makeConv(Tenant::create(['name' => 'Shop']));

        $this->postJson("/api/v1/admin/support-conversations/{$conv->id}/messages", ['body' => 'x'])
            ->assertStatus(401);
    }
}
