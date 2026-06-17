<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Models\CustomerBadReport;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC 0038 — `customers/lookup` bù đắp dữ liệu khách bằng Pancake "bom hàng":
 * gọi + cache khi thiếu/cũ, dùng cache khi còn mới, không ghi đè khi lỗi tạm,
 * chạy CẢ khi khách chưa tồn tại, và tắt cấu hình ⇒ null + không gọi HTTP.
 */
class CustomerBadReportLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Shop A']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);

        config([
            'integrations.pancake.enabled' => true,
            'integrations.pancake.shop_id' => '1720000852',
            'integrations.pancake.access_token' => 'tok',
            'integrations.pancake.api_base_url' => 'https://pos.pancake.vn/api/v1',
            'integrations.pancake.cache_ttl_minutes' => 1440,
            'integrations.pancake.http' => ['timeout' => 5, 'retries' => 0],
        ]);
    }

    private function h(): array
    {
        return ['X-Tenant-Id' => (string) $this->tenant->getKey()];
    }

    private function sample(): array
    {
        return [
            'data' => [
                'reports_by_phone' => ['+84395151515' => ['order_fail' => 4, 'order_success' => 8, 'warning' => 3]],
                'warning_phone_number' => [
                    ['reason' => 'bom hàng con lợn này', 'inserted_at' => '2026-01-18T05:51:04', 'phone_number' => '+84395151515'],
                    ['reason' => 'boom', 'inserted_at' => '2025-11-04T11:22:47', 'phone_number' => '+84395151515'],
                ],
            ],
            'success' => true,
        ];
    }

    private function lookup(string $phone)
    {
        return $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/customers/lookup?phone='.$phone);
    }

    public function test_new_phone_fetches_pancake_and_caches(): void
    {
        Http::fake(['pos.pancake.vn/*' => Http::response($this->sample(), 200)]);

        $res = $this->lookup('0395151515')->assertOk();
        $res->assertJsonPath('data.customer', null)
            ->assertJsonPath('data.bad_report.order_fail', 4)
            ->assertJsonPath('data.bad_report.order_success', 8)
            ->assertJsonPath('data.bad_report.warning_count', 3)
            ->assertJsonPath('data.bad_report.has_data', true)
            ->assertJsonCount(2, 'data.bad_report.warnings')
            ->assertJsonPath('data.bad_report.warnings.0.reason', 'bom hàng con lợn này')
            ->assertJsonPath('data.bad_report.warnings.0.reported_at', '2026-01-18T05:51:04');

        $this->assertDatabaseHas('customer_bad_reports', [
            'tenant_id' => $this->tenant->getKey(),
            'phone_hash' => CustomerPhoneNormalizer::hash('0395151515'),
            'order_fail' => 4, 'order_success' => 8, 'warning_count' => 3, 'has_data' => true,
        ]);
    }

    public function test_fresh_cache_is_used_without_calling_http(): void
    {
        $this->seedRow('0395151515', orderFail: 7, syncedAt: now());
        Http::fake();

        $this->lookup('0395151515')->assertOk()->assertJsonPath('data.bad_report.order_fail', 7);

        Http::assertNothingSent();
    }

    public function test_transient_error_keeps_old_cache(): void
    {
        $this->seedRow('0395151515', orderFail: 2, syncedAt: now()->subDays(5)); // stale
        Http::fake(['pos.pancake.vn/*' => Http::response('boom', 500)]);

        $this->lookup('0395151515')->assertOk()->assertJsonPath('data.bad_report.order_fail', 2);

        // không ghi đè bằng rỗng
        $this->assertDatabaseHas('customer_bad_reports', [
            'phone_hash' => CustomerPhoneNormalizer::hash('0395151515'), 'order_fail' => 2,
        ]);
    }

    public function test_disabled_returns_null_bad_report_and_no_http(): void
    {
        config(['integrations.pancake.enabled' => false]);
        Http::fake();

        $this->lookup('0395151515')->assertOk()->assertJsonPath('data.bad_report', null);

        Http::assertNothingSent();
    }

    private function seedRow(string $phone, int $orderFail, Carbon $syncedAt): void
    {
        CustomerBadReport::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'phone_hash' => CustomerPhoneNormalizer::hash($phone),
            'order_fail' => $orderFail, 'order_success' => 0, 'warning_count' => 0,
            'warnings' => [], 'has_data' => true, 'synced_at' => $syncedAt,
        ]);
    }
}
