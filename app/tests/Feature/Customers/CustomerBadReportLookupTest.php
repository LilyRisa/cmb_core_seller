<?php

namespace Tests\Feature\Customers;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerBadReport;
use CMBcoreSeller\Modules\Customers\Models\CustomerReport;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SPEC 0038 v2 — `customers/lookup` bad_report: ưu tiên nội bộ, thiếu mới Pancake;
 * Pancake gọi 1 lần (có cache thì thôi); payload = tỷ lệ + danh sách cảnh báo.
 */
class CustomerBadReportLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private const PHONE = '0395151515';

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
                    ['reason' => 'bom hàng', 'inserted_at' => '2026-01-18T05:51:04', 'phone_number' => '+84395151515'],
                ],
            ],
            'success' => true,
        ];
    }

    private function lookup(string $phone = self::PHONE)
    {
        return $this->actingAs($this->owner)->withHeaders($this->h())->getJson('/api/v1/customers/lookup?phone='.$phone);
    }

    private function makeCustomer(array $stats = [], bool $blocked = false): Customer
    {
        return Customer::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(),
            'phone_hash' => CustomerPhoneNormalizer::hash(self::PHONE),
            'phone' => self::PHONE, 'name' => 'Khách',
            'lifetime_stats' => $stats + ['orders_total' => 0, 'orders_completed' => 0, 'orders_cancelled' => 0, 'orders_returned' => 0, 'orders_delivery_failed' => 0],
            'reputation_score' => 100, 'reputation_label' => 'ok', 'tags' => [],
            'is_blocked' => $blocked, 'block_reason' => $blocked ? 'Bom nhiều' : null, 'blocked_at' => $blocked ? now() : null,
            'first_seen_at' => now()->subDays(10), 'last_seen_at' => now()->subDay(),
        ]);
    }

    // --- Pancake fallback (no internal data) ---------------------------------

    public function test_new_phone_falls_back_to_pancake_and_caches_once(): void
    {
        Http::fake(['pos.pancake.vn/*' => Http::response($this->sample(), 200)]);

        $this->lookup()->assertOk()
            ->assertJsonPath('data.customer', null)
            ->assertJsonPath('data.bad_report.success_count', 8)
            ->assertJsonPath('data.bad_report.fail_count', 4)
            ->assertJsonPath('data.bad_report.has_warning', true)
            ->assertJsonPath('data.bad_report.warnings.0.reason', 'bom hàng')
            ->assertJsonPath('data.bad_report.warnings.0.source', 'pancake');

        $this->assertDatabaseHas('customer_bad_reports', [
            'phone_hash' => CustomerPhoneNormalizer::hash(self::PHONE), 'order_fail' => 4,
        ]);
    }

    public function test_existing_pancake_cache_is_used_without_http(): void
    {
        CustomerBadReport::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'phone_hash' => CustomerPhoneNormalizer::hash(self::PHONE),
            'order_fail' => 5, 'order_success' => 1, 'warning_count' => 0, 'warnings' => [], 'has_data' => true, 'synced_at' => now()->subYear(),
        ]);
        Http::fake();

        $this->lookup()->assertOk()->assertJsonPath('data.bad_report.fail_count', 5);
        Http::assertNothingSent();
    }

    public function test_pancake_error_returns_null_and_does_not_cache(): void
    {
        Http::fake(['pos.pancake.vn/*' => Http::response('boom', 500)]);

        $this->lookup()->assertOk()->assertJsonPath('data.bad_report', null);
        $this->assertDatabaseMissing('customer_bad_reports', ['phone_hash' => CustomerPhoneNormalizer::hash(self::PHONE)]);
    }

    public function test_disabled_returns_null_and_no_http(): void
    {
        config(['integrations.pancake.enabled' => false]);
        Http::fake();

        $this->lookup()->assertOk()->assertJsonPath('data.bad_report', null);
        Http::assertNothingSent();
    }

    // --- Internal-first (customer / report present ⇒ skip Pancake) -----------

    public function test_existing_customer_uses_internal_ratio_and_skips_pancake(): void
    {
        $this->makeCustomer(['orders_completed' => 8, 'orders_returned' => 2, 'orders_delivery_failed' => 1]);
        Http::fake();

        $this->lookup()->assertOk()
            ->assertJsonPath('data.bad_report.success_count', 8)
            ->assertJsonPath('data.bad_report.fail_count', 3);   // returned 2 + delivery_failed 1

        Http::assertNothingSent();
    }

    /** Ví dụ chủ dự án: Pancake 3 thành công / 1 hoàn + 1 đơn nội bộ hoàn ⇒ 3 / 2. */
    public function test_pancake_baseline_and_internal_orders_accumulate(): void
    {
        // baseline Pancake đã nạp trước đó (3 thành công, 1 hoàn).
        CustomerBadReport::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'phone_hash' => CustomerPhoneNormalizer::hash(self::PHONE),
            'order_fail' => 1, 'order_success' => 3, 'warning_count' => 0, 'warnings' => [], 'has_data' => true, 'synced_at' => now()->subMonth(),
        ]);
        // khách phát sinh trong app + 1 đơn hoàn nội bộ.
        $this->makeCustomer(['orders_completed' => 0, 'orders_returned' => 1]);
        Http::fake();

        $this->lookup()->assertOk()
            ->assertJsonPath('data.bad_report.success_count', 3)   // 3 (Pancake) + 0 nội bộ
            ->assertJsonPath('data.bad_report.fail_count', 2);     // 1 (Pancake) + 1 nội bộ

        Http::assertNothingSent();   // đã có baseline ⇒ không gọi lại Pancake
    }

    public function test_internal_warnings_include_reports_and_blocked(): void
    {
        $this->makeCustomer(['orders_completed' => 1], blocked: true);
        CustomerReport::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenant->getKey(), 'phone_hash' => CustomerPhoneNormalizer::hash(self::PHONE),
            'order_id' => 999, 'order_number' => 'M-1', 'reason' => 'Bom đơn thủ công', 'reported_at' => now(),
        ]);
        Http::fake();

        $res = $this->lookup()->assertOk()->assertJsonPath('data.bad_report.has_warning', true);
        $sources = collect($res->json('data.bad_report.warnings'))->pluck('source')->all();
        $this->assertContains('blocked', $sources);
        $this->assertContains('internal', $sources);
        Http::assertNothingSent();
    }
}
