<?php

namespace Tests\Feature\Console;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_makes_active_admin(): void
    {
        $this->artisan('admin:create', [
            'username' => 'opx',
            '--name' => 'Op X',
            '--email' => 'op@x.vn',
            '--password' => 'pw123456',
        ])->assertExitCode(0);

        $a = AdminUser::query()->where('username', 'opx')->first();
        $this->assertNotNull($a);
        $this->assertTrue(Hash::check('pw123456', $a->password));
        $this->assertTrue($a->is_active);
        $this->assertSame('Op X', $a->name);
        $this->assertSame('op@x.vn', $a->email);
    }

    public function test_admin_create_duplicate_username_fails(): void
    {
        AdminUser::factory()->create(['username' => 'op1']);
        $this->artisan('admin:create', [
            'username' => 'op1',
            '--name' => 'X',
            '--password' => 'pw123456',
        ])->assertExitCode(1);
    }

    public function test_admin_create_validates_username_format(): void
    {
        $this->artisan('admin:create', [
            'username' => 'BAD UPPER',
            '--name' => 'X',
            '--password' => 'pw123456',
        ])->assertExitCode(1);
        $this->assertFalse(AdminUser::query()->where('username', 'BAD UPPER')->exists());
    }

    public function test_admin_reset_password_updates_hash(): void
    {
        $a = AdminUser::factory()->create(['username' => 'op2']);
        $this->artisan('admin:reset-password', ['username' => 'op2', '--password' => 'newp1234'])
            ->assertExitCode(0);
        $this->assertTrue(Hash::check('newp1234', $a->fresh()->password));
    }

    public function test_admin_reset_password_unknown_fails(): void
    {
        $this->artisan('admin:reset-password', ['username' => 'ghost', '--password' => 'newp1234'])
            ->assertExitCode(1);
    }

    public function test_admin_promote_creates_admin_from_user(): void
    {
        User::factory()->create(['email' => 'usr@x.vn', 'name' => 'Usr X']);
        $this->artisan('admin:promote', ['email' => 'usr@x.vn'])->assertExitCode(0);

        $a = AdminUser::query()->where('email', 'usr@x.vn')->first();
        $this->assertNotNull($a);
        $this->assertSame('usr', $a->username);
        $this->assertSame('Usr X', $a->name);
    }

    public function test_admin_promote_idempotent_when_admin_exists(): void
    {
        User::factory()->create(['email' => 'usr2@x.vn']);
        AdminUser::factory()->create(['email' => 'usr2@x.vn', 'username' => 'usr2pre']);
        $this->artisan('admin:promote', ['email' => 'usr2@x.vn'])->assertExitCode(0);
        $this->assertSame(1, AdminUser::query()->where('email', 'usr2@x.vn')->count());
    }

    public function test_admin_demote_deactivates(): void
    {
        $a = AdminUser::factory()->create(['username' => 'op3']);
        $this->artisan('admin:demote', ['username' => 'op3'])->assertExitCode(0);
        $this->assertFalse($a->fresh()->is_active);
    }

    public function test_admin_demote_unknown_fails(): void
    {
        $this->artisan('admin:demote', ['username' => 'ghost'])->assertExitCode(1);
    }
}
