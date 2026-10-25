<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\IssuedCertificate;
use App\Models\Registration;
use App\Models\Tenancy\OrgScope;
use Illuminate\View\View;

/**
 * Public check for a printed certificate (the QR target): confirms the
 * EF-CERT number, recipient, event and organiser. Shows nothing else.
 */
class CertificateVerifyController extends Controller
{
    public function __invoke(string $certNo): View
    {
        $certificate = IssuedCertificate::findByNumber($certNo);
        abort_if($certificate === null || $certificate->issued_at === null, 404);

        $registration = Registration::withoutGlobalScope(OrgScope::class)->find($certificate->registration_id);
        $event = Event::withoutGlobalScope(OrgScope::class)->with('org')->find($certificate->event_id);
        abort_if($registration === null || $event === null, 404);

        return view('certificates.verify', [
            'certificate' => $certificate,
            'participant' => $registration->participant_name,
            'event' => $event,
        ]);
    }
}
