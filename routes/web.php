<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManualRegistrationController;
use App\Http\Controllers\ParticipantReminderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// --- Stream A: foundation (organiser dashboard) ---------------------------

// SaaS marketing site (/, /pricing, /app) and the Indefine platform console (/platform).
require __DIR__.'/marketing.php';
require __DIR__.'/platform.php';

Route::middleware(['auth', 'verified', 'can:view-dashboard'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/participants', [DashboardController::class, 'participants'])->name('dashboard.participants');
    // Session P1: Registrations → Add participant (manual entries).
    Route::get('/participants/new', [ManualRegistrationController::class, 'create'])->name('dashboard.participants.create');
    Route::post('/participants', [ManualRegistrationController::class, 'store'])->middleware('throttle:60,1')->name('dashboard.participants.store');
    Route::get('/participants/export', [DashboardController::class, 'export'])
        ->middleware('can:export-participants')
        ->name('dashboard.participants.export');
    // Session N2: manual "Send reminder now" for selected unpaid registrations.
    Route::post('/participants/remind', [ParticipantReminderController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('dashboard.participants.remind');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// --- Other streams: add one require line each below -----------------------
require __DIR__.'/reports.php';
require __DIR__.'/settings.php';
require __DIR__.'/billing.php';
require __DIR__.'/domains.php';

// Stream C last: /{org} and /{org}/{slug} are catch-alls.
require __DIR__.'/events.php';
