<?php

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

/** Resync catalog plan features sau khi thêm feature key 'einvoice'. SPEC 0041. */
return new class extends Migration
{
    public function up(): void
    {
        if (App::runningUnitTests()) {
            return;
        }

        (new BillingPlanSeeder)->run();
    }

    public function down(): void
    {
        // catalog data — không revert.
    }
};
