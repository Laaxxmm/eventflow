<?php

namespace App\Passes;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Local PNG QR codes via endroid/qr-code (GD). No external QR HTTP API.
 */
final class LocalQr
{
    public function png(string $data, int $size = 320): string
    {
        $result = (new Builder(
            writer: new PngWriter,
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
        ))->build();

        return $result->getString();
    }
}
