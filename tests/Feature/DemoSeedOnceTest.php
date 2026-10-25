<?php

use App\Models\User;
use Database\Seeders\DemoSeeder;

beforeEach(function () {
    config(['plans.demo.admin_password' => 'Test-Admin-Password-1', 'plans.demo.organiser_password' => 'Test-Organiser-Password-1']);
});

it('seeds demo data into an empty database', function () {
    $this->artisan('demo:seed-once')->assertSuccessful();

    expect(User::withoutGlobalScopes()->where('email', 'owner@demo.test')->exists())->toBeTrue()
        ->and(User::withoutGlobalScopes()->where('email', 'admin@eventflow.local')->exists())->toBeTrue();
});

it('never seeds over existing users', function () {
    User::factory()->create(['email' => 'real@organiser.test']);

    $this->artisan('demo:seed-once')->expectsOutputToContain('demo seed skipped')->assertSuccessful();

    expect(User::withoutGlobalScopes()->where('email', 'owner@demo.test')->exists())->toBeFalse();
});

it('uses DEMO_PASSWORD for the demo users', function () {
    config(['plans.demo.demo_password' => 'Preview-Pass-2027']);

    $this->seed(DemoSeeder::class);

    expect(Hash::check('Preview-Pass-2027', User::withoutGlobalScopes()->where('email', 'owner@demo.test')->value('password')))->toBeTrue();
});

it('refuses the default demo password in production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['plans.demo.demo_password' => 'password']);

    app(DemoSeeder::class)->run(); // db:seed would prompt for confirmation in production
})->throws(RuntimeException::class, 'DEMO_PASSWORD');
