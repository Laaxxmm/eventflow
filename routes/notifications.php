<?php

use App\Notifications\Http\NotificationSettingsController;
use App\Notifications\Http\PassLinkController;
use Illuminate\Support\Facades\Route;

// Signed "View pass" link from emails and WhatsApp.
Route::get('/passes/{registration}/pass.pdf', PassLinkController::class)
    ->whereNumber('registration')
    ->middleware('signed')
    ->name('notifications.pass');

// Org Settings -> Notifications (owner only).
Route::middleware(['auth', 'verified', 'can:manage-org'])
    ->prefix('dashboard/settings/notifications')
    ->name('settings.notifications.')
    ->controller(NotificationSettingsController::class)
    ->group(function () {
        Route::get('/', 'show')->name('show');
        Route::put('/platform', 'updatePlatform')->name('platform');
        Route::post('/platform/test', 'testPlatform')->middleware('throttle:6,1')->name('platform.test');
        Route::put('/smtp', 'updateSmtp')->name('smtp');
        Route::post('/smtp/test', 'testSmtp')->middleware('throttle:6,1')->name('smtp.test');
        Route::put('/whatsapp', 'updateWhatsApp')->name('whatsapp');
        Route::post('/whatsapp/test', 'testWhatsApp')->middleware('throttle:6,1')->name('whatsapp.test');
        Route::get('/preview/{type}', 'preview')->name('preview');
    });
