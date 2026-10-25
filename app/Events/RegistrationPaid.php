<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched by the payments stream once a registration is confirmed paid
 * (or confirmed free). The passes stream listens to generate + email the pass.
 */
final class RegistrationPaid implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $registrationId,
    ) {}
}
