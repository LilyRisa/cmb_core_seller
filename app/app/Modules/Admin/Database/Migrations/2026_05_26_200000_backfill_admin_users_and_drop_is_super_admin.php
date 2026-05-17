<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Spec 2026-05-17 — chuyển super-admin cũ (cờ `users.is_super_admin=true` từ
 * SPEC 0020) sang bảng riêng `admin_users`, rồi drop cột.
 *
 * Idempotent: bỏ qua user nếu đã có row admin_users theo email. Username sinh
 * từ local-part của email (sanitize), va chạm → append `_<n>`; email rỗng →
 * fallback `admin_<user_id>`.
 *
 * Rollback: tái tạo cột + set true cho user email match admin_users.email
 * (best-effort — admin tạo mới sau backfill sẽ không có user tương ứng).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_super_admin')) {
            $this->dropIsSuperAdminIfNeeded();

            return;
        }

        $rows = DB::table('users')
            ->where('is_super_admin', true)
            ->select('id', 'name', 'email', 'password')
            ->get();

        foreach ($rows as $u) {
            if ($u->email && DB::table('admin_users')->where('email', $u->email)->exists()) {
                continue;
            }

            $base = $this->sanitize(Str::before((string) $u->email, '@'));
            if ($base === '') {
                $base = "admin_{$u->id}";
            }
            $username = $base;
            $i = 1;
            while (DB::table('admin_users')->where('username', $username)->exists()) {
                $username = $base.'_'.$i++;
            }

            DB::table('admin_users')->insert([
                'username' => $username,
                'email' => $u->email,
                'name' => $u->name,
                'password' => $u->password,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->dropIsSuperAdminIfNeeded();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_super_admin')) {
                $table->boolean('is_super_admin')->default(false)->after('password');
                $table->index('is_super_admin');
            }
        });

        $emails = DB::table('admin_users')
            ->whereNotNull('email')
            ->pluck('email')
            ->all();

        if ($emails) {
            DB::table('users')->whereIn('email', $emails)->update(['is_super_admin' => true]);
        }
    }

    private function dropIsSuperAdminIfNeeded(): void
    {
        if (! Schema::hasColumn('users', 'is_super_admin')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_super_admin']);
            $table->dropColumn('is_super_admin');
        });
    }

    private function sanitize(string $raw): string
    {
        $s = strtolower($raw);
        $s = preg_replace('/[^a-z0-9._-]/', '', $s) ?? '';
        $s = trim($s, '._-');

        return strlen($s) >= 3 ? substr($s, 0, 32) : '';
    }
};
