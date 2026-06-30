<?php

namespace CMBcoreSeller\Modules\EInvoice;

use Illuminate\Support\ServiceProvider;

class EInvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Phần B: bind Contracts → Services (IssueInvoiceContract...) + Event::listen.
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (is_file(__DIR__.'/Http/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        }
    }
}
