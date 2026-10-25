<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reconcile pending payments every 15 minutes
Schedule::command('payments:reconcile')->everyFifteenMinutes();

// First-boot demo data for previews (docker entrypoint, SEED_DEMO=1): seeds only an
// empty database, so redeploys never duplicate or overwrite real data.
Artisan::command('demo:seed-once', function () {
    if (User::withoutGlobalScopes()->exists()) {
        $this->info('Users exist; demo seed skipped.');

        return 0;
    }

    $this->call('db:seed', ['--force' => true]);

    return 0;
})->purpose('Seed demo data into an empty database (first deploy of a preview)');
