<?php

use App\Http\Controllers\OrgSettingsController;
use Illuminate\Support\Facades\Route;

// Session 5: Org Settings (Season Pass + module toggles). Owners only.
Route::middleware(['auth', 'verified', 'can:manage-org'])->prefix('dashboard/settings')->group(function () {
    Route::get('/', [OrgSettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/modules', [OrgSettingsController::class, 'update'])->name('settings.modules.update');
});
