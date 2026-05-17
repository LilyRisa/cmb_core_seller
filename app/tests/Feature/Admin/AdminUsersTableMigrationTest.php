<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUsersTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('admin_users'));
        $this->assertTrue(Schema::hasColumns('admin_users', [
            'id', 'username', 'email', 'name', 'password', 'is_active',
            'last_login_at', 'last_login_ip', 'created_at', 'updated_at',
        ]));
    }

    public function test_admin_password_reset_tokens_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('admin_password_reset_tokens'));
    }
}
