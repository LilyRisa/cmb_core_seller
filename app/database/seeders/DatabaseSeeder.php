<?php

namespace Database\Seeders;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for local dev (`php artisan migrate --seed`):
 *   owner@demo.local / password   — owner of "Cửa hàng demo"
 *   staff@demo.local / password   — staff_order in the same workspace
 * Production runs never seed.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@demo.local'],
            ['name' => 'Chủ shop demo', 'password' => Hash::make('password')],
        );

        $staff = User::firstOrCreate(
            ['email' => 'staff@demo.local'],
            ['name' => 'NV xử lý đơn demo', 'password' => Hash::make('password')],
        );

        $tenant = Tenant::firstOrCreate(['slug' => 'cua-hang-demo'], ['name' => 'Cửa hàng demo']);

        $tenant->users()->syncWithoutDetaching([
            $owner->getKey() => ['role' => Role::Owner->value],
            $staff->getKey() => ['role' => Role::StaffOrder->value],
        ]);
    }
}
