<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Support\Models\SupportConversation;
use CMBcoreSeller\Modules\Support\Models\SupportMessageAttachment;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Tab "Hỏi CSKH" — hội thoại nhiều tin + đính kèm + đóng/mở lại (SPEC-0028). */
class SupportConversationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake((string) config('support.attachments.media_disk'));
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'HelpShop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
    }

    private function actor(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => 'owner']);

        return $u;
    }

    /** @return array<string,string> */
    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    public function test_first_message_opens_conversation(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->post('/api/v1/support/messages', ['body' => 'Làm sao kết nối gian hàng?'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.messages.0.sender', 'user')
            ->assertJsonPath('data.messages.0.body', 'Làm sao kết nối gian hàng?');

        $this->assertDatabaseCount('support_conversations', 1);
        $this->assertDatabaseHas('support_messages', [
            'tenant_id' => $this->tenant->getKey(), 'sender' => 'user', 'type' => 'text',
        ]);
    }

    public function test_second_message_while_open_appends_same_conversation(): void
    {
        $u = $this->actor();
        $this->actingAs($u)->withHeaders($this->h())->post('/api/v1/support/messages', ['body' => 'tin 1'])->assertCreated();
        $this->actingAs($u)->withHeaders($this->h())->post('/api/v1/support/messages', ['body' => 'tin 2'])->assertCreated();

        $this->assertDatabaseCount('support_conversations', 1);
        $this->assertDatabaseCount('support_messages', 2);
    }

    public function test_message_after_closed_opens_new_conversation(): void
    {
        $u = $this->actor();
        $this->actingAs($u)->withHeaders($this->h())->post('/api/v1/support/messages', ['body' => 'tin cuộc 1'])->assertCreated();

        // Đóng cuộc hiện tại (mô phỏng CSKH đóng).
        $conv = SupportConversation::query()->firstOrFail();
        $conv->forceFill(['status' => 'closed', 'closed_at' => now()])->save();

        $this->actingAs($u)->withHeaders($this->h())->post('/api/v1/support/messages', ['body' => 'tin cuộc MỚI'])->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseCount('support_conversations', 2);
    }

    public function test_image_attachment_is_stored_and_returns_download_url(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->post('/api/v1/support/messages', [
                'body' => 'ảnh mô tả lỗi',
                'files' => [UploadedFile::fake()->image('loi.jpg')],
            ])
            ->assertCreated()
            ->assertJsonPath('data.messages.0.attachments.0.kind', 'image')
            ->assertJsonPath('data.messages.0.attachments.0.download_url', fn ($u) => is_string($u) && $u !== '');

        $this->assertDatabaseHas('support_message_attachments', [
            'tenant_id' => $this->tenant->getKey(), 'kind' => 'image', 'status' => 'stored',
        ]);
        $att = SupportMessageAttachment::query()
            ->withoutGlobalScope(TenantScope::class)->firstOrFail();
        Storage::disk((string) config('support.attachments.media_disk'))->assertExists($att->storage_path);
    }

    public function test_oversized_attachment_returns_422_and_persists_nothing(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->post('/api/v1/support/messages', [
                'body' => 'ảnh quá lớn',
                'files' => [UploadedFile::fake()->image('big.jpg')->size(30 * 1024)], // 30MB > 25MB
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ATTACHMENT_INVALID');

        $this->assertDatabaseCount('support_messages', 0);
        $this->assertDatabaseCount('support_conversations', 0);
    }

    public function test_disallowed_mime_returns_422(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->post('/api/v1/support/messages', [
                'files' => [UploadedFile::fake()->create('virus.bin', 4)], // mime không nằm whitelist
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ATTACHMENT_INVALID');
    }

    public function test_too_many_files_returns_422(): void
    {
        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files[] = UploadedFile::fake()->image("a{$i}.jpg");
        }

        $this->actingAs($this->actor())->withHeaders($this->h())
            ->post('/api/v1/support/messages', ['files' => $files])
            ->assertStatus(422);
    }

    public function test_empty_body_without_files_returns_422(): void
    {
        $this->actingAs($this->actor())->withHeaders($this->h())
            ->post('/api/v1/support/messages', ['body' => ''])
            ->assertStatus(422);
    }

    public function test_unread_count_and_read_endpoint(): void
    {
        $u = $this->actor();
        $this->actingAs($u)->withHeaders($this->h())->post('/api/v1/support/messages', ['body' => 'hi'])->assertCreated();
        $conv = SupportConversation::query()->firstOrFail();
        $conv->forceFill(['user_unread_count' => 3])->save();

        $this->actingAs($u)->withHeaders($this->h())->getJson('/api/v1/support/unread')
            ->assertOk()->assertJsonPath('data.unread', 3);

        $this->actingAs($u)->withHeaders($this->h())->postJson("/api/v1/support/conversations/{$conv->id}/read")
            ->assertOk()->assertJsonPath('data.unread', 0);

        $this->assertDatabaseHas('support_conversations', ['id' => $conv->id, 'user_unread_count' => 0]);
    }

    public function test_index_and_unread_are_tenant_scoped(): void
    {
        $other = Tenant::create(['name' => 'Other']);
        $oc = new SupportConversation(['tenant_id' => $other->getKey(), 'status' => 'open', 'user_unread_count' => 5]);
        $oc->save();

        $this->actingAs($this->actor())->withHeaders($this->h())->post('/api/v1/support/messages', ['body' => 'của tôi'])->assertCreated();

        $this->actingAs($this->actor())->withHeaders($this->h())->getJson('/api/v1/support/conversations')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($this->actor())->withHeaders($this->h())->getJson('/api/v1/support/unread')
            ->assertOk()->assertJsonPath('data.unread', 0); // unread của tenant khác KHÔNG lọt
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v1/support/messages', ['body' => 'x'])->assertStatus(401);
    }
}
