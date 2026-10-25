<?php

namespace App\Providers;

use App\Contracts\PassGenerator;
use App\Passes\DomPdfPassGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PassGenerator::class, DomPdfPassGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/checkin.php'));
    }
}
