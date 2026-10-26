<?php

use App\Http\Controllers\Marketing\AccountPlanController;
use App\Http\Controllers\Marketing\MarketingController;
use App\Http\Controllers\Marketing\TemplateGalleryController;
use Illuminate\Support\Facades\Route;

// SaaS marketing site (sells EventFlow). Per-event landings are /{org}/{slug} (routes/events.php).
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('/templates', [TemplateGalleryController::class, 'index'])->name('templates.index');
Route::get('/templates/{template}', [TemplateGalleryController::class, 'show'])->name('templates.show');
Route::get('/signup', fn () => redirect()->route('register', request()->query()))->name('signup');

Route::middleware('auth')->group(function () {
    Route::get('/app', [MarketingController::class, 'app'])->name('app');
    // Not behind `verified`: new organisers land here straight after signup.
    Route::get('/account/plan', AccountPlanController::class)->name('account.plan');
});
