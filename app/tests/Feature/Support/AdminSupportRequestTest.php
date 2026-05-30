<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Support\Models\SupportRequest;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin xem & trả lời yêu cầu CSKH XUYÊN tenant. */
class AdminSupportRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAdmin(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');
    }

    private function seedRequests(): array
    {
        $t1 = Tenant::create(['name' => 'Shop A']);
        $t2 = Tenant::create(['name' => 'Shop B']);
        $r1 = SupportRequest::query()->create(['tenant_id' => $t1->getKey(), 'question' => 'Câu hỏi A', 'status' => 'pending']);
        $r2 = SupportRequest::query()->create(['tenant_id' => $t2->getKey(), 'question' => 'Câu hỏi B', 'status' => 'pending']);

        return [$r1, $r2];
    }

    public function test_admin_lists_requests_across_all_tenants(): void
    {
        $this->seedRequests();
        $this->actingAdmin();

        $this->getJson('/api/v1/admin/support-requests')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonPath('data.0.tenant.name', fn ($n) => is_string($n)); // có nhãn tenant
    }

    public function test_admin_filters_by_status(): void
    {
        [$r1] = $this->seedRequests();
        $r1->forceFill(['status' => 'answered'])->save();
        $this->actingAdmin();

        $this->getJson('/api/v1/admin/support-requests?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.question', 'Câu hỏi B');
    }

    public function test_admin_answers_request(): void
    {
        [$r1] = $this->seedRequests();
        $this->actingAdmin();

        $this->postJson("/api/v1/admin/support-requests/{$r1->id}/answer", ['answer' => 'Bạn vào menu Gian hàng nhé.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'answered');

        $fresh = SupportRequest::query()->withoutGlobalScope(TenantScope::class)->find($r1->id);
        $this->assertSame('answered', $fresh->status);
        $this->assertSame('Bạn vào menu Gian hàng nhé.', $fresh->answer);
        $this->assertNotNull($fresh->answered_at);
    }

    public function test_admin_closes_request(): void
    {
        [$r1] = $this->seedRequests();
        $this->actingAdmin();

        $this->postJson("/api/v1/admin/support-requests/{$r1->id}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_answer_requires_admin_guard(): void
    {
        [$r1] = $this->seedRequests();

        $this->postJson("/api/v1/admin/support-requests/{$r1->id}/answer", ['answer' => 'x'])
            ->assertStatus(401);
    }
}
