<?php

namespace App\Providers;

use App\Contracts\PassGenerator;
use App\Enums\Role;
use App\Models\User;
use App\Passes\DomPdfPassGenerator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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
        $this->bootFoundation();
        $this->bootPasses();
    }

    /**
     * Stream A: roles and rate limits.
     */
    private function bootFoundation(): void
    {
        // Owner manages the org; staff run events; volunteers only check people in.
        Gate::define('manage-org', fn (User $user) => $user->hasRole(Role::Owner));
        Gate::define('view-dashboard', fn (User $user) => $user->hasRole(Role::Owner, Role::Staff));
        Gate::define('export-participants', fn (User $user) => $user->hasRole(Role::Owner, Role::Staff));
        Gate::define('check-in', fn (User $user) => $user->org_id !== null);

        // 5/min/IP for login, signup and password reset (throttle:auth) and for
        // public event registration (throttle:registration, applied by its route).
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by('auth|'.$request->ip()));
        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(5)->by('registration|'.$request->ip()));
    }

    /**
     * Stream D: check-in routes. The web group provides the session `auth` needs.
     */
    private function bootPasses(): void
    {
        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(base_path('routes/checkin.php'));
        }
    }
}
