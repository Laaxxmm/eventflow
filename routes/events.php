<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\PublicEventController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

/*
 * Stream C: event landings, registration and organiser event setup.
 * Required last from routes/web.php: /{org} and /{org}/{slug} are catch-alls.
 */

Route::middleware(['auth', 'verified', 'can:view-dashboard'])->prefix('organiser')->name('organiser.')->group(function () {
    Route::resource('events', EventController::class)->except('show');
});

Route::get('/r/{registration}/review', [RegistrationController::class, 'review'])
    ->whereNumber('registration')
    ->middleware('signed')
    ->name('registrations.review');

// Org slugs that would shadow app routes are never matched as an org.
$org = '(?!(?:'.implode('|', [
    'login', 'logout', 'register', 'dashboard', 'profile', 'organiser', 'pay', 'webhooks', 'up', 'r',
    'forgot-password', 'reset-password', 'verify-email', 'confirm-password', 'email', 'password',
    'build', 'storage', 'api', 'admin', 'checkin', 'race-sheets', 'certificates', 'passes',
]).')$)[a-z0-9]+(?:-[a-z0-9]+)*';
$slug = '[a-z0-9]+(?:-[a-z0-9]+)*';

Route::get('/{org}', [PublicEventController::class, 'org'])
    ->where('org', $org)
    ->name('landing.org');

Route::get('/{org}/{slug}', [PublicEventController::class, 'show'])
    ->where(['org' => $org, 'slug' => $slug])
    ->name('landing.show');

Route::post('/{org}/{slug}/register', [RegistrationController::class, 'store'])
    ->where(['org' => $org, 'slug' => $slug])
    ->middleware('throttle:registration')
    ->name('registrations.store');
