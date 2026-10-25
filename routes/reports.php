<?php

use App\Entitlements\EnsureModuleEnabled;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\RaceSheetController;
use Illuminate\Support\Facades\Route;

// Stream E: certificates and race sheets. Organisers only (owner, staff);
// {event} binds through the org scope, so other orgs' events are 404.
Route::middleware(['auth', 'verified', 'can:view-dashboard'])
    ->prefix('dashboard/events/{event}')
    ->name('reports.')
    ->group(function () {
        // Each needs its module (Season Pass + Org Settings toggle).
        Route::middleware(EnsureModuleEnabled::class.':race_sheets')->group(function () {
            Route::get('race-sheet', [RaceSheetController::class, 'show'])->name('race-sheet');
            Route::get('race-sheet/csv', [RaceSheetController::class, 'csv'])->name('race-sheet.csv');
            Route::get('race-sheet/pdf', [RaceSheetController::class, 'pdf'])->name('race-sheet.pdf');
        });

        Route::middleware(EnsureModuleEnabled::class.':certificates')->group(function () {
            Route::get('certificates', [CertificateController::class, 'index'])->name('certificates');
            Route::post('certificates', [CertificateController::class, 'store'])->name('certificates.generate');
            Route::get('certificates/download', [CertificateController::class, 'download'])->name('certificates.download');
            Route::get('certificates/{registration}', [CertificateController::class, 'show'])->name('certificates.show');
        });
    });
