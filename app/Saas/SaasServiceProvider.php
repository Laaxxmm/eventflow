<?php

namespace App\Saas;

use App\Models\User;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * SaaS surface: platform-admin gate, suspended-org sign-out and plan commands
 * (plans:activate, plans:pack).
 * Routes live in routes/marketing.php and routes/platform.php (required from routes/web.php).
 */
class SaasServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('platform-admin', fn (User $user) => $user->isPlatformAdmin());

        // Through the HTTP kernel: it re-syncs its groups to the router when it is built.
        $kernel = $this->app->make(HttpKernel::class);
        if ($kernel instanceof Kernel) {
            $kernel->appendMiddlewareToGroup('web', EnsureOrgNotSuspended::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([Console\ActivatePlan::class, Console\GrantParticipantPack::class]);
        }
    }
}
