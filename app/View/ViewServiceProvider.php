<?php

namespace App\View;

use App\View\Composers\NavigationComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/** UX layer: view composers for the organiser shell. */
class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.navigation', NavigationComposer::class);
    }
}
