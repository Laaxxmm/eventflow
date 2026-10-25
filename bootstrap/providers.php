<?php

use App\Certificates\CertificateServiceProvider;
use App\Notifications\NotificationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\PaymentServiceProvider;
use App\Saas\SaasServiceProvider;
use App\View\ViewServiceProvider;

return [
    AppServiceProvider::class,
    PaymentServiceProvider::class,
    NotificationServiceProvider::class,
    SaasServiceProvider::class,
    ViewServiceProvider::class,
    CertificateServiceProvider::class,
];
