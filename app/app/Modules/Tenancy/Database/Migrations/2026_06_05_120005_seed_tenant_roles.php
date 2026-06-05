<?php

use CMBcoreSeller\Modules\Tenancy\Support\RolePresets;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed the owner role + editable presets into every existing tenant, then map
 * each membership's legacy `role` string to the matching new `role_id` (SPEC 0031).
 * Permissions mirror the old Role enum verbatim ⇒ no member loses access.
 */
return new class extends Migration
{
    public function up(): void
    {
        $presets = RolePresets::defaults();
        $now = now();

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            $map = []; // legacy role value (enum) => roles.id

            foreach ($presets as $preset) {
                $existing = DB::table('roles')
                    ->where('tenant_id', $tenantId)->where('name', $preset['name'])->first();
                if ($existing) {
                    $map[$preset['key']] = $existing->id;

                    continue;
                }
                $map[$preset['key']] = DB::table('roles')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => $preset['name'],
                    'permissions' => json_encode($preset['permissions']),
                    'is_owner' => $preset['is_owner'],
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach (DB::table('tenant_user')->where('tenant_id', $tenantId)->get(['id', 'role']) as $m) {
                $roleId = $map[$m->role] ?? $map['viewer'] ?? null;
                if ($roleId !== null) {
                    DB::table('tenant_user')->where('id', $m->id)->update(['role_id' => $roleId]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('tenant_user')->update(['role_id' => null]);
        DB::table('roles')->where('is_system', true)->delete();
    }
};
