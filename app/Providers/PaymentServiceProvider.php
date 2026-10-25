<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Payments\GatewayManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GatewayManager::class);
        $this->app->bind(PaymentGateway::class, function ($app) {
            return $app->make(GatewayManager::class)->for();
        });
    }

    public function boot(): void
    {
        // The web group gives route model binding (and sessions/CSRF) to /pay/*;
        // webhooks/* is CSRF-exempt in bootstrap/app.php.
        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(base_path('routes/payments.php'));
        }
    }
}
