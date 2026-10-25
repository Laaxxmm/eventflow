<?php

namespace App\Mail;

/**
 * Kept for App\Listeners\SendPass (stream D), which queues it once the pass is
 * generated. It is the branded registration-confirmed email.
 *
 * @deprecated Use RegistrationConfirmedMail in new code.
 */
class PassMail extends RegistrationConfirmedMail {}
