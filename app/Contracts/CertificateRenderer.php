<?php

namespace App\Contracts;

use App\Certificates\CertificateFile;
use App\Models\Registration;

/**
 * Renders a participant certificate as an A4 PDF from the event's certificate
 * config: a gallery template (config('eventflow.certificates.gallery')) or an
 * uploaded A4 background. Custom layouts are not built in-app; they are a
 * "contact Indefine" request (config('eventflow.certificates.custom_contact')).
 */
interface CertificateRenderer
{
    public function render(Registration $r): CertificateFile;
}
