<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users.username / is_sub_account / created_by_user_id (SPEC 0031). Sub-accounts
 * have no email (email becomes nullable) and log in by username "{name}@{code}".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
            $table->boolean('is_sub_account')->default(false)->after('username');
            $table->foreignId('created_by_user_id')->nullable()->after('is_sub_account')
                ->constrained('users')->nullOnDelete();
        });

        // Sub-accounts have no email ⇒ allow NULL (the unique index already permits multiple NULLs).
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn(['username', 'is_sub_account']);
        });
        // Leave email nullable on rollback (reverting to NOT NULL could fail if NULLs exist).
    }
};
