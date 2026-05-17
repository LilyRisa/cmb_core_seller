<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

/**
 * Spec 2026-05-17 — backfill migration tạo `admin_users` từ `users.is_super_admin=true`
 * rồi drop cột `is_super_admin`. Sau khi toàn bộ migrations chạy xong (RefreshDatabase
 * trait), cột `is_super_admin` không tồn tại. Để test logic backfill, ta tái dựng
 * trạng thái legacy: thêm lại cột tạm, seed user super-admin, gọi handler backfill.
 */
class BackfillSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_migrate_schema_state_is_correct(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'is_super_admin'));
        $this->assertTrue(Schema::hasTable('admin_users'));
    }

    public function test_backfill_creates_admin_user_from_legacy_super_admin(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_super_admin')->default(false);
            $t->index('is_super_admin');
        });

        $userId = DB::table('users')->insertGetId([
            'name' => 'Op Smith',
            'email' => 'opsmith@cmbcore.vn',
            'password' => bcrypt('legacy'),
            'is_super_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runBackfillUp();

        $admin = AdminUser::query()->where('email', 'opsmith@cmbcore.vn')->first();
        $this->assertNotNull($admin);
        $this->assertSame('opsmith', $admin->username);
        $this->assertTrue($admin->is_active);
        $this->assertSame('Op Smith', $admin->name);
    }

    public function test_backfill_handles_username_collision_with_suffix(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_super_admin')->default(false);
            $t->index('is_super_admin');
        });

        // Pre-existing admin reserves "ops".
        AdminUser::factory()->create(['username' => 'ops', 'email' => 'pre@cmbcore.vn']);

        DB::table('users')->insert([
            'name' => 'Ops Two',
            'email' => 'ops@cmbcore.vn',
            'password' => bcrypt('legacy'),
            'is_super_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runBackfillUp();

        $this->assertTrue(AdminUser::query()->where('username', 'ops_1')->exists());
    }

    public function test_backfill_falls_back_to_admin_id_when_email_local_part_empty(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_super_admin')->default(false);
            $t->index('is_super_admin');
        });

        $userId = DB::table('users')->insertGetId([
            'name' => 'NoEmail',
            'email' => '@x.vn',
            'password' => bcrypt('legacy'),
            'is_super_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runBackfillUp();

        $this->assertTrue(AdminUser::query()->where('username', "admin_{$userId}")->exists());
    }

    private function runBackfillUp(): void
    {
        $path = database_path('../app/Modules/Admin/Database/Migrations/2026_05_26_200000_backfill_admin_users_and_drop_is_super_admin.php');
        $migration = require $path;
        $migration->up();
    }
}
