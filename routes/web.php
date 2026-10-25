<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// --- Stream A: foundation (organiser dashboard) ---------------------------

// SaaS marketing site (/, /pricing, /app) and the Indefine platform console (/platform).
require __DIR__.'/marketing.php';
require __DIR__.'/platform.php';

Route::middleware(['auth', 'verified', 'can:view-dashboard'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/participants', [DashboardController::class, 'participants'])->name('dashboard.participants');
    Route::get('/participants/export', [DashboardController::class, 'export'])
        ->middleware('can:export-participants')
        ->name('dashboard.participants.export');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// --- Other streams: add one require line each below -----------------------
require __DIR__.'/reports.php';

// Stream C last: /{org} and /{org}/{slug} are catch-alls.
require __DIR__.'/events.php';
