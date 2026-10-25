<?php

namespace Database\Seeders;

use App\Enums\Module;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\SeasonPass;
use App\Models\User;
use App\Sports\SportPack;
use Illuminate\Database\Seeder;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Local demo data. Log in as owner@demo.test or volunteer@demo.test with DEMO_PASSWORD
 * ("password" by default; production refuses the default).
 * The demo org has an active Season Pass with every module.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $config = Yaml::parseFile(base_path('events/demo/example-marathon.yaml'));
        $pack = SportPack::fromArray(Yaml::parseFile(base_path("events/sports/{$config['sport']}.yaml")));
        $config = $pack->applyTo($config);

        $org = Org::updateOrCreate(['slug' => $config['org']], [
            'name' => 'Demo Runners',
            'phone' => '9800000000',
            'colours' => ['primary' => $config['branding']['primary'], 'secondary' => $config['branding']['secondary']],
        ]);

        // A season covering the demo event, with every module, so check-in, race
        // sheets and certificates work in the demo.
        SeasonPass::withoutGlobalScopes()->updateOrCreate(['org_id' => $org->id, 'plan' => 'Demo season'], [
            'starts_on' => now('Asia/Kolkata')->toDateString(),
            'ends_on' => now('Asia/Kolkata')->addYear()->toDateString(),
            'status' => 'active',
            'modules' => Module::cases(),
        ]);

        $password = (string) config('plans.demo.demo_password');
        if (app()->isProduction() && in_array($password, ['', 'password'], true)) {
            throw new RuntimeException('Set DEMO_PASSWORD to a real password before seeding demo data in production.');
        }

        foreach ([['owner@demo.test', 'Demo Owner', Role::Owner], ['volunteer@demo.test', 'Demo Volunteer', Role::Volunteer]] as [$email, $name, $role]) {
            User::withoutGlobalScopes()->updateOrCreate(['email' => $email], [
                'org_id' => $org->id,
                'role' => $role,
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
            ]);
        }

        $ages = array_column($config['age_bands'], 'min');
        $maxAges = array_column($config['age_bands'], 'max');

        $event = Event::withoutGlobalScopes()->updateOrCreate(
            ['org_id' => $org->id, 'slug' => $config['slug']],
            [
                'name' => $config['name'],
                'sport' => $config['sport'],
                'date' => $config['date'],
                'venue' => $config['venue'],
                'price_paise' => min(array_column($config['distances'], 'price_inr')) * 100,
                'capacity' => array_sum(array_column($config['distances'], 'capacity')),
                'distances' => $config['distances'],
                'min_age' => min($ages),
                'max_age' => max($maxAges),
                'colours' => ['primary' => $config['branding']['primary'], 'secondary' => $config['branding']['secondary']],
                'form_fields' => ['custom' => $config['fields']],
                'status' => 'published',
                'config' => $config,
            ],
        );

        if ($event->registrations()->withoutGlobalScopes()->doesntExist()) {
            Registration::factory()->count(12)->for($event)->paid()->create(['distance_code' => '10K', 'amount_paise' => 89900]);
            Registration::factory()->count(5)->for($event)->checkedIn()->create(['distance_code' => '5K', 'amount_paise' => 59900]);
            Registration::factory()->count(3)->for($event)->create(['distance_code' => 'HM', 'amount_paise' => 149900]);
        }
    }
}
