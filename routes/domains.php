<?php

use App\Http\Controllers\DomainSettingsController;
use Illuminate\Support\Facades\Route;

// Session P1: Settings → Domain (owners), and the on-demand TLS check for the edge proxy.
Route::middleware(['auth', 'verified', 'can:manage-org'])->prefix('dashboard/settings/domain')->name('settings.domain.')->group(function () {
    Route::get('/', [DomainSettingsController::class, 'show'])->name('show');
    Route::post('/', [DomainSettingsController::class, 'store'])->middleware('throttle:20,1')->name('store');
    Route::post('/{domain}/verify', [DomainSettingsController::class, 'verify'])->middleware('throttle:20,1')->name('verify');
    Route::delete('/{domain}', [DomainSettingsController::class, 'destroy'])->name('destroy');
});

Route::get('/domains/tls-check', [DomainSettingsController::class, 'tlsCheck'])->middleware('throttle:120,1')->name('domains.tls-check');
