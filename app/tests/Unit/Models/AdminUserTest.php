<?php

namespace Tests\Unit\Models;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_hashes_password_on_save(): void
    {
        $admin = AdminUser::create([
            'username' => 'ops_a',
            'name' => 'Ops A',
            'password' => 'secret123',
        ]);
        $this->assertNotSame('secret123', $admin->password);
        $this->assertTrue(Hash::check('secret123', $admin->password));
    }

    public function test_admin_user_is_active_defaults_true(): void
    {
        $a = AdminUser::factory()->create();
        $this->assertTrue($a->is_active);
    }

    public function test_password_and_remember_token_hidden_on_serialization(): void
    {
        $a = AdminUser::factory()->create();
        $arr = $a->toArray();
        $this->assertArrayNotHasKey('password', $arr);
        $this->assertArrayNotHasKey('remember_token', $arr);
    }
}
