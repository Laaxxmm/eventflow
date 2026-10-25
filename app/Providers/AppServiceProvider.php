<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootFoundation();
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
}
