<?php

use App\Notifications\NotificationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\PaymentServiceProvider;

return [
    AppServiceProvider::class,
    PaymentServiceProvider::class,
    NotificationServiceProvider::class,
];
