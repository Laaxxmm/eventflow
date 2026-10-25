<?php

namespace App\Certificates;

use App\Models\Event;
use App\Models\IssuedCertificate;
use App\Models\Registration;
use App\Models\Tenancy\OrgScope;
use Illuminate\Support\ServiceProvider;

/**
 * Keeps issued certificates in step with what they print: a renamed
 * participant or a changed event design/branding queues a re-issue.
 */
class CertificateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Registration::updated(function (Registration $r) {
            if ($r->wasChanged(['participant_name', 'distance_code', 'answers'])
                && IssuedCertificate::withoutGlobalScope(OrgScope::class)->where('registration_id', $r->id)->whereNotNull('issued_at')->exists()) {
                IssueCertificate::dispatch($r->id);
            }
        });

        Event::updated(function (Event $event) {
            if ($event->wasChanged(['config', 'colours', 'name', 'date', 'venue'])
                && IssuedCertificate::withoutGlobalScope(OrgScope::class)->where('event_id', $event->id)->whereNotNull('issued_at')->exists()) {
                ReissueEventCertificates::dispatch($event->id);
            }
        });
    }
}
