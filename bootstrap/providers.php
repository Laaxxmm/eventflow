<?php

use App\Notifications\NotificationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\PaymentServiceProvider;
use App\Saas\SaasServiceProvider;

return [
    AppServiceProvider::class,
    PaymentServiceProvider::class,
    NotificationServiceProvider::class,
    SaasServiceProvider::class,
];
