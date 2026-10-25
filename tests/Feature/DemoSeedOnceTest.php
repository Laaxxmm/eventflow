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

it('re-applies demo passwords from the environment on later boots', function () {
    $this->artisan('demo:seed-once')->assertSuccessful();
    config(['plans.demo.demo_password' => 'Rotated-Demo-Pass-1', 'plans.demo.admin_password' => 'Rotated-Admin-Pass-1']);

    $this->artisan('demo:seed-once')->expectsOutputToContain('owner@demo.test: password updated')->assertSuccessful();

    $hash = fn ($email) => User::withoutGlobalScopes()->where('email', $email)->value('password');
    expect(Hash::check('Rotated-Demo-Pass-1', $hash('owner@demo.test')))->toBeTrue()
        ->and(Hash::check('Rotated-Demo-Pass-1', $hash('volunteer@demo.test')))->toBeTrue()
        ->and(Hash::check('Rotated-Admin-Pass-1', $hash('admin@eventflow.local')))->toBeTrue();
});

it('keeps the CHANGE_ME placeholder out of deployed demo logins', function () {
    $this->artisan('demo:seed-once')->assertSuccessful();
    app()->detectEnvironment(fn () => 'staging');
    config(['plans.demo.demo_password' => 'CHANGE_ME']);

    $this->artisan('demo:seed-once')->expectsOutputToContain('CHANGE_ME placeholder')->assertSuccessful();

    expect(Hash::check('CHANGE_ME', User::withoutGlobalScopes()->where('email', 'owner@demo.test')->value('password')))->toBeFalse();
});
