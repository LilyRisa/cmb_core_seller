<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Messaging\Models\AiKnowledgeDocument;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Upload tài liệu AI training (SPEC-0024 §6.1). Queue sync ⇒ IndexKnowledgeDoc chạy
 * ngay trong request: file → trích text → chunk → status ready.
 */
class KnowledgeUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'KbShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);

        Storage::fake(app(MediaStorage::class)->diskName());
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function member(Role $role): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => $role->value]);

        return $u;
    }

    /** Tạo 1 tài liệu inline qua API (queue sync ⇒ index xong ngay). */
    private function createInline(string $text = 'Chính sách đổi trả trong 7 ngày.'): int
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/knowledge-docs', [
                'title' => 'Doc', 'source' => 'inline', 'inline_text' => $text,
            ])->assertStatus(201);

        return (int) $res->json('data.id');
    }

    public function test_uploads_csv_and_indexes_to_ready(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'faq.csv',
            "câu hỏi,trả lời\nGiờ mở cửa?,8h-22h\nĐổi trả?,Trong 7 ngày",
        );

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->post('/api/v1/messaging/knowledge-docs', [
                'title' => 'FAQ shop',
                'source' => 'upload',
                'file' => $file,
            ])
            ->assertStatus(201);

        $doc = AiKnowledgeDocument::query()->where('title', 'FAQ shop')->firstOrFail();

        // Queue sync ⇒ đã index xong: ready + có ít nhất 1 chunk chứa nội dung CSV.
        $this->assertSame(AiKnowledgeDocument::STATUS_READY, $doc->status);
        $this->assertGreaterThan(0, $doc->chunk_count);
        $this->assertStringContainsString('Giờ mở cửa?', (string) $doc->chunks()->first()?->chunk_text);
    }

    public function test_view_chunks_returns_extracted_text(): void
    {
        $id = $this->createInline('Giờ mở cửa 8h-22h. Đổi trả trong 7 ngày.');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson("/api/v1/messaging/knowledge-docs/{$id}/chunks")
            ->assertOk()
            ->assertJsonPath('data.status', 'ready')
            ->assertJsonPath('data.chunks.0.index', 0);

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson("/api/v1/messaging/knowledge-docs/{$id}/chunks")->json('data.chunks');
        $this->assertStringContainsString('Đổi trả trong 7 ngày', collect($res)->pluck('text')->implode(' '));
    }

    public function test_reindex_reprocesses_document(): void
    {
        $id = $this->createInline();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/knowledge-docs/{$id}/reindex")
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        // Queue sync ⇒ index lại xong: ready + có chunk.
        $doc = AiKnowledgeDocument::query()->findOrFail($id);
        $this->assertSame(AiKnowledgeDocument::STATUS_READY, $doc->status);
        $this->assertGreaterThan(0, $doc->chunk_count);
    }

    public function test_staff_can_view_chunks_but_not_reindex(): void
    {
        $id = $this->createInline();
        $staff = $this->member(Role::StaffOrder); // messaging.view, KHÔNG messaging.ai.train

        $this->actingAs($staff)->withHeaders($this->h())
            ->getJson("/api/v1/messaging/knowledge-docs/{$id}/chunks")->assertOk();

        $this->actingAs($staff)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/knowledge-docs/{$id}/reindex")->assertStatus(403);
    }

    public function test_rejects_unsupported_extension(): void
    {
        $file = UploadedFile::fake()->createWithContent('malware.exe', 'MZ binary');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->post('/api/v1/messaging/knowledge-docs', [
                'title' => 'bad',
                'source' => 'upload',
                'file' => $file,
            ])
            ->assertStatus(422);

        $this->assertSame(0, AiKnowledgeDocument::query()->count());
    }
}
