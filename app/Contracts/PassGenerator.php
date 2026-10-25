<?php

namespace App\Contracts;

use App\Models\Registration;
use App\Passes\PassFiles;

/**
 * Renders a participant pass (PNG + PDF) with a locally generated QR code.
 *
 * Implementations must never call an external QR API.
 */
interface PassGenerator
{
    public function generate(Registration $r): PassFiles;
}
