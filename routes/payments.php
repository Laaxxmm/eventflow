<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/pay/{registration}', [PaymentController::class, 'create'])
    ->name('payments.create');

Route::get('/pay/return/{registration}', [PaymentController::class, 'return'])
    ->name('payments.return');

Route::post('/webhooks/{provider}', [WebhookController::class, 'handle'])
    ->name('payments.webhook');
