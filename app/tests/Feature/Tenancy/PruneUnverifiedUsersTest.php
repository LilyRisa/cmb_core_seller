<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Command users:prune-unverified (SPEC 2026-06-10) — chỉ xóa tài khoản chưa xác minh
 * quá hạn & RỖNG; giữ user đã verify / còn hạn / có dữ liệu / sub-account.
 */
class PruneUnverifiedUsersTest extends TestCase
{
    use RefreshDatabase;

    private function account(array $userOver = [], int $ageDays = 2): array
    {
        $u = User::factory()->create(array_merge(['email_verified_at' => null], $userOver));
        $u->forceFill(['created_at' => now()->subDays($ageDays)])->save();
        $t = Tenant::create(['name' => 'T'.$u->id]);
        $t->users()->attach($u->id, ['role' => Role::Owner->value]);

        return [$u, $t];
    }

    public function test_prunes_old_unverified_empty_account(): void
    {
        [$u, $t] = $this->account();

        $this->artisan('users:prune-unverified', ['--days' => 1])->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $u->id]);
        $this->assertDatabaseMissing('tenants', ['id' => $t->id]);
    }

    public function test_keeps_verified_user(): void
    {
        [$u] = $this->account(['email_verified_at' => now()]);
        $this->artisan('users:prune-unverified', ['--days' => 1])->assertExitCode(0);
        $this->assertDatabaseHas('users', ['id' => $u->id]);
    }

    public function test_keeps_recent_unverified_user(): void
    {
        [$u] = $this->account([], 0); // tạo hôm nay
        $this->artisan('users:prune-unverified', ['--days' => 1])->assertExitCode(0);
        $this->assertDatabaseHas('users', ['id' => $u->id]);
    }

    public function test_keeps_unverified_with_business_data(): void
    {
        [$u, $t] = $this->account();
        ChannelAccount::withoutGlobalScopes()->create([
            'tenant_id' => $t->id, 'provider' => 'tiktok', 'external_shop_id' => 'shop1',
            'status' => 'active', 'name' => 'Shop',
        ]);

        $this->artisan('users:prune-unverified', ['--days' => 1])->assertExitCode(0);
        $this->assertDatabaseHas('users', ['id' => $u->id]);
        $this->assertDatabaseHas('tenants', ['id' => $t->id]);
    }

    public function test_dry_run_does_not_delete(): void
    {
        [$u] = $this->account();
        $this->artisan('users:prune-unverified', ['--days' => 1, '--dry-run' => true])->assertExitCode(0);
        $this->assertDatabaseHas('users', ['id' => $u->id]);
    }
}
