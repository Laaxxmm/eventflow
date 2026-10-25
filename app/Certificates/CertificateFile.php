<?php

namespace App\Certificates;

/**
 * Path (relative to config('eventflow.certificates.disk')) of a rendered certificate.
 */
final readonly class CertificateFile
{
    public function __construct(
        public string $pdfPath,
    ) {}
}
