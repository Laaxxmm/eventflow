<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentSettingsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/pay/{registration}', [PaymentController::class, 'create'])
    ->name('payments.create');

Route::get('/pay/return/{registration}', [PaymentController::class, 'return'])
    ->name('payments.return');

Route::post('/webhooks/{provider}', [WebhookController::class, 'handle'])
    ->name('payments.webhook');

// An organiser's own gateway account (Settings → Payments).
Route::post('/webhooks/{provider}/{org:slug}', [WebhookController::class, 'handleForOrg'])
    ->name('payments.webhook.org');

// Settings → Payments (owners only): connect the org's own Cashfree / Razorpay account.
Route::middleware(['auth', 'verified', 'can:manage-org'])
    ->prefix('dashboard/settings/payments')
    ->name('settings.payments.')
    ->controller(PaymentSettingsController::class)
    ->group(function () {
        Route::get('/', 'show')->name('show');
        Route::put('/', 'update')->name('update');
        Route::post('/test', 'test')->middleware('throttle:6,1')->name('test');
        Route::delete('/{provider}', 'disconnect')->name('disconnect');
    });
