<?php

/*
 * Where "Contact Indefine" goes (Session P1). Every Contact Indefine link in the app uses
 * App\Support\Contact: contact_url when it is set (a contact form or booking page), else a
 * mailto: to contact_email. A blank SALES_CONTACT_EMAIL falls back to the default address,
 * so the link is never empty or "#".
 */
return [
    'contact_url' => env('BRAND_CONTACT_URL') ?: null,
    'contact_email' => env('SALES_CONTACT_EMAIL') ?: 'hello@indefine.in',
];
