<?php

use App\Entitlements\EnsureModuleEnabled;
use App\Http\Controllers\CheckinController;
use Illuminate\Support\Facades\Route;

// Check-in is an optional module: it needs a Season Pass that includes it
// and must not be switched off in Org Settings.
Route::middleware(['auth', 'verified', EnsureModuleEnabled::class.':checkin'])->group(function () {
    Route::get('/checkin', [CheckinController::class, 'index'])->name('checkin.index');
    Route::post('/checkin', [CheckinController::class, 'store'])->name('checkin.store');
});
