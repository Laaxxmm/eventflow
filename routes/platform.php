<?php

use App\Http\Controllers\PlatformAdmin\OrganisationController;
use Illuminate\Support\Facades\Route;

// Indefine platform console. Only users with is_platform_admin (gate platform-admin).
Route::middleware(['auth', 'can:platform-admin'])->prefix('platform')->name('platform.')->group(function () {
    Route::redirect('/', '/platform/orgs');
    Route::get('/orgs', [OrganisationController::class, 'index'])->name('orgs.index');
    Route::post('/orgs/{org}/suspend', [OrganisationController::class, 'suspend'])->name('orgs.suspend');
    Route::post('/orgs/{org}/reactivate', [OrganisationController::class, 'reactivate'])->name('orgs.reactivate');
    Route::post('/orgs/{org}/extend-trial', [OrganisationController::class, 'extendTrial'])->name('orgs.extend-trial');
});
