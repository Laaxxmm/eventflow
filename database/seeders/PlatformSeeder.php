<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Org;
use App\Models\User;
use App\Saas\PlanAssigner;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo accounts for the SaaS surface (local/staging):
 *  - platform admin  admin@eventflow.local      password from PLATFORM_ADMIN_PASSWORD
 *  - organiser       organiser@eventflow.local  password from DEMO_ORGANISER_PASSWORD (Starter trial)
 * Plans are config (config/plans.php), so there is nothing to seed for them.
 */
class PlatformSeeder extends Seeder
{
    /** The documented .env.example defaults; refused in production. */
    private const EXAMPLE_PASSWORDS = ['ChangeMe-EventFlow-Admin!', 'ChangeMe-EventFlow-Organiser!'];

    public function run(): void
    {
        $demo = config('plans.demo');

        $admin = User::withoutGlobalScopes()->updateOrCreate(['email' => $demo['admin_email']], [
            'org_id' => null,
            'name' => 'Indefine Admin',
            'password' => $this->password($demo['admin_password'], 'PLATFORM_ADMIN_PASSWORD'),
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['is_platform_admin' => true])->save();

        $org = Org::firstOrCreate(['slug' => 'demo-organiser'], ['name' => 'Demo Organiser']);
        if ($org->plan === null) {
            PlanAssigner::startTrial($org, config('plans.default'));
        }

        User::withoutGlobalScopes()->updateOrCreate(['email' => $demo['organiser_email']], [
            'org_id' => $org->id,
            'role' => Role::Owner,
            'name' => 'Demo Organiser',
            'password' => $this->password($demo['organiser_password'], 'DEMO_ORGANISER_PASSWORD'),
            'email_verified_at' => now(),
        ]);
    }

    public function password(?string $password, string $env): string
    {
        if (blank($password)) {
            throw new RuntimeException("Set {$env} in .env before seeding demo accounts (see .env.example).");
        }
        if (app()->isProduction() && in_array($password, self::EXAMPLE_PASSWORDS, true)) {
            throw new RuntimeException("{$env} still has the .env.example value; choose a real password.");
        }
        if (! app()->environment('local', 'testing') && str_contains($password, 'CHANGE_ME')) {
            throw new RuntimeException("{$env} is still the CHANGE_ME placeholder; choose a real password.");
        }

        return $password; // hashed by the User model's cast
    }
}
