<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\EventOverviewController;
use App\Http\Controllers\PublicEventController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TemplatePreviewController;
use Illuminate\Support\Facades\Route;

/*
 * Stream C: event landings, registration and organiser event setup.
 * Required last from routes/web.php: /{org} and /{org}/{slug} are catch-alls.
 */

Route::middleware(['auth', 'verified', 'can:view-dashboard'])->prefix('organiser')->name('organiser.')->group(function () {
    Route::resource('events', EventController::class)->except('show');
    Route::get('templates/{template}/preview', TemplatePreviewController::class)->name('templates.preview');
    Route::get('events/{event}/published', [EventController::class, 'published'])->name('events.published');
    // Event → Overview: live preview of the public page, publish, template, add-ons (Session UX-H2).
    Route::get('events/{event}', [EventOverviewController::class, 'show'])->name('events.show');
    Route::get('events/{event}/preview', [EventOverviewController::class, 'preview'])->name('events.preview');
    Route::patch('events/{event}/template', [EventOverviewController::class, 'updateTemplate'])->name('events.template');
    Route::patch('events/{event}/status', [EventOverviewController::class, 'updateStatus'])->name('events.status');
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
