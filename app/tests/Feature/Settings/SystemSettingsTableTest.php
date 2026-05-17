<?php

namespace Tests\Feature\Settings;

use CMBcoreSeller\Modules\Settings\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SystemSettingsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_columns(): void
    {
        $this->assertTrue(Schema::hasTable('system_settings'));
        $this->assertTrue(Schema::hasColumns('system_settings', [
            'key', 'value', 'type', 'group', 'is_secret', 'description',
            'updated_by_admin_id', 'created_at', 'updated_at',
        ]));
    }

    public function test_model_can_persist_row(): void
    {
        $r = SystemSetting::create([
            'key' => 'foo.bar',
            'value' => '1',
            'type' => 'int',
            'group' => 'sync',
            'is_secret' => false,
        ]);
        $this->assertSame('foo.bar', $r->key);
        $this->assertFalse($r->is_secret);
    }

    public function test_key_is_unique(): void
    {
        SystemSetting::create(['key' => 'x.y', 'value' => 'a', 'type' => 'string', 'group' => 'sync', 'is_secret' => false]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        SystemSetting::create(['key' => 'x.y', 'value' => 'b', 'type' => 'string', 'group' => 'sync', 'is_secret' => false]);
    }
}
