<?php

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reconcile pending payments every 15 minutes
Schedule::command('payments:reconcile')->everyFifteenMinutes();

// Demo data for previews (docker entrypoint, SEED_DEMO=1). Seeds only an empty database,
// so redeploys never duplicate or overwrite real data. On later boots it re-applies the
// demo passwords from the environment, so changing DEMO_PASSWORD (etc.) takes effect.
Artisan::command('demo:seed-once', function () {
    if (! User::withoutGlobalScopes()->exists()) {
        $this->call('db:seed', ['--force' => true]);

        return 0;
    }

    $this->info('Users exist; demo seed skipped. Syncing demo passwords.');
    $demo = config('plans.demo');
    $accounts = [
        ...array_map(fn ($email) => [$email, fn () => DemoSeeder::password()], DemoSeeder::EMAILS),
        [$demo['admin_email'], fn () => (new PlatformSeeder)->password($demo['admin_password'], 'PLATFORM_ADMIN_PASSWORD')],
        [$demo['organiser_email'], fn () => (new PlatformSeeder)->password($demo['organiser_password'], 'DEMO_ORGANISER_PASSWORD')],
    ];

    foreach ($accounts as [$email, $password]) {
        $user = User::withoutGlobalScopes()->where('email', $email)->first();
        if ($user === null) {
            continue;
        }
        try {
            $value = $password();
        } catch (RuntimeException $e) {
            $this->warn("{$email}: {$e->getMessage()}");

            continue;
        }
        if (! Hash::check($value, $user->password)) {
            $user->forceFill(['password' => $value])->save();
            $this->info("{$email}: password updated.");
        }
    }

    return 0;
})->purpose('Seed demo data into an empty database, or re-apply demo passwords from the environment');
