<?php

use App\Certificates\CertificateServiceProvider;
use App\Notifications\NotificationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\PaymentServiceProvider;
use App\Reminders\ReminderServiceProvider;
use App\Saas\SaasServiceProvider;
use App\View\ViewServiceProvider;

return [
    AppServiceProvider::class,
    PaymentServiceProvider::class,
    NotificationServiceProvider::class,
    ReminderServiceProvider::class,
    SaasServiceProvider::class,
    ViewServiceProvider::class,
    CertificateServiceProvider::class,
];
