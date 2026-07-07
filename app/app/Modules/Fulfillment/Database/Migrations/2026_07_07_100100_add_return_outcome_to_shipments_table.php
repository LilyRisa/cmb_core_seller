<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedInteger('cod_collected')->nullable()->after('cod_amount');
            $table->unsignedInteger('failed_collect_collected')->nullable()->after('cod_collected');
            $table->unsignedInteger('return_fee')->nullable()->after('failed_collect_collected');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['cod_collected', 'failed_collect_collected', 'return_fee']);
        });
    }
};
