<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Integrations\Messaging\MessagingRegistry;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * CRUD + publish/validate API kịch bản tự động (Flow Builder S3a).
 * RBAC dùng lại: đọc messaging.view, mutate messaging.rule.manage.
 */
class AutomationFlowApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        config([
            'integrations.messaging' => ['facebook_page'],
            'integrations.messaging_facebook_page.app_secret' => 'secret',
        ]);
        $this->app->forgetInstance(MessagingRegistry::class);

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'FlowShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $this->activateSubscription(Plan::CODE_PRO);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function activateSubscription(string $planCode): void
    {
        Subscription::withoutGlobalScopes()->where('tenant_id', $this->tenant->getKey())->delete();
        $plan = Plan::query()->where('code', $planCode)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    private function member(Role $role): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant->users()->attach($u->getKey(), ['role' => $role->value]);

        return $u;
    }

    /** @return array<string,mixed> */
    private function validGraph(): array
    {
        return [
            'nodes' => [
                ['id' => 't', 'type' => 'trigger', 'data' => []],
                ['id' => 's', 'type' => 'send_message', 'data' => ['text' => 'Xin chào']],
                ['id' => 'e', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 't', 'target' => 's', 'sourceHandle' => null],
                ['source' => 's', 'target' => 'e', 'sourceHandle' => null],
            ],
        ];
    }

    private function createFlow(array $overrides = []): int
    {
        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/automation-flows', array_merge([
                'name' => 'Chào khách',
                'trigger_type' => 'inbox_first_message',
                'graph' => $this->validGraph(),
            ], $overrides))
            ->assertStatus(201);

        return (int) $res->json('data.id');
    }

    public function test_owner_crud_flow(): void
    {
        $id = $this->createFlow();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson("/api/v1/messaging/automation-flows/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.provider', 'facebook_page');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->patchJson("/api/v1/messaging/automation-flows/{$id}", ['name' => 'Đổi tên'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Đổi tên');

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/automation-flows')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/automation-flows/{$id}")
            ->assertOk()
            ->assertJsonPath('data.ok', true);

        $this->assertSoftDeleted('automation_flows', ['id' => $id]);
    }

    public function test_staff_order_can_read_but_not_mutate(): void
    {
        $id = $this->createFlow();
        $so = $this->member(Role::StaffOrder);

        $this->actingAs($so)->withHeaders($this->h())
            ->getJson('/api/v1/messaging/automation-flows')->assertOk();

        $this->actingAs($so)->withHeaders($this->h())
            ->postJson('/api/v1/messaging/automation-flows', [
                'name' => 'X', 'trigger_type' => 'inbox_any',
            ])->assertStatus(403);

        $this->actingAs($so)->withHeaders($this->h())
            ->deleteJson("/api/v1/messaging/automation-flows/{$id}")->assertStatus(403);
    }

    public function test_publish_rejects_invalid_graph(): void
    {
        // Graph thiếu trigger ⇒ không hợp lệ.
        $id = $this->createFlow([
            'graph' => ['nodes' => [['id' => 'e', 'type' => 'end', 'data' => []]], 'edges' => []],
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/automation-flows/{$id}/publish")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'flow_invalid')
            ->assertJsonCount(1, 'error.details.errors');

        $this->assertSame('draft', AutomationFlow::query()->findOrFail($id)->status);
    }

    public function test_validate_endpoint_returns_errors_without_changing_status(): void
    {
        $id = $this->createFlow([
            'graph' => ['nodes' => [], 'edges' => []],
        ]);

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/automation-flows/{$id}/validate")
            ->assertOk()
            ->assertJsonPath('data.valid', false);

        $this->assertSame('draft', AutomationFlow::query()->findOrFail($id)->status);
    }

    public function test_publish_activates_valid_graph(): void
    {
        $id = $this->createFlow();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/automation-flows/{$id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_pause_sets_paused(): void
    {
        $id = $this->createFlow();
        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/automation-flows/{$id}/publish")->assertOk();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/automation-flows/{$id}/pause")
            ->assertOk()
            ->assertJsonPath('data.status', 'paused');
    }

    public function test_owner_uploads_flow_media(): void
    {
        Storage::fake((string) config('messaging.media_disk'));
        $id = $this->createFlow();

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->post("/api/v1/messaging/automation-flows/{$id}/media", [
                'kind' => 'image',
                'file' => UploadedFile::fake()->create('promo.jpg', 200, 'image/jpeg'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.kind', 'image')
            ->assertJsonPath('data.mime', 'image/jpeg');

        $this->assertNotEmpty($res->json('data.storage_path'));
        Storage::disk((string) config('messaging.media_disk'))->assertExists((string) $res->json('data.storage_path'));
    }

    public function test_flow_media_rejects_disallowed_mime(): void
    {
        Storage::fake((string) config('messaging.media_disk'));
        $id = $this->createFlow();

        $this->actingAs($this->owner)->withHeaders($this->h())
            ->post("/api/v1/messaging/automation-flows/{$id}/media", [
                'kind' => 'image',
                'file' => UploadedFile::fake()->create('x.exe', 10, 'application/x-msdownload'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'ATTACHMENT_INVALID');
    }

    public function test_duplicate_creates_draft_copy(): void
    {
        $id = $this->createFlow();

        $res = $this->actingAs($this->owner)->withHeaders($this->h())
            ->postJson("/api/v1/messaging/automation-flows/{$id}/duplicate")
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');

        $this->assertStringContainsString('(bản sao)', (string) $res->json('data.name'));
        $this->assertNotSame($id, (int) $res->json('data.id'));
    }
}
