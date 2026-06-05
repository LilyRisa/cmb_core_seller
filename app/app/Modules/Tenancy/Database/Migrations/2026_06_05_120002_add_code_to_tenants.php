<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * tenants.code — 5-char [a-z0-9] shop code (SPEC 0031). Used to build email-less
 * sub-account usernames "{name}@{code}". Immutable, unique, auto-generated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->char('code', 5)->nullable()->unique()->after('slug');
        });

        // Backfill a unique code for every existing tenant.
        $used = array_flip(DB::table('tenants')->whereNotNull('code')->pluck('code')->all());
        foreach (DB::table('tenants')->whereNull('code')->pluck('id') as $id) {
            do {
                $code = self::randomCode();
            } while (isset($used[$code]));
            $used[$code] = true;
            DB::table('tenants')->where('id', $id)->update(['code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }

    private static function randomCode(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }
};
