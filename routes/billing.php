<?php

use App\Http\Controllers\Billing\PurchaseController;
use Illuminate\Support\Facades\Route;

// Session P1: organiser purchases from Indefine (participant packs, per-event add-ons).
Route::middleware(['auth', 'verified', 'can:view-dashboard'])->prefix('dashboard/billing')->name('billing.')->group(function () {
    Route::get('/return/{purchase}', [PurchaseController::class, 'return'])->whereNumber('purchase')->name('return');
    Route::get('/{sku}', [PurchaseController::class, 'show'])->where('sku', '[a-z_]+')->name('show');
    Route::post('/{sku}', [PurchaseController::class, 'store'])->where('sku', '[a-z_]+')->middleware('throttle:12,1')->name('store');
});
