<?php

namespace CMBcoreSeller\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Serves the React SPA shell (resources/views/app.blade.php) for every web path
 * that isn't an API / webhook / OAuth callback / asset. React Router takes over
 * client-side routing from there. An invokable controller (not a Closure) so
 * `php artisan route:cache` works in production. See docs/06-frontend/overview.md.
 */
class SpaController extends Controller
{
    public function __invoke(): View
    {
        return view('app');
    }
}
